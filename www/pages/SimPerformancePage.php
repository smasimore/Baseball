<?php
include_once 'Page.php';
include_once __DIR__ . '/../ui/UOList.php';
include_once __DIR__ . '/../ui/Input.php';
include_once __DIR__ . '/../ui/ParamInput.php';
include_once __DIR__ . '/../../Models/DataTypes/SimOutputDataType.php';
include_once __DIR__ . '/../../Models/DataTypes/HistoricalOddsDataType.php';
include_once __DIR__ . '/../../Models/Utils/SimPerformanceUtils.php';
include_once __DIR__ . '/../../Models/Utils/ArrayUtils.php';

class SimPerformancePage extends Page {

    const HIST_BUCKET_SIZE = 5;

    // Param names.
    const P_GROUP_SWITCH_PERC = 'group_switch_perc';
    const P_FIRST_SEASON = 'first_season';
    const P_LAST_SEASON = 'last_season';
    const P_FIRST_BUCKET = 'first_bucket';
    const P_LAST_BUCKET = 'last_bucket';

    // Bet param names.
    const P_BET_AMOUNT = 'bet_amount';

    // Param values.
    private $groupSwitchPerc;
    private $firstSeason;
    private $lastSeason;
    private $firstBucket;
    private $lastBucket;

    // Group param values.
    private $weights;
    private $statsYear;
    private $statsType;

    // Bet param values.
    private $betAmount = 100;

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
        $bet_param_list = $this->getBetParamList();
        $submit_button = "<input class='button' type='submit' value='Submit'>";

        $form =
            "<form class='sim_perf' action='sim_perf.php'>
                <div class='blue_list'>
                    $sim_param_list
                    $group_param_list_a
                    $group_param_list_b
                    $bet_param_list
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
    }

    private function getSimParamList() {
        $group_switch_slider = new Slider(
            'Group Switch Perc',
            self::P_GROUP_SWITCH_PERC,
            $this->groupSwitchPerc,
            0, 100, 25
        );

        $first_season = new Selector(
            'First Season',
            self::P_FIRST_SEASON,
            $this->firstSeason,
            $this->possibleParams['season']
        );
        $last_season = new Selector(
            'Last Season',
            self::P_LAST_SEASON,
            $this->lastSeason,
            $this->possibleParams['season']
        );

        $possible_buckets = $this->possibleParams['rand_bucket'];
        asort($possible_buckets);
        $first_bucket = new Selector(
            'First Bucket',
            self::P_FIRST_BUCKET,
            $this->firstBucket,
            $possible_buckets
        );
        $last_bucket = new Selector(
            'Last Bucket',
            self::P_LAST_BUCKET,
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

    private function getBetParamList() {
        $bet_amount = (new Input())
            ->setType(InputTypes::NUMBER)
            ->setName(self::P_BET_AMOUNT)
            ->setValue($this->betAmount)
            ->getHTML();

        $param_list = new UOList(
            array(
                "<font class='list_title'>
                    Bet Params
                </font>",
                (new ParamInput($bet_amount))
                    ->setTitle('Bet Amount')
                    ->getHTML()
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
        $this->groupSwitchPerc = idx($params, self::P_GROUP_SWITCH_PERC, 100);
        $this->firstSeason = idx($params, self::P_FIRST_SEASON, 2013);
        $this->lastSeason = idx($params, self::P_LAST_SEASON, 2013);
        $this->firstBucket = idx($params, self::P_FIRST_BUCKET, 0);
        $this->lastBucket = idx($params, self::P_LAST_BUCKET, 9);

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

    public function getPerfScoreLabel($data, $label) {
        list($perf_score_vegas, $perf_score_sim) =
            SimPerformanceUtils::calculateSimPerfScores($data);
        return sprintf(
            '%s - Sim: %g / Vegas: %g',
            $label,
            round($perf_score_sim, 2),
            round($perf_score_vegas, 2)
        );
    }

    public function getPerfScoreLabelsByYear($data_by_year) {
        $labels_by_year = array();
        foreach ($data_by_year as $year => $data) {
            $labels_by_year[$year] = $this->getPerfScoreLabel($data, $year);
        }

        return $labels_by_year;
    }
}
?>
