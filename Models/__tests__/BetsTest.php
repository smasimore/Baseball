<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ . '/../Bets.php';

class BetsTest extends PHPUnit_Framework_TestCase {

    private $gameData = array(
        '2000-06-01' => array(
            'test_gameid' => array(
                BetsRequiredFields::VEGAS_HOME_ODDS => 100,
                BetsRequiredFields::VEGAS_AWAY_ODDS => 100,
                BetsRequiredFields::VEGAS_HOME_PCT => 50,
                BetsRequiredFields::VEGAS_AWAY_PCT => 50,
                BetsRequiredFields::SIM_HOME_PCT => 50,
                BetsRequiredFields::SIM_AWAY_PCT => 50,
            ),
        ),
    );

    public function providerGameDataValidationException() {
        return array(
            array(
                array('test' => array()),
            ),
            array(
                array(
                    '2000-06-01' => array(
                        'test_gameid' => array(),
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider providerGameDataValidationException
     * @expectedException Exception
     */
    public function testGameDataValidationException($game_data) {
        new Bets($game_data);
    }

    public function providerPctInputValidationException() {
        return array(
            array(.5),
            array(-1),
            array(101),
        );
    }

    /**
     * @dataProvider providerPctInputValidationException
     * @expectedException Exception
     */
    public function testPctInputValidationException($input) {
        (new Bets($this->gameData))->setSimVegasPctDiff($input);
    }

    public function providerBets() {
        return array(
            array(
                array(
                    '2000-06-01' => array(
                        'test_gameid' => array(
                            BetsRequiredFields::VEGAS_HOME_ODDS => 100,
                            BetsRequiredFields::VEGAS_AWAY_ODDS => 100,
                            BetsRequiredFields::VEGAS_HOME_PCT => 50,
                            BetsRequiredFields::VEGAS_AWAY_PCT => 50,
                            BetsRequiredFields::SIM_HOME_PCT => 50,
                            BetsRequiredFields::SIM_AWAY_PCT => 50,
                        ),
                    ),
                ),
                array(
                    '2000-06-01' => array(
                        'test_gameid' => array(
                            Bets::BET_TEAM => null,
                            Bets::BET_AMOUNT => null,
                            Bets::BET_ODDS => null,
                            Bets::BET_VEGAS_PCT => null,
                            Bets::BET_SIM_PCT => null,
                            Bets::BET_TEAM_WINNER => null,
                            Bets::BET_NET_PAYOUT => null,
                            Bets::BET_PCT_DIFF => null,
                        ),
                    ),
                ),
                true, // allow home bet
                true, // allow away bet
                5, // sim veg pct diff
                0, // default bet amount
            ),
            array(
                array(
                    '2000-06-01' => array(
                        'test_gameid' => array(
                            BetsRequiredFields::VEGAS_HOME_ODDS => 105,
                            BetsRequiredFields::VEGAS_AWAY_ODDS => 100,
                            BetsRequiredFields::VEGAS_HOME_PCT => 50,
                            BetsRequiredFields::VEGAS_AWAY_PCT => 50,
                            BetsRequiredFields::SIM_HOME_PCT => 50,
                            BetsRequiredFields::SIM_AWAY_PCT => 50,
                        ),
                    ),
                ),
                array(
                    '2000-06-01' => array(
                        'test_gameid' => array(
                            Bets::BET_TEAM => TeamTypes::HOME,
                            Bets::BET_AMOUNT => 100,
                            Bets::BET_ODDS => 105,
                            Bets::BET_VEGAS_PCT => 50,
                            Bets::BET_SIM_PCT => 50,
                            Bets::BET_TEAM_WINNER => null,
                            Bets::BET_NET_PAYOUT => null,
                            Bets::BET_PCT_DIFF => 0,
                        ),
                    ),
                ),
                true, // allow home bet
                true, // allow away bet
                0, // sim veg pct diff
                100, // default bet amount
            ),
            array(
                array(
                    '2000-06-01' => array(
                        'test_gameid' => array(
                            BetsRequiredFields::VEGAS_HOME_ODDS => -150,
                            BetsRequiredFields::VEGAS_AWAY_ODDS => 130,
                            BetsRequiredFields::VEGAS_HOME_PCT => 55,
                            BetsRequiredFields::VEGAS_AWAY_PCT => 45,
                            BetsRequiredFields::SIM_HOME_PCT => 50,
                            BetsRequiredFields::SIM_AWAY_PCT => 50,
                            BetsRequiredFields::HOME_TEAM_WINNER => '1',
                        ),
                    ),
                ),
                array(
                    '2000-06-01' => array(
                        'test_gameid' => array(
                            Bets::BET_TEAM => TeamTypes::AWAY,
                            Bets::BET_AMOUNT => 50,
                            Bets::BET_ODDS => 130,
                            Bets::BET_VEGAS_PCT => 45,
                            Bets::BET_SIM_PCT => 50,
                            Bets::BET_TEAM_WINNER => 0,
                            Bets::BET_NET_PAYOUT => -50,
                            Bets::BET_PCT_DIFF => 5,
                        ),
                    ),
                ),
                true, // allow home bet
                true, // allow away bet
                5, // sim veg pct diff
                50, // default bet amount
            ),
            array(
                array(
                    '2000-06-01' => array(
                        'test_gameid' => array(
                            BetsRequiredFields::VEGAS_HOME_ODDS => -150,
                            BetsRequiredFields::VEGAS_AWAY_ODDS => 130,
                            BetsRequiredFields::VEGAS_HOME_PCT => 55,
                            BetsRequiredFields::VEGAS_AWAY_PCT => 45,
                            BetsRequiredFields::SIM_HOME_PCT => 50,
                            BetsRequiredFields::SIM_AWAY_PCT => 50,
                            BetsRequiredFields::HOME_TEAM_WINNER => '0',
                        ),
                    ),
                ),
                array(
                    '2000-06-01' => array(
                        'test_gameid' => array(
                            Bets::BET_TEAM => TeamTypes::AWAY,
                            Bets::BET_AMOUNT => 50,
                            Bets::BET_ODDS => 130,
                            Bets::BET_VEGAS_PCT => 45,
                            Bets::BET_SIM_PCT => 50,
                            Bets::BET_TEAM_WINNER => true,
                            Bets::BET_NET_PAYOUT => 65.0,
                            Bets::BET_PCT_DIFF => 5,
                        ),
                    ),
                ),
                true, // allow home bet
                true, // allow away bet
                5, // sim veg pct diff
                50, // default bet amount
            ),
            // Test variations in bet amount.
            array(
                array(
                    '2000-06-01' => array(
                        'test_gameid' => array(
                            BetsRequiredFields::VEGAS_HOME_ODDS => -150,
                            BetsRequiredFields::VEGAS_AWAY_ODDS => 130,
                            BetsRequiredFields::VEGAS_HOME_PCT => 55,
                            BetsRequiredFields::VEGAS_AWAY_PCT => 45,
                            BetsRequiredFields::SIM_HOME_PCT => 50,
                            BetsRequiredFields::SIM_AWAY_PCT => 50,
                            BetsRequiredFields::HOME_TEAM_WINNER => '1',
                        ),
                        'test_gameid2' => array(
                            BetsRequiredFields::VEGAS_HOME_ODDS => -150,
                            BetsRequiredFields::VEGAS_AWAY_ODDS => 130,
                            BetsRequiredFields::VEGAS_HOME_PCT => 55,
                            BetsRequiredFields::VEGAS_AWAY_PCT => 45,
                            BetsRequiredFields::SIM_HOME_PCT => 50,
                            BetsRequiredFields::SIM_AWAY_PCT => 50,
                            BetsRequiredFields::HOME_TEAM_WINNER => '0',
                        ),
                    ),
                ),
                array(
                    '2000-06-01' => array(
                        'test_gameid' => array(
                            Bets::BET_TEAM => TeamTypes::AWAY,
                            Bets::BET_AMOUNT => 10000,
                            Bets::BET_ODDS => 130,
                            Bets::BET_VEGAS_PCT => 45,
                            Bets::BET_SIM_PCT => 50,
                            Bets::BET_TEAM_WINNER => 0,
                            Bets::BET_NET_PAYOUT => -10000,
                            Bets::BET_PCT_DIFF => 5,
                        ),
                        'test_gameid2' => array(
                            Bets::BET_TEAM => TeamTypes::AWAY,
                            Bets::BET_AMOUNT => 10000,
                            Bets::BET_ODDS => 130,
                            Bets::BET_VEGAS_PCT => 45,
                            Bets::BET_SIM_PCT => 50,
                            Bets::BET_TEAM_WINNER => 1,
                            Bets::BET_NET_PAYOUT => 13000,
                            Bets::BET_PCT_DIFF => 5,
                        ),
                    ),
                ),
                true, // allow home bet
                true, // allow away bet
                5, // sim veg pct diff
                10000, // base bet amount
            ),
        );
    }

    /**
     * @dataProvider providerBets
     */
    public function testBets(
        $game_data,
        $results,
        $allow_home_bet,
        $allow_away_bet,
        $sim_veg_pct_diff,
        $base_bet_amount
    ) {
        $this->assertEquals(
            (new Bets($game_data))
                ->setAllowHomeBet($allow_home_bet)
                ->setAllowAwayBet($allow_away_bet)
                ->setSimVegasPctDiff($sim_veg_pct_diff)
                ->setBaseBetAmount($base_bet_amount)
                ->getBetData(),
            $results
        );
    }
}
