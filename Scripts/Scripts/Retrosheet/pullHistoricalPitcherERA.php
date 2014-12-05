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
$seasonInnings = array();
$careerInnings = array();
$seasonPlayers = array(
    '0' => array(),
    '-1' => array(),
    '-2' => array(),
    '-3' => array(),
    '-4' => array()
);
$seasonRoster = array();
$startingPitchers = array();

function convertRetroDateToDs($season, $date) {
    $month = substr($date, 0, 2);
    $day = substr($date, -2);
    $ds = "$season-$month-$day";
    return $ds;
}

function updateSeasonVars($season, $season_vars) {
    if ($season > $season_vars['start_script']) {
        $season_vars['previous_end'] =
            ds_modify($season_vars['current_end'], '+1 day');
        $season_vars['previous'] = $season - 1;
    }
    $season_sql =
        "SELECT min(substr(game_id,8,4)) as start,
            max(substr(game_id,8,4)) as end,
            season
        FROM events
        WHERE season = '$season'
        GROUP BY season";
    $season_dates = exe_sql(DATABASE, $season_sql);
    $season_vars['current_start'] =
        convertRetroDateToDs($season, $season_dates['start']);
    $season_vars['current_end'] =
        convertRetroDateToDs($season, $season_dates['end']);
    return $season_vars;
}

function updateSeasonPlayers() {
    global $seasonPlayers;
    $seasonPlayers['-4'] = $seasonPlayers['-3'] ?: array();
    $seasonPlayers['-3'] = $seasonPlayers['-2'] ?: array();
    $seasonPlayers['-2'] = $seasonPlayers['-1'] ?: array();
    $seasonPlayers['-1'] = $seasonPlayers['0'] ?: array();
    $seasonPlayers['0'] = array();
}

function updateSeasonRoster($season) {
    global $seasonRoster;
    $seasonRoster = null;
    $sql = "SELECT min(team_id) as team_id,
            0 as is_pitcher,
            player_id,
            last_name_tx,
            first_name_tx,
            bat_hand_cd,
            pit_hand_cd
        FROM rosters
        WHERE year_id = $season
        GROUP BY player_id,
            last_name_tx,
            first_name_tx,
            bat_hand_cd,
            pit_hand_cd";
    $season_data = exe_sql(DATABASE, $sql);
    $seasonRoster = $season_data ? index_by($season_data, 'player_id') : null;
}

function pullPitchingData($season, $date) {
    $data = null;
    $sql =
        "SELECT lower(concat(c.first, '_', c.last)) AS player_name,
           CASE
               WHEN bat_home_id = 1 THEN away_team_id
               ELSE home_team_id
           END as team,
           event_id,
           game_id,
           inn_ct as inning,
           pit_hand_cd as hand,
           event_outs_ct,
           CASE
               WHEN bat_dest_id = 4 THEN 'ER'
               ELSE 'NR'
           END AS bat_dest_id,
           CASE
               WHEN run1_dest_id = 4 THEN 'ER'
               ELSE 'NR'
           END AS run1_dest_id,
           CASE
               WHEN run2_dest_id = 4 THEN 'ER'
               ELSE 'NR'
           END AS run2_dest_id,
           CASE
               WHEN run3_dest_id = 4 THEN 'ER'
               ELSE 'NR'
           END AS run3_dest_id,
           pit_id AS bat_resp_pit_id,
           run1_resp_pit_id,
           run2_resp_pit_id,
           run3_resp_pit_id
        FROM events a
        LEFT OUTER JOIN id c ON a.pit_id = c.id
        WHERE season = '$season'
        AND (event_outs_ct > 0
            OR bat_dest_id = 4
            OR run1_dest_id = 4
            OR run2_dest_id = 4
            OR run3_dest_id = 4
            OR event_id = 1)
        AND substr(game_id,8,4) = '$date'
        ORDER BY game_id, event_id";
    $data = exe_sql(DATABASE, $sql);
    return $data;
}

function updatePlayerCareer($season, $ds) {
    global $playerCareer;
    $playerCareer = array();
    $player_last_season_career = exe_sql(DATABASE,
        "SELECT *
        FROM retrosheet_historical_eras_career
        WHERE season = '$season'
        AND ds = '$ds'"
    );
    foreach ($player_last_season_career as $player) {
        $player_id = $player['player_id'];
        $starter_outs = $player['starter_innings'] * 3;
        $reliever_outs = $player['reliever_innings'] * 3;
        $playerCareer[$player_id] = $player;
        $playerCareer[$player_id]['starter_outs'] = $starter_outs;
        $playerCareer[$player_id]['reliever_outs'] = $reliever_outs;
        $playerCareer[$player_id]['starter_earned_runs'] =
            $player['starter_era'] * $player['starter_innings'] / 9;
    }
}

