<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ .'/../Utils/ArrayUtils.php';
include_once __DIR__ .'/../Constants/SimPerfKeys.php';
include_once __DIR__ .'/../Bets.php';

class SimPerformanceUtils {

    // Sim perf return keys.
    const NUM_GAMES = 'num_games';
    const NUM_GAMES_WINNER = 'num_games_winner';
    const ACTUAL_PCT = 'actual_win_pct';

    const CUMULATIVE_NUM_GAMES = 'cumulative_num_games';
    const CUMULATIVE_NUM_GAMES_BET = 'cumulative_num_games_bet';
    const CUMULATIVE_NUM_GAMES_WINNER = 'cumulatiave_num_games_winner';
    const CUMULATIVE_BET_AMOUNT = 'cumulative_bet_amount';
    const CUMULATIVE_PAYOUT = 'cumulative_payout';

    // Private keys - DON'T USE OUTSIDE OF THIS CLASS.
    const VEGAS_PCT_SUM = 'vegas_pct_sum';
    const SIM_PCT_SUM = 'sim_pct_sum';

    const BIN_MIN = 0;
    const BIN_MAX = 100;
    const MIN_SAMPLE_SIZE_IN_BIN = 10;

    /*
     * @param array($date => array($gameid => array(...)))
     * @param int
     */
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
                self::NUM_GAMES_WINNER => 0,
                self::VEGAS_PCT_SUM => 0,
                self::SIM_PCT_SUM => 0
            );
        }

        foreach ($game_data as $date => $games) {
            foreach ($games as $game) {
                for ($i = self::BIN_MIN; $i < self::BIN_MAX; $i += $bin_size) {
                    $vegas_pct_win = $game[SimPerfKeys::VEGAS_HOME_PCT];
                    if ($vegas_pct_win !== null && $vegas_pct_win >= $i &&
                        $vegas_pct_win < $i + $bin_size) {
                        $perf_data[$i][self::NUM_GAMES] += 1;
                        $perf_data[$i][self::NUM_GAMES_WINNER] +=
                            $game[SimPerfKeys::HOME_TEAM_WINNER];
                        $perf_data[$i][self::VEGAS_PCT_SUM] +=
                            $game[SimPerfKeys::VEGAS_HOME_PCT];
                        $perf_data[$i][self::SIM_PCT_SUM] +=
                            $game[SimPerfKeys::SIM_HOME_PCT];
                        break;
                    }
                }
            }
        }

        $return_perf_data = array();
        foreach ($perf_data as $bin => $data) {
            $num_games = $data[self::NUM_GAMES];
            $return_perf_data[$bin] = array(
                self::NUM_GAMES => $num_games,
                self::ACTUAL_PCT => $num_games !== 0
                    ? $data[self::NUM_GAMES_WINNER] / $num_games * 100
                    : null,
                SimPerfKeys::VEGAS_HOME_PCT => $num_games !== 0
                    ? $data[self::VEGAS_PCT_SUM] / $num_games
                    : null,
                SimPerfKeys::SIM_HOME_PCT => $num_games !== 0
                    ? $data[self::SIM_PCT_SUM] / $num_games
                    : null
            );
        }

        return $return_perf_data;
    }

    /*
     * @param array($season => array($date => array($gameid => array(...))))
     * @param int
     */
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
                abs($data[SimPerfKeys::VEGAS_HOME_PCT] - $actual_pct);
            $sim_numerator += $num_games *
                abs($data[SimPerfKeys::SIM_HOME_PCT] - $actual_pct);
        }

        if ($total_games === 0) {
            return array(null, null);
        }

        return array(
            $vegas_numerator / $total_games,
            $sim_numerator / $total_games
        );
    }

    /*
     * Keyed on date.
     */
    public static function calculateBetCumulativeData(
        array $games_by_date,
        $filter_by_key = null,
        $filter_by_value = null
    ) {
        $cumulative_num_games = 0;
        $cumulative_num_games_bet = 0;
        $cumulative_num_games_winner = 0;
        $cumulative_bet_amount = 0;
        $cumulative_payout = 0;

        $cumulative_data_by_date = array();
        foreach ($games_by_date as $date => $games) {
            foreach ($games as $game) {
                if ($filter_by_key !== null) {
                    if (!array_key_exists($filter_by_key, $game)) {
                        throw new Exception(
                            sprintf(
                                '%s must be key in game data.',
                                $filter_by_key
                            )
                        );
                    }

                    $game_filter_value = idx($game, $filter_by_key);
                    if ($game_filter_value !== $filter_by_value) {
                        continue;
                    }
                }

                $cumulative_num_games++;

                if ($game[Bets::BET_TEAM] !== null) {
                    $cumulative_num_games_bet++;
                    $cumulative_bet_amount += $game[Bets::BET_AMOUNT];

                    if ($game[Bets::BET_TEAM_WINNER] === true) {
                        $cumulative_num_games_winner++;
                    }

                    $cumulative_payout += $game[Bets::BET_NET_PAYOUT];
                }
            }

            $cumulative_data_by_date[$date] = array(
                self::CUMULATIVE_NUM_GAMES => $cumulative_num_games,
                self::CUMULATIVE_NUM_GAMES_BET => $cumulative_num_games_bet,
                self::CUMULATIVE_NUM_GAMES_WINNER =>
                    $cumulative_num_games_winner,
                self::CUMULATIVE_BET_AMOUNT => $cumulative_bet_amount,
                self::CUMULATIVE_PAYOUT => $cumulative_payout,
            );
        }

        return $cumulative_data_by_date;
    }

    /*
     * @param array($season => array($date => array($gameid => array(...))))
     */
    public static function calculateBetCumulativeDataByYear(
        $games_by_year
    ) {
        if (!ArrayUtils::isArrayOfArrays($games_by_year)) {
            throw new Exception('Game data must be array of arrays.');
        }

        $cumulative_data = array();
        foreach ($games_by_year as $year => $game_data_by_date) {
            $cumulative_data[$year] = self::calculateBetCumulativeData(
                $game_data_by_date
            );
        }

        return $cumulative_data;
    }
}

?>
