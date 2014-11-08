<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');

const INF_ERA = 99;
const ERA_25 = 25;
const ERA_50 = 50;
const ERA_75 = 75;
const ERA_100 = 100;

$playerSeason = array();
$playerCareer = array();
$seasonPlayers = array();
$seasonInnings = array();
$careerInnings = array();

function convertRetroDateToDs($season, $date) {
    $month = substr($date, 0, 2);
    $day = substr($date, -2);
    $ds = "$season-$month-$day";
    return $ds;
}

// Set initial values for the cumulative arrays if not already set.
function initializePlayerArray(
    $player_id,
    $player_name = null,
    $player_hand = null
) {
    global $playerSeason, $playerCareer, $seasonPlayers;
    if (!in_array($player_id, $seasonPlayers)) {
        $seasonPlayers[] = $player_id;
    }
    $player_name = $player_name ? format_for_mysql($player_name) : null;
    $initial_stats = array(
        'player_id' => $player_id,
        'player_name' => $player_name,
        'hand' => $player_hand,
        'innings' => 0,
        'era' => 0,
        'outs' => 0,
        'earned_runs' => 0,
    );
    if (!isset($playerSeason[$player_id])) {
        $playerSeason[$player_id] = $initial_stats;
    }
    if (!isset($playerCareer[$player_id])) {
        $playerCareer[$player_id] = $initial_stats;
    }
    // Check to see if player_name and player_hand need to be filled in.
    if ($player_name && !isset($playerSeason[$player_id]['player_name'])) {
        $playerSeason[$player_id]['player_name'] = $player_name;
        $playerSeason[$player_id]['hand'] = $player_hand;
    }
    if ($player_name && !isset($playerCareer[$player_id]['player_name'])) {
        $playerCareer[$player_id]['player_name'] = $player_name;
        $playerCareer[$player_id]['hand'] = $player_hand;
    }
}

function updateCalculatedERA($player_id) {
    global $playerSeason, $playerCareer;
    $season_ip = $playerSeason[$player_id]['outs'] / 3;
    $career_ip = $playerCareer[$player_id]['outs'] / 3;
    $season_era =
        $season_ip ?
        $playerSeason[$player_id]['earned_runs'] / $season_ip * 9 : INF_ERA;
    $career_era =
        $career_ip ?
        $playerCareer[$player_id]['earned_runs'] / $career_ip * 9 : INF_ERA;
    $playerSeason[$player_id]['innings'] = number_format($season_ip, 2);
    $playerCareer[$player_id]['innings'] = number_format($career_ip, 2);
    $playerSeason[$player_id]['era'] = number_format($season_era, 2);
    $playerCareer[$player_id]['era'] = number_format($career_era, 2);
}

function updateAverageInnings($stat) {
    global $seasonInnings, $careerInnings;
    $player_id = $stat['bat_resp_pit_id'];
    $game_id = $stat['game_id'];
    $inning = $stat['inning'];
    $outs = $stat['event_outs_ct'];
    if (!$seasonInnings[$player_id][$game_id]) {
        $starter = $inning == 1 && !$stat['outs_ct'] ? 1 : 0;
        $initialized_data = array(
            'starter' => $starter,
            'outs_as_starter' => 0,
            'outs_as_reliever' => 0,
            'reliever_entry_inning' => null
        );
        $seasonInnings[$player_id][$game_id] = $initialized_data;
        $careerInnings[$player_id][$game_id] = $initialized_data;
        if (!$starter) {
            $seasonInnings[$player_id][$game_id]['reliever_entry_inning'] =
                $inning;
            $careerInnings[$player_id][$game_id]['reliever_entry_inning'] =
                $inning;
        }
    } else {
        $starter = $seasonInnings[$player_id][$game_id]['starter'];
    }
    if ($starter) {
        $seasonInnings[$player_id][$game_id]['outs_as_starter'] += $outs;
        $careerInnings[$player_id][$game_id]['outs_as_starter'] += $outs;
    } else {
        $seasonInnings[$player_id][$game_id]['outs_as_reliever'] += $outs;
        $careerInnings[$player_id][$game_id]['outs_as_reliever'] += $outs;
    }
}

