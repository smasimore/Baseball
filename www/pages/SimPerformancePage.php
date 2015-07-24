<?php
include_once 'Page.php';
include_once __DIR__ . '/../ui/UOList.php';
include_once __DIR__ . '/../../Models/DataTypes/SimOutputDataType.php';
include_once __DIR__ . '/../../Models/DataTypes/HistoricalOddsDataType.php';
include_once __DIR__ . '/../../Models/Utils/SimPerformanceUtils.php';
include_once __DIR__ . '/../../Models/Utils/ArrayUtils.php';

class SimPerformancePage extends Page {

    const HIST_BUCKET_SIZE = 5;

    // Params.
    private $groupSwitchPerc;
    private $firstSeason;
    private $lastSeason;
    private $firstBucket;
    private $lastBucket;

    // Params that vary by group.
    private $weights;
    private $statsYear;
    private $statsType;

    // Vars.
    private $possibleParams = array();
    private $gamesByYear = array();
    private $perfData = array();
    private $perfDataByYear = array();

    private $perfScoreVegas;
    private $perfScoreSim;

    final protected function renderPageIfErrors() {
        return true;
    }

    final protected function gen() {
        $this->genPossibleParams();

        $weights_0 = $this->weights[0];
        $weights_1 = $this->weights[1];
        $stats_year_0 = $this->statsYear[0];
        $stats_year_1 = $this->statsYear[1];

        $sim_output_data_0 = (new SimOutputDataType())
            ->setSeasonRange($this->firstSeason, $this->lastSeason)
            ->setRandBucketRange($this->firstBucket, $this->lastBucket)
            ->setWeights($weights_0)
            ->setStatsYear($stats_year_0)
            ->gen()
            ->getData();

        $sim_output_data_1 = (new SimOutputDataType())
            ->setSeasonRange($this->firstSeason, $this->lastSeason)
            ->setRandBucketRange($this->firstBucket, $this->lastBucket)
            ->setWeights($weights_1)
            ->setStatsYear($stats_year_1)
            ->gen()
            ->getData();

        $odds_data = (new HistoricalOddsDataType())
            ->setSeasonRange($this->firstSeason, $this->lastSeason)
            ->gen()
            ->getData();

        $num_games_0 = count($sim_output_data_0);
        $num_games_1 = count($sim_output_data_1);
        if (!$num_games_0) {
            $this->errors[] =
                "<div>
                    Incomplete Data: No games in group 0.
                </div>";
        }
        if (!$num_games_1) {
            $this->errors[] =
                "<div>
                    Incomplete Data: No games in group 1.
                </div>";
        }
        if ($num_games_0 !== $num_games_1) {
            $this->errors[] =
                "<div>
                    Incomplete Data: Num games group 0 = $num_games_0,
                    group 1 = $num_games_1.
                </div>";
        }

        $results_0 = $this->formatSQLGameData($sim_output_data_0, $odds_data);
        $results_1 = $this->formatSQLGameData($sim_output_data_1, $odds_data);

        $this->gamesByYear = $this->applySeasonSwitchPerc(
            $results_0,
            $results_1
        );

        $this->calculatePerfData();
    }

    final protected function renderPage() {
        $sim_param_list = $this->getSimParamList();
        $group_param_list_a = $this->getGroupParamList(0);
        $group_param_list_b = $this->getGroupParamList(1);
        $submit_button = "<input class='button' type='submit' value='Submit'>";

        $form =
            "<form class='sim_perf' action='sim_perf.php'>
                <div class='blue_list'>
                    $sim_param_list
                    $group_param_list_a
                    $group_param_list_b
                    <div'>$submit_button</div>
                </div>
            </form>";


        $charts[] = $this->getChart('overall');
        foreach (array_keys($this->perfDataByYear) as $year) {
            $charts[] = $this->getChart($year);
        }
        $chart_list = new UOList(
            $charts,
            null,
            'chart_list_item bottom_border'
        );
        
        echo $form;
        $chart_list->display();
    }

    private function genPossibleParams() {
        $this->possibleParams = (new SimOutputDataType())
            ->genDistinctColumnValues(array(
                'season',
                'weights',
                'stats_year',
                'rand_bucket'
            ));

        // Odds limited by season, so need to get cross season of seasons from
        // sim_output and historical_odds.
        $possible_odds_seasons = (new HistoricalOddsDataType())
            ->genDistinctColumnValues(array('season'));
        $this->possibleParams['season'] = array_intersect(
            $this->possibleParams['season'],
            $possible_odds_seasons['season']
        );
    }

