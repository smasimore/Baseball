<?php
include_once 'Page.php';
include_once __DIR__ . '/../ui/UOList.php';

class SimPerformancePage extends Page {

    const HIST_BUCKET_SIZE = 5;

    private $loggedIn;

    // Params.
    private $seasonSwitchPerc;
    private $firstSeason;
    private $lastSeason;
    private $bucketStart;
    private $bucketEnd;

    private $weightsA;
    private $statsYearA;
    private $statsTypeA;

    private $weightsB;
    private $statsYearB;
    private $statsTypeB;

    // Vars.
    private $gamesByYear = array();
    private $histByYear = array();
    private $hist = array();
    private $perfScores = array();

    public function __construct($logged_in, $params) {
        parent::__construct($logged_in, true);
        $this->loggedIn = $logged_in;

        $this->seasonSwitchPerc = idx($params, 'season_switch_perc', 50);
        $this->firstSeason = idx($params, 'first_season', 1950);
        $this->lastSeason = idx($params, 'last_season', 1951);
        $this->bucketStart = idx($params, 'bucket_start', 0);
        $this->bucketEnd = idx($params, 'bucket_end', 9);

        $this->weightsA = idx($params, 'weights_a', 'b_total_100');
        $this->statsYearA = idx($params, 'stats_year_a', 'career');
        $this->statsTypeA = idx($params, 'stats_type_a', 'basic');

        $this->weightsB = idx($params, 'weights_b', 'b_total_100');
        $this->statsYearB = idx($params, 'stats_year_b', 'season');
        $this->statsTypeB = idx($params, 'stats_type_b', 'basic');

        $this->fetchData();
        $this->calculateHist();
        $this->calculatePerfScores();
        $this->display();
    }

    private function fetchData() {
        $query_where_a =
                "a.weights = '$this->weightsA' AND
                a.stats_year = '$this->statsYearA' AND
                a.stats_type = '$this->statsTypeA'";
        $query_where_b =
                "a.weights = '$this->weightsB' AND
                a.stats_year = '$this->statsYearB' AND
                a.stats_type = '$this->statsTypeB'";

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

        $results_a = exe_sql(
            'baseball',
            $query . $query_where_a . ' ORDER BY a.game_date;'
        );
        $results_b = exe_sql(
            'baseball',
            $query . $query_where_b . ' ORDER BY a.game_date;'
        );

        if (count($results_a) !== count($results_b)) {
            //throw new Exception ('Incomplete Data');
        }

        $results_a = $this->formatSQLData($results_a);
        $results_b = $this->formatSQLData($results_b);

        $this->gamesByYear = $this->applySeasonSwitchPerc(
            $results_a,
            $results_b
        );
    }

    private function formatSQLData($data) {
        $f_data = array();
        foreach ($data as $game) {
            $f_data[$game['season']][$game['game_date']][] = array(
                'home_win_pct' => $game['home_win_pct'],
                'home_team_winner' => $game['home_team_winner']
            );
        }

        return $f_data;
    }

    private function applySeasonSwitchPerc($data_a, $data_b) {
        $data = array();
        foreach ($data_a as $year => $games_by_date_a) {
            $games_by_date_b = $data_b[$year];
            $dates = array_keys($games_by_date_a);
            $switch_date = $this->seasonSwitchPerc ?
                $dates[round(count($dates) / (100 / $this->seasonSwitchPerc))] :
                $dates[0];
            foreach ($games_by_date_a as $date => $games_a) {
                if ($date < $switch_date) {
                    foreach ($games_a as $game) {
                        $data[$year][] = $game;
                    }
                } else {
                    foreach ($games_by_date_b[$date] as $game) {
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
        $this->perfScores['overall'] = $this->calculatePerfScore($this->hist);
        foreach ($this->histByYear as $year => $hist) {
            $this->perfScores[$year] = $this->calculatePerfScore($hist);;
        }
    }

    private function calculatePerfScore($hist) {
        $total_num_games = 0;
        $sum_diff_from_expected = 0;
        foreach ($hist as $bin_start => $data) {
            $num_games = $data['games'];
            $total_num_games += $num_games;

            // For this bin, calculate percent that home actually won.
            $perc_home_won_actual = $num_games ?
                $data['home_team_winner'] / $data['games'] * 100 : 0;

            // Calculate what we'd expect percent home to win based on what
            // bin games are in. E.g. if in 0% -> 5% bin, this should be 2.5%.
            $perc_home_won_expected = $bin_start + self::HIST_BUCKET_SIZE / 2;

            // Calculate absolute distance between actual and expected.
            $sum_diff_from_expected += $num_games *
                abs($perc_home_won_expected - $perc_home_won_actual);
        }

        return round($sum_diff_from_expected / $total_num_games, 2);
    }


    public function display() {
        // TODO(smas): Display selectors and histograms.
    }
}
?>