function updateEventOuts($game_stat) {
    global $playerSeason, $playerCareer;
    $player_id = $game_stat['bat_resp_pit_id'];
    $player_name = $game_stat['player_name'];
    $player_hand = $game_stat['hand'];
    initializePlayerArray($player_id, $player_name, $player_hand);
    $outs = $game_stat['event_outs_ct'];
    $playerSeason[$player_id]['outs'] += $outs;
    $playerCareer[$player_id]['outs'] += $outs;
    updateAverageInnings($game_stat);
    updateCalculatedERA($player_id);
}

function updateEventRuns($runs_pitcher, $run_type) {
    global $playerSeason, $playerCareer;
    initializePlayerArray($runs_pitcher);
    switch ($run_type) {
        case "ER":
            $playerSeason[$runs_pitcher]['earned_runs'] += 1;
            $playerCareer[$runs_pitcher]['earned_runs'] += 1;
            break;
    }
    updateCalculatedERA($runs_pitcher);
}

function updateDailyStats($game_stat) {
    // Update pitcher outs seperately from runs since the runs
    // could be earned by a different pitcher.
    if ($game_stat['event_outs_ct'] > 0) {
        updateEventOuts($game_stat);
    }
    $batter_dest = $game_stat['bat_dest_id'];
    $runner1_dest = $game_stat['run1_dest_id'];
    $runner2_dest = $game_stat['run2_dest_id'];
    $runner3_dest = $game_stat['run3_dest_id'];
    // Use seperate if statements since multiple runners
    // can score per play.
    if ($batter_dest !== 'NR') {
        $runs_pitcher = $game_stat['bat_resp_pit_id'];
        updateEventRuns($runs_pitcher, $batter_dest);
    }
    if ($runner1_dest !== 'NR') {
        $runs_pitcher = $game_stat['run1_resp_pit_id'];
        updateEventRuns($runs_pitcher, $runner1_dest);
    }
    if ($runner2_dest !== 'NR') {
        $runs_pitcher = $game_stat['run2_resp_pit_id'];
        updateEventRuns($runs_pitcher, $runner2_dest);
    }
    if ($runner3_dest !== 'NR') {
        $runs_pitcher = $game_stat['run3_resp_pit_id'];
        updateEventRuns($runs_pitcher, $runner3_dest);
    }
}

function getSeasonStartEnd($season) {
    $season_sql =
        "SELECT min(substr(game_id,8,4)) as start,
            max(substr(game_id,8,4)) as end,
            season
        FROM events
        WHERE season = '$season'
        GROUP BY season";
    $season_dates = exe_sql(DATABASE, $season_sql);
    $season_start = convertRetroDateToDs($season, $season_dates['start']);
    $season_end = convertRetroDateToDs($season, $season_dates['end']);
    return array($season_start, $season_end);
}

function pullPitchingData($season, $date) {
    $data = null;
    $sql =
        "SELECT lower(concat(c.first, '_', c.last)) AS player_name,
           game_id,
           inn_ct as inning,
           outs_ct,
           pit_hand_cd as hand,
           event_outs_ct,
           CASE
               WHEN bat_dest_id = 4 THEN 'ER'
               WHEN bat_dest_id in(5,6) THEN 'UER'
               ELSE 'NR'
           END AS bat_dest_id,
           CASE
               WHEN run1_dest_id = 4 THEN 'ER'
               WHEN run1_dest_id in(5,6) THEN 'UER'
               ELSE 'NR'
           END AS run1_dest_id,
           CASE
               WHEN run2_dest_id = 4 THEN 'ER'
               WHEN run2_dest_id in(5,6) THEN 'UER'
               ELSE 'NR'
           END AS run2_dest_id,
           CASE
               WHEN run3_dest_id = 4 THEN 'ER'
               WHEN run3_dest_id in(5,6) THEN 'UER'
               ELSE 'NR'
           END AS run3_dest_id,
           pit_id AS bat_resp_pit_id,
           run1_resp_pit_id,
           run2_resp_pit_id,
           run3_resp_pit_id
        FROM events a
        JOIN id c ON a.pit_id = c.id
        WHERE season = '$season'
        AND (event_outs_ct > 0
            OR bat_dest_id in(4,5,6)
            OR run1_dest_id in(4,5,6)
            OR run2_dest_id in(4,5,6)
            OR run3_dest_id in(4,5,6))
        AND substr(game_id,8,4) = '$date'
        ORDER BY event_id";
    $data = exe_sql(DATABASE, $sql);
    return $data;
}

