<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ . '/../StatsCategories.php';

class StatsCategoriesTest extends PHPUnit_Framework_TestCase {

    public function providerGetReadableWeights() {
        // Array of stat name => state val, readable weight.
        return array(
            array(
                array(StatsCategories::B_TOTAL => 1.0),
                'b_total_100'
            ),
            array(
                array(
                    StatsCategories::B_HOME_AWAY => .5,
                    StatsCategories::P_HOME_AWAY => .5
                ),
                'b_home_away_50__p_home_away_50'
            )
        );
    }

    /**
     * @dataProvider providerGetReadableWeights
     */
    public function testGetReadableWeights($weights, $readable_weights) {
        $test_readable_weights = StatsCategories::getReadableWeights($weights);
        $this->assertEquals($readable_weights, $test_readable_weights);
    }

    /**
     * @expectedException Exception
     */
    public function testGetReadableWeightsException() {
        $test_readable_weights = StatsCategories::getReadableWeights(
            array(StatsCategories::B_HOME_AWAY => .5)
        );
    }
}
