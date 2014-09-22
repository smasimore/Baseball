<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('mysqli.reconnect', '1');
ini_set('mysqli.connect_timeout', '-1');
ini_set('default_socket_timeout', '-1');
ini_set('max_execution_time', '-1');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/LIB_http.php');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/LIB_parse.php');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/LIB_mysql_updatedbyus.php');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/sweetfunctions.php');
$database = 'baseball';

$schedule_2013 = exe_sql($database,
	'SELECT *
	FROM schedule_scores_2013'
	);
$count_games2013 = count($schedule_2013);
$player_batting_expanded_2013 = exe_sql($database,
	'SELECT *
	FROM batting_final_magic_2013'
	);
$pitchingstats_2013 = exe_sql($database,
	'SELECT *
	FROM pitching_2013'
	);
$startingpitchers2013 = exe_sql($database,
	'SELECT *
	FROM startingpitchers_2013'
	);
$starting_pitcher_era_map_2013 = exe_sql($database,
	'SELECT *
	FROM era_map_2013'
	);
//$pitcher_batting_2012 = exe_sql($database,
//	'SELECT *
//	FROM pitcher_batting_2012'
//	);
//$pitcher_batting_2013 = exe_sql($database,
//	'SELECT *
//	FROM pitcher_batting_2013'
//	);
$fieldingstats_2012 = exe_sql($database,
	'SELECT *
	FROM fielding_2012'
	);
$fieldingstats_2013 = exe_sql($database,
	'SELECT *
	FROM fielding_2013'
	);

foreach ($schedule_2013 as $i => $schedule) {

    $date = fixSQLjson($schedule["date"]);
    $lineup_h = fixSQLjson($schedule["lineup_h"]);
    $lineup_h = json_decode($lineup_h, true);
    $lineup_a = fixSQLjson($schedule["lineup_a"]);
    $lineup_a = json_decode($lineup_a, true);
    $home_odds = fixSQLjson($schedule["home_odds"]);
    $home_odds = json_decode($home_odds, true);

    $schedule_2013[$i]["date"] = $date;
    $schedule_2013[$i]["lineup_h"] = $lineup_h;
    $schedule_2013[$i]["lineup_a"] = $lineup_a;
    $schedule_2013[$i]["home_odds"] = $home_odds;
}

function checkGameID($gameid, &$gamearray) {

	$i = 10;
	$new_gameid = $gameid.$i;
	while (in_array($new_gameid, $gamearray)) {
		$new_gameid += 1;
	}
	array_push($gamearray, $new_gameid);
	return $new_gameid;
}

function getPitcherStats($pitcher_batting, $year, $stats) {

	$player_name = $stats['player_name'];
	$hits = $stats['hits'];
	$doubles = $stats['doubles'];
	$triples = $stats['triples'];
	$home_runs = $stats['home_runs'];
	$hit_by_pitch = $stats['hit_by_pitch'];
	$intentional_walks = $stats['intentional_walks'];
	$walks = $stats['walks'];
	$all_walks = $walks + $hit_by_pitch + $intentional_walks;
	$strikeouts = $stats['strikeouts'];
	$ground_balls = $stats['ground_balls'];
	$fly_balls = $stats['fly_balls'];
	$singles = $hits - $doubles - $triples - $home_runs;
	$ground_ball_rate = $ground_balls / ($ground_balls + $fly_balls);
	$at_bats_walks = $stats['total_batters_faced'];
	$fielding_outs = $at_bats_walks - $hits - $strikeouts - $all_walks;

	$pitcher_batting[$year][$player_name]['player_name'] = $player_name;
	$pitcher_batting[$year][$player_name]['pct_single'] = $singles / $at_bats_walks;
	$pitcher_batting[$year][$player_name]['pct_double'] = $doubles / $at_bats_walks;
	$pitcher_batting[$year][$player_name]['pct_triple'] = $triples / $at_bats_walks;
	$pitcher_batting[$year][$player_name]['pct_home_run'] = $home_runs / $at_bats_walks;
	$pitcher_batting[$year][$player_name]['pct_walk'] = $all_walks / $at_bats_walks;
	$pitcher_batting[$year][$player_name]['pct_strikeout'] = $strikeouts / $at_bats_walks;
	$pitcher_batting[$year][$player_name]['pct_ground_out'] = ($fielding_outs * $ground_ball_rate) / $at_bats_walks;
	$pitcher_batting[$year][$player_name]['pct_fly_out'] = ($fielding_outs * (1 - $ground_ball_rate)) / $at_bats_walks;
	$pitcher_batting[$year][$player_name]['gbr'] = $ground_ball_rate;

	return $pitcher_batting;
}

