<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/sweetfunctions.php');
date_default_timezone_set('America/Los_Angeles');

function convertRetroDateToDs($season, $date) {
    $month = substr($date, 0, 2);
    $day = substr($date, -2);
    $ds = "$season-$month-$day";
    return $ds;
}

// Set initial values for arrays (or add 0 if already there)
function initializePlayerArray($game_stat, $split) {
    global $player_daily, $player_career;
    $player_id = $game_stat['player_id'];
    $player_daily[$player_id][$split]['player_id'] = $player_id;
    $player_daily[$player_id][$split]['player_name'] = format_header($game_stat['player_name']);
    $player_daily[$player_id][$split]['season'] = $game_stat['season'];
    //$player_daily[$player_id][$split]['ds'] = $game_stat['ds'];
    $player_daily[$player_id][$split]['split'] = $split;
    $player_daily[$player_id][$split]['singles'] += 0;
    $player_daily[$player_id][$split]['doubles'] += 0;
    $player_daily[$player_id][$split]['triples'] += 0;
    $player_daily[$player_id][$split]['home_runs'] += 0;
    $player_daily[$player_id][$split]['walks'] += 0;
    $player_daily[$player_id][$split]['strikeouts'] += 0;
    $player_daily[$player_id][$split]['ground_outs'] += 0;
    $player_daily[$player_id][$split]['fly_outs'] += 0;
    $player_daily[$player_id][$split]['plate_appearances'] += 0;
    $player_career[$player_id][$split]['player_id'] = $player_id;
    $player_career[$player_id][$split]['player_name'] = format_header($game_stat['player_name']);
    $player_career[$player_id][$split]['season'] = $game_stat['season'];
    //$player_career[$player_id][$split]['ds'] = $game_stat['ds'];
    $player_career[$player_id][$split]['split'] = $split;
    $player_career[$player_id][$split]['singles'] += 0;
    $player_career[$player_id][$split]['doubles'] += 0;
    $player_career[$player_id][$split]['triples'] += 0;
    $player_career[$player_id][$split]['home_runs'] += 0;
    $player_career[$player_id][$split]['walks'] += 0;
    $player_career[$player_id][$split]['strikeouts'] += 0;
    $player_career[$player_id][$split]['ground_outs'] += 0;
    $player_career[$player_id][$split]['fly_outs'] += 0;
    $player_career[$player_id][$split]['plate_appearances'] += 0;
}

// Function to add event impact to daily and careers arrays
function updateEvent($game_stat, $split) {
    global $player_daily, $player_career;
    initializePlayerArray($game_stat, $split);
    $player_id = $game_stat['player_id'];

    $event = $game_stat['event_name'];
    $player_daily[$player_id][$split]['plate_appearances'] += 1;
    $player_career[$player_id][$split]['plate_appearances'] += 1;
    switch ($event) {
        case 'single':
            $player_daily[$player_id][$split]['singles'] += 1;
            $player_career[$player_id][$split]['singles'] += 1;
            break;
        case 'double':
            $player_daily[$player_id][$split]['doubles'] += 1;
            $player_career[$player_id][$split]['doubles'] += 1;
            break;
        case 'triple':
            $player_daily[$player_id][$split]['triples'] += 1;
            $player_career[$player_id][$split]['triples'] += 1;
            break;
        case 'homerun':
            $player_daily[$player_id][$split]['home_runs'] += 1;
            $player_career[$player_id][$split]['home_runs'] += 1;
            break;
        case 'walk':
            $player_daily[$player_id][$split]['walks'] += 1;
            $player_career[$player_id][$split]['walks'] += 1;
            break;
        case 'strikeout':
            $player_daily[$player_id][$split]['strikeouts'] += 1;
            $player_career[$player_id][$split]['strikeouts'] += 1;
            break;
        case 'ground_out':
            $player_daily[$player_id][$split]['ground_outs'] += 1;
            $player_career[$player_id][$split]['ground_outs'] += 1;
            break;
        case 'fly_out':
            $player_daily[$player_id][$split]['fly_outs'] += 1;
            $player_career[$player_id][$split]['fly_outs'] += 1;
            break;
        // Subtract the PA if somehow they get through to here
        default:
            $player_daily[$player_id][$split]['plate_appearances'] -= 1;
            $player_career[$player_id][$split]['plate_appearances'] -= 1;
            break;
    }
}

