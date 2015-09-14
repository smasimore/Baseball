<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ . '/SimPerformanceUtils.php';
include_once __DIR__ . '/../Bets.php';

class SimPerformanceUtilsTest extends PHPUnit_Framework_TestCase {

    private $emptyBin = array(
        SimPerformanceUtils::NUM_GAMES => 0,
        SimPerformanceUtils::ACTUAL_PCT => null,
        BetsRequiredFields::VEGAS_HOME_PCT => null,
        BetsRequiredFields::SIM_HOME_PCT => null
    );

    private $cumulativeTestArray = array(
        '2014-06-01' => array(
            array(
                Bets::BET_TEAM => TeamTypes::HOME,
                Bets::BET_TEAM_WINNER => true,
                Bets::BET_AMOUNT => 100,
                Bets::BET_NET_PAYOUT => 100,
                Bets::BET_PCT_DIFF => 5,
            ),
            array(
                Bets::BET_TEAM => TeamTypes::HOME,
                Bets::BET_TEAM_WINNER => false,
                Bets::BET_AMOUNT => 100,
                Bets::BET_NET_PAYOUT => -100,
                Bets::BET_PCT_DIFF => 3,
            ),
            array(
                Bets::BET_TEAM => null,
                Bets::BET_TEAM_WINNER => null,
                Bets::BET_AMOUNT => null,
                Bets::BET_NET_PAYOUT => null,
                Bets::BET_PCT_DIFF => null,
            ),
        ),
        '2014-06-02' => array(
            array(
                Bets::BET_TEAM => TeamTypes::HOME,
                Bets::BET_TEAM_WINNER => true,
                Bets::BET_AMOUNT => 100,
                Bets::BET_NET_PAYOUT => 100,
                Bets::BET_PCT_DIFF => 10
            ),
            array(
                Bets::BET_TEAM => TeamTypes::AWAY,
                Bets::BET_TEAM_WINNER => true,
                Bets::BET_AMOUNT => 100,
                Bets::BET_NET_PAYOUT => 200,
                Bets::BET_PCT_DIFF => 3,
            ),
        ),
    );

    /**
     * @expectedException Exception
     */
    public function testCalculateSimPerfDataArrayException() {
        SimPerformanceUtils::calculateSimPerfData('test');
    }

    /**
     * @expectedException Exception
     */
    public function testCalculateSimPerfDataArrayOfArraysException() {
        SimPerformanceUtils::calculateSimPerfData(array('test'));
    }

    /**
     * @expectedException Exception
     */
    public function testCalculateSimPerfDataBinZeroException() {
        SimPerformanceUtils::calculateSimPerfData(array(array()), 0);
    }

    /**
     * @expectedException Exception
     */
    public function testCalculateSimPerfDataBinMinException() {
        SimPerformanceUtils::calculateSimPerfData(array(array()), -2);
    }

    /**
     * @expectedException Exception
     */
    public function testCalculateSimPerfDataBinMaxException() {
        SimPerformanceUtils::calculateSimPerfData(array(array()), 105);
    }

    /**
     * @expectedException Exception
     */
    public function testCalculateSimPerfDataBinRemainderException() {
        SimPerformanceUtils::calculateSimPerfData(array(array()), 3);
    }

    /**
     * @expectedException Exception
     */
    public function testCalculateSimPerfDataByYearArrayOfArrayException() {
        SimPerformanceUtils::calculateSimPerfDataByYear(
            array('test' => 'test2')
        );
    }

