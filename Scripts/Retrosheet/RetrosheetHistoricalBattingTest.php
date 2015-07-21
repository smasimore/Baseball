<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'RetrosheetHistoricalBatting.php';
include_once __DIR__ .'/../../Models/Traits/TTestWithPrivateMethod.PHP';


class RetrosheetHistoricalBattingTest extends PHPUnit_Framework_TestCase {

    use TTestWithPrivateMethod;

    public function providerGenBattingStatsFromRetrosheet() {
        return array(
            array(
                '2013-06-01',
                'poseb001',
                4,
                array(
                    array(
                        'num_events' => 1,
                        'event_name' => 'double',
                        'season' => 2013,
                        'ds' => '2013-06-01',
                        'player_id' => 'poseb001',
                        'bat_hand_cd' => 'R',
                        'home_away' => 'Away',
                        'outs' => 0,
                        'situation' => 'NoneOn',
                        'winning' => 'Losing',
                        'pit_id' => 'mills001',
                        'vs_hand' => 'VsRight'
                    ),
                    array(
                        'num_events' => 1,
                        'event_name' => 'fly_out',
                        'season' => 2013,
                        'ds' => '2013-06-01',
                        'player_id' => 'poseb001',
                        'bat_hand_cd' => 'R',
                        'home_away' => 'Away',
                        'outs' => 1,
                        'situation' => 'NoneOn',
                        'winning' => 'Losing',
                        'pit_id' => 'mills001',
                        'vs_hand' => 'VsRight'
                    ),
                    array(
                        'num_events' => 1,
                        'event_name' => 'ground_out',
                        'season' => 2013,
                        'ds' => '2013-06-01',
                        'player_id' => 'poseb001',
                        'bat_hand_cd' => 'R',
                        'home_away' => 'Away',
                        'outs' => 2,
                        'situation' => 'NoneOn',
                        'winning' => 'Losing',
                        'pit_id' => 'martv002',
                        'vs_hand' => 'VsRight'
                    ),
                    array(
                        'num_events' => 1,
                        'event_name' => 'strikeout',
                        'season' => 2013,
                        'ds' => '2013-06-01',
                        'player_id' => 'poseb001',
                        'bat_hand_cd' => 'R',
                        'home_away' => 'Away',
                        'outs' => 2,
                        'situation' => 'RunnersOn',
                        'winning' => 'Tied',
                        'pit_id' => 'mills001',
                        'vs_hand' => 'VsRight'
                    )
                )
            )
        );
    }

    /**
     * @dataProvider providerGenBattingStatsFromRetrosheet
     */
    public function testGenBattingStatsFromRetrosheet(
        $ds,
        $player_id,
        $expected_num_records,
        $expected_return
    ) {
        $retrosheet_obj = new RetrosheetHistoricalBatting();
        $data = $this->invokeMethod(
            $retrosheet_obj,
            'genBattingStatsFromRetrosheet',
            array($ds)
        );
        $filter = function($x) use($player_id) {
                return $x['player_id'] === $player_id;
            };
        $data = array_values(array_filter($data, $filter));
        $this->assertEquals(count($data), $expected_num_records);
        $this->assertEquals($data, $expected_return);
    }
}

?>