function getAveragePitcherStats($pitcher_batting, $year, $stats) {

	$hits = $stats['hits'];
	$doubles = $stats['doubles'];
	$triples = $stats['triples'];
	$home_runs = $stats['home_runs'];
	$hit_by_pitch = $stats['hit_by_pitch'];
	$intentional_walks = $stats['intentional_walks'];
	$walks = $stats['walks'];
	$all_walks = $walks + $hit_by_pitch + $intentional_walks;
	$strikeouts = $stats['strikeouts'];
	$ground_balls = $stats['ground_balls'];
	$fly_balls = $stats['fly_balls'];
	$singles = $hits - $doubles - $triples - $home_runs;
	$ground_ball_rate = $ground_balls / ($ground_balls + $fly_balls);
	$at_bats_walks = $stats['total_batters_faced'];
	$fielding_outs = $at_bats_walks - $hits - $strikeouts - $all_walks;

	$pitcher_batting[$year]['pct_single'] += $singles / $at_bats_walks;
	$pitcher_batting[$year]['pct_double'] += $doubles / $at_bats_walks;
	$pitcher_batting[$year]['pct_triple'] += $triples / $at_bats_walks;
	$pitcher_batting[$year]['pct_home_run'] += $home_runs / $at_bats_walks;
	$pitcher_batting[$year]['pct_walk'] += $all_walks / $at_bats_walks;
	$pitcher_batting[$year]['pct_strikeout'] += $strikeouts / $at_bats_walks;
	$pitcher_batting[$year]['pct_ground_out'] += ($fielding_outs * $ground_ball_rate) / $at_bats_walks;
	$pitcher_batting[$year]['pct_fly_out'] += ($fielding_outs * (1 - $ground_ball_rate)) / $at_bats_walks;

	return $pitcher_batting;
}

$player_batting_2013 = array();
foreach ($player_batting_expanded_2013 as $name) {
	$player_name = $name['player_name'];
	$stats = json_decode($name['stats'], true);
	foreach ($stats as $year => $data) {
		$player_batting_2013[$year][$player_name] = $data;
	}		
}

$starting_pitcher_info_2013 = array();
foreach ($starting_pitcher_era_map_2013 as $pitcher) {
	$player_name = $pitcher['name'];
	$starting_pitcher_info_2013[$player_name]['hand'] = $pitcher['hand'];
	$starting_pitcher_info_2013[$player_name]['2013bin'] = $pitcher['2013bin'];
	$starting_pitcher_info_2013[$player_name]['2012bin'] = $pitcher['2012bin'];
	$starting_pitcher_info_2013[$player_name]['era'] = $pitcher['era'];
}

