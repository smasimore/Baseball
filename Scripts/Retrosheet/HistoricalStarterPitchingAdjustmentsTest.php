<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'HistoricalStarterPitchingAdjustments.php';
include_once __DIR__ .'/../../Models/Traits/TTestWithPrivateMethod.php';

class HistoricalStarterPitchingAdjustmentsTest
    extends PHPUnit_Framework_TestCase {

    use TTestWithPrivateMethod;

    private $avgPitchingStats = array(
        'Total' => array(
            'hit' => .18,
            'walk' => .15,
            'strikeout' => .12,
            'out' => .55
        )
    );

    public function providerAvgPitcherBuckets() {
        return array(
            array(
                array(
                    'pct_single' => .1,
                    'pct_double' => .01,
                    'pct_triple' => .02,
                    'pct_home_run' => .05,
                    'pct_walk' => .15,
                    'pct_strikeout' => .12,
                    'pct_fly_out' => .07,
                    'pct_ground_out' => .48
                ),
                $this->avgPitchingStats['Total']
            )
        );
    }

    public function providerBucketPitcherStats() {
        return array(
            array(
                array(
                    'pct_single' => .2,
                    'pct_double' => .02,
                    'pct_triple' => .04,
                    'pct_home_run' => .04,
                    'pct_walk' => .14,
                    'pct_strikeout' => .13,
                    'pct_fly_out' => .05,
                    'pct_ground_out' => .38
                ),
                $this->avgPitchingStats,
                'Total',
                array(
                    'hit' => .12,
                    'walk' => -.01,
                    'strikeout' => .01,
                    'out' => -.12
                )
            )
        );
    }

    /**
     * @dataProvider providerAvgPitcherBuckets
     */
    public function testGetAvgPitcherBuckets($split, $expected) {
        $retrosheet_obj = new HistoricalStarterPitchingAdjustments();
        $data = $this->invokeMethod(
            $retrosheet_obj,
            'getAveragePitcherBucket',
            array($split)
        );
        $this->assertEquals($data, $expected);
    }

    /**
     * @dataProvider providerBucketPitcherStats
     */
    public function testBucketPitcherStats(
        $split,
        $average_stats,
        $split_name,
        $expected
    ) {
        $retrosheet_obj = new HistoricalStarterPitchingAdjustments();
        $data = $this->invokeMethod(
            $retrosheet_obj,
            'getPitcherAdjustments',
            array($split, $average_stats, $split_name)
        );
        $this->assertEquals($data, $expected);
    }
}

?>
