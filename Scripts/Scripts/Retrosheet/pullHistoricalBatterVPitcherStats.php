<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');

const DEFAULT_BUCKET = 75;

$playerSeason = array();
$playerCareer = array();
$seasonPlayers = array(
    '0' => array(),
    '-1' => array(),
    '-2' => array(),
    '-3' => array(),
    '-4' => array()
);

function convertRetroDateToDs($season, $date) {
    $month = substr($date, 0, 2);
    $day = substr($date, -2);
    $ds = "$season-$month-$day";
    return $ds;
}

function updateSeasonPlayers() {
    global $seasonPlayers;
    $seasonPlayers['-4'] = $seasonPlayers['-3'] ?: array();
    $seasonPlayers['-3'] = $seasonPlayers['-2'] ?: array();
    $seasonPlayers['-2'] = $seasonPlayers['-1'] ?: array();
    $seasonPlayers['-1'] = $seasonPlayers['0'] ?: array();
    $seasonPlayers['0'] = array();
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

// Set initial values for the cumulative arrays if not already set.
function initializePlayerArray($game_stat, $split) {
    global $playerSeason, $playerCareer, $seasonPlayers;
    $player_id = $game_stat['player_id'];
    if (!in_array($player_id, $seasonPlayers)) {
        $seasonPlayers['0'][] = $player_id;
    }
    $season = $game_stat['season'];
    $player_name = format_for_mysql($game_stat['player_name']);
    $initial_stats = array(
        'player_id' => $player_id,
        'player_name' => $player_name,
        'season' => $season,
        'split' => $split,
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
    if (!isset($playerSeason[$player_id][$split])) {
        $playerSeason[$player_id][$split] = $initial_stats;
    }
    if (!isset($playerCareer[$player_id][$split])) {
        $playerCareer[$player_id][$split] = $initial_stats;
    }
}

// Function to add event impact to daily and careers arrays.
function updateEvent($game_stat, $type) {
    global $playerSeason, $playerCareer;
    $player_id = $game_stat['player_id'];
    $event = $game_stat['event_name'];
    $split = $game_stat[$type.'_vs_bucket'] ?: 99;
    if (!$split) {
        return;
    }
    initializePlayerArray($game_stat, $split);
    switch ($type) {
        case "season":
            $playerSeason[$player_id][$split]['plate_appearances'] += 1;
            $playerSeason[$player_id][$split][$event . 's'] += 1;
            break;
        case "career";
            $playerCareer[$player_id][$split]['plate_appearances'] += 1;
            $playerCareer[$player_id][$split][$event . 's'] += 1;
            break;
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

function pullBattingData($season, $date) {
    $season_data = null;
    $ds = convertRetroDateToDs($season, $date);
    $sql =
        "SELECT lower(concat(c.first, '_', c.last)) AS player_name,
        a.bat_id AS player_id,
        coalesce(d.starter_bucket, d.overall_bucket) as season_vs_bucket,
        coalesce(e.starter_bucket, e.overall_bucket) as career_vs_bucket,
        d.starter_innings as season_innings,
        e.starter_innings as season_innings,
        a.season,
        a.ds,
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
        (SELECT bat_id,
        pit_id,
        event_cd AS event,
        battedball_cd,
        season,
        concat(substr(game_id,4,4), '-', substr(game_id,8,2), '-',
            substr(game_id,10,2)) AS ds
    FROM events
    WHERE season = $season) a
    JOIN id c ON a.bat_id = c.id
    LEFT OUTER JOIN retrosheet_historical_eras d
    ON a.pit_id = d.player_id
    AND d.season = $season
    AND d.ds = a.ds
    LEFT OUTER JOIN retrosheet_historical_eras_career e
    ON a.pit_id = e.player_id
    AND e.season = $season
    AND e.ds = a.ds";
    $season_data = exe_sql(DATABASE, $sql);
    return $season_data;
}

function updatePlayerCareer($season, $ds) {
    global $playerCareer;
    $playerCareer = null;
    $player_last_season_career = exe_sql(DATABASE,
        "SELECT *
        FROM retrosheet_historical_batting_career
        WHERE season = '$season'
        AND ds = '$ds'"
    );
    foreach ($player_last_season_career as $stat) {
        $player_id = $stat['player_id'];
        $split = $stat['split'];
        $playerCareer[$player_id][$split] = $stat;
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
    'player_name',
    'player_id',
    'singles',
    'doubles',
    'triples',
    'home_runs',
    'walks',
    'strikeouts',
    'ground_outs',
    'fly_outs',
    'plate_appearances',
    'split',
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
$daily_table = 'retrosheet_historical_batting_vspitcher';
$career_table = 'retrosheet_historical_batting_vspitcher_career';

for ($season = $season_vars['start_script'];
    $season < $season_vars['end_script'];
    $season++) {

    $season_vars = updateSeasonVars($season, $season_vars);
    unset($playerSeason);
    updateSeasonPlayers();
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
        $daily_stats = pullBattingData($season, $retro_ds);
        if ($daily_stats) {
            foreach ($daily_stats as $game_stat) {
                // Filter out unneccesary events.
                $event = $game_stat['event_name'];
                if ($event == 'other') {
                    continue;
                }
                updateEvent($game_stat, 'season');
                updateEvent($game_stat, 'career');
            }
        }
        // Prep the tables for daily insertion into mysql.
        $player_season_daily_insert = array();
        $player_career_daily_insert = array();
        foreach ($playerCareer as $name => $split) {
            // Only insert players who have played in last 5 years.
            if (!playedRecently($name)) {
                continue;
            }
            foreach ($split as $split_name => $stat) {
                $stat['ds'] = $entry_ds;
                $stat['season'] = $season;
                $player_career_daily_insert[] = $stat;
                if (isset($playerSeason[$name][$split_name])) {
                    $playerSeason[$name][$split_name]['ds'] = $entry_ds;
                    $player_season_daily_insert[] =
                        $playerSeason[$name][$split_name];
                }
            }
        }
        $la = 1;
        if ($la == 30) {
            print_r($player_season_daily_insert); exit();
        }
        $la++;
        if (!$test) {
            multi_insert(
                DATABASE,
                $daily_table,
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
