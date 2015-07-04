<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'ROIUtils.php';

class ROIUtilsTest extends PHPUnit_Framework_TestCase {

    public function providerBetsData() {
        return array(
            array(
                array(
                    array(
                        'bet' => 100,
                        'payout' => -100
                    )
                ),
                '-1',
                array(0, 1)
            ),
            array(
                array(
                    array(
                        'bet' => 100,
                        'payout' => 250
                    ),
                    array(
                        'bet' => 100,
                        'payout' => -100
                    )
                ),
                .75,
                array(1, 1)
            ),
            array(
                array(
                    array('bet' => 100)
                ),
                null,
                array(0, 0)
            )
        );
    }

    /**
     * @dataProvider providerBetsData
     */
    public function testCalculateROI($bet_data, $return_roi, $return_record) {
        $roi = ROIUtils::calculateROI($bet_data);
        $this->assertEquals($roi, $return_roi);
    }

    /**
     * @dataProvider providerBetsData
     */
    public function testCalculateRecord(
        $bet_data,
        $return_roi,
        $return_record
    ) {
        $record = ROIUtils::calculateRecord($bet_data);
        $this->assertEquals($record, $return_record);
    }
}

?>
