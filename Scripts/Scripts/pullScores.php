<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/sweetfunctions.php');

$countup = 0;
$player_stats = array();

date_default_timezone_set('America/Los_Angeles');
$date = date('Y-m-d');
if ($argv[1]) {
    $ds = $argv[1];
    $date = $argv[1];
}
$ts = date("Y-m-d H:i:s");
$hour = date("G");

$target = "http://espn.go.com/mlb/";
$source_code = scrape($target);
$source_code = return_between($source_code, "\"sport\":\"mlb\"", "\"sport\":\"nba\"", EXCL);

$teams_start = "\"name\":\"";
$teams_end = "\"";
$teams = parse_array_clean($source_code, $teams_start, $teams_end);

$time_start = "\"date\":\"";
$time_end = "00\",\"home\"";
$game_time = parse_array_clean($source_code, $time_start, $time_end);
$game_time[0] = split_string($game_time[0], "\"date\":\"", AFTER, EXCL);

$scores_start = "\"score\":";
$scores_end = ",";
$scores = parse_array_clean($source_code, $scores_start, $scores_end);

$types_start = "\"statusText\":\"";
$types_end = "\"";
$types = parse_array_clean($source_code, $types_start, $types_end);

$final_array = array(array('home', 'away', 'home_score', 'away_score','status','game_date','game_time','ts'));

// Since things are grouped in treat even and odds (home/away)
// seperately
$team_num = 1;
$odd = 0;
foreach ($teams as $team) {
    if ($odd === 0) {
        $final_array[$team_num]['home'] = $team;
        $odd = 1;
    } else {
        $final_array[$team_num]['away'] = $team;
        $odd = 0;
        $team_num++;
    }
}

$team_num = 1;
$odd = 0;
foreach ($scores as $score) {
    if ($odd === 0) {
        $final_array[$team_num]['home_score'] = $score;
        $odd = 1;
    } else {
        $final_array[$team_num]['away_score'] = $score;
        $odd = 0;
        $team_num++;
    }
}

foreach ($types as $i => $type) {
	// Start at array 1 because of colheads
	$final_array[$i + 1]['status']	= $type;	
	$final_array[$i+1]['ts'] = $ts;
}

foreach ($game_time as $i => $time) {
	$game_date = substr($time, 0, 8); 
	$game_year = substr($game_date, 0, 4);
	$game_month = substr($game_date, 4, 2);
	$game_day = substr($game_date, -2);
	$game_date = "$game_year-$game_month-$game_day";
	$time = substr($time, -4);
	$hour = substr($time, 0, 2);
	$minute = substr($time, -2);
	$time = "$hour:$minute:00";
	$final_array[$i + 1]['game_date'] = $game_date;
	$final_array[$i + 1]['game_time'] = $time;
}

print_r($final_array);


// Run function to export the data to mysql, backup a copy to csv,
// and leave a record in table_status -> in sweetfunctions.php
$table_name = 'live_scores_2014';
export_and_save($database, $table_name, $final_array, $date);

?>
