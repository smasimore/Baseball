<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/RetrosheetInclude.php');

// Using a more aggresive MIN_PLATE_APPEARANCE here to
// weed out data from 1950's.
const MIN_PLATE_APPEARANCE = 180;
const EVENTS_TABLE = 'events';
const JOE_AVERAGE_TABLE = 'historical_joe_average';

$test = false;

$season_vars = array(
    'start_script' => 1950,
    'end_script' => 2014
);
$colheads = array(
    'player_id',
    'season',
    'batter_stats',
    'starter_stats',
    'reliever_stats'
);
$run_type = array(
    'B' => RetrosheetConstants::BATTER,
    'S' => RetrosheetConstants::PITCHER,
    'R' => RetrosheetConstants::PITCHER
);
$splits = RetrosheetSplits::getSplits();

for ($season = $season_vars['start_script'];
    $season < $season_vars['end_script'];
    $season++
) {

    echo "$season \n";
    $season_where = RetrosheetParseUtils::getSeasonWhere(
        RetrosheetStatsYear::SEASON,
        $season
    );
    $player_where = 'TRUE';
    $split_data = array();
    foreach ($run_type as $player_type => $type) {
        $bat_home_id = $type == RetrosheetConstants::BATTER
            ? RetrosheetHomeAway::HOME : RetrosheetHomeAway::AWAY;
        $opp_hand = $type == RetrosheetConstants::BATTER
            ? RetrosheetEventColumns::PIT_HAND_CD
            : RetrosheetEventColumns::BAT_HAND_CD;
        foreach ($splits as $split) {
            $where = RetrosheetParseUtils::getWhereBySplit(
                $split,
                $bat_home_id,
                $opp_hand,
                $player_type
            );
            $sql = RetrosheetParseUtils::getEventsByBatterQuery(
                $player_where,
                $where,
                $season_where
            );
            $average_data = exe_sql(DATABASE, $sql);
            $type_index = $type;
            if ($type == RetrosheetConstants::PITCHER) {
                $type_index = $player_type === 'S' ? 'starter' : 'reliever';
            }
            foreach ($average_data as $data) {
                if ($data['plate_appearances'] < MIN_PLATE_APPEARANCE) {
                    continue;
                }
                $event_name = $data['event_name'];
                $pct_name = "pct_$event_name";
                $stat_pct = $data['pct'];
                $split_data[$type_index][$split][$pct_name] = $stat_pct;
            }
        }
        // Loop a second time for any missed splits (i.e. VsLeft/Right in early years)
        foreach ($splits as $split) {
            if (!isset($split_data[$type_index][$split])) {
                $split_data[$type_index][$split] =
                    $split_data[$type_index][RetrosheetSplits::TOTAL];
            }
        }
    }
    $insert_data = array();
    // 1951 Joe Average is from 1950's data so insert_season = $season + 1
    $insert_season = $season + 1;
    $insert_data['player_id'] = RetrosheetJoeAverage::JOE_AVERAGE;
    $insert_data['season'] = $insert_season;
    $insert_data['batter_stats'] = json_encode($split_data['batter']);
    $insert_data['starter_stats'] = json_encode($split_data['starter']);
    $insert_data['reliever_stats'] = json_encode($split_data['reliever']);
    $insert_data = array($insert_data);

    if ($test) {
        print_r($insert_data);
        exit();
    }
    if (isset($insert_data)) {
        multi_insert(
            DATABASE,
            JOE_AVERAGE_TABLE,
            $insert_data,
            $colheads
        );
    }
}

?>
