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
include_once __DIR__ .'/../../Models/Constants/BetsRequiredFields.php';

class SimPerformancePage extends Page {

    const HIST_BUCKET_SIZE = 5;

    // Param names.
    const P_GROUP_SWITCH_PCT = 'group_switch_pct';
    const P_FIRST_SEASON = 'first_season';
    const P_LAST_SEASON = 'last_season';
    const P_FIRST_BUCKET = 'first_bucket';
    const P_LAST_BUCKET = 'last_bucket';

    // Bet param names.
    const P_BET_AMOUNT = 'bet_amount';
    const P_BET_HOME = 'bet_home';
    const P_BET_AWAY = 'bet_away';
    const P_SIM_VEGAS_PCT_DIFF = 'sim_vegas_pct_diff';

    // Param values.
    private $groupSwitchPct;
    private $firstSeason;
    private $lastSeason;
    private $firstBucket;
    private $lastBucket;

    // Group param values.
    private $weights;
    private $statsYear;
    private $statsType;

    // Bet param values.
    private $betAmount;
    private $betHome;
    private $betAway;
    private $simVegasPctDiff;

    // Param vars.
    private $possibleParams = array();
    private $defaultParams = array(
        self::P_GROUP_SWITCH_PCT => 100,
        self::P_FIRST_SEASON => 2013,
        self::P_LAST_SEASON => 2013,
        self::P_FIRST_BUCKET => 0,
        self::P_LAST_BUCKET => 9,
        'weights_0' => 'b_total_100',
        'weights_1' => 'b_total_100',
        'stats_year_0' => 'career',
        'stats_year_1' => 'career',
        self::P_BET_AMOUNT => 100,
        self::P_SIM_VEGAS_PCT_DIFF => 5,
        self::P_BET_HOME => true,
        self::P_BET_AWAY => true,
    );


    // Vars for storing calculated Sim perf data to be used for rendering
    // charts.
    private $perfData = array();
    private $perfDataByYear = array();
    private $perfScoreVegas;
    private $perfScoreSim;
    private $perfScoreVegasByYear = array();
    private $perfScoreSimByYear = array();

    // Vars for storing calculated bet data to be used for rendering charts.
    private $roi;
    private $roiByYear = array();
    private $betCumulativeData = array();
    private $betCumulativeDataByYear = array();
    private $betCumulativeDataByTeam = array();
    private $betCumulativeDataByPctDiff = array();

    final protected function renderPageIfErrors() {
        return true;
    }

    public function setParams($params) {
        // TODO(smas): create $this->params to store these.
        // Set default params.
        $params = $params ?: $this->defaultParams;

        $this->groupSwitchPct = (int)$params[self::P_GROUP_SWITCH_PCT];
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

        $this->betAmount = (int)$params[self::P_BET_AMOUNT];
        $this->simVegasPctDiff = (int)$params[self::P_SIM_VEGAS_PCT_DIFF];
        $this->betHome = idx($params, self::P_BET_HOME) === null ? false : true;
        $this->betAway = idx($params, self::P_BET_AWAY) === null ? false : true;

        return $this;
    }

    // Do all necessary data fetching and score, bet, and ROI calculations.
    final protected function gen() {
        $this->possibleParams = $this->genPossibleParams();

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

        $games_by_season_and_date = $this->applySeasonSwitchPct(
            $results_0,
            $results_1
        );

        list(
            $games_bet_on,
            $this->betCumulativeData,
            $this->betCumulativeDataByYear,
            $this->betCumulativeDataByTeam,
            $this->betCumulativeDataByPctDiff,
            $this->roi,
            $this->roiByYear,
        ) = $this->calculateBetData($games_by_season_and_date);

        list(
            $this->perfData,
            $this->perfDataByYear,
            $this->perfScoreVegas,
            $this->perfScoreSim,
            $this->perfScoreVegasByYear,
            $this->perfScoreSimByYear,
        ) = $this->calculatePerfData($games_by_season_and_date, $games_bet_on);
    }