function updatePlayerCareer($season, $ds) {
    global $playerCareer;
    $playerCareer = null;
    $player_last_season_career = exe_sql(DATABASE,
        "SELECT *
        FROM retrosheet_historical_eras_career
        WHERE season = '$season'
        AND ds = '$ds'"
    );
    foreach ($player_last_season_career as $stat) {
        $player_id = $stat['player_id'];
        $playerCareer[$player_id] = $stat;
    }
}

function calculate_innings_pitched($season_data, $innings_data) {
    $innings_pitched = array();
    foreach ($innings_data as $player_id => $player_data) {
        if (!$innings_pitched[$player_id]) {
            $innings_pitched[$player_id] = array(
                'player_id' => $player_id,
                'starts' => 0,
                'games' => 0,
                'outs_as_starter' => 0,
                'outs_as_reliever' => 0,
                'reliever_entry_inning' => 0
            );
        }
        foreach ($player_data as $game_id => $game_data) {
            $innings_pitched[$player_id]['starts'] += $game_data['starter'];
            $innings_pitched[$player_id]['games'] += 1;
            $innings_pitched[$player_id]['outs_as_starter'] +=
                $game_data['outs_as_starter'];
            $innings_pitched[$player_id]['outs_as_reliever'] +=
                $game_data['outs_as_reliever'];
            $innings_pitched[$player_id]['reliever_entry_inning'] +=
                $game_data['reliever_entry_inning'];
        }
    }
    foreach ($innings_pitched as $pitcher_id => $pitcher) {
        $games = $pitcher['games'];
        $starts = $pitcher['starts'];
        $relief_starts = $games - $starts;
        $season_data[$pitcher_id]['games_pitched'] = $games;
        $season_data[$pitcher_id]['starts'] = $starts ? $starts : 0;
        $season_data[$pitcher_id]['pct_start'] = $starts ?
            number_format($starts / $games, 2) : 0;
        if ($starts) {
            $season_data[$pitcher_id]['avg_innings_starter'] =
                number_format($pitcher['outs_as_starter'] / 3 / $starts, 2);
        }
        if ($relief_starts) {
            $season_data[$pitcher_id]['avg_innings_reliever'] =
                number_format(
                    $pitcher['outs_as_reliever'] / 3 / $relief_starts,
                    2
                );
            $season_data[$pitcher_id]['avg_relief_entry_inning'] =
                number_format(
                    $pitcher['reliever_entry_inning'] / $relief_starts,
                    2
                );
        }

    }
    return $season_data;
}

function calculate_era_buckets($daily_stats) {
    $eras = array();
    foreach ($daily_stats as $player) {
        $eras[] = $player['era'];
    }
    sort($eras);
    $era_divider = count($eras) / 4;
    $era_25 = $eras[$era_divider];
    $era_50 = $eras[$era_divider * 2];
    $era_75 = $eras[$era_divider * 3];
    foreach ($daily_stats as $player_name => $player) {
        $player_era = $player['era'];
        switch (true) {
            case $player_era < $era_25:
                $daily_stats[$player_name]['bucket'] = ERA_25;
                break;
            case $player_era >= $era_25 && $player_era < $era_50:
                $daily_stats[$player_name]['bucket'] = ERA_50;
                break;
            case $player_era >= $era_50 && $player_era < $era_75:
                $daily_stats[$player_name]['bucket'] = ERA_75;
                break;
            case $player_era >= $era_75:
                $daily_stats[$player_name]['bucket'] = ERA_100;
                break;
        }
    }
    return $daily_stats;
}

// Prompt user to confirm since this will overwrite a full seasons' data.
echo "\n"."Are you sure you want to overwrite historical data? (y/n) ";
$handle = fopen("php://stdin","r");
$confirm = trim(fgets($handle));
if ($confirm !== 'y') {
    exit();
}

