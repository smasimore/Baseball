<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');

const EVENTS_TABLE = 'events';

function pullSeasonGames($season) {
    // NOTE: Using JOIN instead of LOJ will exclude games without lineups
    // and is intended.
    $sql =
        "SELECT a.game_id,
            b.away_start_pit_id,
            b.home_start_pit_id,
            a.season
        FROM
            (SELECT DISTINCT a.game_id,
                a.season
            FROM events a
            WHERE season = $season) a
        JOIN games b
        ON a.game_id = b.game_id";
    return exe_sql(DATABASE, $sql);
}

$startScript = '1950';
$endScript  = '2014';
$playerMap = array();

for ($season = $startScript;
    $season < $endScript;
    $season++) {

    echo "$season \n";
    $season_games = pullSeasonGames($season);
    foreach ($season_games as $game) {
        $game_id = $game['game_id'];
        echo "$game_id \n";
        $away_start_pit = $game['away_start_pit_id'];
        $home_start_pit = $game['home_start_pit_id'];
        $season = $game['season'];
        $pitcher_case_when = "CASE
            WHEN PIT_ID = '$away_start_pit' THEN 'S'
            WHEN PIT_ID = '$home_start_pit' THEN 'S'
            ELSE 'R' END";
        $insert_pitcher_type = array('pitcher_type' => $pitcher_case_when);
        update(
            DATABASE,
            EVENTS_TABLE,
            $insert_pitcher_type,
            'game_id',
            $game_id,
            'non-string',
            'season',
            $season
        );
    }
}

?>
