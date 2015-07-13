<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include_once('/Users/constants.php');
include_once(HOME_PATH.'Scripts/Include/sweetfunctions.php');

const INSERT_TABLE = 'retrosheet_historical_lineups';

$startScript = '1950';
$endScript  = '2014';
$playerMap = array();

function updatePlayerMap($season) {
    global $playerMap;
    $sql = "SELECT player_id, bat_hand_cd
        FROM rosters
        WHERE year_id = $season";
    $data = exe_sql(DATABASE, $sql);
    $playerMap = index_by($data, 'player_id');
}

function pullSeasonGames($season) {
    $sql = "SELECT game_id, lineup_h, lineup_a
        FROM retrosheet_historical_lineups
        WHERE season = $season";
    $data = exe_sql(DATABASE, $sql);
    $data = index_by($data, 'game_id');
    return $data;
}

function addPlayerHand($lineup) {
    global $playerMap;
    foreach ($lineup as $index => $player) {
        $player_id = $player['player_id'];
        $player['hand'] =
            isset($playerMap[$player_id]['bat_hand_cd'])
            ? $playerMap[$player_id]['bat_hand_cd'] : null;
        $lineup[$index] = $player;
    }
    return $lineup;
}

for ($season = $startScript;
    $season < $endScript;
    $season++) {

    echo "$season \n";
    updatePlayerMap($season);
    $season_games = pullSeasonGames($season);
    foreach ($season_games as $game) {
        $game_id = $game['game_id'];
        $lineup_h = json_decode($game['lineup_h'], true);
        $lineup_a = json_decode($game['lineup_a'], true);
        $lineup_h = addPlayerHand($lineup_h);
        $lineup_a = addPlayerHand($lineup_a);
        $insert_array = array(
            'lineup_h' => json_encode($lineup_h),
            'lineup_a' => json_encode($lineup_a)
        );
        update(
            DATABASE,
            INSERT_TABLE,
            $insert_array,
            'game_id',
            $game_id
        );
    }
}

?>
