<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'OddsUtils.php';

class OddsUtilsTest extends PHPUnit_Framework_TestCase {

    public function providerPctToOddsArray() {
        // Pct, Odds
        return array(
            array(.25, 300),
            array(.5, 100),
            array(.75, -300)
        );
    }

    public function providerOddsToPayoutArray() {
        // Bet, Odds, Payout
        return array(
            array(100, 300, 300),
            array(100, 100, 100),
            array(100, -300, 33.33)
        );
    }

    /**
     * @dataProvider providerPctToOddsArray
     */
    public function testConvertPctToOdds($pct, $odds) {
        $test_odds = OddsUtils::convertPctToOdds($pct);
        $this->assertEquals($odds, $test_odds);
    }

    /**
     * @expectedException Exception
     */
    public function testConvertPctToOddsException() {
        $pct = 50;
        $odds = OddsUtils::convertPctToOdds($pct);
    }

    /**
     * @dataProvider providerPctToOddsArray
     */
    public function testConvertOddsToPct($pct, $odds) {
        $test_pct = OddsUtils::convertOddsToPct($odds);
        $this->assertEquals($pct, $test_pct);
    }

    /**
     * @dataProvider providerOddsToPayoutArray
     */
    public function testCalculatePayout($bet, $odds, $payout) {
        $test_payout = OddsUtils::calculatePayout($bet, $odds);
        $this->assertEquals($payout, $test_payout);
    }
}

?>
