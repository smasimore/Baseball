<?php
include_once 'Page.php';
include_once __DIR__ . '/../ui/UOList.php';

class SimPerformancePage extends Page {

    const HIST_BUCKET_SIZE = 5;

    private $loggedIn;

    // Params.
    private $groupSwitchPerc;
    private $firstSeason;
    private $lastSeason;
    private $bucketStart;
    private $bucketEnd;

    // Params that vary by group.
    private $weights;
    private $statsYear;
    private $statsType;

    // Vars.
    private $possibleParams = array();
    private $gamesByYear = array();
    private $histByYear = array();
    private $hist = array();
    private $perfScores = array();

    // Passed to js.
    private $histActual = array();
    private $histNumGames = array();

    private $errors = array();

    public function __construct($logged_in, $params) {
        parent::__construct($logged_in, true);
        $this->loggedIn = $logged_in;
        $this->setHeader(' ');

        $this->groupSwitchPerc = idx($params, 'group_switch_perc', 50);
        $this->firstSeason = idx($params, 'first_season', 1950);
        $this->lastSeason = idx($params, 'last_season', 1950);
        $this->bucketStart = idx($params, 'bucket_start', 0);
        $this->bucketEnd = idx($params, 'bucket_end', 9);

        $this->weights = array(
            0 => idx($params, 'weights_0', 'b_total_100'),
            1 => idx($params, 'weights_1', 'b_total_100')
        );
        $this->statsYear = array(
            0 => idx($params, 'stats_year_0', 'career'),
            1 => idx($params, 'stats_year_1', 'career')
        );
        $this->statsType = array(
            0 => idx($params, 'stats_type_0', 'basic'),
            1 => idx($params, 'stats_type_1', 'basic')
        );

        $this->fetchData();
        $this->calculateHist();
        $this->calculatePerfScores();
        $this->display();
    }

    private function fetchData() {
        $param_query =
            "SELECT DISTINCT
                weights,
                stats_year,
                stats_type,
                season,
                rand_bucket
            FROM sim_output";

        $this->possibleParams = exe_sql(DATABASE, $param_query);

        $weights_0 = $this->weights[0];
        $weights_1 = $this->weights[1];
        $stats_year_0 = $this->statsYear[0];
        $stats_year_1 = $this->statsYear[1];
        $stats_type_0 = $this->statsType[0];
        $stats_type_1 = $this->statsType[1];

        $query_where_0 =
                "a.weights = '$weights_0' AND
                a.stats_year = '$stats_year_0' AND
                a.stats_type = '$stats_type_0'
                ORDER BY a.game_date;";
        $query_where_1 =
                "a.weights = '$weights_1' AND
                a.stats_year = '$stats_year_1' AND
                a.stats_type = '$stats_type_1'
                ORDER BY a.game_date;";

        $query =
            "SELECT a.home_win_pct, a.game_date, a.season, b.home_team_winner
            FROM sim_output a
            LEFT OUTER JOIN (
                SELECT
                    game_id,
                    CASE
                        WHEN home_score_ct > away_score_ct THEN 1
                        WHEN home_score_ct = away_score_ct THEN 2
                    ELSE 0 END as home_team_winner
                FROM games
            ) b ON a.gameid = b.game_id
            WHERE
                b.home_team_winner <> 2 AND
                a.analysis_runs = 5000 AND
                a.season >= $this->firstSeason AND
                a.season <= $this->lastSeason AND
                a.rand_bucket >= $this->bucketStart AND
                a.rand_bucket <= $this->bucketEnd AND ";

        $results_0 = exe_sql(
            DATABASE,
            $query . $query_where_0
        );
        $results_1 = exe_sql(
            DATABASE,
            $query . $query_where_1
        );

        $num_games_0 = count($results_0);
        $num_games_1 = count($results_1);
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

        $results_0 = $this->formatSQLGameData($results_0);
        $results_1 = $this->formatSQLGameData($results_1);

        $this->gamesByYear = $this->applySeasonSwitchPerc(
            $results_0,
            $results_1
        );
    }

