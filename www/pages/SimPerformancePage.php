<?php
include_once 'Page.php';
include_once __DIR__ . '/../ui/UOList.php';
include_once __DIR__ . '/../ui/Input.php';
include_once __DIR__ . '/../ui/Label.php';
include_once __DIR__ . '/../ui/Table.php';
include_once __DIR__ . '/../../Models/Bets.php';
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

    private $betData = array();
    private $betDataByYear = array();
    private $betCumulativeData = array();
    private $betCumulativeDataByYear = array();
    private $betCumulativeDataByTeam = array();

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
        $this->calculateBetData();
    }

    final protected function renderPage() {
        $this->renderParamsForm();

        if ($this->gamesBySeasonAndDate) {
            $this->renderChartTable();
        }

    }

    private function renderParamsForm() {
        $sim_param_list = $this->getSimParamList();
        $bet_param_list = $this->getBetParamList();
        $submit_button = "<input class='button' type='submit' value='Submit'>";

        $params_table = (new Table())
            ->setData(array(
                $this->getSimParamList(),
                $this->getBetParamList()
            ))
            ->setColumns(2)
            ->setClass('topmargin sim_perf_params_table')
            ->setCellClass('sim_perf_param_table_td')
            ->getHTML();

        echo
            "<form class='sim_perf' action='sim_perf.php'>
                <div class='sim_perf_params_form'>
                    $params_table
                    <div>$submit_button</div>
                </div>
            </form>";
    }

    private function renderChartTable() {
        $overall_charts = array(
            $this->getChart('overall_perf'),
            $this->getChart('overall_bets'),
            $this->getChart('bets_by_team'),
            $this->getChart('PLACEHOLDER'), // TODO(smas): Replace this with something useful.
        );

        $by_year_charts = array();
        foreach (array_keys($this->perfDataByYear) as $year) {
            $by_year_charts[] = $this->getChart("perf_$year");
            $by_year_charts[] = $this->getChart("bet_$year");
        }

        $charts = array_merge($overall_charts, $by_year_charts);

        return (new Table())
            ->setData($charts)
            ->setColumns(2)
            ->setClass('topmargin table')
            ->render();
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
                SimPerfKeys::VEGAS_HOME_ODDS => $odds['home_odds'],
                SimPerfKeys::VEGAS_AWAY_ODDS => $odds['away_odds'],
                SimPerfKeys::VEGAS_HOME_PCT => $odds['home_pct_win'],
                SimPerfKeys::VEGAS_AWAY_PCT => $odds['away_pct_win'],
                SimPerfKeys::SIM_HOME_PCT => $game['home_win_pct'] * 100,
                SimPerfKeys::SIM_AWAY_PCT => 100 - $game['home_win_pct'] * 100,
                SimPerfKeys::HOME_TEAM_WINNER => $odds['home_team_winner']
            );
        }

        foreach ($f_data as $season => $games_by_date) {
            ksort($games_by_date);
            $f_data[$season] = $games_by_date;
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

    private function calculateBetData() {
        foreach ($this->gamesBySeasonAndDate as $year => $game_data_by_date) {
            $this->betDataByYear[$year] = (new Bets($game_data_by_date))
                ->setAllowHomeBet(true)
                ->setAllowAwayBet(true)
                ->setSimVegasPctDiff(5)
                ->setDefaultBetAmount(100)
                ->getBetData();
        }

        $this->betCumulativeDataByYear =
            SimPerformanceUtils::calculateBetCumulativeDataByYear(
                $this->betDataByYear
            );

        $this->betData = ArrayUtils::flatten($this->betDataByYear, true);
        $this->betCumulativeData =
            SimPerformanceUtils::calculateBetCumulativeData($this->betData);

        $cumulative_bet_home = SimPerformanceUtils::calculateBetCumulativeData(
            $this->betData,
            Bets::BET_TEAM,
            TeamTypes::HOME
        );
        $cumulative_bet_away = SimPerformanceUtils::calculateBetCumulativeData(
            $this->betData,
            Bets::BET_TEAM,
            TeamTypes::AWAY
        );
        $this->betCumulativeDataByTeam = array(
            'Home' => $cumulative_bet_home,
            'Away' => $cumulative_bet_away,
        );
    }

    private function getSimParamList() {
        $sim_params = array_merge(
            $this->getOverallSimParams(),
            $this->getGroupParams(0),
            $this->getGroupParams(1)
        );

        return (new UOList())
            ->setItems($sim_params)
            ->setItemClass('list_item')
            ->getHTML();
    }

    private function getOverallSimParams() {
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

        return array(
            "<font class='list_title'>
                Sim Params
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
        );
    }

    private function getGroupParams($group) {
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

        return array(
            "<font class='list_title'>
                Group $group
            </font>",
            (new Label($weights_selector))
                ->setLabel('Weights')
                ->getHTML(),
            (new Label($stats_year_selector))
                ->setLabel('Stats Year')
                ->getHTML(),
        );
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

    private function getROI(array $data) {
        if (!$data) {
            return null;
        }

        $last_day = end($data);
        $cumulative_bet_amount =
            $last_day[SimPerformanceUtils::CUMULATIVE_BET_AMOUNT];

        return $cumulative_bet_amount
            ? round(
                $last_day[SimPerformanceUtils::CUMULATIVE_PAYOUT] /
                    $cumulative_bet_amount * 100,
                2
            ) : null;
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

    public function getPerfScoreLabelsByYear() {
        $labels_by_year = array();
        foreach ($this->perfDataByYear as $year => $data) {
            $labels_by_year[$year] = $this->getPerfScoreLabel($data, $year);
        }

        return $labels_by_year;
    }

    public function getBetCumulativeData() {
        return $this->betCumulativeData;
    }

    public function getBetCumulativeDataByYear() {
        return $this->betCumulativeDataByYear;
    }

    public function getBetCumulativeDataLabel(
        $data,
        $label
    ) {
        $roi = $this->getROI($data);;

        return sprintf(
            '%s - ROI: %g%%',
            $label,
            $roi
        );
    }

    public function getBetCumulativeDataLabelByYear() {
        $labels_by_year = array();
        foreach ($this->betCumulativeDataByYear as $year => $data) {
            $labels_by_year[$year] = $this->getBetCumulativeDataLabel(
                $data,
                $year
            );
        }

        return $labels_by_year;
    }

    public function getBetCumulativeDataByTeam() {
        return $this->betCumulativeDataByTeam;
    }
}
?>
