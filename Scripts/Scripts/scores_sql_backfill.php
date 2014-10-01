<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php'); 
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');

$date = $argv[1];
if (!$argv[1]) {
	exit('No Date Entered!');
}

$scores_2014 = exe_sql('baseball',
	"SELECT DISTINCT date, home, away, runs_h, runs_a
	FROM schedule_scores_2014"
);
$final_array = array(array(
	'home',
	'away',
	'home_score',
	'away_score',
	'status',
	'game_date',
	'game_time',
	'ts'
));
foreach ($scores_2014 as $scores) {
	$raw_date = $scores['date'];
	$month = return_between($raw_date, " ", " ", EXCL);
	$day = split_string($raw_date, $month." ", AFTER, EXCL);
	if ($day < 10) {
		$day = "0".$day;
	}
	$month = '0'.$month_mapping[$month];
	$game_date = "2014-$month-$day";
	if ($date !== $game_date) {
		continue;
	}
	$home = strtoupper($scores['home']);
	$away = strtoupper($scores['away']);
	$home_score = $scores['runs_h'];
	$away_score = $scores['runs_a'];

	if (!$final_array[$game_date.$home]) {
		$final_array[$game_date.$home]['home'] = $home;
		$final_array[$game_date.$home]['away'] = $away;
		$final_array[$game_date.$home]['home_score'] = $home_score;
		$final_array[$game_date.$home]['away_score'] = $away_score;
		$final_array[$game_date.$home]['status'] = 'F';
		$final_array[$game_date.$home]['game_date'] = $game_date;
		$final_array[$game_date.$home]['game_time'] = 1;
	} else {
		$final_array[$game_date.$home."2"]['home'] = $home;
        $final_array[$game_date.$home."2"]['away'] = $away;
        $final_array[$game_date.$home."2"]['home_score'] = $home_score;
        $final_array[$game_date.$home."2"]['away_score'] = $away_score;
        $final_array[$game_date.$home."2"]['status'] = 'F';
        $final_array[$game_date.$home."2"]['game_date'] = $game_date;
        $final_array[$game_date.$home."2"]['game_time'] = 2;
	}
}


// Run function to export the data to mysql, backup a copy to csv,
// and leave a record in table_status -> in sweetfunctions.php
$table_name = 'live_scores_2014';
export_and_save($database, $table_name, $final_array, $date);

?>