    private function genPossibleParams() {
        $possible_params = (new SimOutputDataType())
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
        $possible_params['season'] = array_intersect(
            $possible_params['season'],
            $possible_odds_seasons['season']
        );

        return $possible_params;
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

            // Bets model requires these fields.
            $f_data[$game['season']][$game['game_date']][$game['gameid']] =
                array(
                    BetsRequiredFields::VEGAS_HOME_ODDS => $odds['home_odds'],
                    BetsRequiredFields::VEGAS_AWAY_ODDS => $odds['away_odds'],
                    BetsRequiredFields::VEGAS_HOME_PCT => $odds['home_pct_win'],
                    BetsRequiredFields::VEGAS_AWAY_PCT => $odds['away_pct_win'],
                    BetsRequiredFields::SIM_HOME_PCT =>
                        $game['home_win_pct'] * 100,
                    BetsRequiredFields::SIM_AWAY_PCT =>
                        100 - $game['home_win_pct'] * 100,
                    BetsRequiredFields::HOME_TEAM_WINNER =>
                        $odds['home_team_winner']
                );
        }

        foreach ($f_data as $season => $games_by_date) {
            ksort($games_by_date);
            $f_data[$season] = $games_by_date;
        }

        return $f_data;
    }

    private function applySeasonSwitchPct($data_0, $data_1) {
        $data = array();
        foreach ($data_1 as $year => $games_by_date_1) {
            $games_by_date_0 = idx($data_0, $year, array());
            $dates = array_keys($games_by_date_1);
            $switch_date = $this->groupSwitchPct ?
                $dates[round((count($dates) - 1) /
                (100 / $this->groupSwitchPct))] :
                $dates[0];
            foreach ($games_by_date_1 as $date => $games_1) {
                if ($date <= $switch_date) {
                    // Use idx because of Incomplete Data error. Error will
                    // display and data shouldn't be trusted.
                    $games_0 = idx($games_by_date_0, $date, array());
                    foreach ($games_0 as $gameid => $game) {
                        $data[$year][$date][$gameid] = $game;
                    }
                } else {
                    foreach ($games_1 as $gameid => $game) {
                        $data[$year][$date][$gameid] = $game;
                    }
                }
            }
        }

        return $data;
    }

    private function calculateBetData(array $games_by_season_and_date) {
        $bet_data_by_year = array();
        foreach ($games_by_season_and_date as $year => $game_data_by_date) {
            $bet_data_by_year[$year] = (new Bets($game_data_by_date))
                ->setAllowHomeBet($this->betHome)
                ->setAllowAwayBet($this->betAway)
                ->setSimVegasPctDiff($this->simVegasPctDiff)
                ->setBaseBetAmount($this->betAmount)
                ->getBetData();
        }
        $bet_data = ArrayUtils::flatten($bet_data_by_year, true);

        $games_bet_on = array_keys(array_filter(
            ArrayUtils::flatten($bet_data, true),
            function($game_data) {
                return $game_data[Bets::BET_TEAM] !== null;
            }
        ));
        $bet_cumulative_data =
            SimPerformanceUtils::calculateBetCumulativeData($bet_data);
        $bet_cumulative_data_by_year =
            SimPerformanceUtils::calculateBetCumulativeDataByYear(
                $bet_data_by_year
            );
        $bet_cumulative_data_by_team = $this->calculateBetDataByTeam(
            $bet_data
        );
        $bet_cumulative_data_by_pct_diff = $this->calculateBetDataByPctDiff(
            $bet_data
        );
        $roi = $this->calculateROI($bet_cumulative_data);
        $roi_by_year = $this->calculateROIByYear($bet_cumulative_data_by_year);

        return array(
            $games_bet_on,
            $bet_cumulative_data,
            $bet_cumulative_data_by_year,
            $bet_cumulative_data_by_team,
            $bet_cumulative_data_by_pct_diff,
            $roi,
            $roi_by_year,
        );
    }

    private function calculateBetDataByTeam($bet_data) {
        $cumulative_bet_home = SimPerformanceUtils::calculateBetCumulativeData(
            $bet_data,
            Bets::BET_TEAM,
            TeamTypes::HOME
        );
        $cumulative_bet_away = SimPerformanceUtils::calculateBetCumulativeData(
            $bet_data,
            Bets::BET_TEAM,
            TeamTypes::AWAY
        );

        return array(
            'Home' => $cumulative_bet_home,
            'Away' => $cumulative_bet_away,
        );
    }

    private function calculateBetDataByPctDiff($bet_data) {
        $bet_cumulative_data_by_pct_diff = array();
        for ($i = $this->simVegasPctDiff; $i <= 40; $i += 5) {
            $bet_cumulative_data_by_pct_diff[$i] =
                SimPerformanceUtils::calculateBetCumulativeData(
                    $bet_data,
                    Bets::BET_PCT_DIFF,
                    null,
                    $i,
                    $i + 5
                );
        }

        return $bet_cumulative_data_by_pct_diff;
    }

    private function calculateROI(array $data) {
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

    private function calculateROIByYear(array $data_by_year) {
        $roi_by_year = array();
        foreach ($data_by_year as $year => $data) {
            $roi_by_year[$year] = $this->calculateROI($data);
        }

        return $roi_by_year;
    }

    private function calculatePerfData(
        array $games_by_season_and_date,
        array $games_bet_on
    ) {
        $game_data_by_date = ArrayUtils::flatten($games_by_season_and_date);
        $game_data_by_date = array_intersect_key(
            $game_data_by_date,
            $games_bet_on
        );

        if ($game_data_by_date === array()) {
            return array(
                array(),
                array(),
                null,
                null,
                array(),
                array(),
            );
        }

        $game_data = ArrayUtils::flatten($game_data_by_date, true);

        $perf_data = SimPerformanceUtils::calculateSimPerfData(
            $game_data,
            self::HIST_BUCKET_SIZE
        );

        $perf_data_by_year = SimPerformanceUtils::calculateSimPerfDataByYear(
            $games_by_season_and_date,
            self::HIST_BUCKET_SIZE
        );

        list($perf_score_vegas, $perf_score_sim) =
            SimPerformanceUtils::calculateSimPerfScores($perf_data);

        $perf_score_vegas_by_year = array();
        $perf_score_sim_by_year = array();
        foreach ($perf_data_by_year as $year => $year_data) {
            list(
                $perf_score_vegas_by_year[$year],
                $perf_score_sim_by_year[$year],
            ) = SimPerformanceUtils::calculateSimPerfScores($year_data);
        }

        return array(
            $perf_data,
            $perf_data_by_year,
            $perf_score_vegas,
            $perf_score_sim,
            $perf_score_vegas_by_year,
            $perf_score_sim_by_year,
        );
    }

    final protected function renderPage() {
        $this->renderParamsForm();

        if ($this->perfDataByYear) {
            $this->renderSimAndBetSummary();
            $this->renderChartTable();
        }
    }

    private function renderParamsForm() {
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
                    <div>
                        <input class='button' type='submit' value='Submit'>
                    </div>
                </div>
            </form>";
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
            ->setName(self::P_GROUP_SWITCH_PCT)
            ->setValue($this->groupSwitchPct)
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
                ->setLabel('Group Switch Pct')
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

        $sim_vegas_pct_diff = (new Input())
            ->setType(InputTypes::NUMBER)
            ->setName(self::P_SIM_VEGAS_PCT_DIFF)
            ->setValue($this->simVegasPctDiff)
            ->getHTML();

        $bet_home = (new Input())
            ->setType(InputTypes::CHECKBOX)
            ->setName(self::P_BET_HOME)
            ->setValue($this->betHome)
            ->getHTML();

        $bet_away = (new Input())
            ->setType(InputTypes::CHECKBOX)
            ->setName(self::P_BET_AWAY)
            ->setValue($this->betAway)
            ->getHTML();

        return (new UOList())
            ->setItems(array(
                "<font class='list_title'>
                    Bet Params
                </font>",
                (new Label($bet_amount))
                    ->setLabel('Bet Amount')
                    ->getHTML(),
                (new Label($sim_vegas_pct_diff))
                    ->setLabel('Sim Vegas Pct Diff')
                    ->getHTML(),
                (new Label($bet_home))
                    ->setLabel('Bet Home')
                    ->getHTML(),
                (new Label($bet_away))
                    ->setLabel('Bet Away')
                    ->getHTML(),
            ))
            ->setItemClass('list_item')
            ->getHTML();
    }

    private function renderSimAndBetSummary() {
        $num_years = count($this->roiByYear);
        $years_positive = 0;
        foreach ($this->roiByYear as $year => $roi) {
            if ($roi > 0) {
                $years_positive++;
            }
        }
        $pct_years_positive = round($years_positive / $num_years * 100, 2);

        $min_roi = min(array_values($this->roiByYear));
        $max_roi = max(array_values($this->roiByYear));

        $data = array(
            array(
                'Perf Score' => round($this->perfScoreSim, 2),
                'Avg ROI' => sprintf('%g%%', $this->roi),
                'Min ROI' => sprintf('%g%%', $min_roi),
                'Max ROI' => sprintf('%g%%', $max_roi),
                'Pct Years Positive ROI' =>
                    sprintf('%g%%', $pct_years_positive),
            ),
        );
        (new DataTable())
            ->setData($data)
            ->setID('sim_and_bet_summary')
            ->render();
    }

    private function renderChartTable() {
        $overall_charts = array(
            $this->getChart('overall_perf'),
            $this->getChart('overall_bets'),
            $this->getChart('bets_by_team'),
            $this->getChart('bets_by_pct_diff'),
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

    private function getChart($type) {
        return
            "<div
                id=$type
                class='chart_canvas'>
            </div>";
    }

    public function getPerfData() {
        return $this->perfData;
    }

    public function getPerfDataByYear() {
        return $this->perfDataByYear;
    }

    public function getPerfScoreChartTitle() {
        return sprintf(
            'Sim Performance Score - Sim: %g / Vegas: %g',
            round($this->perfScoreSim, 2),
            round($this->perfScoreVegas, 2)
        );
    }

    public function getPerfScoreChartTitlesByYear() {
        $labels_by_year = array();
        foreach ($this->perfScoreSimByYear as $year => $sim_score) {
            $labels_by_year[$year] = sprintf(
                '%s - Sim: %g / Vegas: %g',
                $year,
                round($sim_score, 2),
                round($this->perfScoreVegasByYear[$year], 2)
            );
        }

        return $labels_by_year;
    }

    public function getBetCumulativeData() {
        return $this->betCumulativeData;
    }

    public function getBetCumulativeDataByYear() {
        return $this->betCumulativeDataByYear;
    }

    public function getBetCumulativeDataChartTitle() {
        return sprintf('Bet Performance - ROI: %g%%', $this->roi);
    }

    public function getBetCumulativeDataChartTitlesByYear() {
        $labels_by_year = array();
        foreach ($this->roiByYear as $year => $roi) {
            $labels_by_year[$year] = sprintf('%s - ROI: %g%%', $year, $roi);
        }

        return $labels_by_year;
    }

    public function getBetCumulativeDataByTeam() {
        return $this->betCumulativeDataByTeam;
    }

    public function getBetCumulativeDataByPctDiff() {
        return $this->betCumulativeDataByPctDiff;
    }
}
?>