/*
$pitcher_batting = array();
$pitcher_average = array();
foreach ($pitcher_batting_2012 as $stats) {
	if ($stats['innings_pitched'] < 18) {
		continue;
	}
	$pitcher_batting = getPitcherStats($pitcher_batting, '2012', $stats);
	$pitcher_average = getAveragePitcherStats($pitcher_average, '2012', $stats);
}
foreach ($pitcher_batting_2013 as $stats) {
	if ($stats['innings_pitched'] < 18) {
		continue;
	}
	$pitcher_batting = getPitcherStats($pitcher_batting, '2013', $stats);
	$pitcher_average = getAveragePitcherStats($pitcher_average, '2013', $stats);
}

$pitcher_average_final = array();
foreach ($pitcher_average as $year => $split) {
	foreach ($split as $stat_name => $data) {
		$stat_denom = array_sum($pitcher_average[$year]);
		$avg_stat = $data / $stat_denom;
		$pitcher_average_final[$year][$stat_name] = $avg_stat;
	}
}

$pitcher_magic_final = array();
foreach ($pitcher_batting as $year => $player) {
	foreach ($player as $player_name => $stats) {
		$player_name = $stats['player_name'];
		foreach ($stats as $stat_name => $data) {
			if ($stat_name == 'player_name' || $stat_name == 'gbr') {
				continue;
			}
			$pitcher_magic_final[$year][$player_name][$stat_name] = $data - $pitcher_average_final[$year][$stat_name];
		}
	}
}
*/

$fieldingstats_2012_total = array();
foreach ($fieldingstats_2012 as $stats) {
	$fieldingstats_2012_total[$stats['player_name']]['putouts'] += $stats['putouts']; 
	$fieldingstats_2012_total[$stats['player_name']]['assists'] += $stats['assists'];
	$fieldingstats_2012_total[$stats['player_name']]['innings'] += $stats['innings'];  
	$fieldingstats_2012_total[$stats['player_name']]['errors'] += $stats['errors']; 
}
$fieldingstats_2012_average = array();
foreach ($fieldingstats_2012_total as $name => $stats) {
	$fieldingstats_2012_average['errors'] += $stats['errors'];
	$fieldingstats_2012_average['total'] += $stats['errors'] + $stats['putouts'] + $stats['assists'];
	$fieldingstats_2012_average['count'] += 1;
}

$fieldingstats_2013_total = array();
foreach ($fieldingstats_2013 as $stats) {
	$fieldingstats_2013_total[$stats['player_name']]['putouts'] += $stats['putouts']; 
	$fieldingstats_2013_total[$stats['player_name']]['assists'] += $stats['assists'];
	$fieldingstats_2013_total[$stats['player_name']]['innings'] += $stats['innings'];  
	$fieldingstats_2013_total[$stats['player_name']]['errors'] += $stats['errors']; 
}
$fieldingstats_2013_average = array();
foreach ($fieldingstats_2013_total as $name => $stats) {
	$fieldingstats_2013_average['errors'] += $stats['errors'];
	$fieldingstats_2013_average['total'] += $stats['errors'] + $stats['putouts'] + $stats['assists'];
	$fieldingstats_2013_average['count'] += 1;
}

$fieldingstats_average['2012']['errors'] = $fieldingstats_2012_average['errors'] / $fieldingstats_2012_average['count'];
$fieldingstats_average['2012']['total'] = $fieldingstats_2012_average['total'] / $fieldingstats_2012_average['count'];
$fieldingstats_average['2013']['errors'] = $fieldingstats_2013_average['errors'] / $fieldingstats_2013_average['count'];
$fieldingstats_average['2013']['total'] = $fieldingstats_2013_average['total'] / $fieldingstats_2013_average['count'];

$master_table = array();
$gamearray = array();
$col_heads = array(
	'gameid',
	'date_i',
	'month_i',
	'day_i',									
	'home_i',
	'away_i',
	'pitcher_h_i',
	'pitcher_a_i',
	'pitcher_h_handedness_i',
	'pitcher_a_handedness_i',
	'pitcher_h_2012_era_bucket_i',
	'pitcher_a_2012_era_bucket_i',
	'pitcher_h_2013_era_bucket_i',
	'pitcher_a_2013_era_bucket_i',
	'pitcher_h_era',
	'pitcher_a_era',
	'pitcher_era_delta',
	'runs_h_i',									
	'runs_a_i',									
	'home_team_winner',
	'upset',
	'home_odds',
	'away_odds',
	'home_pct_win',
	'away_pct_win',
	'fielding_mult_2012_home',
	'fielding_mult_2013_home',
	'fielding_mult_2012_away',
	'fielding_mult_2013_away',
	'lineup_h_stats',
	'lineup_a_stats',
	'stadium_stats',
	//'pitcher_batting_stats',
	'gamenumber'
);
array_push($master_table, $col_heads);

