<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include_once('/Users/constants.php');
include_once(HOME_PATH.'Scripts/Include/RetrosheetInclude.php');

$playerSeason = array();
$playerCareer = array();
$seasonPlayers = array();
$pitcherLastGame = array();

function convertRetroDateToDs($season, $date) {
    $month = substr($date, 0, 2);
    $day = substr($date, -2);
    $ds = "$season-$month-$day";
    return $ds;
}

// Set initial values for the cumulative arrays if not already set.
function initializePlayerArray($game_stat, $split) {
    global $playerSeason, $playerCareer, $seasonPlayers;
    $player_id = $game_stat['player_id'];
    if (!in_array($player_id, $seasonPlayers)) {
        $seasonPlayers[] = $player_id;
    }
    $season = $game_stat['season'];
    $pitcher_type = $game_stat['pitcher_type'];
    $player_name = format_for_mysql($game_stat['player_name']);
    $initial_stats = array(
        'player_id' => $player_id,
        'player_name' => $player_name,
        'pitcher_type' => $pitcher_type,
        'season' => $season,
        'split' => $split,
        'total_outs' => 0,
        'total_games' => 0,
        'singles' => 0,
        'doubles' => 0,
        'triples' => 0,
        'home_runs' => 0,
        'walks' => 0,
        'strikeouts' => 0,
        'ground_outs' => 0,
        'fly_outs' => 0,
        'plate_appearances' => 0
    );
    if (!isset($playerSeason[$player_id][$split][$pitcher_type])) {
        $playerSeason[$player_id][$split][$pitcher_type] = $initial_stats;
    }
    if (!isset($playerCareer[$player_id][$split][$pitcher_type])) {
        $playerCareer[$player_id][$split][$pitcher_type] = $initial_stats;
    }
}

// Function to keep track of games pitched per pitcher.
function isPitcherNewGame($player_id, $split, $pitcher_type, $game_id) {
    global $pitcherLastGame;
    $pitcher_key = $player_id.$split.$pitcher_type;
    if (idx($pitcherLastGame, $pitcher_key) === $game_id) {
        return false;
    }
    $pitcherLastGame[$pitcher_key] = $game_id;
    return true;
}

// Function to add event impact to daily and careers arrays.
function updateEvent($game_stat, $split) {
    global $playerSeason, $playerCareer;
    initializePlayerArray($game_stat, $split);
    $player_id = $game_stat['player_id'];
    $event = $game_stat['event_name'];
    $ds = $game_stat['ds'];
    $game_id = $game_stat['game_id'];
    $pitcher_type = $game_stat['pitcher_type'];
    $last_team = $game_stat['last_team'];
    $event_outs = $game_stat['event_outs_ct'];
    // Only update last team/game on the 'Total' split
    if ($split === RetrosheetSplits::TOTAL) {
        $playerSeason[$player_id][$split][$pitcher_type]['last_team'] =
            $last_team;
        $playerCareer[$player_id][$split][$pitcher_type]['last_team'] =
            $last_team;
        $playerSeason[$player_id][$split][$pitcher_type]['last_game'] = $ds;
        $playerCareer[$player_id][$split][$pitcher_type]['last_game'] = $ds;
    }
    $is_new_game =
        isPitcherNewGame($player_id, $split, $pitcher_type, $game_id);
    if ($is_new_game) {
        $playerSeason[$player_id][$split][$pitcher_type]['total_games'] += 1;
        $playerCareer[$player_id][$split][$pitcher_type]['total_games'] += 1;
    }
    $playerSeason[$player_id][$split][$pitcher_type]['total_outs'] +=
        $event_outs;
    $playerCareer[$player_id][$split][$pitcher_type]['total_outs'] +=
        $event_outs;
    // If event is 'other' don't count it as a PA.
    if ($event === 'other') {
        return;
    }
    $playerSeason[$player_id][$split][$pitcher_type]['plate_appearances'] += 1;
    $playerCareer[$player_id][$split][$pitcher_type]['plate_appearances'] += 1;
    $playerSeason[$player_id][$split][$pitcher_type][$event . 's'] += 1;
    $playerCareer[$player_id][$split][$pitcher_type][$event . 's'] += 1;
}

// Function to split out event by situation (home, vsright, etc.).
function updateDailyStats($game_stat) {
    updateEvent($game_stat, 'Total');
    updateEvent($game_stat, $game_stat['home_away']);
    updateEvent($game_stat, $game_stat['vs_hand']);
    updateEvent($game_stat, $game_stat['situation']);
}

function getSeasonStartEnd($season) {
    $season_sql = 
        "SELECT min(substr(game_id,8,4)) as start,
            max(substr(game_id,8,4)) as end,
            season
        FROM events
        WHERE season = $season
        GROUP BY season";
    $season_dates = reset(exe_sql(DATABASE, $season_sql));
    $season_start = convertRetroDateToDs($season, $season_dates['start']);
    $season_end = convertRetroDateToDs($season, $season_dates['end']);
    return array($season_start, $season_end);
}

