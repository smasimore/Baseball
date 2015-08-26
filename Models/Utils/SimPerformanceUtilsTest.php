<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ . '/SimPerformanceUtils.php';
include_once __DIR__ . '/../Bets.php';

class SimPerformanceUtilsTest extends PHPUnit_Framework_TestCase {

    private $emptyBin = array(
        SimPerformanceUtils::NUM_GAMES => 0,
        SimPerformanceUtils::ACTUAL_PCT => null,
        SimPerfKeys::VEGAS_HOME_PCT => null,
        SimPerfKeys::SIM_HOME_PCT => null
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
                    '2000-06-01' => array(
                        array(
                            SimPerfKeys::VEGAS_HOME_PCT => 2.5,
                            SimPerfKeys::SIM_HOME_PCT => 5,
                            SimPerfKeys::HOME_TEAM_WINNER => 0
                        ),
                        array(
                            SimPerfKeys::VEGAS_HOME_PCT => 2.5,
                            SimPerfKeys::SIM_HOME_PCT => 10,
                            SimPerfKeys::HOME_TEAM_WINNER => 1
                        ),
                        array(
                            SimPerfKeys::VEGAS_HOME_PCT => 52,
                            SimPerfKeys::SIM_HOME_PCT => 50,
                            SimPerfKeys::HOME_TEAM_WINNER => 0
                        ),
                        array(
                            SimPerfKeys::VEGAS_HOME_PCT => 53,
                            SimPerfKeys::SIM_HOME_PCT => 60,
                            SimPerfKeys::HOME_TEAM_WINNER => 0
                        ),
                    ),
                ),
                5,
                array(
                    0 => array(
                        SimPerformanceUtils::NUM_GAMES => 2,
                        SimPerformanceUtils::ACTUAL_PCT => 50,
                        SimPerfKeys::VEGAS_HOME_PCT => 2.5,
                        SimPerfKeys::SIM_HOME_PCT => 7.5
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
                        SimPerfKeys::VEGAS_HOME_PCT => 52.5,
                        SimPerfKeys::SIM_HOME_PCT => 55
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
                                SimPerfKeys::VEGAS_HOME_PCT => 2.5,
                                SimPerfKeys::SIM_HOME_PCT => 5,
                                SimPerfKeys::HOME_TEAM_WINNER => 0
                            ),
                            array(
                                SimPerfKeys::VEGAS_HOME_PCT => 2.5,
                                SimPerfKeys::SIM_HOME_PCT => 10,
                                SimPerfKeys::HOME_TEAM_WINNER => 1
                            ),
                        ),
                    ),
                    2001 => array(
                        '2001-06-01' => array(
                            array(
                                SimPerfKeys::VEGAS_HOME_PCT => 52,
                                SimPerfKeys::SIM_HOME_PCT => 50,
                                SimPerfKeys::HOME_TEAM_WINNER => 0
                            ),
                            array(
                                SimPerfKeys::VEGAS_HOME_PCT => 53,
                                SimPerfKeys::SIM_HOME_PCT => 60,
                                SimPerfKeys::HOME_TEAM_WINNER => 0
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
                            SimPerfKeys::VEGAS_HOME_PCT => 2.5,
                            SimPerfKeys::SIM_HOME_PCT => 7.5
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
                            SimPerfKeys::VEGAS_HOME_PCT => 52.5,
                            SimPerfKeys::SIM_HOME_PCT => 55
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
                        SimPerfKeys::VEGAS_HOME_PCT => 2.5,
                        SimPerfKeys::SIM_HOME_PCT => 7.5
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
                        SimPerfKeys::VEGAS_HOME_PCT => 52.5,
                        SimPerfKeys::SIM_HOME_PCT => 55
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
                        SimPerfKeys::VEGAS_HOME_PCT => 40,
                        SimPerfKeys::SIM_HOME_PCT => 20
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
                        SimPerfKeys::VEGAS_HOME_PCT => 30,
                        SimPerfKeys::SIM_HOME_PCT => 56
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
                array(
                    '2014-06-01' => array(
                        array(
                            Bets::BET_TEAM => TeamTypes::HOME,
                            Bets::BET_TEAM_WINNER => true,
                            Bets::BET_AMOUNT => 100,
                            Bets::BET_NET_PAYOUT => 100,
                        ),
                        array(
                            Bets::BET_TEAM => TeamTypes::HOME,
                            Bets::BET_TEAM_WINNER => false,
                            Bets::BET_AMOUNT => 100,
                            Bets::BET_NET_PAYOUT => -200,
                        ),
                    ),
                    '2014-06-02' => array(
                        array(
                            Bets::BET_TEAM => TeamTypes::HOME,
                            Bets::BET_TEAM_WINNER => true,
                            Bets::BET_AMOUNT => 100,
                            Bets::BET_NET_PAYOUT => 100,
                        ),
                        array(
                            Bets::BET_TEAM => TeamTypes::AWAY,
                            Bets::BET_TEAM_WINNER => true,
                            Bets::BET_AMOUNT => 100,
                            Bets::BET_NET_PAYOUT => 200,
                        ),
                    ),
                ),
                array(
                    '2014-06-01' => array(
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES => 2,
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES_BET => 2,
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES_WINNER => 1,
                        SimPerformanceUtils::CUMULATIVE_BET_AMOUNT => 200,
                        SimPerformanceUtils::CUMULATIVE_PAYOUT => -100,
                    ),
                    '2014-06-02' => array(
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES => 4,
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES_BET => 4,
                        SimPerformanceUtils::CUMULATIVE_NUM_GAMES_WINNER => 3,
                        SimPerformanceUtils::CUMULATIVE_BET_AMOUNT => 400,
                        SimPerformanceUtils::CUMULATIVE_PAYOUT => 200,
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
                    2014 => array(
                        '2014-06-01' => array(
                            array(
                                Bets::BET_TEAM => TeamTypes::HOME,
                                Bets::BET_TEAM_WINNER => true,
                                Bets::BET_AMOUNT => 100,
                                Bets::BET_NET_PAYOUT => 100,
                            ),
                            array(
                                Bets::BET_TEAM => TeamTypes::HOME,
                                Bets::BET_TEAM_WINNER => false,
                                Bets::BET_AMOUNT => 100,
                                Bets::BET_NET_PAYOUT => -200,
                            ),
                        ),
                        '2014-06-02' => array(
                            array(
                                Bets::BET_TEAM => TeamTypes::HOME,
                                Bets::BET_TEAM_WINNER => true,
                                Bets::BET_AMOUNT => 100,
                                Bets::BET_NET_PAYOUT => 100,
                            ),
                            array(
                                Bets::BET_TEAM => TeamTypes::AWAY,
                                Bets::BET_TEAM_WINNER => true,
                                Bets::BET_AMOUNT => 100,
                                Bets::BET_NET_PAYOUT => 200,
                            ),
                        ),
                    ),
                ),
                array(
                    2014 => array(
                        '2014-06-01' => array(
                            SimPerformanceUtils::CUMULATIVE_NUM_GAMES => 2,
                            SimPerformanceUtils::CUMULATIVE_NUM_GAMES_BET => 2,
                            SimPerformanceUtils::CUMULATIVE_NUM_GAMES_WINNER =>
                                1,
                            SimPerformanceUtils::CUMULATIVE_BET_AMOUNT => 200,
                            SimPerformanceUtils::CUMULATIVE_PAYOUT => -100,
                        ),
                        '2014-06-02' => array(
                            SimPerformanceUtils::CUMULATIVE_NUM_GAMES => 4,
                            SimPerformanceUtils::CUMULATIVE_NUM_GAMES_BET =>
                                4,
                            SimPerformanceUtils::CUMULATIVE_NUM_GAMES_WINNER =>
                                3,
                            SimPerformanceUtils::CUMULATIVE_BET_AMOUNT => 400,
                            SimPerformanceUtils::CUMULATIVE_PAYOUT => 200,
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

}

?>
