<?php
//Copyright 2014, Saber Tooth Ventures, LLC

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

$test = true;

$season_vars = array(
    'start_script' => 1950,
    'end_script' => 2014
);
$colheads = array(
    'player_id',
    'season',
    'batter_stats',
    'pitcher_stats'
);
$run_type = array(
    RetrosheetConstants::BATTER,
    RetrosheetConstants::PITCHER
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
    foreach ($run_type as $type) {
        $bat_home_id = $type == RetrosheetConstants::BATTER
        ? RetrosheetHomeAway::HOME : RetrosheetHomeAway::AWAY;
        $opp_hand = $type == RetrosheetConstants::BATTER
        ? RetrosheetEventColumns::PIT_HAND_CD
        : RetrosheetEventColumns::BAT_HAND_CD;
        foreach ($splits as $split) {
            $where = RetrosheetParseUtils::getWhereBySplit(
                $split,
                $bat_home_id,
                $opp_hand
            );
            $sql = RetrosheetParseUtils::getEventsByBatterQuery(
                $player_where,
                $where,
                $season_where
            );
            $average_data = exe_sql(DATABASE, $sql);
            foreach ($average_data as $data) {
                if ($data['plate_appearances'] < MIN_PLATE_APPEARANCE) {
                    continue;
                }
                $event_name = $data['event_name'];
                $pct_name = "pct_$event_name";
                $stat_pct = $data['pct'];
                $split_data[$type][$split][$pct_name] = $stat_pct;
            }
        }
        // Loop a second time for any missed splits (i.e. VsLeft/Right in early years)
        foreach ($splits as $split) {
            if (!isset($split_data[$type][$split])) {
                $split_data[$type][$split] = $split_data[$type][RetrosheetSplits::TOTAL];
            }
        }
    }
    $insert_data = array();
    // 1951 Joe Average is from 1950's data so insert_season = $season + 1
    $insert_season = $season + 1;
    $insert_data['player_id'] = RetrosheetJoeAverage::JOE_AVERAGE;
    $insert_data['season'] = $insert_season;
    $insert_data['batter_stats'] = json_encode($split_data['batter']);
    $insert_data['pitcher_stats'] = json_encode($split_data['pitcher']);
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
