<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php'); 
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');

CONST HOUR_OFFSET = 3;

$final_odds = array(array(
	'home',
	'away',
	'casino',
	'game_date',
	'game_time',
	'max_home_odds',
	'min_home_odds',
	'median_home_odds',
	'max_away_odds',
	'min_away_odds',
	'median_away_odds',
	'max_home_odds_day',
	'min_home_odds_day',
	'median_home_odds_day',
	'max_away_odds_day',
	'min_away_odds_day',
	'median_away_odds_day',
	'max_home_odds_hour',
	'min_home_odds_hour',
	'median_home_odds_hour',
	'max_away_odds_hour',
	'min_away_odds_hour',
	'median_away_odds_hour',
	'max_home_pct_win',
	'min_home_pct_win',
	'median_home_pct_win',
	'max_away_pct_win',
	'min_away_pct_win',
	'median_away_pct_win',
	'max_home_pct_win_day',
	'min_home_pct_win_day',
	'median_home_pct_win_day',
	'max_away_pct_win_day',
	'min_away_pct_win_day',
	'median_away_pct_win_day',
	'max_home_pct_win_hour',
	'min_home_pct_win_hour',
	'median_home_pct_win_hour',
	'max_away_pct_win_hour',
	'min_away_pct_win_hour',
	'median_away_pct_win_hour',
	'last_home_odds',
	'last_away_odds',
	'last_home_pct_win',
	'last_away_pct_win',
	'home_odds_hour',
	'away_odds_hour'
));
$odds_stg = array();
$date = ds_modify($date, '-1 Day');
if ($argv[1]) {
	$date = $argv[1];
}	

$sql = 
	"SELECT * 
	FROM odds_2014 
	WHERE ds = '$date'";
$raw_odds = exe_sql($database, $sql);

foreach ($raw_odds as $odds) {
	$home = $odds['home'];
	$away = $odds['away'];
	$game_date = $odds['game_date'];
	$game_time = $odds['game_time'];
	$game_hour = substr($game_time, 0, 2);
	$odds_date = $odds['odds_date'];
	$odds_time = $odds['odds_time'];
	$odds_hour = substr($odds_time, 0, 2);
	$home_odds = $odds['home_odds'];
	$away_odds = $odds['away_odds'];
	$home_pct_win = $odds['home_pct_win'];
	$away_pct_win = $odds['away_pct_win'];
	$casino = $odds['casino'];

	$odds_stg[$home][$game_date.$game_time][$casino]['home'] = $home;
	$odds_stg[$home][$game_date.$game_time][$casino]['away'] = $away;
	$odds_stg[$home][$game_date.$game_time][$casino]['casino'] = $casino;
	$odds_stg[$home][$game_date.$game_time][$casino]['game_date'] = $game_date;
	$odds_stg[$home][$game_date.$game_time][$casino]['game_time'] = $game_time;
	$odds_stg[$home][$game_date.$game_time][$casino]['home_odds'][] = $home_odds;
	$odds_stg[$home][$game_date.$game_time][$casino]['away_odds'][] = $away_odds;
	$odds_stg[$home][$game_date.$game_time][$casino]['home_pct_win'][] = $home_pct_win;
	$odds_stg[$home][$game_date.$game_time][$casino]['away_pct_win'][] = $away_pct_win;
	$odds_stg[$home][$game_date.$game_time][$casino]['last_home_odds'] = $home_odds;
	$odds_stg[$home][$game_date.$game_time][$casino]['last_away_odds'] = $away_odds;
	$odds_stg[$home][$game_date.$game_time][$casino]['last_home_pct_win'] = $home_pct_win;
	$odds_stg[$home][$game_date.$game_time][$casino]['last_away_pct_win'] = $away_pct_win;
	if ($game_date == $odds_date) {
		$odds_stg[$home][$game_date.$game_time][$casino]['home_odds_day'][] = $home_odds;
		$odds_stg[$home][$game_date.$game_time][$casino]['away_odds_day'][] = $away_odds;
		$odds_stg[$home][$game_date.$game_time][$casino]['home_pct_win_day'][] = $home_pct_win;
    	$odds_stg[$home][$game_date.$game_time][$casino]['away_pct_win_day'][] = $away_pct_win;
		if ($odds_hour >= ($game_hour - HOUR_OFFSET)) {
			$odds_stg[$home][$game_date.$game_time][$casino]['home_odds_hour'][] = $home_odds;
			$odds_stg[$home][$game_date.$game_time][$casino]['away_odds_hour'][] = $away_odds;
			$odds_stg[$home][$game_date.$game_time][$casino]['home_pct_win_hour'][] = $home_pct_win;
    		$odds_stg[$home][$game_date.$game_time][$casino]['away_pct_win_hour'][] = $away_pct_win;
		}
	}
}

