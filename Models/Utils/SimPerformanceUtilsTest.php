<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'SimPerformanceUtils.php';

class SimPerformanceUtilsTest extends PHPUnit_Framework_TestCase {

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

    public function providerCalculateSimPerfData() {
        $empty_bin = array(
            SimPerformanceUtils::NUM_GAMES => 0,
            SimPerformanceUtils::ACTUAL_PCT => null,
            SimPerformanceUtils::VEGAS_PCT => null,
            SimPerformanceUtils::SIM_PCT => null
        );

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
                    5 => $empty_bin,
                    10 => $empty_bin,
                    15 => $empty_bin,
                    20 => $empty_bin,
                    25 => $empty_bin,
                    30 => $empty_bin,
                    35 => $empty_bin,
                    40 => $empty_bin,
                    45 => $empty_bin,
                    50 => array(
                        SimPerformanceUtils::NUM_GAMES => 2,
                        SimPerformanceUtils::ACTUAL_PCT => 0,
                        SimPerformanceUtils::VEGAS_PCT => 52.5,
                        SimPerformanceUtils::SIM_PCT => 55
                    ),
                    55 => $empty_bin,
                    60 => $empty_bin,
                    65 => $empty_bin,
                    70 => $empty_bin,
                    75 => $empty_bin,
                    80 => $empty_bin,
                    85 => $empty_bin,
                    90 => $empty_bin,
                    95 => $empty_bin
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

}

?>
