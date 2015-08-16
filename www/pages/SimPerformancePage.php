<?php
include_once 'Page.php';
include_once __DIR__ . '/../ui/UOList.php';
include_once __DIR__ . '/../ui/Input.php';
include_once __DIR__ . '/../ui/Label.php';
include_once __DIR__ . '/../../Models/DataTypes/SimOutputDataType.php';
include_once __DIR__ . '/../../Models/DataTypes/HistoricalOddsDataType.php';
include_once __DIR__ . '/../../Models/Utils/SimPerformanceUtils.php';
include_once __DIR__ . '/../../Models/Utils/ArrayUtils.php';
include_once __DIR__ .'/../../Models/Constants/SimPerfKeys.php';

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
    private $gamesBySeasonAndDate = array();
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

        $this->gamesBySeasonAndDate = $this->applySeasonSwitchPerc(
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
        $chart_list = (new UOList())
            ->setItems($charts)
            ->setItemClass('chart_list_item bottom_border');
        
        echo $form;
        $chart_list->render();
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
                SimPerfKeys::VEGAS_HOME_PCT => $odds['home_pct_win'],
                SimPerfKeys::VEGAS_AWAY_PCT => $odds['away_pct_win'],
                SimPerfKeys::SIM_HOME_PCT => $game['home_win_pct'] * 100,
                SimPerfKeys::SIM_AWAY_PCT => 100 - $game['home_win_pct'] * 100,
                SimPerfKeys::HOME_TEAM_WINNER => $odds['home_team_winner']
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
                        $data[$year][$date][] = $game;
                    }
                } else {
                    foreach ($games_1 as $game) {
                        $data[$year][$date][] = $game;
                    }
                }
            }
        }

        return $data;
    }

    private function calculatePerfData() {
        $this->perfData = SimPerformanceUtils::calculateSimPerfData(
            ArrayUtils::flatten($this->gamesBySeasonAndDate),
            self::HIST_BUCKET_SIZE
        );
        $this->perfDataByYear = SimPerformanceUtils::calculateSimPerfDataByYear(
            $this->gamesBySeasonAndDate,
            self::HIST_BUCKET_SIZE
        );
    }

    private function getSimParamList() {
        $group_switch_slider = (new Slider())
            ->setName(self::P_GROUP_SWITCH_PERC)
            ->setValue($this->groupSwitchPerc)
            ->setMinAndMax(0, 100)
            ->setTickIncrement(25)
            ->getHTML();

        $first_season = (new Selector())
            ->setName(self::P_FIRST_SEASON)
            ->setValue($this->firstSeason)
            ->setOptions($this->possibleParams['season'])
            ->getHTML();
        $last_season = (new Selector())
            ->setName(self::P_LAST_SEASON)
            ->setValue($this->lastSeason)
            ->setOptions($this->possibleParams['season'])
            ->getHTML();

        $possible_buckets = $this->possibleParams['rand_bucket'];
        asort($possible_buckets);
        $first_bucket = (new Selector())
            ->setName(self::P_FIRST_BUCKET)
            ->setValue($this->firstBucket)
            ->setOptions($possible_buckets)
            ->getHTML();
        $last_bucket = (new Selector())
            ->setName(self::P_LAST_BUCKET)
            ->setValue($this->lastBucket)
            ->setOptions($possible_buckets)
            ->getHTML();

        return (new UOList())
            ->setItems(array(
                "<font class='list_title'>
                    Overall Params
                </font>",
                (new Label($group_switch_slider))
                    ->setLabel('Group Switch Perc')
                    ->getHTML(),
                (new Label($first_season))
                    ->setLabel('First Season')
                    ->getHTML(),
                (new Label($last_season))
                    ->setLabel('Last Season')
                    ->getHTML(),
                (new Label($first_bucket))
                    ->setLabel('First Bucket')
                    ->getHTML(),
                (new Label($last_bucket))
                    ->setLabel('Last Bucket')
                    ->getHTML(),
            ))
            ->setItemClass('list_item')
            ->getHTML();
    }

    private function getGroupParamList($group) {
        $weights_selector = (new Selector())
            ->setName(sprintf('weights_%s', $group))
            ->setValue($this->weights[$group])
            ->setOptions($this->possibleParams['weights'])
            ->getHTML();

        $stats_year_selector = (new Selector())
            ->setName(sprintf('stats_year_%s', $group))
            ->setValue($this->statsYear[$group])
            ->setOptions($this->possibleParams['stats_year'])
            ->getHTML();

        return (new UOList())
            ->setItems(array(
                "<font class='list_title'>
                    Group $group
                </font>",
                (new Label($weights_selector))
                    ->setLabel('Weights')
                    ->getHTML(),
                (new Label($stats_year_selector))
                    ->setLabel('Stats Year')
                    ->getHTML(),
            ))
            ->setItemClass('list_item')
            ->getHTML();
    }

    private function getBetParamList() {
        $bet_amount = (new Input())
            ->setType(InputTypes::NUMBER)
            ->setName(self::P_BET_AMOUNT)
            ->setValue($this->betAmount)
            ->getHTML();

        return (new UOList())
            ->setItems(array(
                "<font class='list_title'>
                    Bet Params
                </font>",
                (new Label($bet_amount))
                    ->setLabel('Bet Amount')
                    ->getHTML()
            ))
            ->setItemClass('list_item')
            ->getHTML();
    }

    private function getChart($type) {
        return
            "<div
                id=$type
                class='chart_canvas'>
            </div>";
    }

    public function setParams($params) {
        // Set default params.
        if (!$params) {
            $params = array(
                self::P_GROUP_SWITCH_PERC => 100,
                self::P_FIRST_SEASON => 2013,
                self::P_LAST_SEASON => 2013,
                self::P_FIRST_BUCKET => 0,
                self::P_LAST_BUCKET => 9,
                'weights_0' => 'b_total_100',
                'weights_1' => 'b_total_100',
                'stats_year_0' => 'career',
                'stats_year_1' => 'career'
            );
        }

        $this->groupSwitchPerc = (int)$params[self::P_GROUP_SWITCH_PERC];
        $this->firstSeason = (int)$params[self::P_FIRST_SEASON];
        $this->lastSeason = (int)$params[self::P_LAST_SEASON];
        $this->firstBucket = (int)$params[self::P_FIRST_BUCKET];
        $this->lastBucket = (int)$params[self::P_LAST_BUCKET];

        if ($this->firstSeason > $this->lastSeason) {
            $this->errors[] = 'First Season should be before Last Season.';
        }

        $this->weights = array(
            0 => $params['weights_0'],
            1 => $params['weights_1']
        );
        $this->statsYear = array(
            0 => $params['stats_year_0'],
            1 => $params['stats_year_1']
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