// Function to split out event by situation (home, vsright, etc.)
function updateDailyStats($game_stat) {
    // Update Total split
    updateEvent($game_stat, 'Total');
    // Update HomeAwawy split
    if ($game_stat['home_away'] == 'H') {
        updateEvent($game_stat, 'Home');
    } else {
        updateEvent($game_stat, 'Away');
    }
    // Update PicherHand split (note ?/Unknown pitchers skipped)
    if ($game_stat['vs_hand'] == 'R') {
        updateEvent($game_stat, 'VsRight');
    } elseif ($game_stat['vs_hand'] == 'L') {
        updateEvent($game_stat, 'VsLeft');
    }
    switch ($game_stat['situation']) {
        case 'noneon':
            updateEvent($game_stat, 'NoneOn');
            break;
        case 'runnerson':
            updateEvent($game_stat, 'RunnersOn');
            break;
        case 'scoringpos':
            updateEvent($game_stat, 'ScoringPos');
            break;
        case 'basesloaded':
            updateEvent($game_stat, 'BasesLoaded');
            break;
        case 'basesloaded2out':
            updateEvent($game_stat, 'BasesLoaded2Out');
            break;
        default:
            break;
    }
}

function updateSeasonStartEnd($season) {
    global $season_start, $season_end;
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
}

function pullBattingData($season, $date) {
    $season_data = null;
    $sql = 
       "SELECT lower(concat(c.first, '_', c.last)) AS player_name,
       a.resp_bat_id AS player_id,
       a.season,
       a.ds,
       a.home_away,
       a.pit_hand_cd AS vs_hand,
       a.situation,
       CASE
           WHEN (a.event = 2
                 AND battedball_cd = 'G') THEN 'ground_out'
           WHEN (a.event = 2
                 AND battedball_cd != 'G') THEN 'fly_out'
           WHEN a.event = 3 THEN 'strikeout'
           WHEN a.event in(14,15,16) THEN 'walk'
           WHEN a.event = 20 THEN 'single'
           WHEN a.event = 21 THEN 'double'
           WHEN a.event = 22 THEN 'triple'
           WHEN a.event = 23 THEN 'homerun'
           ELSE 'other'
       END AS event_name
    FROM
        (SELECT event_cd AS event,
        season,
        concat(substr(game_id,4,4), '-', substr(game_id,8,2), '-', substr(game_id,10,2)) AS ds,
        CASE
            WHEN bat_home_id = 1 THEN 'H'
            ELSE 'A'
        END AS home_away,
        pit_hand_cd,
        CASE
            WHEN start_bases_cd = 0 THEN 'noneon'
            WHEN start_bases_cd = 1 THEN 'runnerson'
            WHEN (start_bases_cd = 7
                AND outs_ct != 2) THEN 'basesloaded'
              WHEN (start_bases_cd = 7
                    AND outs_ct = 2) THEN 'basesloaded2out'
            ELSE 'scoringpos'
          END AS situation,
          battedball_cd,
          resp_bat_id
    FROM events
    WHERE season = '$season'
    AND substr(game_id,8,4) = '$date') a
    JOIN id c ON a.resp_bat_id = c.id";
    $season_data = exe_sql(DATABASE, $sql);
    return $season_data;
}