function updateAverageInnings($stat) {
    global $seasonInnings, $careerInnings;
    $player_id = $stat['bat_resp_pit_id'];
    $game_id = $stat['game_id'];
    $inning = $stat['inning'];
    $outs = $stat['event_outs_ct'];
    if (!isset($seasonInnings[$player_id][$game_id]['player_id'])) {
        $starter = $inning == 1 ? 1 : 0;
        $initialized_data = array(
            'player_id' => $player_id,
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

function initializePlayerArray(
    $player_id,
    $player_name = null,
    $player_hand = null,
    $starter = null
) {
    global $playerSeason, $playerCareer, $startingPitchers, $seasonPlayers;

    $initial_stats = array(
        'player_id' => $player_id,
        'player_name' => $player_name,
        'hand' => $player_hand,
        'games' => 0,
        'starts' => 0,
        'pct_start' => 0,
        'starter_era' => null,
        'starter_outs' => 0,
        'starter_earned_runs' => 0,
        'reliever_era' => null,
        'reliever_outs' => 0,
        'reliever_earned_runs' => 0,
        'overall_era' => null,
        'overall_earned_runs' => 0
    );

    if (!in_array($player_id, $seasonPlayers['0'])) {
        $seasonPlayers['0'][] = $player_id;
    }

    if (!isset($startingPitchers[$player_id])) {
        $startingPitchers[$player_id] =
            $starter ? 'starter' : 'reliever';
    }

    if (!isset($playerSeason[$player_id])) {
        $playerSeason[$player_id] = $initial_stats;
    }
    if (!isset($playerCareer[$player_id])) {
        $playerCareer[$player_id] = $initial_stats;
    }

    // If a pitcher allows a run before his first out he won't have his
    // name or hand filled in. Account for this case.
    if ($player_name && !$playerSeason[$player_id]['player_name']) {
        $playerSeason[$player_id]['player_name'] = $player_name;
        $playerSeason[$player_id]['hand'] = $player_hand;
        $playerCareer[$player_id]['player_name'] = $player_name;
        $playerCareer[$player_id]['hand'] = $player_hand;
    }
}

function updateEventOuts($game_stat) {
    global $playerSeason, $playerCareer, $startingPitchers;
    $player_id = $game_stat['bat_resp_pit_id'];
    $player_name = $game_stat['player_name'];
    $player_name = $player_name ? format_for_mysql($player_name) : null;
    $player_hand = $game_stat['hand'];
    $starter = $game_stat['inning'] == 1 ? 1 : 0;
    initializePlayerArray($player_id, $player_name, $player_hand, $starter);
    $pitcher_type = $startingPitchers[$player_id];
    $outs = $game_stat['event_outs_ct'];
    $playerSeason[$player_id][$pitcher_type.'_outs'] += $outs;
    $playerCareer[$player_id][$pitcher_type.'_outs'] += $outs;
    updateAverageInnings($game_stat);
}

function updateEventRuns($runs_pitcher, $starter) {
    global $playerSeason, $playerCareer, $startingPitchers;
    initializePlayerArray($runs_pitcher, null, null, $starter);
    $pitcher_type = $startingPitchers[$runs_pitcher];
    $playerSeason[$runs_pitcher][$pitcher_type.'_earned_runs'] += 1;
    $playerCareer[$runs_pitcher][$pitcher_type.'_earned_runs'] += 1;
}

function checkTeamRoster($game_stat) {
    global $seasonRoster;
    if (!isset($seasonRoster)) {
        return;
    }
    $player_id = $game_stat['bat_resp_pit_id'];
    $team = $game_stat['team'];
    $is_pitcher = $seasonRoster[$player_id]['is_pitcher'];
    if ($seasonRoster[$player_id]['team_id'] != $team) {
        $seasonRoster[$player_id]['team_id'] = $team;
    }
    if (!$is_pitcher) {
        $seasonRoster[$player_id]['is_pitcher'] = 1;
        $seasonRoster[$player_id]['first_name_tx'] =
            format_for_mysql($seasonRoster[$player_id]['first_name_tx']);
        $seasonRoster[$player_id]['last_name_tx'] =
            format_for_mysql($seasonRoster[$player_id]['last_name_tx']);
    }
}

function updateDailyStats($game_stat) {
    // Check to see if player has been traded since Retrosheet
    // only updates rosters once a season.
    checkTeamRoster($game_stat);
    // Update pitcher outs seperately from runs since the runs
    // could be earned by a different pitcher.
    if ($game_stat['event_outs_ct'] > 0) {
        updateEventOuts($game_stat);
    }
    $starter = $game_stat['inning'] == 1 ? 1 : 0;
    $batter_dest = $game_stat['bat_dest_id'];
    $runner1_dest = $game_stat['run1_dest_id'];
    $runner2_dest = $game_stat['run2_dest_id'];
    $runner3_dest = $game_stat['run3_dest_id'];
    // Use seperate if statements since multiple runners
    // can score per play.
    if ($batter_dest == 'ER') {
        $runs_pitcher = $game_stat['bat_resp_pit_id'];
        updateEventRuns($runs_pitcher, $starter);
    }
    if ($runner1_dest == 'ER') {
        $runs_pitcher = $game_stat['run1_resp_pit_id'];
        updateEventRuns($runs_pitcher, $starter);
    }
    if ($runner2_dest == 'ER') {
        $runs_pitcher = $game_stat['run2_resp_pit_id'];
        updateEventRuns($runs_pitcher, $starter);
    }
    if ($runner3_dest == 'ER') {
        $runs_pitcher = $game_stat['run3_resp_pit_id'];
        updateEventRuns($runs_pitcher, $starter);
    }
}

function calculate_era($player, $type) {
    $starter = 0;
    $reliever = 0;
    switch ($type) {
        case 'starter':
            $starter = 1;
            break;
        case 'reliever':
            $reliever = 1;
            break;
        case 'overall':
            $starter = 1;
            $reliever = 1;
            break;
    }
    $innings =
        (($starter * $player['starter_outs']) +
        ($reliever * $player['reliever_outs'])) / 3;
    $runs =
        ($starter * $player['starter_earned_runs']) +
        ($reliever * $player['reliever_earned_runs']);
    $era = $innings ? $runs / $innings * 9 : INF_ERA;

    return array(
        format_double($innings, 2),
        format_double($runs, 2),
        format_double($era, 2)
    );
}

function calculate_innings_pitched($season_data, $innings_data) {
    global $playerCareer;
    $innings_pitched = array();
    foreach ($innings_data as $player_id => $player_data) {
        if (!isset($playerCareer[$player_id])) {
            continue;
        }
        if (!isset($innings_pitched[$player_id])) {
            $innings_pitched[$player_id] = array(
                'player_id' => $player_id,
                'starts' => 0,
                'games' => 0,
                'outs_as_starter' => 0,
                'outs_as_reliever' => 0,
                'avg_innings_starter' => null,
                'avg_innings_reliever' => null,
                'reliever_entry_inning' => null
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
        $season_data[$pitcher_id]['games'] = $games;
        $season_data[$pitcher_id]['starts'] = $starts ? $starts : 0;
        $season_data[$pitcher_id]['pct_start'] =
            format_double($starts ? $starts / $games : 0, 2);
        if ($starts) {
            $season_data[$pitcher_id]['avg_innings_starter'] =
                format_double($pitcher['outs_as_starter'] / 3 / $starts, 2);
        }
        if ($relief_starts) {
            $season_data[$pitcher_id]['avg_innings_reliever'] =
                format_double(
                    $pitcher['outs_as_reliever'] / 3 / $relief_starts,
                    2
                );
            $season_data[$pitcher_id]['avg_relief_entry_inning'] =
                format_double(
                    $pitcher['reliever_entry_inning'] / $relief_starts,
                    2
                );
        }
    }
    return $season_data;
}

function add_player_bucket(
    $player_name,
    $daily_stats,
    $era_25,
    $era_50,
    $era_75,
    $pitcher_type
) {
    $player_era = $daily_stats[$player_name][$pitcher_type.'_era'];
    switch (true) {
        case $player_era >= $era_75[$pitcher_type]:
            $daily_stats[$player_name][$pitcher_type.'_bucket'] = ERA_100;
            break;
        case $player_era >= $era_50[$pitcher_type]:
            $daily_stats[$player_name][$pitcher_type.'_bucket'] = ERA_75;
            break;
        case $player_era >= $era_25[$pitcher_type]:
            $daily_stats[$player_name][$pitcher_type.'_bucket'] = ERA_50;
            break;
        case $player_era < $era_25[$pitcher_type]:
            $daily_stats[$player_name][$pitcher_type.'_bucket'] = ERA_25;
            break;
    }
    return $daily_stats;
}

function playedRecently($name) {
    global $seasonPlayers;
    $played_0 = in_array($name, $seasonPlayers['0']);
    $played_1 = in_array($name, $seasonPlayers['-1']);
    $played_2 = in_array($name, $seasonPlayers['-2']);
    $played_3 = in_array($name, $seasonPlayers['-3']);
    $played_4 = in_array($name, $seasonPlayers['-4']);
    if (!($played_0 || $played_1 || $played_2 || $played_3 || $played_4)) {
        return false;
    }
    return true;
}

function calculate_era_buckets($daily_stats) {
    $overall_eras = array();
    $starter_eras = array();
    $reliever_eras = array();
    foreach ($daily_stats as $player) {
        if (isset($player['overall_era'])) {
            $overall_eras[] = $player['overall_era'];
        }
        if (isset($player['starter_era'])) {
            $starter_eras[] = $player['starter_era'];
        }
        if (isset($player['reliever_era'])) {
            $reliever_eras[] = $player['reliever_era'];
        }
    }
    sort($starter_eras);
    sort($reliever_eras);
    sort($overall_eras);
    $starter_era_divider = count($starter_eras) / 4;
    $reliever_era_divider = count($reliever_eras) / 4;
    $overall_era_divider = count($overall_eras) / 4;
    $era_25 = array(
        'overall' => $overall_eras[$overall_era_divider],
        'starter' => $starter_eras[$starter_era_divider],
        'reliever' => $reliever_eras[$reliever_era_divider]
    );
    $era_50 = array(
        'overall' => $overall_eras[$overall_era_divider * 2],
        'starter' => $starter_eras[$starter_era_divider * 2],
        'reliever' => $reliever_eras[$reliever_era_divider * 2]
    );
    $era_75 = array(
        'overall' => $overall_eras[$overall_era_divider * 3],
        'starter' => $starter_eras[$starter_era_divider * 3],
        'reliever' => $reliever_eras[$reliever_era_divider * 3]
    );
    foreach ($daily_stats as $player_name => $player) {
        if (isset($player['starter_era'])
        || isset($player['reliever_era'])) {
            $daily_stats = add_player_bucket(
                $player_name,
                $daily_stats,
                $era_25,
                $era_50,
                $era_75,
                'overall'
            );
        }
        if (isset($player['starter_era'])) {
            $daily_stats = add_player_bucket(
                $player_name,
                $daily_stats,
                $era_25,
                $era_50,
                $era_75,
                'starter'
            );
        }
    }
    return $daily_stats;
}

function updateCalculatedERA($player_id) {
    global $playerSeason, $playerCareer;
    list(
        $playerCareer[$player_id]['starter_innings'],
        $playerCareer[$player_id]['starter_earned_runs'],
        $playerCareer[$player_id]['starter_era']
    ) = calculate_era($playerCareer[$player_id], 'starter');
    list(
        $playerCareer[$player_id]['reliever_innings'],
        $playerCareer[$player_id]['reliever_earned_runs'],
        $playerCareer[$player_id]['reliever_era']
    ) = calculate_era($playerCareer[$player_id], 'reliever');
    list(
        $playerCareer[$player_id]['overall_innings'],
        $playerCareer[$player_id]['overall_earned_runs'],
        $playerCareer[$player_id]['overall_era']
    ) = calculate_era($playerCareer[$player_id], 'overall');
    if (!isset($playerSeason[$player_id])) {
        return;
    }
    list(
        $playerSeason[$player_id]['starter_innings'],
        $playerSeason[$player_id]['starter_earned_runs'],
        $playerSeason[$player_id]['starter_era']
    ) = calculate_era($playerSeason[$player_id], 'starter');
    list(
        $playerSeason[$player_id]['reliever_innings'],
        $playerSeason[$player_id]['reliever_earned_runs'],
        $playerSeason[$player_id]['reliever_era']
    ) = calculate_era($playerSeason[$player_id], 'reliever');
    list(
        $playerSeason[$player_id]['overall_innings'],
        $playerSeason[$player_id]['overall_earned_runs'],
        $playerSeason[$player_id]['overall_era']
    ) = calculate_era($playerSeason[$player_id], 'overall');
}

// Add test variable to turn off all sql writes
$test = false;
$colheads = array(
    'player_id' => '!',
    'player_name' => '?',
    'hand' => '?',
    'games' => '!',
    'starts' => '!',
    'pct_start' => '!',
    'overall_era' => '!',
    'starter_era' => '?',
    'reliever_era' => '?',
    'overall_bucket' => '!',
    'starter_bucket' => '?',
    'overall_innings' => '!',
    'starter_innings' => '?',
    'reliever_innings' => '?',
    'avg_innings_starter' => '?',
    'avg_innings_reliever' => '?',
    'avg_relief_entry_inning' => '?',
    'reliever_earned_runs' => '?',
    'season' => '!',
    'ds' => '!'
);
$roster_colheads = array(
    'team_id',
    'player_id',
    'last_name_tx',
    'first_name_tx',
    'bat_hand_cd',
    'pit_hand_cd',
    'season',
    'ds'
);

$season_vars = array(
    'start_script' => 1950,
    'end_script' => 2014,
    'current_start' => null,
    'current_end' => null,
    'previous' => null,
    'previous_end' => null
);
$roster_table = 'retrosheet_historical_pitching_rosters';
$season_table = 'retrosheet_historical_eras';
$career_table = 'retrosheet_historical_eras_career';

for ($season = $season_vars['start_script'];
    $season < $season_vars['end_script'];
    $season++) {

    $season_vars = updateSeasonVars($season, $season_vars);
    unset($playerSeason, $seasonInnings);
    updateSeasonPlayers();
    updateSeasonRoster($season);
    if ($season_vars['previous']) {
        updatePlayerCareer(
            $season_vars['previous'],
            $season_vars['previous_end']
        );
        $player_career_daily_insert = array();
        foreach ($playerCareer as $stat) {
            $stat['season'] = $season;
            $stat['ds'] = $season_vars['current_start'];
            $player_career_daily_insert[] = $stat;
        }
        if (!$test) {
            multi_insert(
                DATABASE,
                $career_table,
                $player_career_daily_insert,
                $colheads
            );
        }
    }

    for ($ds = $season_vars['current_start'];
        $ds <= $season_vars['current_end'];
        $ds = ds_modify($ds, '+1 day')) {
        echo $ds."\n";
        $retro_ds = return_between($ds, "-", "-", EXCL).substr($ds, -2);
        // For MySQL insert we'll use the following day to simulate pulling
        // data at 12am the night before.
        $entry_ds = ds_modify($ds, '+1 day');
        $daily_stats = pullPitchingData($season, $retro_ds);
        if ($daily_stats) {
            foreach ($daily_stats as $game_stat) {
                if ($game_stat['event_id'] == 1) {
                    unset($startingPitchers);
                }
                updateDailyStats($game_stat);
            }
        }
        foreach ($playerCareer as $player_id => $stats) {
            updateCalculatedERA($player_id);
        }
        $playerSeason =
            calculate_innings_pitched($playerSeason, $seasonInnings);
        $playerCareer =
            calculate_innings_pitched($playerCareer, $careerInnings);
        $playerSeason = calculate_era_buckets($playerSeason);
        $playerCareer = calculate_era_buckets($playerCareer);

        // Prep the tables for daily insertion into mysql.
        $player_season_daily_insert = array();
        $player_career_daily_insert = array();
        $roster_daily_insert = array();
        foreach ($playerCareer as $name => $stat) {
            // Only insert players who have played in last 5 years.
            if (!playedRecently($name)) {
                continue;
            }
            $stat['ds'] = $entry_ds;
            $stat['season'] = $season;
            $player_career_daily_insert[] = $stat;
            if (isset($playerSeason[$name])) {
                $playerSeason[$name]['ds'] = $entry_ds;
                $playerSeason[$name]['season'] = $season;
                $player_season_daily_insert[] = $playerSeason[$name];
            }
        }

        if (isset($seasonRoster) && !$test) {
            foreach ($seasonRoster as $pitcher) {
                if (!$pitcher['is_pitcher']) {
                    continue;
                }
                $pitcher['season'] = $season;
                $pitcher['ds'] = $entry_ds;
                $roster_daily_insert[] = $pitcher;
            }
            multi_insert(
                DATABASE,
                $roster_table,
                $roster_daily_insert,
                $roster_colheads
            );
        }

        if ($test) {
            verifyTestData($test_data, $season, $retro_ds);
        } else {
            multi_insert(
                DATABASE,
                $season_table,
                $player_season_daily_insert,
                $colheads
            );
            multi_insert(
                DATABASE,
                $career_table,
                $player_career_daily_insert,
                $colheads
            );
        }

    }
}

?>