function pullPitchingData($season, $date) {
    $season_data = null;
    $sql = 
       "SELECT lower(concat(c.first, '_', c.last)) AS player_name,
       a.pit_id AS player_id,
       a.last_team,
       a.season,
       a.pitcher_type,
       a.event_outs_ct,
       a.game_id,
       a.ds,
       a.home_away,
       a.bat_hand_cd AS vs_hand,
       a.situation,
       CASE
           WHEN (a.event in(2,19)
                 AND battedball_cd = 'G') THEN 'ground_out'
           WHEN (a.event in(2,19)
                 AND battedball_cd != 'G') THEN 'fly_out'
           WHEN a.event = 3 THEN 'strikeout'
           WHEN a.event in(14,15,16) THEN 'walk'
           WHEN a.event = 20 THEN 'single'
           WHEN a.event = 21 THEN 'double'
           WHEN a.event = 22 THEN 'triple'
           WHEN a.event = 23 THEN 'home_run'
           ELSE 'other'
       END AS event_name
    FROM
        (SELECT event_cd AS event,
        season,
        game_id,
        concat(substr(game_id,4,4), '-', substr(game_id,8,2), '-', 
            substr(game_id,10,2)) AS ds,
        CASE
            WHEN bat_home_id = 0 THEN 'Home'
            ELSE 'Away'
        END AS home_away,
        CASE
            WHEN bat_home_id = 0 THEN HOME_TEAM_ID
            ELSE AWAY_TEAM_ID
        END as last_team,
        CASE 
            WHEN bat_hand_cd = 'R' then 'VsRight'
            WHEN bat_hand_cd = 'L' then 'VsLeft'
            ELSE 'VsUnknown'
        END as bat_hand_cd,
        CASE
            WHEN start_bases_cd = 0 THEN 'NoneOn'
            WHEN start_bases_cd = 1 THEN 'RunnersOn'
            WHEN (start_bases_cd = 7
                AND outs_ct != 2) THEN 'BasesLoaded'
            WHEN (start_bases_cd > 1
                AND outs_ct = 2) THEN 'ScoringPos2Out'
            ELSE 'ScoringPos'
          END AS situation,
        battedball_cd,
        pit_id,
        pitcher_type,
        event_outs_ct
    FROM events
    WHERE season = $season
    AND substr(game_id,8,4) = '$date') a
    LEFT OUTER JOIN id c ON a.pit_id = c.id";
    $season_data = exe_sql(DATABASE, $sql);
    return $season_data;
}

function updatePlayerCareer($season, $ds) {
    global $playerCareer;
    $playerCareer = null;
    $player_last_season_career = exe_sql(DATABASE, 
        "SELECT * 
        FROM retrosheet_historical_pitching_career
        WHERE season = $season
        AND ds = '$ds'"
    );
    foreach ($player_last_season_career as $stat) {
        $player_id = $stat['player_id'];
        $split = $stat['split'];
        $pitcher_type = $stat['pitcher_type'];
        $playerCareer[$player_id][$split][$pitcher_type] = $stat;
    }
}

// Prompt user to confirm since this will overwrite a full seasons' data.
echo "\n"."Are you sure you want to overwrite historical data? (y/n) ";
$handle = fopen("php://stdin","r");
$confirm = trim(fgets($handle));
if ($confirm !== 'y') {
    exit();
}

$test = true;

$colheads = array(
    'player_name' => '?',
    'player_id' => '!',
    'pitcher_type' => '!',
    'last_team' => '!',
    'last_game' => '!',
    'total_outs' => '!',
    'total_games' => '!',
    'singles' => '!',
    'doubles' => '!',
    'triples' => '!',
    'home_runs' => '!',
    'walks' => '!',
    'strikeouts' => '!',
    'ground_outs' => '!',
    'fly_outs' => '!',
    'plate_appearances' => '!',
    'split' => '!',
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
$daily_table = 'retrosheet_historical_pitching';
$career_table = 'retrosheet_historical_pitching_career';

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
    if ($previous_season) {
        updatePlayerCareer($previous_season, $previous_season_end);
        $player_career_daily_insert = array();
        foreach ($playerCareer as $name => $split) {
            foreach ($split as $pitcher_type) {
                foreach ($pitcher_type as $stat) {
                    $stat['season'] = $season;
                    $stat['ds'] = $season_start;
                    $player_career_daily_insert[] = $stat;
                }
            }
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
    for ($ds = $season_start; $ds <= $season_end;
        $ds = ds_modify($ds, '+1 day')) {  
        echo $ds."\n";
        $retro_ds = return_between($ds, "-", "-", EXCL).substr($ds, -2);
        // Enter today's game data into tomorrow's ds (as if we pulled at 12am)
        $entry_ds = ds_modify($ds, '+1 day');
        $daily_stats = pullPitchingData($season, $retro_ds);
        if ($daily_stats) {
            foreach ($daily_stats as $game_stat) {
                // Filter out unneccesary events.
                $event = $game_stat['event_name'];
                updateDailyStats($game_stat);
            }
        }
        // Prep the tables for daily insertion into mysql.
        $player_season_daily_insert = array();
        $player_career_daily_insert = array();
        foreach ($playerCareer as $name => $split) {
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
            foreach ($split as $split_name => $split) {
                foreach ($split as $pitcher_type => $stat) {
                    $stat['ds'] = $entry_ds;
                    $stat['season'] = $season;
                    $player_career_daily_insert[] = $stat;
                    if (isset($playerSeason[$name][$split_name][$pitcher_type])) {
                        $playerSeason[$name][$split_name][$pitcher_type]['ds'] =
                            $entry_ds;
                        $player_season_daily_insert[] =
                            $playerSeason[$name][$split_name][$pitcher_type];
                    }
                }
            }
        } 
        if ($test) {
            print_r($player_season_daily_insert);
            exit();
        }
        if (isset($player_season_daily_insert)) {
            multi_insert(
                DATABASE,
                $daily_table,
                $player_season_daily_insert,
                $colheads
            );
        }
        if (isset($player_career_daily_insert)) {
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
