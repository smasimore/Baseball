<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');
include(HOME_PATH.'Scripts/Include/RetrosheetParseUtils.php');

const MIN_PLATE_APPEARANCE = 18;
const NUM_DECIMALS = 3;
const JOE_AVERAGE = 'joe_average';
const SEASON = 'season';
const EVENTS_TABLE = 'events';
const JOE_AVERAGE_TABLE = 'historical_joe_average';

$test = false;

$season_vars = array(
    'start_script' => 1950,
    'end_script' => 2014
);
$colheads = array(
    'player_id',
    'stats',
    'season'
);
$splits = RetrosheetSplits::getSplits();

for ($season = $season_vars['start_script'];
    $season < $season_vars['end_script'];
    $season++
) {

    echo "$season \n";
    $season_where = RetrosheetParseUtils::getSeasonWhere(
        SEASON,
        $season
    );
    $player_where = 'TRUE';
    $split_data = array();
    foreach ($splits as $split) {
        $where = RetrosheetParseUtils::getWhereBySplit($split);
        $sql = RetrosheetParseUtils::getEventsByBatterQuery(
            $player_where,
            $where,
            $season_where
        );
        $average_data = exe_sql(DATABASE, $sql);
        foreach ($average_data as $data) {
            $event_name = $data['event_name'];
            $pct_name = "pct_$event_name";
            $stat_pct = $data['pct'];
            $split_data[$split][$pct_name] = $stat_pct;
        }
    }
    $insert_data = array();
    // 1951 Joe Average is from 1950's data so insert_season = $season + 1
    $insert_season = $season + 1;
    $insert_data['player_id'] = JOE_AVERAGE;
    $insert_data['season'] = $insert_season;
    $insert_data['stats'] = json_encode($split_data);
    $insert_data = array($insert_data);

    if (!$test && isset($insert_data)) {
        multi_insert(
            DATABASE,
            JOE_AVERAGE_TABLE,
            $insert_data,
            $colheads
        );
    }
    if ($test && isset($insert_data)) {
        print_r($insert_data);
        exit();
    }
}

?>
