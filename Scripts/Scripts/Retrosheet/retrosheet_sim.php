<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');

function updateSeasonVars($season, $season_vars) {
    $season_sql =
        "SELECT min(game_date) as start,
            max(game_date) as end
        FROM retrosheet_historical_lineups
        WHERE season = $season
        GROUP BY season";
    $season_dates = reset(exe_sql(DATABASE, $season_sql));
    $season_vars['season_start'] = $season_dates['start'];
    $season_vars['season_end'] = $season_dates['end'];
    return $season_vars;
}

function pullDailyLineup($season, $ds) {
    $sql = "SELECT *
        FROM retrosheet_historical_lineups
        WHERE season = $season
        AND game_date = '$ds'";
    return exe_sql(DATABASE, $sql);
}

function pullSeasonData($season, $ds, $table) {
    $sql = "SELECT *
        FROM $table
        WHERE season = $season
        AND ds = '$ds'";
    $data = exe_sql(DATABASE, $sql);
    $data = index_by($data, 'player_id');
    return $data;
}

function fillPitchers($pitcher, $stats) {
    $pitcher = json_decode($pitcher, true);
    $player_id = $pitcher['id'];
    $pitcher['player_id'] = $player_id;
    unset($pitcher['id']);
    $pitcher['plate_appearances'] =
        $stats[$player_id]
        ? $stats[$player_id]['plate_appearances']
        : $stats['joe_average']['plate_appearances'];
    $pitcher['defaults'] =
        $stats[$player_id]
        ? $stats[$player_id]['defaults']
        : $stats['joe_average']['defaults'];
    $pitcher['stats'] =
        $stats[$player_id]
        ? $stats[$player_id]['stats']
        : $stats['joe_average']['stats'];
    return $pitcher;
}

function fillLineups($lineup, $stats) {
    $lineup = json_decode($lineup, true);
    $filled_lineups = array();
    foreach ($lineup as $lpos => $player) {
        $pos = trim($lpos, "L");
        $player_id = $player['player_id'];
        $player['plate_appearances'] =
            isset($stats[$player_id])
            ? $stats[$player_id]['plate_appearances']
            : $stats['joe_average']['plate_appearances'];
        $player['defaults'] =
            isset($stats[$player_id])
            ? $stats[$player_id]['defaults']
            : $stats['joe_average']['defaults'];
        $player['stats'] =
            isset($stats[$player_id])
            ? $stats[$player_id]['stats'] : $stats['joe_average']['stats'];
        $filled_lineups[$pos] = $player;
    }
    return $filled_lineups;
}

$test = false;

$data_type =
    //'season';
    'career';

$season_vars = array(
    'start_script' => 1950,
    'end_script' => 2014,
    'season_start' => null,
    'season_end' => null,
);
$tables = array(
    'batter' => "historical_$data_type"."_batting",
    'pitcher' => "historical_$data_type"."_pitching"
);

$insert_table = "sim_basic_career";

$colheads = array(
    'game_id' => '!',
    'home' => '!',
    'away' => '!',
    'home_score' => '!',
    'away_score' => '!',
    'home_team_winner' => '!',
    'pitcher_h' => '!',
    'pitcher_a' => '!',
    'lineup_h' => '!',
    'lineup_a' => '!',
    'reliever_h' => '?',
    'reliever_a' => '?',
    'game_time' => '?',
    'season' => '!',
    'game_date' => '!'
);

for ($season = $season_vars['start_script'];
    $season < $season_vars['end_script'];
    $season++) {

    $season_vars = updateSeasonVars($season, $season_vars);
    for ($ds = $season_vars['season_start'];
        $ds <= $season_vars['season_end'];
        $ds = ds_modify($ds, '+1 day')) {

        $lineups = array();
        $daily_lineup = pullDailyLineup($season, $ds);
        $batter_stats = pullSeasonData($season, $ds, $tables['batter']);
        $pitcher_stats = pullSeasonData($season, $ds, $tables['pitcher']);
        if (!$batter_stats) {
            echo "No Data On $ds \n";
            continue;
        }
        if (!$daily_lineup) {
            echo "No Games On $ds \n";
            continue;
        }
        echo "$ds \n";
        foreach ($daily_lineup as $i => $lineup) {
            $lineups[$i]['game_id'] = $lineup['game_id'];
            $lineups[$i]['home'] = $lineup['home'];
            $lineups[$i]['away'] = $lineup['away'];
            $lineups[$i]['home_score'] = $lineup['home_score'];
            $lineups[$i]['away_score'] = $lineup['away_score'];
            $lineups[$i]['home_team_winner'] = $lineup['home_team_winner'];
            $lineups[$i]['game_time'] = $lineup['game_time'];
            $lineups[$i]['season'] = $lineup['season'];
            $lineups[$i]['game_date'] = $lineup['game_date'];
            $lineups[$i]['pitcher_h'] =
                json_encode(fillPitchers($lineup['pitcher_h'], $pitcher_stats));
            $lineups[$i]['pitcher_a'] =
                json_encode(fillPitchers($lineup['pitcher_a'], $pitcher_stats));
            $lineups[$i]['lineup_h'] =
                json_encode(fillLineups($lineup['lineup_h'], $batter_stats));
            $lineups[$i]['lineup_a'] =
                json_encode(fillLineups($lineup['lineup_a'], $batter_stats));
        }
        if (!$test && isset($lineups)) {
            multi_insert(
                DATABASE,
                $insert_table,
                $lineups,
                $colheads
            );
        }
    }
}

?>