function updateToDateData($season, $ds) {
    global $player_daily, $player_career;
    $player_yesterday = exe_sql(DATABASE,
        "SELECT * 
        FROM retrosheet_historical_batting 
        WHERE season = '$season' 
        AND ds = '$ds'"
    );
    $player_daily = null;
    if ($player_yesterday) {
        foreach ($player_yesterday as $stat) {
            $player_id = $stat['player_id'];
            $split = $stat['split'];
            $player_daily[$player_id][$split] = $stat;
        }
    }
    $player_career = null;
    $player_yesterday_career = exe_sql(DATABASE, 
        "SELECT * 
        FROM retrosheet_historical_batting_career 
        WHERE season = '$season' 
        AND ds = '$ds'"
    );
    if ($player_yesterday_career) {
        foreach ($player_yesterday_career as $stat) {
            $player_id = $stat['player_id'];
            $split = $stat['split'];
            $player_career[$player_id][$split] = $stat;
        }
    }
}

// Prompt user to confirm since this will overwrite a full seasons' data
/*
echo "\n"."Are you sure you want to overwrite historical data? (y/n) ";
$confirm = fgets(STDIN);
if ($confirm !== 'y') {
    exit();
}
 */

// Vars
$final_player_daily_clean = array(array(
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
));
$player_daily = array();
$player_career = array();
$season_start = null;
$season_end = null;
$daily_table = 'retrosheet_historical_batting';
$career_table = 'retrosheet_historical_batting_career';

for ($season = 1962; $season < 2014; $season++) {
    send_email("new season $season","",'d');
    if ($season > 1961) {
        if ($season == 1962) {
            $previous_season_end = '1961-10-10';
        } else {
            $previous_season_end = ds_modify($season_end, '+1 day');
        }
        $last_season = $season - 1;
    }
    updateSeasonStartEnd($season);
    if ($season > 1951) {
        updateToDateData($last_season, $previous_season_end);
	print_r($player_daily);
	exit('oh hey');
        $final_player_career = $final_player_daily_clean;
        foreach ($player_career as $name => $split) {
            foreach ($split as $stat) {
                $stat['season'] = $season;
                $final_player_career[] = $stat;
            }
        } 
        echo "Backfilling $last_season into career table"."\n";
        export_and_save(DATABASE, $career_table, $final_player_career, $season_start);
    }
    for ($ds = $season_start; $ds <= $season_end; $ds = ds_modify($ds, '+1 day')) {  
        echo $ds."\n";
        $retro_ds = return_between($ds, "-", "-", EXCL).substr($ds, -2);
        // Enter today's game data into tomorrow's ds
        $entry_ds = ds_modify($ds, '+1 day');
        $daily_stats = pullBattingData($season, $retro_ds);
        // Pull cumulative season/career stats to be added to
        updateToDateData($season, $ds);
        if (!$daily_stats) {
            $final_player_daily = $final_player_daily_clean;
            $final_player_career = $final_player_daily_clean;
            foreach ($player_daily as $name => $split) {
                foreach ($split as $stat) {
                    $final_player_daily[] = $stat;
                }
            }
            foreach ($player_career as $name => $split) {
                foreach ($split as $stat) {
                    $final_player_career[] = $stat;
                }
            }
            export_and_save(DATABASE, $daily_table, $final_player_daily, $entry_ds);
            export_and_save(DATABASE, $career_table, $final_player_career, $entry_ds);
            continue;
        }
        foreach ($daily_stats as $game_stat) {
            // Filter out unneccesary events
            $event = $game_stat['event_name'];
            if ($event == 'other') {
                continue;
            }
            updateDailyStats($game_stat);
        }
        // Prep the tables for insertion into mysql
        $final_player_daily = $final_player_daily_clean;
        $final_player_career = $final_player_daily_clean;
        foreach ($player_daily as $name => $split) {
            foreach ($split as $stat) { 
                $final_player_daily[] = $stat;
            }
        }
        foreach ($player_career as $name => $split) {
            foreach ($split as $stat) {
                $final_player_career[] = $stat;
            }
        }
        export_and_save(DATABASE, $daily_table, $final_player_daily, $entry_ds);
        export_and_save(DATABASE, $career_table, $final_player_career, $entry_ds);
    }
}

?>
