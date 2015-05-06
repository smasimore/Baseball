<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/ESPNParseUtils.php');
include(HOME_PATH.'Scripts/Include/DateTimeUtils.php');
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');

$countup = 0;
$player_stats = array();

date_default_timezone_set('America/Los_Angeles');
$date = date('Y-m-d');
$season = substr($date, 0, 4);
$ts = date("Y-m-d H:i:s");

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
	'gameid',
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
	// If type is a date game has not started.
	$type = strpos($type, $season) !== false ? 'Not Started' : $type;
	$final_array[$i]['status']	= $type;	
	$final_array[$i]['ts'] = $ts;
}

// Create var for overall game date since pulls after midnight (which we need
// for extra innings west coast games) should be logged on actual game date
// instead of pull date (we'll need this in the delete query later).
$overall_game_date = null;
foreach ($game_time as $i => $time) {
	// ESPN has decided to be annoying and have it's time in GMT...convert
	// this back to EST.
	$game_date = substr($time, 0, 10);
	$time = split_string($time, 'T', AFTER, EXCL);
	list($converted_date, $converted_time) =
		DateTimeUtils::getESTDateTimeFromGMT($game_date, $time);
	$overall_game_date = $converted_date;
	$final_array[$i]['gameid'] = ESPNParseUtils::createGameID(
		$final_array[$i]['home'],
		$converted_date,
		$converted_time
	);
	$final_array[$i]['game_date'] = $converted_date;
	$final_array[$i]['game_time'] = $converted_time;
	$final_array[$i]['season'] = $season;
	$final_array[$i]['ds'] = date('Y-m-d');
}

$insert_table = 'live_scores';
// First delete previous entries (where score isn't Final) and
// write updated scores to the table.
exe_sql(
	DATABASE,
	sprintf(
		"DELETE FROM %s
		WHERE game_date = '%s'",
		$insert_table,
		$overall_game_date
	),
	'delete'
);
multi_insert(
	DATABASE,
	$insert_table,
	$final_array,
	$colheads
);

?>