$starting_pitchers_2013 = array();
$all_starting_pitchers_2013 = array();
foreach ($startingpitchers2013 as $day) {
	$date = $day['ds'];
	$day_games = json_decode($day['data'], true);
	foreach ($day_games as $team => $game) {
		foreach ($game as $gamenum => $pitchers) {
			$team = convertStartingPitcherTeams($team);
			$starting_pitchers_2013[$date][$team][$gamenum] = $pitchers;
			foreach ($pitchers as $pitcher) {
				if (!in_array($pitcher, $all_starting_pitchers_2013)) {
					array_push($all_starting_pitchers_2013, $pitcher);
				}
			}
		}
	}
}

foreach ($schedule_2013 as $i => $game) {

	$progress = round($i/$count_games2013 * 100);
	if ($i % 100 == 0) {
		echo 'Progress: '.$progress.'%'."\n";
	}
	if ($i == $count_games2013) {
		echo 'Last Game...Get Excited!'."\n";
	}

	$team_stats = array();
	$master_row = array();
	$date_i = $game['date'];
	$month = $month_mapping[substr($game['date'], 5, 3)];
	$day = str_replace(" ", "0", substr($game['date'], -2));
	$date_map = $month.$day;
	$gameid = 20130000+$date_map;
	$gameid = checkGameID($gameid, $gamearray);
	$away_i = strtoupper($game['away']);
	$home_i = strtoupper($game['home']);
	//Add data fix from some random fluke
	if ($date_map == 723 && $home_i == 'CIN') {
		$home_i = 'SF';
		$away_i = 'CIN';
	} 
	$stadium = $stadiums[$home_i];

	//Skip any games that are postponed
	if ($game['pitcher_h'] == 'postponed' || !isset($game['pitcher_h'])) {
		$master_row['gameid'] = $gameid;
		$master_row['date_i'] = $date_i;
		$master_row['month_i'] = $month;
		$master_row['day_i'] = $day;						
		$master_row['home_i'] = 'postponed';
		$master_row['away_i'] = 'postponed';
		$master_row['pitcher_h_i'] = 'postponed';
		$master_row['pitcher_a_i'] = 'postponed';
		continue;
	}

	$starting_pitchers = $starting_pitchers_2013[$date_map][$home_i][0];
	if (!isset($starting_pitchers)) {
		if ($double_header == 1) {
			$starting_pitchers = $starting_pitchers_2013[$date_map][$home_i][1];
			$double_header = 0;
		} else {
			$starting_pitchers = $starting_pitchers_2013[$date_map][$home_i][2];
			$double_header = 1;
		}
	}
	$pitcher_h_i = checkDuplicatePlayers($starting_pitchers['home'], $home_i, $duplicate_names);
	$pitcher_a_i = checkDuplicatePlayers($starting_pitchers['away'], $away_i, $duplicate_names);
	$pitcher_h_handedness_i = $starting_pitcher_info_2013[$pitcher_h_i]['hand'];
	$pitcher_a_handedness_i = $starting_pitcher_info_2013[$pitcher_a_i]['hand'];
	$pitcher_h_2012_era_bucket_i = $starting_pitcher_info_2013[$pitcher_h_i]['2012bin'];
	$pitcher_a_2012_era_bucket_i = $starting_pitcher_info_2013[$pitcher_a_i]['2012bin'];
	$pitcher_h_2013_era_bucket_i = $starting_pitcher_info_2013[$pitcher_h_i]['2013bin'];
	$pitcher_a_2013_era_bucket_i = $starting_pitcher_info_2013[$pitcher_a_i]['2013bin'];
	$pitcher_h_era = $starting_pitcher_info_2013[$pitcher_h_i]['era'];
	$pitcher_a_era = $starting_pitcher_info_2013[$pitcher_a_i]['era'];
	$pitcher_era_delta = $pitcher_h_era - $pitcher_a_era;

	if (!$pitcher_h_i || !$pitcher_a_i) {
		error_log("Missing Pitchers: ".$date_map."   ".$home_i."\n", 3, "/Users/baseball/Desktop/Baseball/Scripts/Errors/battingsim_missingpitchers.log");
	}

	$runs_h_i = $game['runs_h'];
	$runs_a_i = $game['runs_a'];
	if ($runs_h_i > $runs_a_i) {
		$home_team_winner = 1;
	} else {
		$home_team_winner = 0;
	}

	$home_odds = $game['home_odds']['odds_h'];
	$away_odds = $game['home_odds']['odds_a'];
	// One more data anomoly 
	if ($date_map == 609) {
		if ($home_i == 'LAD') {
			$home_odds = 157;
			$away_odds = -167;
		} elseif ($home_i == 'DET') {
			$home_odds = -120;
			$away_odds = 110;
		}
	}
	if ($home_odds < 0) {
		$home_pct_win = ($home_odds * -1) / (($home_odds * -1) + 100);
	} else {
		$home_pct_win = 100 / ($home_odds + 100);
	}
	if ($away_odds < 0) {
		$away_pct_win = ($away_odds * -1) / (($away_odds * -1) + 100);
	} else {
		$away_pct_win = 100 / ($away_odds + 100);
	}

	$upset = 1;
	if (($home_odds < 0 && $home_team_winner == 1) || ($home_odds > 0 && $home_team_winner == 0)) {
		$upset = 0;
	} 

	$stadium_stats = array();
	$stadium_stats['2012']['Away'] = $player_batting_2013['2012']['stadium'][$stadium];
	$stadium_stats['2013']['Away'] = $player_batting_2013['2013']['stadium'][$stadium];
	$stadium_stats['2012']['Home'] = array(
		'pct_single' => 0,
		'pct_double' => 0,
		'pct_triple' => 0,
		'pct_home_run' => 0,
		'pct_walk' => 0,
		'pct_strikeout' => 0,
		'pct_ground_out' => 0,
		'pct_fly_out' => 0
		);
	$stadium_stats['2013']['Home'] = $stadium_stats['2012']['Home'];
	//Add below for Sarahs temp test
	$stadium_stats['2012']['Home'] = $stadium_stats['2012']['Away'];
	$stadium_stats['2013']['Home'] = $stadium_stats['2013']['Away'];
	$stadium_stats = json_encode($stadium_stats);

	$fielding_stats = array();
	// I will fill this in below...

	$lineup_h = array();
	foreach ($game['lineup_h'] as $p => $player) {
		$name = checkDuplicatePlayers($player['name'], $home_i, $duplicate_names);
		$position = $player['position'];
		$lineup_position = $p;
		$lineup_h['2012'][$lineup_position] = $player_batting_2013['2012'][$name];
		$lineup_h['2013'][$lineup_position] = $player_batting_2013['2013'][$name];
		if (isset($fieldingstats_2012_total[$name])) {
			$fielding_stats['2012']['Home'] += ( 1 - ($fieldingstats_2012_total[$name]['errors'] / ($fieldingstats_2012_total[$name]['errors'] + $fieldingstats_2012_total[$name]['assists'] + $fieldingstats_2012_total[$name]['putouts']))) * $position_mapping[$position];
		} else {
			$fielding_stats['2012']['Home'] += (1 - ($fieldingstats_average['2012']['errors'] / $fieldingstats_average['2012']['total'])) * $position_mapping[$position];
		}
		if (isset($fieldingstats_2013_total[$name])) {
			$fielding_stats['2013']['Home'] += ( 1 - ($fieldingstats_2013_total[$name]['errors'] / ($fieldingstats_2013_total[$name]['errors'] + $fieldingstats_2013_total[$name]['assists'] + $fieldingstats_2013_total[$name]['putouts']))) * $position_mapping[$position];
		} else {
			$fielding_stats['2013']['Home'] += (1 - ($fieldingstats_average['2013']['errors'] / $fieldingstats_average['2013']['total'])) * $position_mapping[$position];
		}
		if (!isset($player_batting_2013['2012'][$name])) {
			error_log("Missing Batter: ".$name." 2012 "."\n", 3, "/Users/baseball/Desktop/Baseball/Scripts/Errors/nullbatter_error.log");
		}
		if (!isset($player_batting_2013['2013'][$name])) {
			error_log("Missing Batter: ".$name." 2013 "."\n", 3, "/Users/baseball/Desktop/Baseball/Scripts/Errors/nullbatter_error.log");
		}
	}
	$lineup_h = json_encode($lineup_h);

	$lineup_a = array();
	foreach ($game['lineup_a'] as $p => $player) {
		$name = checkDuplicatePlayers($player['name'], $away_i, $duplicate_names);
		$position = $player['position'];
		$lineup_position = $p;
		$lineup_a['2012'][$lineup_position] = $player_batting_2013['2012'][$name];
		$lineup_a['2013'][$lineup_position] = $player_batting_2013['2013'][$name];
		if (isset($fieldingstats_2012_total[$name])) {
			$fielding_stats['2012']['Away'] += ( 1 - ($fieldingstats_2012_total[$name]['errors'] / ($fieldingstats_2012_total[$name]['errors'] + $fieldingstats_2012_total[$name]['assists'] + $fieldingstats_2012_total[$name]['putouts']))) * $position_mapping[$position];
		} else {
			$fielding_stats['2012']['Away'] += (1 - ($fieldingstats_average['2012']['errors'] / $fieldingstats_average['2012']['total'])) * $position_mapping[$position];
		}
		if (isset($fieldingstats_2013_total[$name])) {
			$fielding_stats['2013']['Away'] += ( 1 - ($fieldingstats_2013_total[$name]['errors'] / ($fieldingstats_2013_total[$name]['errors'] + $fieldingstats_2013_total[$name]['assists'] + $fieldingstats_2013_total[$name]['putouts']))) * $position_mapping[$position];
		} else {
			$fielding_stats['2013']['Away'] += (1 - ($fieldingstats_average['2013']['errors'] / $fieldingstats_average['2013']['total'])) * $position_mapping[$position];
		}
		if (!isset($player_batting_2013['2012'][$name])) {
			error_log("Missing Batter: ".$name." 2012 "."\n", 3, "/Users/baseball/Desktop/Baseball/Scripts/Errors/nullbatter_error.log");
		}
		if (!isset($player_batting_2013['2013'][$name])) {
			error_log("Missing Batter: ".$name." 2013 "."\n", 3, "/Users/baseball/Desktop/Baseball/Scripts/Errors/nullbatter_error.log");
		}
	}
	$lineup_a = json_encode($lineup_a);

	// NOTE: THIS PULLS HOME PITCHER FOR HOME WHICH ISN'T RIGHT SINCE THEY DON'T BAT AGAINST THAT PITCHER
	// IN SARAH'S SCRIPT SHE CORRECTS THIS BY PULLING THE OPPOSITE!
/*
	unset($pitcher_batting_stats);
	$pitcher_no_change = array(
		'pct_single' => 0,
		'pct_double' => 0,
		'pct_triple' => 0,
		'pct_home_run' => 0,
		'pct_walk' => 0,
		'pct_strikeout' => 0,
		'pct_ground_out' => 0,
		'pct_fly_out' => 0
	);
	$pitcher_batting_stats['2012']['Home'] = $pitcher_magic_final['2012'][$pitcher_h_i];
	if (!isset($pitcher_batting_stats['2012']['Home'])) {
		$pitcher_batting_stats['2012']['Home'] = $pitcher_no_change;
	}
	$pitcher_batting_stats['2013']['Home'] = $pitcher_magic_final['2013'][$pitcher_h_i];
	if (!isset($pitcher_batting_stats['2013']['Home'])) {
		$pitcher_batting_stats['2013']['Home'] = $pitcher_no_change;
	}
	$pitcher_batting_stats['2012']['Away'] = $pitcher_magic_final['2012'][$pitcher_a_i];
	if (!isset($pitcher_batting_stats['2012']['Away'])) {
		$pitcher_batting_stats['2012']['Away'] = $pitcher_no_change;
	}
	$pitcher_batting_stats['2013']['Away'] = $pitcher_magic_final['2013'][$pitcher_a_i];
	if (!isset($pitcher_batting_stats['2013']['Away'])) {
		$pitcher_batting_stats['2013']['Away'] = $pitcher_no_change;
	}
	$pitcher_batting_stats = json_encode($pitcher_batting_stats);
*/

	$master_row['gameid'] = $gameid;
	$master_row['date_i'] = $date_i;	
	$master_row['month_i'] = $month;
	$master_row['day_i'] = $day;							
	$master_row['home_i'] = $home_i;
	$master_row['away_i'] = $away_i;
	$master_row['pitcher_h_i'] = $pitcher_h_i;
	$master_row['pitcher_a_i'] = $pitcher_a_i;
	$master_row['pitcher_h_handedness_i'] = $pitcher_h_handedness_i;
	$master_row['pitcher_a_handedness_i'] = $pitcher_a_handedness_i;
	$master_row['pitcher_h_2012_era_bucket_i'] = $pitcher_h_2012_era_bucket_i;
	$master_row['pitcher_a_2012_era_bucket_i'] = $pitcher_a_2012_era_bucket_i;
	$master_row['pitcher_h_2013_era_bucket_i'] = $pitcher_h_2013_era_bucket_i;
	$master_row['pitcher_a_2013_era_bucket_i'] = $pitcher_a_2013_era_bucket_i;
	$master_row['pitcher_h_era'] = $pitcher_h_era;
	$master_row['pitcher_a_era'] = $pitcher_a_era;
	$master_row['pitcher_era_delta'] = $pitcher_era_delta;
	$master_row['runs_h_i']	= $runs_h_i;							
	$master_row['runs_a_i']	= $runs_a_i;							
	$master_row['home_team_winner'] = $home_team_winner;
	$master_row['upset'] = $upset;
	$master_row['home_odds'] = $home_odds;
	$master_row['away_odds'] = $away_odds;
	$master_row['home_pct_win'] = $home_pct_win;
	$master_row['away_pct_win'] = $away_pct_win;
	$master_row['fielding_mult_2012_home'] = $fielding_stats['2012']['Home'];
	$master_row['fielding_mult_2013_home'] = $fielding_stats['2013']['Home'];
	$master_row['fielding_mult_2012_away'] = $fielding_stats['2012']['Away'];
	$master_row['fielding_mult_2013_away'] = $fielding_stats['2013']['Away'];
	$master_row['lineup_h_stats'] = $lineup_h;
	$master_row['lineup_a_stats'] = $lineup_a;
	$master_row['stadium_stats'] = $stadium_stats;
	//$master_row['pitcher_batting_stats'] = $pitcher_batting_stats;

	$master_table[$gameid] = $master_row;
}

ksort($master_table);
$final_table = array();
$key = 0;
foreach ($master_table as $game => $data) {
	// Skip the row of ColHeads
	if ($key == 0) {
		$final_table[$key] = $data;
		$key++;
		continue;
	}
	$data['gamenumber'] = $key;
	$final_table[$key] = $data;
	$key++;
}

$sql_colheads = $final_table[0];
foreach ($final_table as $key => $stats) {
	if ($stats[0] == 'gameid') {
		continue;
	} 
	$data = array();
	for ($k = 0; $k < count($stats); $k++) {
		$data[$sql_colheads[$k]] = $stats[$sql_colheads[$k]];
	}
	insert($database, 'sim_magic_2013', $data);
}

?>