    public function providerCalculateSimPerfData() {
        return array(
            array(
                array(
                    array(
                        BetsRequiredFields::VEGAS_HOME_PCT => 2.5,
                        BetsRequiredFields::SIM_HOME_PCT => 5,
                        BetsRequiredFields::HOME_TEAM_WINNER => 0
                    ),
                    array(
                        BetsRequiredFields::VEGAS_HOME_PCT => 2.5,
                        BetsRequiredFields::SIM_HOME_PCT => 10,
                        BetsRequiredFields::HOME_TEAM_WINNER => 1
                    ),
                    array(
                        BetsRequiredFields::VEGAS_HOME_PCT => 52,
                        BetsRequiredFields::SIM_HOME_PCT => 50,
                        BetsRequiredFields::HOME_TEAM_WINNER => 0
                    ),
                    array(
                        BetsRequiredFields::VEGAS_HOME_PCT => 53,
                        BetsRequiredFields::SIM_HOME_PCT => 60,
                        BetsRequiredFields::HOME_TEAM_WINNER => 0
                    ),
                ),
                5,
                array(
                    0 => array(
                        SimPerformanceUtils::NUM_GAMES => 2,
                        SimPerformanceUtils::ACTUAL_PCT => 50,
                        BetsRequiredFields::VEGAS_HOME_PCT => 2.5,
                        BetsRequiredFields::SIM_HOME_PCT => 7.5
                    ),
                    5 => $this->emptyBin,
                    10 => $this->emptyBin,
                    15 => $this->emptyBin,
                    20 => $this->emptyBin,
                    25 => $this->emptyBin,
                    30 => $this->emptyBin,
                    35 => $this->emptyBin,
                    40 => $this->emptyBin,
                    45 => $this->emptyBin,
                    50 => array(
                        SimPerformanceUtils::NUM_GAMES => 2,
                        SimPerformanceUtils::ACTUAL_PCT => 0,
                        BetsRequiredFields::VEGAS_HOME_PCT => 52.5,
                        BetsRequiredFields::SIM_HOME_PCT => 55
                    ),
                    55 => $this->emptyBin,
                    60 => $this->emptyBin,
                    65 => $this->emptyBin,
                    70 => $this->emptyBin,
                    75 => $this->emptyBin,
                    80 => $this->emptyBin,
                    85 => $this->emptyBin,
                    90 => $this->emptyBin,
                    95 => $this->emptyBin
                )
            )
        );
    }

    /**
     * @dataProvider providerCalculateSimPerfData
     */
    public function testCalculateSimPerfData($game_data, $bin_size, $return) {
        $this->assertEquals(
            SimPerformanceUtils::calculateSimPerfData($game_data, $bin_size),
            $return
        );
    }