$colheads = array(
    'player_name' => '?',
    'player_id' => '!',
    'hand' => '?',
    'innings' => '!',
    'era' => '!',
    'bucket' => '!',
    'outs' => '!',
    'earned_runs' => '!',
    'season' => '!',
    'ds' => '!'
);
$season_start = null;
$season_end = null;
$previous_season_players = array(
    "-1" => array(),
    "-2" => array(),
    "-3" => array(),
    "-4" => array()
);
$daily_table = 'retrosheet_historical_eras';
$career_table = 'retrosheet_historical_eras_career';

for ($season = 1950; $season < 2014; $season++) {
    if ($season > 1950) {
        $previous_season_end = ds_modify($season_end, '+1 day');
        $previous_season = $season - 1;
        // There won't be a -4/-3, etc. the first few seasons.
        $previous_season_players["-4"] = $previous_season_players["-3"]
            ?: array();
        $previous_season_players["-3"] = $previous_season_players["-2"]
            ?: array();
        $previous_season_players["-2"] = $previous_season_players["-1"]
            ?: array();
        $previous_season_players["-1"] = $seasonPlayers;
        $seasonPlayers = array();
    } else {
        $previous_season_end = null;
        $previous_season = null;
    }
    list($season_start, $season_end) = getSeasonStartEnd($season);
    // For the start of each season, update the career table of
    // the players who played in previous seasons and insert them
    // in that current season's table. Also reset playerSeason.
    $playerSeason = null;
    $seasonInnings = null;
    if ($previous_season) {
        updatePlayerCareer($previous_season, $previous_season_end);
        $player_career_daily_insert = array();
        foreach ($playerCareer as $stat) {
                $stat['season'] = $season;
                $stat['ds'] = $season_start;
                $player_career_daily_insert[] = $stat;
        }
        multi_insert(DATABASE, $career_table, $player_career_daily_insert, $colheads);
    }
    for ($ds = $season_start; $ds <= $season_end;
        $ds = ds_modify($ds, '+1 day')) {
        echo $ds."\n";
        $retro_ds = return_between($ds, "-", "-", EXCL).substr($ds, -2);
        // Enter today's game data into tomorrow's ds (as if we pulled at 12am)
        $entry_ds = ds_modify($ds, '+1 day');
        $daily_stats = pullPitchingData($season, $retro_ds);
        if ($daily_stats) {
            foreach ($daily_stats as $game_stat) {
                updateDailyStats($game_stat);
            }
        }
        $playerSeason = calculate_era_buckets($playerSeason);
        $playerCareer = calculate_era_buckets($playerCareer);
        $playerSeason =
            calculate_innings_pitched($playerSeason, $seasonInnings);
        $playerCareer =
            calculate_innings_pitched($playerCareer, $careerInnings);
        // Prep the tables for daily insertion into mysql.
        $player_season_daily_insert = array();
        $player_career_daily_insert = array();
        foreach ($playerCareer as $name => $stat) {
            // Only insert players who have played in last 5 years.
            $played_0 = in_array($name, $seasonPlayers);
            $played_1 = in_array($name, $previous_season_players["-1"]);
            $played_2 = in_array($name, $previous_season_players["-2"]);
            $played_3 = in_array($name, $previous_season_players["-3"]);
            $played_4 = in_array($name, $previous_season_players["-4"]);
            if (!($played_0 || $played_1 || $played_2
                || $played_3 || $played_4)) {
                continue;
            }
            $stat['ds'] = $entry_ds;
            $stat['season'] = $season;
            $player_career_daily_insert[] = $stat;
            if (isset($playerSeason[$name])) {
                $playerSeason[$name]['ds'] = $entry_ds;
                $playerSeason[$name]['season'] = $season;
                $player_season_daily_insert[] =
                    $playerSeason[$name];
            }
        }
        multi_insert(DATABASE, $daily_table, $player_season_daily_insert, $colheads);
        multi_insert(DATABASE, $career_table, $player_career_daily_insert, $colheads);
    }
}

?>