    private function formatSQLGameData($data) {
        $f_data = array();
        foreach ($data as $game) {
            $f_data[$game['season']][$game['game_date']][] = array(
                'home_win_pct' => $game['home_win_pct'],
                'home_team_winner' => $game['home_team_winner']
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
                if ($date < $switch_date) {
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

    private function calculateHist() {
        foreach ($this->gamesByYear as $year => $games) {
            // Initialize.
            $this->histByYear[$year] = array();
            for ($i = 0; $i < 100; $i += self::HIST_BUCKET_SIZE) {
                if (!idx($this->hist, $i)) {
                    $this->hist[$i] = array(
                        'games' => 0,
                        'home_team_winner' => 0
                    );
                }

                $this->histByYear[$year][$i] = array(
                    'games' => 0,
                    'home_team_winner' => 0
                );
            }

            // Go through each game and add to hist.
            foreach ($games as $game) {
                $home_win_pct = $game['home_win_pct'] * 100;
                for ($i = 0; $i < 100; $i += self::HIST_BUCKET_SIZE) {
                    if ($home_win_pct >= $i &&
                        $home_win_pct < $i + self::HIST_BUCKET_SIZE
                    ) {
                        $this->hist[$i]['games'] += 1;
                        $this->hist[$i]['home_team_winner'] +=
                            $game['home_team_winner'];
                        $this->histByYear[$year][$i]['games'] += 1;
                        $this->histByYear[$year][$i]['home_team_winner'] +=
                            $game['home_team_winner'];
                    }
                }
            }
        }
    }

    private function calculatePerfScores() {
        $this->perfScores['overall'] = $this->calculatePerfScore(
            $this->hist,
            'overall'
        );
        foreach ($this->histByYear as $year => $hist) {
            $this->perfScores[$year] = $this->calculatePerfScore($hist, $year);
        }
    }

    public function getHistData() {
        return array($this->histActual, $this->histNumGames);
    }

    private function calculatePerfScore($hist, $type) {
        $total_num_games = 0;
        $sum_diff_from_expected = 0;
        foreach ($hist as $bin_start => $data) {
            $num_games = $data['games'];
            $total_num_games += $num_games;

            // For this bin, calculate percent that home actually won.
            $perc_home_won_actual = $num_games ?
                $data['home_team_winner'] / $data['games'] * 100 : 0;

            $this->histActual[$type][$bin_start] = $perc_home_won_actual;
            $this->histNumGames[$type][$bin_start] = $num_games;

            // Calculate what we'd expect percent home to win based on what
            // bin games are in. E.g. if in 0% -> 5% bin, this should be 2.5%.
            $perc_home_won_expected = $bin_start + self::HIST_BUCKET_SIZE / 2;

            // Calculate absolute distance between actual and expected.
            $sum_diff_from_expected += $num_games *
                abs($perc_home_won_expected - $perc_home_won_actual);
        }

        if (!$total_num_games) {
            $this->errors[] =
                "<div>
                    Missing Data: $type games = $total_num_games.
                </div>";
            return 0;
        }

        return round($sum_diff_from_expected / $total_num_games, 2);
    }


    public function display() {
        $errors_list = new UOList($this->errors, null, 'errorbox medium_w');
        $errors_list->display();

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


        echo $form;

        print_r($this->perfScores);

        $this->renderHistogram('overall');
    }

    private function getSimParamList() {
        $group_switch_slider = new Slider(
            'Group Switch Perc',
            'group_switch_perc',
            $this->groupSwitchPerc,
            0, 100, 25,
            'params_list'
        );

        $list = new UOList(
            array(
                "<font class='list_title'>
                    Overall Params
                </font>",
                $group_switch_slider->getHTML()
            ),
            null,
            'params_list'
        );

        return $list->getHTML();

    }

    private function getGroupParamList($group) {
        $weights_selector = new Selector(
            'Weights',
            'weights_' . $group,
            $this->weights[$group],
            array_unique(array_column($this->possibleParams, 'weights'))
        );
        $weights_selector = $weights_selector->getHTML();

        $param_list = new UOList(
            array(
                "<font class='list_title'>
                    Group $group
                </font>",
                $weights_selector
            ),
            null,
            'params_list'
        );

        return $param_list->getHTML();
    }

    private function renderHistogram($type) {
        echo
            "<div
                id=$type
                style='min-width: 310px; height: 400px; margin: 0 auto'>
            </div>";
    }
}
?>