    public function providerCalculateSimPerfDataByYear() {
        return array(
            array(
                array(
                    2000 => array(
                        '2000-06-01' => array(
                            array(
                                BetsRequiredFields::VEGAS_HOME_PCT => 2.5,
                                BetsRequiredFields::SIM_HOME_PCT => 5,
                                BetsRequiredFields::HOME_TEAM_WINNER => 0
                            ),
                            array(
                                BetsRequiredFields::VEGAS_HOME_PCT => 2.5,
                                BetsRequiredFields::SIM_HOME_PCT => 10,
                                BetsRequiredFields::HOME_TEAM_WINNER => 1
                            ),
                        ),
                    ),
                    2001 => array(
                        '2001-06-01' => array(
                            array(
                                BetsRequiredFields::VEGAS_HOME_PCT => 52,
                                BetsRequiredFields::SIM_HOME_PCT => 50,
                                BetsRequiredFields::HOME_TEAM_WINNER => 0
                            ),
                            array(
                                BetsRequiredFields::VEGAS_HOME_PCT => 53,
                                BetsRequiredFields::SIM_HOME_PCT => 60,
                                BetsRequiredFields::HOME_TEAM_WINNER => 0
                            ),
                        ),
                    ),
                ),
                5,
                array(
                    2000 => array(
                        0 => array(
                            SimPerformanceUtils::NUM_GAMES => 2,
                            SimPerformanceUtils::ACTUAL_PCT => 50,
                            BetsRequiredFields::VEGAS_HOME_PCT => 2.5,
                            BetsRequiredFields::SIM_HOME_PCT => 7.5
                        ),
                        5 => $this->emptyBin,
                        10 => $this->emptyBin,
                        15 => $this->emptyBin,
                        20 => $this->emptyBin,
                        25 => $this->emptyBin,
                        30 => $this->emptyBin,
                        35 => $this->emptyBin,
                        40 => $this->emptyBin,
                        45 => $this->emptyBin,
                        50 => $this->emptyBin,
                        55 => $this->emptyBin,
                        60 => $this->emptyBin,
                        65 => $this->emptyBin,
                        70 => $this->emptyBin,
                        75 => $this->emptyBin,
                        80 => $this->emptyBin,
                        85 => $this->emptyBin,
                        90 => $this->emptyBin,
                        95 => $this->emptyBin
                    ),
                    2001 => array(
                        0 => $this->emptyBin,
                        5 => $this->emptyBin,
                        10 => $this->emptyBin,
                        15 => $this->emptyBin,
                        20 => $this->emptyBin,
                        25 => $this->emptyBin,
                        30 => $this->emptyBin,
                        35 => $this->emptyBin,
                        40 => $this->emptyBin,
                        45 => $this->emptyBin,
                        50 => array(
                            SimPerformanceUtils::NUM_GAMES => 2,
                            SimPerformanceUtils::ACTUAL_PCT => 0,
                            BetsRequiredFields::VEGAS_HOME_PCT => 52.5,
                            BetsRequiredFields::SIM_HOME_PCT => 55
                        ),
                        55 => $this->emptyBin,
                        60 => $this->emptyBin,
                        65 => $this->emptyBin,
                        70 => $this->emptyBin,
                        75 => $this->emptyBin,
                        80 => $this->emptyBin,
                        85 => $this->emptyBin,
                        90 => $this->emptyBin,
                        95 => $this->emptyBin
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider providerCalculateSimPerfDataByYear
     */
    public function testCalculateSimPerfDataByYear(
        $game_data,
        $bin_size,
        $return
    ) {
        $this->assertEquals(
            SimPerformanceUtils::calculateSimPerfDataByYear(
                $game_data,
                $bin_size
            ),
            $return
        );
    }

    public function providerCalculateSimPerfScores() {
        return array(
            array(
                array(
                    0 => array(
                        SimPerformanceUtils::NUM_GAMES => 2,
                        SimPerformanceUtils::ACTUAL_PCT => 50,
                        BetsRequiredFields::VEGAS_HOME_PCT => 2.5,
                        BetsRequiredFields::SIM_HOME_PCT => 7.5
                    ),
                    5 => $this->emptyBin,
                    10 => $this->emptyBin,
                    15 => $this->emptyBin,
                    20 => $this->emptyBin,
                    25 => $this->emptyBin,
                    30 => $this->emptyBin,
                    35 => $this->emptyBin,
                    40 => $this->emptyBin,
                    45 => $this->emptyBin,
                    50 => array(
                        SimPerformanceUtils::NUM_GAMES => 2,
                        SimPerformanceUtils::ACTUAL_PCT => 0,
                        BetsRequiredFields::VEGAS_HOME_PCT => 52.5,
                        BetsRequiredFields::SIM_HOME_PCT => 55
                    ),
                    55 => $this->emptyBin,
                    60 => $this->emptyBin,
                    65 => $this->emptyBin,
                    70 => $this->emptyBin,
                    75 => $this->emptyBin,
                    80 => $this->emptyBin,
                    85 => $this->emptyBin,
                    90 => $this->emptyBin,
                    95 => $this->emptyBin
                ),
                null,
                null
            ),
            array(
                array(
                    0 => array(
                        SimPerformanceUtils::NUM_GAMES => 10,
                        SimPerformanceUtils::ACTUAL_PCT => 50,
                        BetsRequiredFields::VEGAS_HOME_PCT => 40,
                        BetsRequiredFields::SIM_HOME_PCT => 20
                    ),
                    5 => $this->emptyBin,
                    10 => $this->emptyBin,
                    15 => $this->emptyBin,
                    20 => $this->emptyBin,
                    25 => $this->emptyBin,
                    30 => $this->emptyBin,
                    35 => $this->emptyBin,
                    40 => $this->emptyBin,
                    45 => $this->emptyBin,
                    50 => array(
                        SimPerformanceUtils::NUM_GAMES => 10,
                        SimPerformanceUtils::ACTUAL_PCT => 0,
                        BetsRequiredFields::VEGAS_HOME_PCT => 30,
                        BetsRequiredFields::SIM_HOME_PCT => 56
                    ),
                    55 => $this->emptyBin,
                    60 => $this->emptyBin,
                    65 => $this->emptyBin,
                    70 => $this->emptyBin,
                    75 => $this->emptyBin,
                    80 => $this->emptyBin,
                    85 => $this->emptyBin,
                    90 => $this->emptyBin,
                    95 => $this->emptyBin
                ),
                20,
                43
            )
        );
    }

    /**
     * @dataProvider providerCalculateSimPerfScores
     */
    public function testCalculateSimPerfScores(
        $perf_data,
        $exp_vegas_score,
        $exp_sim_score
    ) {
        list($vegas_score, $sim_score) =
            SimPerformanceUtils::calculateSimPerfScores($perf_data);
        $this->assertEquals(
            $vegas_score,
            $exp_vegas_score
        );
        $this->assertEquals(
            $sim_score,
            $exp_sim_score
        );
    }

    public function providerCalculateBetCumulativeData() {
        return array(
            array(
                $this->cumulativeTestArray,
                array(
                    '2014-06-01' => array(
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES => 3,
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES_BET => 2,
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES_WINNER => 1,
                        SimPerformanceUtils::CUMULATIVE_BET_AMOUNT => 200.0,
                        SimPerformanceUtils::CUMULATIVE_PAYOUT => 0.0,
                        SimPerformanceUtils::PCT_GAMES_BET_ON =>
                            round(2/3*100, 2),
                        SimPerformanceUtils::PCT_GAMES_WINNER =>
                            round(1/2*100, 2),
                        SimPerformanceUtils::ROI => 0.0,
                    ),
                    '2014-06-02' => array(
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES => 5,
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES_BET => 4,
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES_WINNER => 3,
                        SimPerformanceUtils::CUMULATIVE_BET_AMOUNT => 400.0,
                        SimPerformanceUtils::CUMULATIVE_PAYOUT => 300.0,
                        SimPerformanceUtils::PCT_GAMES_BET_ON =>
                            round(4/5*100, 2),
                        SimPerformanceUtils::PCT_GAMES_WINNER =>
                            round(3/4*100, 2),
                        SimPerformanceUtils::ROI => round(300/400*100, 2),
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider providerCalculateBetCumulativeData
     */
    public function testCalculateBetCumulativeData(
        $bet_data,
        $result
    ) {
        $this->assertEquals(
            SimPerformanceUtils::calculateBetCumulativeData($bet_data),
            $result
        );
    }

    public function providerCalculateBetCumulativeDataByYear() {
        return array(
            array(
                array(
                    2014 => $this->cumulativeTestArray,
                ),
                array(
                    2014 => array(
                        '2014-06-01' => array(
                            SimPerformanceUtils::CUMULATIVE_NUM_GAMES => 3,
                            SimPerformanceUtils::CUMULATIVE_NUM_GAMES_BET => 2,
                            SimPerformanceUtils::CUMULATIVE_NUM_GAMES_WINNER =>
                                1,
                            SimPerformanceUtils::CUMULATIVE_BET_AMOUNT => 200.0,
                            SimPerformanceUtils::CUMULATIVE_PAYOUT => 0.0,
                            SimPerformanceUtils::PCT_GAMES_BET_ON =>
                                round(2/3*100, 2),
                            SimPerformanceUtils::PCT_GAMES_WINNER =>
                                round(1/2*100, 2),
                            SimPerformanceUtils::ROI => 0.0,
                        ),
                        '2014-06-02' => array(
                            SimPerformanceUtils::CUMULATIVE_NUM_GAMES => 5,
                            SimPerformanceUtils::CUMULATIVE_NUM_GAMES_BET =>
                                4,
                            SimPerformanceUtils::CUMULATIVE_NUM_GAMES_WINNER =>
                                3,
                            SimPerformanceUtils::CUMULATIVE_BET_AMOUNT => 400.0,
                            SimPerformanceUtils::CUMULATIVE_PAYOUT => 300.0,
                            SimPerformanceUtils::PCT_GAMES_BET_ON =>
                                round(4/5*100, 2),
                            SimPerformanceUtils::PCT_GAMES_WINNER =>
                                round(3/4*100, 2),
                            SimPerformanceUtils::ROI => round(300/400*100, 2),
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider providerCalculateBetCumulativeDataByYear
     */
    public function testCalculateBetCumulativeDataByYear(
        $bet_data,
        $result
    ) {
        $this->assertEquals(
            SimPerformanceUtils::calculateBetCumulativeDataByYear($bet_data),
            $result
        );
    }

    public function providerCalculateBetCumulativeDataBetTeam() {
        return array(
            array(
                $this->cumulativeTestArray,
                Bets::BET_TEAM,
                TeamTypes::HOME,
                array(
                    '2014-06-01' => array(
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES => 3,
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES_BET => 2,
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES_WINNER => 1,
                        SimPerformanceUtils::CUMULATIVE_BET_AMOUNT => 200.0,
                        SimPerformanceUtils::CUMULATIVE_PAYOUT => 0.0,
                        SimPerformanceUtils::PCT_GAMES_BET_ON =>
                            round(2/3*100, 2),
                        SimPerformanceUtils::PCT_GAMES_WINNER =>
                            round(1/2*100, 2),
                        SimPerformanceUtils::ROI => 0.0,
                    ),
                    '2014-06-02' => array(
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES => 5,
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES_BET => 3,
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES_WINNER => 2,
                        SimPerformanceUtils::CUMULATIVE_BET_AMOUNT => 300.0,
                        SimPerformanceUtils::CUMULATIVE_PAYOUT => 100.0,
                        SimPerformanceUtils::PCT_GAMES_BET_ON =>
                            round(3/5*100, 2),
                        SimPerformanceUtils::PCT_GAMES_WINNER =>
                            round(2/3*100, 2),
                        SimPerformanceUtils::ROI => round(100/300*100, 2),
                    ),
                ),
            ),
            array(
                $this->cumulativeTestArray,
                Bets::BET_TEAM,
                null,
                array(
                    '2014-06-01' => array(
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES => 3,
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES_BET => 0,
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES_WINNER => 0,
                        SimPerformanceUtils::CUMULATIVE_BET_AMOUNT => 0,
                        SimPerformanceUtils::CUMULATIVE_PAYOUT => 0,
                        SimPerformanceUtils::PCT_GAMES_BET_ON => 0,
                        SimPerformanceUtils::PCT_GAMES_WINNER => 0,
                        SimPerformanceUtils::ROI => 0,
                    ),
                    '2014-06-02' => array(
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES => 5,
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES_BET => 0,
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES_WINNER => 0,
                        SimPerformanceUtils::CUMULATIVE_BET_AMOUNT => 0,
                        SimPerformanceUtils::CUMULATIVE_PAYOUT => 0,
                        SimPerformanceUtils::PCT_GAMES_BET_ON => 0,
                        SimPerformanceUtils::PCT_GAMES_WINNER => 0,
                        SimPerformanceUtils::ROI => 0,
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider providerCalculateBetCumulativeDataBetTeam
     */
    public function testCalculateBetCumulativeDataBetTeam(
        $bet_data,
        $filter_key,
        $filter_value,
        $result
    ) {
        $this->assertEquals(
            SimPerformanceUtils::calculateBetCumulativeData(
                $bet_data,
                $filter_key,
                $filter_value
            ),
            $result
        );
    }

    public function providerCalculateBetCumulativeDataBetPctDiff() {
        return array(
            array(
                $this->cumulativeTestArray,
                Bets::BET_PCT_DIFF,
                0,
                5,
                array(
                    '2014-06-01' => array(
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES => 3,
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES_BET => 1,
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES_WINNER => 0,
                        SimPerformanceUtils::CUMULATIVE_BET_AMOUNT => 100.0,
                        SimPerformanceUtils::CUMULATIVE_PAYOUT => -100.0,
                        SimPerformanceUtils::PCT_GAMES_BET_ON =>
                            round(1/3*100, 2),
                        SimPerformanceUtils::PCT_GAMES_WINNER =>
                            round(0/1*100, 2),
                        SimPerformanceUtils::ROI => round(-100/100*100, 2),
                    ),
                    '2014-06-02' => array(
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES => 5,
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES_BET => 2,
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES_WINNER => 1,
                        SimPerformanceUtils::CUMULATIVE_BET_AMOUNT => 200.0,
                        SimPerformanceUtils::CUMULATIVE_PAYOUT => 100.0,
                        SimPerformanceUtils::PCT_GAMES_BET_ON =>
                            round(2/5*100, 2),
                        SimPerformanceUtils::PCT_GAMES_WINNER =>
                            round(1/2*100, 2),
                        SimPerformanceUtils::ROI => round(100/200*100, 2),
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider providerCalculateBetCumulativeDataBetPctDiff
     */
    public function testCalculateBetCumulativeDataBetPctDiff(
        $bet_data,
        $filter_key,
        $filter_by_min,
        $filter_by_max,
        $result
    ) {
        $this->assertEquals(
            SimPerformanceUtils::calculateBetCumulativeData(
                $bet_data,
                $filter_key,
                null,
                $filter_by_min,
                $filter_by_max
            ),
            $result
        );
    }

}

?>