foreach ($odds_stg as $team_name => $team) {
	foreach ($team as $time_name => $time) {
		foreach ($time as $casino_name => $casino) {
			if (!$casino['home_odds']) {
				continue;
			}
			// NOTE: I did it so "max" pct win is really the min so it matches 
			// with odds (which is flipped)
			$max_home_odds = max($casino['home_odds']);
			$min_home_odds = min($casino['home_odds']);
			$median_home_odds = median($casino['home_odds']);
			$max_away_odds = max($casino['away_odds']);
			$min_away_odds = min($casino['away_odds']);
			$median_away_odds = median($casino['away_odds']);
			$odds_stg[$team_name][$time_name][$casino_name]['max_home_odds'] = $max_home_odds;
			$odds_stg[$team_name][$time_name][$casino_name]['min_home_odds'] = $min_home_odds;
			$odds_stg[$team_name][$time_name][$casino_name]['median_home_odds'] = $median_home_odds;
			unset($odds_stg[$team_name][$time_name][$casino_name]["home_odds"]);
			$odds_stg[$team_name][$time_name][$casino_name]['max_away_odds'] = $max_away_odds;
            $odds_stg[$team_name][$time_name][$casino_name]['min_away_odds'] = $min_away_odds;
            $odds_stg[$team_name][$time_name][$casino_name]['median_away_odds'] = $median_away_odds;
			unset($odds_stg[$team_name][$time_name][$casino_name]["away_odds"]);
		
			$max_home_odds_day = max($casino['home_odds_day']);
            $min_home_odds_day = min($casino['home_odds_day']);
            $median_home_odds_day = median($casino['home_odds_day']);
            $max_away_odds_day = max($casino['away_odds_day']);
            $min_away_odds_day = min($casino['away_odds_day']);
			$median_away_odds_day = median($casino['away_odds_day']);
			$odds_stg[$team_name][$time_name][$casino_name]['max_home_odds_day'] = $max_home_odds_day;
            $odds_stg[$team_name][$time_name][$casino_name]['min_home_odds_day'] = $min_home_odds_day;
            $odds_stg[$team_name][$time_name][$casino_name]['median_home_odds_day'] = $median_home_odds_day;
            unset($odds_stg[$team_name][$time_name][$casino_name]["home_odds_day"]);
            $odds_stg[$team_name][$time_name][$casino_name]['max_away_odds_day'] = $max_away_odds_day;
            $odds_stg[$team_name][$time_name][$casino_name]['min_away_odds_day'] = $min_away_odds_day;
            $odds_stg[$team_name][$time_name][$casino_name]['median_away_odds_day'] = $median_away_odds_day;
            unset($odds_stg[$team_name][$time_name][$casino_name]["away_odds_day"]);

			$max_home_odds_hour = max($casino['home_odds_hour']);
            $min_home_odds_hour = min($casino['home_odds_hour']);
            $median_home_odds_hour = median($casino['home_odds_hour']);
            $max_away_odds_hour = max($casino['away_odds_hour']);
            $min_away_odds_hour = min($casino['away_odds_hour']);
			$median_away_odds_hour = median($casino['away_odds_hour']);
			$odds_stg[$team_name][$time_name][$casino_name]['max_home_odds_hour'] = elvis($max_home_odds_hour, $max_home_odds_day);
            $odds_stg[$team_name][$time_name][$casino_name]['min_home_odds_hour'] = elvis($min_home_odds_hour, $min_home_odds_day);
            $odds_stg[$team_name][$time_name][$casino_name]['median_home_odds_hour'] = elvis($median_home_odds_hour, $median_home_odds_day);
			//unset($odds_stg[$team_name][$time_name][$casino_name]["home_odds_hour"]);
			$odds_stg[$team_name][$time_name][$casino_name]["home_odds_hour"] = json_encode($odds_stg[$team_name][$time_name][$casino_name]["home_odds_hour"]);
            $odds_stg[$team_name][$time_name][$casino_name]['max_away_odds_hour'] = elvis($max_away_odds_hour, $max_away_odds_day);
            $odds_stg[$team_name][$time_name][$casino_name]['min_away_odds_hour'] = elvis($min_away_odds_hour, $min_away_odds_day);
            $odds_stg[$team_name][$time_name][$casino_name]['median_away_odds_hour'] = elvis($median_away_odds_hour, $median_away_odds_day);
			//unset($odds_stg[$team_name][$time_name][$casino_name]["away_odds_hour"]);
			$odds_stg[$team_name][$time_name][$casino_name]["away_odds_hour"] = json_encode($odds_stg[$team_name][$time_name][$casino_name]["away_odds_hour"]);

			$max_home_pct_win = min($casino['home_pct_win']);
            $min_home_pct_win = max($casino['home_pct_win']);
            $median_home_pct_win = median($casino['home_pct_win']);
            $max_away_pct_win = min($casino['away_pct_win']);
            $min_away_pct_win = max($casino['away_pct_win']);
            $median_away_pct_win = median($casino['away_pct_win']);
            $odds_stg[$team_name][$time_name][$casino_name]['max_home_pct_win'] = $max_home_pct_win;
            $odds_stg[$team_name][$time_name][$casino_name]['min_home_pct_win'] = $min_home_pct_win;
            $odds_stg[$team_name][$time_name][$casino_name]['median_home_pct_win'] = $median_home_pct_win;
            unset($odds_stg[$team_name][$time_name][$casino_name]["home_pct_win"]);
            $odds_stg[$team_name][$time_name][$casino_name]['max_away_pct_win'] = $max_away_pct_win;
            $odds_stg[$team_name][$time_name][$casino_name]['min_away_pct_win'] = $min_away_pct_win;
            $odds_stg[$team_name][$time_name][$casino_name]['median_away_pct_win'] = $median_away_pct_win;
            unset($odds_stg[$team_name][$time_name][$casino_name]["away_pct_win"]);

			$max_home_pct_win_day = min($casino['home_pct_win_day']);
            $min_home_pct_win_day = max($casino['home_pct_win_day']);
            $median_home_pct_win_day = median($casino['home_pct_win_day']);
            $max_away_pct_win_day = min($casino['away_pct_win_day']);
            $min_away_pct_win_day = max($casino['away_pct_win_day']);
            $median_away_pct_win_day = median($casino['away_pct_win_day']);
            $odds_stg[$team_name][$time_name][$casino_name]['max_home_pct_win_day'] = $max_home_pct_win_day;
            $odds_stg[$team_name][$time_name][$casino_name]['min_home_pct_win_day'] = $min_home_pct_win_day;
            $odds_stg[$team_name][$time_name][$casino_name]['median_home_pct_win_day'] = $median_home_pct_win_day;
            unset($odds_stg[$team_name][$time_name][$casino_name]["home_pct_win_day"]);
            $odds_stg[$team_name][$time_name][$casino_name]['max_away_pct_win_day'] = $max_away_pct_win_day;
            $odds_stg[$team_name][$time_name][$casino_name]['min_away_pct_win_day'] = $min_away_pct_win_day;
            $odds_stg[$team_name][$time_name][$casino_name]['median_away_pct_win_day'] = $median_away_pct_win_day;
            unset($odds_stg[$team_name][$time_name][$casino_name]["away_pct_win_day"]);

            $max_home_pct_win_hour = min($casino['home_pct_win_hour']);
            $min_home_pct_win_hour = max($casino['home_pct_win_hour']);
            $median_home_pct_win_hour = median($casino['home_pct_win_hour']);
            $max_away_pct_win_hour = min($casino['away_pct_win_hour']);
            $min_away_pct_win_hour = max($casino['away_pct_win_hour']);
            $median_away_pct_win_hour = median($casino['away_pct_win_hour']);
            $odds_stg[$team_name][$time_name][$casino_name]['max_home_pct_win_hour'] = elvis($max_home_pct_win_hour, $max_home_pct_win_day);
            $odds_stg[$team_name][$time_name][$casino_name]['min_home_pct_win_hour'] = elvis($min_home_pct_win_hour, $min_home_pct_win_day);
            $odds_stg[$team_name][$time_name][$casino_name]['median_home_pct_win_hour'] = elvis($median_home_pct_win_hour, $median_home_pct_win_day);
            unset($odds_stg[$team_name][$time_name][$casino_name]["home_pct_win_hour"]);
            $odds_stg[$team_name][$time_name][$casino_name]['max_away_pct_win_hour'] = elvis($max_away_pct_win_hour, $max_away_pct_win_day);
            $odds_stg[$team_name][$time_name][$casino_name]['min_away_pct_win_hour'] = elvis($min_away_pct_win_hour, $min_away_pct_win_day);
            $odds_stg[$team_name][$time_name][$casino_name]['median_away_pct_win_hour'] = elvis($median_away_pct_win_hour, $median_away_pct_win_day);
            unset($odds_stg[$team_name][$time_name][$casino_name]["away_pct_win_hour"]);

			$final_odds[] = $odds_stg[$team_name][$time_name][$casino_name];
		}
	}
}

// Run function to export the data to mysql, backup a copy to csv,
// and leave a record in table_status -> in sweetfunctions.php
//print_r($final_odds);
$table_name = 'odds_aggregate_2014';
export_and_save($database, $table_name, $final_odds, $date);

?>
