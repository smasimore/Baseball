<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'SimPerformanceUtils.php';

class SimPerformanceUtilsTest extends PHPUnit_Framework_TestCase {

    private $emptyBin = array(
        SimPerformanceUtils::NUM_GAMES => 0,
        SimPerformanceUtils::ACTUAL_PCT => null,
        SimPerformanceUtils::VEGAS_PCT => null,
        SimPerformanceUtils::SIM_PCT => null
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
                        SimPerformanceUtils::VEGAS_PCT => 2.5,
                        SimPerformanceUtils::SIM_PCT => 5,
                        SimPerformanceUtils::TEAM_WINNER => 0
                    ),
                    array(
                        SimPerformanceUtils::VEGAS_PCT => 2.5,
                        SimPerformanceUtils::SIM_PCT => 10,
                        SimPerformanceUtils::TEAM_WINNER => 1
                    ),
                    array(
                        SimPerformanceUtils::VEGAS_PCT => 52,
                        SimPerformanceUtils::SIM_PCT => 50,
                        SimPerformanceUtils::TEAM_WINNER => 0
                    ),
                    array(
                        SimPerformanceUtils::VEGAS_PCT => 53,
                        SimPerformanceUtils::SIM_PCT => 60,
                        SimPerformanceUtils::TEAM_WINNER => 0
                    ),
                ),
                5,
                array(
                    0 => array(
                        SimPerformanceUtils::NUM_GAMES => 2,
                        SimPerformanceUtils::ACTUAL_PCT => 50,
                        SimPerformanceUtils::VEGAS_PCT => 2.5,
                        SimPerformanceUtils::SIM_PCT => 7.5
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
                        SimPerformanceUtils::VEGAS_PCT => 52.5,
                        SimPerformanceUtils::SIM_PCT => 55
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
                        array(
                            SimPerformanceUtils::VEGAS_PCT => 2.5,
                            SimPerformanceUtils::SIM_PCT => 5,
                            SimPerformanceUtils::TEAM_WINNER => 0
                        ),
                        array(
                            SimPerformanceUtils::VEGAS_PCT => 2.5,
                            SimPerformanceUtils::SIM_PCT => 10,
                            SimPerformanceUtils::TEAM_WINNER => 1
                        ),
                    ),
                    2001 => array(
                        array(
                            SimPerformanceUtils::VEGAS_PCT => 52,
                            SimPerformanceUtils::SIM_PCT => 50,
                            SimPerformanceUtils::TEAM_WINNER => 0
                        ),
                        array(
                            SimPerformanceUtils::VEGAS_PCT => 53,
                            SimPerformanceUtils::SIM_PCT => 60,
                            SimPerformanceUtils::TEAM_WINNER => 0
                        ),
                    ),
                ),
                5,
                array(
                    2000 => array(
                        0 => array(
                            SimPerformanceUtils::NUM_GAMES => 2,
                            SimPerformanceUtils::ACTUAL_PCT => 50,
                            SimPerformanceUtils::VEGAS_PCT => 2.5,
                            SimPerformanceUtils::SIM_PCT => 7.5
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
                            SimPerformanceUtils::VEGAS_PCT => 52.5,
                            SimPerformanceUtils::SIM_PCT => 55
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
            )
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

}

?>
