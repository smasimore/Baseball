<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ .'/../Utils/ArrayUtils.php';

class SimPerformanceUtils {

    // Input and return keys.
    const VEGAS_PCT = 'vegas_win_pct';
    const SIM_PCT = 'sim_win_pct';
    const TEAM_WINNER = 'team_winner';
    const NUM_GAMES = 'num_games';
    const NUM_GAMES_TEAM_WINNER = 'num_games_team_winner';
    const ACTUAL_PCT = 'actual_win_pct';

    // Private keys - DON'T USE OUTSIDE OF THIS CLASS.
    const VEGAS_PCT_SUM = 'vegas_pct_sum';
    const SIM_PCT_SUM = 'sim_pct_sum';

    const BIN_MIN = 0;
    const BIN_MAX = 100;
    const MIN_SAMPLE_SIZE_IN_BIN = 10;

    public static function calculateSimPerfData($game_data, $bin_size = 5) {
        if (!ArrayUtils::isArrayOfArrays($game_data)) {
            throw new Exception('Game data must be array of arrays.');
        }

        if ($bin_size < 0 || $bin_size > 100) {
            throw new Exception(sprintf(
                'Bin size must be between %d and %d',
                self::BIN_MIN,
                self::BIN_MAX
            ));
        }

        if ($bin_size === 0 ||
            (self::BIN_MAX - self::BIN_MIN) % $bin_size !== 0) {
            throw new Exception('Bin max - min must be divisible by bin size.');
        }

        // Initialize perf_data.
        $perf_data = array();
        for ($i = self::BIN_MIN; $i < self::BIN_MAX; $i += $bin_size) {
            $perf_data[$i] = array(
                self::NUM_GAMES => 0,
                self::NUM_GAMES_TEAM_WINNER => 0,
                self::VEGAS_PCT_SUM => 0,
                self::SIM_PCT_SUM => 0
            );
        }

        foreach ($game_data as $game) {
            for ($i = self::BIN_MIN; $i < self::BIN_MAX; $i += $bin_size) {
                $vegas_pct_win = $game[self::VEGAS_PCT];
                if ($vegas_pct_win !== null && $vegas_pct_win >= $i &&
                    $vegas_pct_win < $i + $bin_size) {
                    $perf_data[$i][self::NUM_GAMES] += 1;
                    $perf_data[$i][self::NUM_GAMES_TEAM_WINNER] +=
                        $game[self::TEAM_WINNER];
                    $perf_data[$i][self::VEGAS_PCT_SUM] +=
                        $game[self::VEGAS_PCT];
                    $perf_data[$i][self::SIM_PCT_SUM] += $game[self::SIM_PCT];
                    break;
                }
            }
        }

        $return_perf_data = array();
        foreach ($perf_data as $bin => $data) {
            $num_games = $data[self::NUM_GAMES];
            $return_perf_data[$bin] = array(
                self::NUM_GAMES => $num_games,
                self::ACTUAL_PCT => $num_games !== 0
                    ? $data[self::NUM_GAMES_TEAM_WINNER] / $num_games * 100
                    : null,
                self::VEGAS_PCT => $num_games !== 0
                    ? $data[self::VEGAS_PCT_SUM] / $num_games
                    : null,
                self::SIM_PCT => $num_games !== 0
                    ? $data[self::SIM_PCT_SUM] / $num_games
                    : null
            );
        }

        return $return_perf_data;
    }

    public static function calculateSimPerfDataByYear(
        $game_data_by_year,
        $bin_size = 5
    ) {
        if (!ArrayUtils::isArrayOfArrays($game_data_by_year)) {
            throw new Exception('Game data must be array of arrays.');
        }

        $perf_data = array();
        foreach ($game_data_by_year as $year => $game_data) {
            $perf_data[$year] = self::calculateSimPerfData($game_data);
        }

        return $perf_data;
    }

    /**
     * Requires input in format of sim perf data returned by above functions.
     *
     * @return array(vegas perf score, sim perf score)
     */
    public static function calculateSimPerfScores($perf_data) {
        $vegas_numerator = 0;
        $sim_numerator = 0;
        $total_games = 0;
        foreach ($perf_data as $data) {
            $num_games = $data[self::NUM_GAMES];
            if ($num_games < self::MIN_SAMPLE_SIZE_IN_BIN) {
                continue;
            }

            $actual_pct = $data[self::ACTUAL_PCT];
            $total_games += $num_games;
            $vegas_numerator += $num_games *
                abs($data[self::VEGAS_PCT] - $actual_pct);
            $sim_numerator += $num_games *
                abs($data[self::SIM_PCT] - $actual_pct);
        }

        if ($total_games === 0) {
            return array(null, null);
        }

        return array(
            $vegas_numerator / $total_games,
            $sim_numerator / $total_games
        );
    }
}

?>
