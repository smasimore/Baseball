<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');

$countup = 0;
$player_stats = array();

date_default_timezone_set('America/Los_Angeles');
$date = date('Y-m-d');
if (idx($argv, 1) !== null) {
    $ds = $argv[1];
    $date = $argv[1];
}
$ts = date("Y-m-d H:i:s");
$hour = date("G");

// Now a version of a cached scores script is embedded in the MLB page on ESPN.
// Grab that link to parse first and then pull the scores.
$target = "http://espn.go.com/mlb/";
$source_code = scrape($target);
$source_code = return_between(
	$source_code,
	'espn.scoreboard.activeLeague = "mlb"',
	'"name":"Major League Baseball"',
	EXCL
);

$teams_start = 'abbreviation":"';
$teams_end = '"';
$teams = parse_array_clean($source_code, $teams_start, $teams_end);

$time_start = '"date":"';
$time_end = 'Z';
$game_time = parse_array_clean($source_code, $time_start, $time_end);

$scores_start = '"score":"';
$scores_end = '"';
$scores = parse_array_clean($source_code, $scores_start, $scores_end);

$types_start = '"summary":"';
$types_end = '"';
$types = parse_array_clean($source_code, $types_start, $types_end);

$final_array = array();
$colheads = array(
	'home',
	'away',
	'home_score',
	'away_score',
	'status',
	'game_date',
	'game_time',
	'season',
	'ts',
	'ds'
);

// Since things are grouped in treat even and odds (home/away)
// seperately
$game_num = 0;
foreach ($teams as $i => $team) {
    if ($i % 2 === 0) {
        $final_array[$game_num]['home'] = $team;
    } else {
        $final_array[$game_num]['away'] = $team;
        $game_num++;
    }
}

$game_num = 0;
foreach ($scores as $i => $score) {
    if ($i % 2 === 0) {
        $final_array[$game_num]['home_score'] = $score;
    } else {
        $final_array[$game_num]['away_score'] = $score;
        $game_num++;
    }
}

foreach ($types as $i => $type) {
	$final_array[$i]['status']	= $type;	
	$final_array[$i]['ts'] = $ts;
}

foreach ($game_time as $i => $time) {
	$final_array[$i]['game_date'] = substr($time, 0, 10);
	$final_array[$i]['game_time'] = split_string($time, 'T', AFTER, EXCL);
	$final_array[$i]['season'] = substr($time, 0, 4);
	$final_array[$i]['ds'] = $date;
}

$insert_table = 'live_scores';
multi_insert(
	DATABASE,
	$insert_table,
	$final_array,
	$colheads
);

?>