    private function formatSQLGameData($sim_data, $odds_data) {
        $f_data = array();
        foreach ($sim_data as $gameid => $game) {
            // A few games in the odds data have an incorrect gameid. Just skip
            // them.
            if (!idx($odds_data, $gameid)) {
                continue;
            }

            $odds = $odds_data[$gameid];
            $f_data[$game['season']][$game['game_date']][] = array(
                SimPerformanceUtils::VEGAS_PCT => $odds['home_pct_win'],
                SimPerformanceUtils::SIM_PCT => $game['home_win_pct'] * 100,
                SimPerformanceUtils::TEAM_WINNER => $odds['home_team_winner']
            );
        }

        return $f_data;
    }

    private function applySeasonSwitchPerc($data_0, $data_1) {
        $data = array();
        foreach ($data_1 as $year => $games_by_date_1) {
            $games_by_date_0 = idx($data_0, $year, array());
            $dates = array_keys($games_by_date_1);
            $switch_date = $this->groupSwitchPerc ?
                $dates[round((count($dates) - 1) /
                (100 / $this->groupSwitchPerc))] :
                $dates[0];
            foreach ($games_by_date_1 as $date => $games_1) {
                if ($date <= $switch_date) {
                    // Use idx because of Incomplete Data error. Error will
                    // display and data shouldn't be trusted.
                    foreach (idx($games_by_date_0, $date, array()) as $game) {
                        $data[$year][] = $game;
                    }
                } else {
                    foreach ($games_1 as $game) {
                        $data[$year][] = $game;
                    }
                }
            }
        }

        return $data;
    }

    private function calculatePerfData() {
        $this->perfData = SimPerformanceUtils::calculateSimPerfData(
            ArrayUtils::flatten($this->gamesByYear),
            self::HIST_BUCKET_SIZE
        );
        $this->perfDataByYear = SimPerformanceUtils::calculateSimPerfDataByYear(
            $this->gamesByYear,
            self::HIST_BUCKET_SIZE
        );

        list($this->perfScoreVegas, $this->perfScoreSim) =
            SimPerformanceUtils::calculateSimPerfScores($this->perfData);
    }

    private function getSimParamList() {
        $group_switch_slider = new Slider(
            'Group Switch Perc',
            'group_switch_perc',
            $this->groupSwitchPerc,
            0, 100, 25
        );

        $first_season = new Selector(
            'First Season',
            'first_season',
            $this->firstSeason,
            $this->possibleParams['season']
        );
        $last_season = new Selector(
            'Last Season',
            'last_season',
            $this->lastSeason,
            $this->possibleParams['season']
        );

        $possible_buckets = $this->possibleParams['rand_bucket'];
        asort($possible_buckets);
        $first_bucket = new Selector(
            'First Bucket',
            'first_bucket',
            $this->firstBucket,
            $possible_buckets
        );
        $last_bucket = new Selector(
            'Last Bucket',
            'last_bucket',
            $this->lastBucket,
            $possible_buckets
        );

        $list = new UOList(
            array(
                "<font class='list_title'>
                    Overall Params
                </font>",
                $group_switch_slider->getHTML(),
                $first_season->getHTML(),
                $last_season->getHTML(),
                $first_bucket->getHTML(),
                $last_bucket->getHTML()
            ),
            null,
            'list_item'
        );

        return $list->getHTML();

    }

    private function getGroupParamList($group) {
        $weights_selector = new Selector(
            'Weights',
            'weights_' . $group,
            $this->weights[$group],
            $this->possibleParams['weights']
        );

        $stats_year_selector = new Selector(
            'Stats Year',
            'stats_year_' . $group,
            $this->statsYear[$group],
            $this->possibleParams['stats_year']
        );

        $param_list = new UOList(
            array(
                "<font class='list_title'>
                    Group $group
                </font>",
                $weights_selector->getHTML(),
                $stats_year_selector->getHTML(),
            ),
            null,
            'list_item'
        );

        return $param_list->getHTML();
    }

    private function getChart($type) {
        return
            "<div
                id=$type
                class='chart_canvas'>
            </div>";
    }

    public function setParams($params) {
        $this->groupSwitchPerc = idx($params, 'group_switch_perc', 100);
        $this->firstSeason = idx($params, 'first_season', 2013);
        $this->lastSeason = idx($params, 'last_season', 2013);
        $this->firstBucket = idx($params, 'first_bucket', 0);
        $this->lastBucket = idx($params, 'last_bucket', 9);

        $this->weights = array(
            0 => idx($params, 'weights_0', 'b_total_100'),
            1 => idx($params, 'weights_1', 'b_total_100')
        );
        $this->statsYear = array(
            0 => idx($params, 'stats_year_0', 'career'),
            1 => idx($params, 'stats_year_1', 'career')
        );

        return $this;
    }

    public function getPerfData() {
        return $this->perfData;
    }

    public function getPerfDataByYear() {
        return $this->perfDataByYear;
    }

    public function getPerfScoreLabel() {
        return sprintf(
            'OVERALL - Sim: %g / Vegas: %g',
            round($this->perfScoreSim, 2),
            round($this->perfScoreVegas, 2)
        );
    }
}
?>
