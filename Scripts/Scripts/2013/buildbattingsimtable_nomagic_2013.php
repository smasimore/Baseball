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

// Pull 2012 Data //
$all_players_2012 = exe_sql($database,
	'SELECT *
	FROM players_2012'
	);   
checkSQLError($all_players_2012, 500, 2000);          
//$startingpitchers2012 = exe_sql($database,
//	'SELECT *
//	FROM startingpitchers_2012'
//	);  
// checkSQLError($startingpitchers_2012, 1, 500); 
$battingstats_expanded_2012 = exe_sql($database,
	'SELECT *
	FROM batting_total_2012'
	);  
checkSQLError($battingstats_expanded_2012, 1, 2000); 
$battingstats_expanded_home_2012 = exe_sql($database,
	'SELECT *
	FROM batting_home_2012'
	); 
checkSQLError($battingstats_expanded_home_2012, 1, 2000); 
$battingstats_expanded_away_2012 = exe_sql($database,
	'SELECT *
	FROM batting_away_2012'
	); 
checkSQLError($battingstats_expanded_away_2012, 1, 2000); 
$battingstats_expanded_vsleft_2012 = exe_sql($database,
	'SELECT *
	FROM batting_vsleft_2012'
	); 
checkSQLError($battingstats_expanded_vsleft_2012, 1, 2000); 
$battingstats_expanded_vsright_2012 = exe_sql($database,
	'SELECT *
	FROM batting_vsright_2012'
	); 
checkSQLError($battingstats_expanded_vsright_2012, 1, 2000); 
$battingstats_expanded_noneon_2012 = exe_sql($database,
	'SELECT *
	FROM batting_noneon_2012'
	); 
checkSQLError($battingstats_expanded_noneon_2012, 1, 2000);
$battingstats_expanded_runnerson_2012 = exe_sql($database,
	'SELECT *
	FROM batting_runnerson_2012'
	); 
checkSQLError($battingstats_expanded_runnerson_2012, 1, 2000);
$battingstats_expanded_scoringpos_2012 = exe_sql($database,
	'SELECT *
	FROM batting_scoringpos_2012'
	); 
checkSQLError($battingstats_expanded_scoringpos_2012, 1, 2000);
$battingstats_expanded_basesloaded_2012 = exe_sql($database,
	'SELECT *
	FROM batting_basesloaded_2012'
	);
checkSQLError($battingstats_expanded_basesloaded_2012, 1, 2000);
$battingstats_expanded_scoringpos2out_2012 = exe_sql($database,
	'SELECT *
	FROM batting_scoringpos2out_2012'
	); 
checkSQLError($battingstats_expanded_scoringpos2out_2012, 1, 2000);
$pitchingstats_2012 = exe_sql($database,
	'SELECT *
	FROM pitching_2012'
	); 
checkSQLError($pitchingstats_2012, 1, 50000);
$stadiumstats_2012 = exe_sql($database,
	'SELECT *
	FROM batting_byfield_nomagic_2012'
	); 
checkSQLError($stadiumstats_2012, 1, 50);

// Pull 2013 Data //
$all_players_2013 = exe_sql($database,
	'SELECT *
	FROM players_2013'
	);   
checkSQLError($all_players_2013, 500, 2000);          
$startingpitchers2013 = exe_sql($database,
	'SELECT *
	FROM startingpitchers_2013'
	);  
checkSQLError($startingpitchers2013, 1, 500); 
$battingstats_expanded_2013 = exe_sql($database,
	'SELECT *
	FROM batting_total_2013'
	);  
checkSQLError($battingstats_expanded_2013, 1, 2000); 
$battingstats_expanded_home_2013 = exe_sql($database,
	'SELECT *
	FROM batting_home_2013'
	); 
checkSQLError($battingstats_expanded_home_2013, 1, 2000); 
$battingstats_expanded_away_2013 = exe_sql($database,
	'SELECT *
	FROM batting_away_2013'
	); 
checkSQLError($battingstats_expanded_away_2013, 1, 2000); 
$battingstats_expanded_vsleft_2013 = exe_sql($database,
	'SELECT *
	FROM batting_vsleft_2013'
	); 
checkSQLError($battingstats_expanded_vsleft_2013, 1, 2000); 
$battingstats_expanded_vsright_2013 = exe_sql($database,
	'SELECT *
	FROM batting_vsright_2013'
	); 
checkSQLError($battingstats_expanded_vsright_2013, 1, 2000); 
$battingstats_expanded_noneon_2013 = exe_sql($database,
	'SELECT *
	FROM batting_noneon_2013'
	); 
checkSQLError($battingstats_expanded_noneon_2013, 1, 2000);
$battingstats_expanded_runnerson_2013 = exe_sql($database,
	'SELECT *
	FROM batting_runnerson_2013'
	); 
checkSQLError($battingstats_expanded_runnerson_2013, 1, 2000);
$battingstats_expanded_scoringpos_2013 = exe_sql($database,
	'SELECT *
	FROM batting_scoringpos_2013'
	); 
checkSQLError($battingstats_expanded_scoringpos_2013, 1, 2000);
$battingstats_expanded_basesloaded_2013 = exe_sql($database,
	'SELECT *
	FROM batting_basesloaded_2013'
	);
checkSQLError($battingstats_expanded_basesloaded_2013, 1, 2000);
$battingstats_expanded_scoringpos2out_2013 = exe_sql($database,
	'SELECT *
	FROM batting_scoringpos2out_2013'
	); 
checkSQLError($battingstats_expanded_scoringpos2out_2013, 1, 2000);
$battingstats_vpitcher_2013 = exe_sql($database,
	'SELECT *
	FROM batting_vspitcher_2013'
	); 
checkSQLError($battingstats_vpitcher_2013, 1, 150000);
// Since we don't have this for 2012 we have to default to 2013 values
$battingstats_vpitcher_2012 = $battingstats_vpitcher_2013;
$pitchingstats_2013 = exe_sql($database,
	'SELECT *
	FROM pitching_2013'
	); 
checkSQLError($pitchingstats_2013, 1, 50000);
$stadiumstats_2013 = exe_sql($database,
	'SELECT *
	FROM batting_byfield_nomagic_2013'
	); 
checkSQLError($stadiumstats_2013, 1, 50);

function getAverageBattingStats($stats, $player_batting) {

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
	$ground_ball_rate = ($ground_balls / ($ground_balls + $fly_balls)) ?: .5;
	$at_bats = $stats['at_bats'];
	$fielding_outs = $at_bats - $hits - $strikeouts;
	$at_bats_walks = $at_bats + $all_walks;

	if ($at_bats_walks == 0) {
	  return null;
	}

	$player_batting['pct_single'] += $singles / $at_bats_walks;
	$player_batting['pct_double'] += $doubles / $at_bats_walks;
	$player_batting['pct_triple'] += $triples / $at_bats_walks;
	$player_batting['pct_home_run'] += $home_runs / $at_bats_walks;
	$player_batting['pct_walk'] += $all_walks / $at_bats_walks;
	$player_batting['pct_strikeout'] += $strikeouts / $at_bats_walks;
	$player_batting['pct_ground_out'] += ($fielding_outs * $ground_ball_rate) / $at_bats_walks;
	$player_batting['pct_fly_out'] += ($fielding_outs * (1 - $ground_ball_rate)) / $at_bats_walks;

	return $player_batting;
}

function getPlayerBattingStats($stats) {

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
	$ground_ball_rate = ($ground_balls / ($ground_balls + $fly_balls)) ?: .5;
	$at_bats = $stats['at_bats'];
	$fielding_outs = $at_bats - $hits - $strikeouts;
	$at_bats_walks = $at_bats + $all_walks;

	if ($at_bats_walks == 0) {
	  return null;
	}

	$player_batting['player_name'] = $player_name;
	$player_batting['pct_single'] = $singles / $at_bats_walks;
	$player_batting['pct_double'] = $doubles / $at_bats_walks;
	$player_batting['pct_triple'] = $triples / $at_bats_walks;
	$player_batting['pct_home_run'] = $home_runs / $at_bats_walks;
	$player_batting['pct_walk'] = $all_walks / $at_bats_walks;
	$player_batting['pct_strikeout'] = $strikeouts / $at_bats_walks;
	$player_batting['pct_ground_out'] = ($fielding_outs * $ground_ball_rate) / $at_bats_walks;
	$player_batting['pct_fly_out'] = ($fielding_outs * (1 - $ground_ball_rate)) / $at_bats_walks;
	$player_batting['gbr'] = $ground_ball_rate;

	return $player_batting;
}

function getBattingSplit($player_array, $name, $year, $battingstats, $split) {

	foreach ($battingstats as $j => $stats) {
		if ($stats['player_name'] != $name) {
			continue;
		}
	// Only include stats if they have more than 9 at bats - otherwise defaults to average
		if ($stats['at_bats'] >= 9) {
			$player_stats = getPlayerBattingStats($stats);
			$player_array[$name][$year][$split] = $player_stats;
			break;
		}
	}
	return $player_array;
}

function getAverageBattingSplit($name, $player_average, $average_count, $year, $battingstats, $split) {

	foreach ($battingstats as $j => $stats) {
		if ($stats['player_name'] != $name) {
			continue;
		}
		// Do something with this...and/or limit it so we don't get crazy people in our averages
		if ($split == 'Total') {
			$war = $stats['wins_above_replacement'];
		}
		if ($stats['at_bats'] >= 9) {
			$player_average[$year][$split] = getAverageBattingStats($stats, $player_average[$year][$split]);
			$average_count[$year] += 1;
			break;
		}
	}
	return array('stats' => $player_average, 'count' => $average_count);
}

function getBatterVPitcherStats($batter, $output) {

	$player_name = $batter['player_name'];
	$hits = $batter['hits'];
	$doubles = $batter['doubles'];
	$triples = $batter['triples'];
	$home_runs = $batter['home_runs'];
	$singles = $hits - $doubles - $triples - $home_runs;
	$walks = $batter['walks'];
	$strikeouts = $batter['strikeouts'];
	$at_bats_walks = $batter['at_bats'] + $walks;

	$output[$player_name]['player_name'] = $player_name;
	$output[$player_name]['single'] += $singles;
	$output[$player_name]['double'] += $doubles;
	$output[$player_name]['triple'] += $triples;
	$output[$player_name]['home_run'] += $home_runs;
	$output[$player_name]['walk'] += $walks;
	$output[$player_name]['strikeout'] += $strikeouts;
	$output[$player_name]['fielding_outs'] += $at_bats_walks - $hits - $walks - $strikeouts;
	$output[$player_name]['at_bats_walks'] += $at_bats_walks;

	return $output;
}

function getPlayerVPitcherBattingStats($stats, $player_gbr) {

	$player_name = $stats['player_name'];
	$singles = $stats['single'];
	$doubles = $stats['double'];
	$triples = $stats['triple'];
	$home_runs = $stats['home_run'];
	$walks = $stats['walk'];
	$strikeouts = $stats['strikeout'];
	$ground_ball_rate = $player_gbr;
	$at_bats_walks = $stats['at_bats_walks'];
	$fielding_outs = $stats['fielding_outs'];

	if ($at_bats_walks == 0) {
	  return null;
	}

	$player_batting['player_name'] = $player_name;
	$player_batting['pct_single'] = $singles / $at_bats_walks;
	$player_batting['pct_double'] = $doubles / $at_bats_walks;
	$player_batting['pct_triple'] = $triples / $at_bats_walks;
	$player_batting['pct_home_run'] = $home_runs / $at_bats_walks;
	$player_batting['pct_walk'] = $walks / $at_bats_walks;
	$player_batting['pct_strikeout'] = $strikeouts / $at_bats_walks;
	$player_batting['pct_ground_out'] = ($fielding_outs * $ground_ball_rate) / $at_bats_walks;
	$player_batting['pct_fly_out'] = ($fielding_outs * (1 - $ground_ball_rate)) / $at_bats_walks;
	$player_batting['gbr'] = $player_gbr;

	return $player_batting;
}

function getAveragePlayerVPitcherBattingStats($stats, $player_gbr, $player_batting) {

	$singles = $stats['single'];
	$doubles = $stats['double'];
	$triples = $stats['triple'];
	$home_runs = $stats['home_run'];
	$walks = $stats['walk'];
	$strikeouts = $stats['strikeout'];
	$ground_ball_rate = $player_gbr;
	$at_bats_walks = $stats['at_bats_walks'];
	$fielding_outs = $stats['fielding_outs'];

	if ($at_bats_walks ==0) {
	  return null;
	}

	$player_batting['pct_single'] += $singles / $at_bats_walks;
	$player_batting['pct_double'] += $doubles / $at_bats_walks;
	$player_batting['pct_triple'] += $triples / $at_bats_walks;
	$player_batting['pct_home_run'] += $home_runs / $at_bats_walks;
	$player_batting['pct_walk'] += $walks / $at_bats_walks;
	$player_batting['pct_strikeout'] += $strikeouts / $at_bats_walks;
	$player_batting['pct_ground_out']+= ($fielding_outs * $ground_ball_rate) / $at_bats_walks;
	$player_batting['pct_fly_out'] += ($fielding_outs * (1 - $ground_ball_rate)) / $at_bats_walks;

	return $player_batting;
}

function getBattingVPitcherSplit($player_array, $name, $year, $battingstats, $split) {

	$player_gbr = $player_array[$name][$year]['Total']['gbr'];
	if (!isset($player_gbr)) {
		return $player_array;
	}
	foreach ($battingstats as $stats) {
		if ($stats['player_name'] != $name) {
			continue;
		}
		$player_batting = getPlayerVPitcherBattingStats($stats, $player_gbr);
		$player_array[$name][$year][$split] = $player_batting;
		break;
	}
	return $player_array;		
}

function getAverageBattingVPitcherSplit($name, $player_array, $player_average, $average_count, $year, $battingstats, $split) {

	$player_gbr = $player_array[$name][$year]['Total']['gbr'];
	if (!isset($player_gbr)) {
		return array('stats' => $player_average, 'count' => $average_count);
	}
	foreach ($battingstats as $stats) {
		if ($stats['player_name'] != $name) {
			continue;
		}
		$player_batting = getAveragePlayerVPitcherBattingStats($stats, $player_gbr, $player_average[$year][$split]);
		$player_average[$year][$split] = $player_batting;
		$average_count[$year] += 1;
		break;
	}
	return array('stats' => $player_average, 'count' => $average_count);	
}

$stadium_stats = array();
foreach ($stadiumstats_2012 as $stadium) {
	$stadium_name = $stadium['stadium'];
	$stadium_stats['2012'][$stadium_name] = $stadium;
}
foreach ($stadiumstats_2013 as $stadium) {
	$stadium_name = $stadium['stadium'];
	$stadium_stats['2013'][$stadium_name] = $stadium;
}

/*
$starting_pitchers_2012 = array();
$all_starting_pitchers_2012 = array();
foreach ($startingpitchers2012 as $day) {
	$date = $day['ds'];
	$day_games = json_decode($day['data'], true);
	foreach ($day_games as $team => $game) {
		foreach ($game as $gamenum => $pitchers) {
			$team = convertStartingPitcherTeams($team);
			$starting_pitchers_2012[$date][$team][$gamenum] = $pitchers;
			foreach ($pitchers as $pitcher) {
				if (!in_array($pitcher, $all_starting_pitchers_2012)) {
					array_push($all_starting_pitchers_2012, $pitcher);
				}
			}
		}
	}
}
 */

$pitcher_era_map_2012 = array();
$starting_pitcher_era_map_2012 = array();
foreach ($pitchingstats_2012 as $j => $stats) {
	if (isset($pitcher_era_map_2012[$stats['player_name']])) {
		continue;
	} else if ($stats['split'] == 'Total') {
		$starting_pitcher_era_map_2012[$stats['player_name']]['era'] = $stats['earned_run_average'];
		$starting_pitcher_era_map_2012[$stats['player_name']]['hand'] = $stats['handedness'];
		$starting_pitcher_era_map_2012[$stats['player_name']]['innings'] = $stats['innings'];
		if ($stats['innings'] >= 18) {
			$pitcher_era_map_2012[$stats['player_name']] = $stats['earned_run_average'];
		}
	}
}

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

$pitcher_era_map_2013 = array();
$starting_pitcher_era_map_2013 = array();
foreach ($pitchingstats_2013 as $j => $stats) {
	if (isset($pitcher_era_map_2013[$stats['player_name']])) {
		continue;
	} else if ($stats['split'] == 'Total') {
		$starting_pitcher_era_map_2013[$stats['player_name']]['era'] = $stats['earned_run_average'];
		$starting_pitcher_era_map_2013[$stats['player_name']]['hand'] = $stats['handedness'];
		$starting_pitcher_era_map_2013[$stats['player_name']]['innings'] = $stats['innings'];
		if ($stats['innings'] >= 18) {
			$pitcher_era_map_2013[$stats['player_name']] = $stats['earned_run_average'];
		}
	}
}

$era_values_2012 = array_values($pitcher_era_map_2012);
sort($era_values_2012);
$era_divider_2012 = count($era_values_2012) / 4;
$batter_vs_era_25_2012 = array();
$batter_vs_era_50_2012 = array();
$batter_vs_era_75_2012 = array();
$batter_vs_era_100_2012 = array();
$era_25_2012 = $era_values_2012[$era_divider_2012];
$era_50_2012 = $era_values_2012[$era_divider_2012 * 2];
$era_75_2012 = $era_values_2012[$era_divider_2012 * 3];

foreach ($battingstats_vpitcher_2012 as $batter) {

	$pitcher_era = $pitcher_era_map_2012[$batter['vs_pitcher']];
	if (!isset($pitcher_era)) {
		continue;
	}

	if ($pitcher_era < $era_25_2012) {
		$batter_vs_era_25_2012 = getBatterVPitcherStats($batter, $batter_vs_era_25_2012);
	}
	if ($pitcher_era >= $era_25_2012 && $pitcher_era < $era_50_2012) {
		$batter_vs_era_50_2012 = getBatterVPitcherStats($batter, $batter_vs_era_50_2012);
	}
	if ($pitcher_era >= $era_50_2012 && $pitcher_era < $era_75_2012) {
		$batter_vs_era_75_2012 = getBatterVPitcherStats($batter, $batter_vs_era_75_2012);
	}
	if ($pitcher_era >= $era_75_2012) {
		$batter_vs_era_100_2012 = getBatterVPitcherStats($batter, $batter_vs_era_100_2012);
	}
}

foreach ($starting_pitcher_era_map_2012 as $name => $pitcher) {	
	$pitcher_era = $pitcher['era'];
	$innings = $pitcher['innings'];
	$starting_pitcher_era_map_2012[$name]['name'] = $name;
	if ($pitcher_era < $era_25_2012) {
		$starting_pitcher_era_map_2012[$name]['2012bin'] = 25;
	}
	if ($pitcher_era >= $era_25_2012 && $pitcher_era < $era_50_2012) {
		$starting_pitcher_era_map_2012[$name]['2012bin'] = 50;
	}
	if ($pitcher_era >= $era_50_2012 && $pitcher_era < $era_75_2012) {
		$starting_pitcher_era_map_2012[$name]['2012bin'] = 75;
	}
	if ($pitcher_era >= $era_75_2012) {
		$starting_pitcher_era_map_2012[$name]['2012bin'] = 100;
	}
	if ($innings < 18) {
		$starting_pitcher_era_map_2012[$name]['2012bin'] = 75;
	}
}

$era_values_2013 = array_values($pitcher_era_map_2013);
sort($era_values_2013);
$era_divider_2013 = count($era_values_2013) / 4;
$batter_vs_era_25_2013 = array();
$batter_vs_era_50_2013 = array();
$batter_vs_era_75_2013 = array();
$batter_vs_era_100_2013 = array();
$era_25_2013 = $era_values_2013[$era_divider_2013];
$era_50_2013 = $era_values_2013[$era_divider_2013 * 2];
$era_75_2013 = $era_values_2013[$era_divider_2013 * 3];

foreach ($battingstats_vpitcher_2013 as $batter) {	
	$pitcher_era = $pitcher_era_map_2013[$batter['vs_pitcher']];
	if (!isset($pitcher_era)) {
		continue;
	}
	if ($pitcher_era < $era_25_2013) {
		$batter_vs_era_25_2013 = getBatterVPitcherStats($batter, $batter_vs_era_25_2013);
	}
	if ($pitcher_era >= $era_25_2013 && $pitcher_era < $era_50_2013) {
		$batter_vs_era_50_2013 = getBatterVPitcherStats($batter, $batter_vs_era_50_2013);
	}
	if ($pitcher_era >= $era_50_2013 && $pitcher_era < $era_75_2013) {
		$batter_vs_era_75_2013 = getBatterVPitcherStats($batter, $batter_vs_era_75_2013);
	}
	if ($pitcher_era >= $era_75_2013) {
		$batter_vs_era_100_2013 = getBatterVPitcherStats($batter, $batter_vs_era_100_2013);
	}
}

foreach ($starting_pitcher_era_map_2013 as $name => $pitcher) {	
	$pitcher_era = $pitcher['era'];
	$innings = $pitcher['innings'];
	$starting_pitcher_era_map_2013[$name]['name'] = $name;
	if ($pitcher_era < $era_25_2013) {
		$starting_pitcher_era_map_2013[$name]['2013bin'] = 25;
	}
	if ($pitcher_era >= $era_25_2013 && $pitcher_era < $era_50_2013) {
		$starting_pitcher_era_map_2013[$name]['2013bin'] = 50;
	}
	if ($pitcher_era >= $era_50_2013 && $pitcher_era < $era_75_2013) {
		$starting_pitcher_era_map_2013[$name]['2013bin'] = 75;
	}
	if ($pitcher_era >= $era_75_2013) {
		$starting_pitcher_era_map_2013[$name]['2013bin'] = 100;
	}
	if ($innings < 18) {
		$starting_pitcher_era_map_2013[$name]['2013bin'] = 75;
	}
	if (isset($starting_pitcher_era_map_2012[$name]['2012bin'])) {
		$starting_pitcher_era_map_2013[$name]['2012bin'] = $starting_pitcher_era_map_2012[$name]['2012bin'];
		$starting_pitcher_era_map_2013[$name]['2012innings'] = $starting_pitcher_era_map_2012[$name]['innings'];
		$starting_pitcher_era_map_2013[$name]['2012era'] = $starting_pitcher_era_map_2012[$name]['era'];
	} else {
		$starting_pitcher_era_map_2013[$name]['2012bin'] = 75;
	}
}

$player_stat_difference = array();
$average_player_stat_difference = array();
$player_array = array();
$final_player_array = array();
$magic_player_array = array();
$players_2012 = array();
$players_2013 = array();
$player_average = array();
$average_count[2012] = 0;
$average_count[2013] = 0;

foreach ($all_players_2012 as $player) {
	$id = $player['id'];
	$name = $player['unixname'];
	//Make an array of 2012 names to x-check to 2013 players
	array_push($players_2012, $name);
	$player_array = getBattingSplit($player_array, $name, '2012', $battingstats_expanded_2012, 'Total');
	$averages = getAverageBattingSplit($name, $player_average, $average_count, '2012', $battingstats_expanded_2012, 'Total');
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	if (!$player_array[$name]['2012']['Total']) {
		error_log("Missing Batter Total: ".$name." 2012 "."\n", 3, "/Users/baseball/Desktop/Baseball/Scripts/Errors/missing_batting_splits_error.log");
		$player_array[$name]['2012'] = null;
		continue;
	}
	// I can probably make a function and clean this up...
	$player_array = getBattingSplit($player_array, $name, '2012', $battingstats_expanded_home_2012, 'Home');
	$averages = getAverageBattingSplit($name, $player_average, $average_count, '2012', $battingstats_expanded_home_2012, 'Home');
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	$player_array = getBattingSplit($player_array, $name, '2012', $battingstats_expanded_away_2012, 'Away');
	$averages = getAverageBattingSplit($name, $player_average, $average_count, '2012', $battingstats_expanded_away_2012, 'Away');
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	$player_array = getBattingSplit($player_array, $name, '2012', $battingstats_expanded_vsleft_2012, 'VsLeft');
	$averages = getAverageBattingSplit($name, $player_average, $average_count, '2012', $battingstats_expanded_vsleft_2012, 'VsLeft');
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	$player_array = getBattingSplit($player_array, $name, '2012', $battingstats_expanded_vsright_2012, 'VsRight');
	$averages = getAverageBattingSplit($name, $player_average, $average_count, '2012', $battingstats_expanded_vsright_2012, 'VsRight');
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	$player_array = getBattingSplit($player_array, $name, '2012', $battingstats_expanded_noneon_2012, 'NoneOn');
	$averages = getAverageBattingSplit($name, $player_average, $average_count, '2012', $battingstats_expanded_noneon_2012, 'NoneOn');
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	$player_array = getBattingSplit($player_array, $name, '2012', $battingstats_expanded_runnerson_2012, 'RunnersOn');
	$averages = getAverageBattingSplit($name, $player_average, $average_count, '2012', $battingstats_expanded_runnerson_2012, 'RunnersOn');
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	$player_array = getBattingSplit($player_array, $name, '2012', $battingstats_expanded_scoringpos_2012, 'ScoringPos');
	$averages = getAverageBattingSplit($name, $player_average, $average_count, '2012', $battingstats_expanded_scoringpos_2012, 'ScoringPos');
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	$player_array = getBattingSplit($player_array, $name, '2012', $battingstats_expanded_scoringpos2out_2012, 'ScoringPos2Out');
	$averages = getAverageBattingSplit($name, $player_average, $average_count, '2012', $battingstats_expanded_scoringpos2out_2012, 'ScoringPos2Out');
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	$player_array = getBattingSplit($player_array, $name, '2012', $battingstats_expanded_basesloaded_2012, 'BasesLoaded');
	$averages = getAverageBattingSplit($name, $player_average, $average_count, '2012', $battingstats_expanded_basesloaded_2012, 'BasesLoaded');
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	//$player_array = getBattingVPitcherSplit($player_array, $name, '2012', $batter_vs_era_25_2012, 25);
	$averages = getAverageBattingVPitcherSplit($name, $player_array, $player_average, $average_count, '2012', $batter_vs_era_25_2012, 25);
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	//$player_array = getBattingVPitcherSplit($player_array, $name, '2012', $batter_vs_era_50_2012, 50);
	$averages = getAverageBattingVPitcherSplit($name, $player_array, $player_average, $average_count, '2012', $batter_vs_era_50_2012, 50);
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	//$player_array = getBattingVPitcherSplit($player_array, $name, '2012', $batter_vs_era_75_2012, 75);
	$averages = getAverageBattingVPitcherSplit($name, $player_array, $player_average, $average_count, '2012', $batter_vs_era_75_2012, 75);
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	//$player_array = getBattingVPitcherSplit($player_array, $name, '2012', $batter_vs_era_100_2012, 100);
	$averages = getAverageBattingVPitcherSplit($name, $player_array, $player_average, $average_count, '2012', $batter_vs_era_100_2012, 100);
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	foreach ($splits as $split) {
		if (!isset($player_array[$name]['2012'][$split])) {
			error_log("Missing Batter: ".$name." 2012 ".$split."\n", 3, "/Users/baseball/Desktop/Baseball/Scripts/Errors/missing_batting_splits_error_extra.log");
			$player_array[$name]['2012'][$split] = $player_array[$name]['2012']['Total'];
		}
	}
}

foreach ($all_players_2013 as $player) {
	$id = $player['id'];
	$name = $player['unixname'];
	if (!in_array($name, $players_2012)) {
		$player_array[$name]['2012'] = null;
	}
	array_push($players_2013, $name);
	$player_array = getBattingSplit($player_array, $name, '2013', $battingstats_expanded_2013, 'Total');
	$averages = getAverageBattingSplit($name, $player_average, $average_count, '2013', $battingstats_expanded_2013, 'Total');
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	if (!$player_array[$name]['2013']['Total']) {
		error_log("Missing Batter Total: ".$name." 2013 "."\n", 3, "/Users/baseball/Desktop/Baseball/Scripts/Errors/missing_batting_splits_error.log");
		$player_array[$name]['2013'] = null;
		continue;
	}
	$player_array = getBattingSplit($player_array, $name, '2013', $battingstats_expanded_home_2013, 'Home');
	$averages = getAverageBattingSplit($name, $player_average, $average_count, '2013', $battingstats_expanded_home_2013, 'Home');
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	$player_array = getBattingSplit($player_array, $name, '2013', $battingstats_expanded_away_2013, 'Away');
	$averages = getAverageBattingSplit($name, $player_average, $average_count, '2013', $battingstats_expanded_away_2013, 'Away');
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	$player_array = getBattingSplit($player_array, $name, '2013', $battingstats_expanded_vsleft_2013, 'VsLeft');
	$averages = getAverageBattingSplit($name, $player_average, $average_count, '2013', $battingstats_expanded_vsleft_2013, 'VsLeft');
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	$player_array = getBattingSplit($player_array, $name, '2013', $battingstats_expanded_vsright_2013, 'VsRight');
	$averages = getAverageBattingSplit($name, $player_average, $average_count, '2013', $battingstats_expanded_vsright_2013, 'VsRight');
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	$player_array = getBattingSplit($player_array, $name, '2013', $battingstats_expanded_noneon_2013, 'NoneOn');
	$averages = getAverageBattingSplit($name, $player_average, $average_count, '2013', $battingstats_expanded_noneon_2013, 'NoneOn');
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	$player_array = getBattingSplit($player_array, $name, '2013', $battingstats_expanded_runnerson_2013, 'RunnersOn');
	$averages = getAverageBattingSplit($name, $player_average, $average_count, '2013', $battingstats_expanded_runnerson_2013, 'RunnersOn');
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	$player_array = getBattingSplit($player_array, $name, '2013', $battingstats_expanded_scoringpos_2013, 'ScoringPos');
	$averages = getAverageBattingSplit($name, $player_average, $average_count, '2013', $battingstats_expanded_scoringpos_2013, 'ScoringPos');
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	$player_array = getBattingSplit($player_array, $name, '2013', $battingstats_expanded_scoringpos2out_2013, 'ScoringPos2Out');
	$averages = getAverageBattingSplit($name, $player_average, $average_count, '2013', $battingstats_expanded_scoringpos2out_2013, 'ScoringPos2Out');
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	$player_array = getBattingSplit($player_array, $name, '2013', $battingstats_expanded_basesloaded_2013, 'BasesLoaded');
	$averages = getAverageBattingSplit($name, $player_average, $average_count, '2013', $battingstats_expanded_basesloaded_2013, 'BasesLoaded');
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	$player_array = getBattingVPitcherSplit($player_array, $name, '2013', $batter_vs_era_25_2013, 25);
	$averages = getAverageBattingVPitcherSplit($name, $player_array, $player_average, $average_count, '2013', $batter_vs_era_25_2013, 25);
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	$player_array = getBattingVPitcherSplit($player_array, $name, '2013', $batter_vs_era_50_2013, 50);
	$averages = getAverageBattingVPitcherSplit($name, $player_array, $player_average, $average_count, '2013', $batter_vs_era_50_2013, 50);
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	$player_array = getBattingVPitcherSplit($player_array, $name, '2013', $batter_vs_era_75_2013, 75);
	$averages = getAverageBattingVPitcherSplit($name, $player_array, $player_average, $average_count, '2013', $batter_vs_era_75_2013, 75);
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	$player_array = getBattingVPitcherSplit($player_array, $name, '2013', $batter_vs_era_100_2013, 100);
	$averages = getAverageBattingVPitcherSplit($name, $player_array, $player_average, $average_count, '2013', $batter_vs_era_100_2013, 100);
	$player_average = $averages['stats'];
	$average_count = $averages['count'];
	foreach ($splits as $split) {
		if (!isset($player_array[$name]['2013'][$split])) {
			error_log("Missing Batter: ".$name." 2013 ".$split."\n", 3, "/Users/baseball/Desktop/Baseball/Scripts/Errors/missing_batting_splits_error_extra.log");
			$player_array[$name]['2013'][$split] = $player_array[$name]['2013']['Total'];
		}
	}
}

$player_average_final = array();
foreach ($player_average as $year => $split) {
	foreach ($split as $split_name => $stat) {
		foreach ($stat as $stat_name => $data) {
			$stat_denom = array_sum($player_average[$year][$split_name]);
			$avg_stat = $data / $stat_denom;
			$player_average_final[$year][$split_name][$stat_name] = $avg_stat;
		}
	}
}

foreach ($player_array as $name => $info) {
	foreach ($info as $year => $data) {
		if (isset($data)) {
			continue;
		} else {
			foreach ($splits as $split) {
				if (isset($player_average_final[$year][$split])) {
					$player_array[$name][$year][$split] = $player_average_final[$year][$split];
				} else {
					$player_array[$name][$year][$split] = $player_average_final[$year]['Total'];
				}
			}
		}
	}
}

foreach ($player_average_final as $year => $info) {
	foreach ($info as $split_name => $stats) {
		if ($split_name !== 'Total') {
			continue;
		} else {
			foreach ($splits as $split) {
				$average_player_stat_difference[$year][$split] = $stats;
			}
		}
	}
}

foreach ($player_average_final as $year => $info) {
	foreach ($info as $split_name => $stats) {
		if ($split_name == 'Total') {
			continue;
		}
		foreach ($stats as $stat_name => $number) {
			$average_player_stat_difference[$year][$split_name][$stat_name] = ($number - $average_player_stat_difference[$year][$split_name][$stat_name]);
		}
	}
}

foreach ($player_array as $name => $info) {
	foreach ($info as $year => $data) {
		foreach ($data as $split_name => $stats) {
			if ($split_name !== 'Total') {
				continue;
			} else {
				foreach ($splits as $split) {
					$player_stat_difference[$name][$year][$split] = $stats;
				}
			}
		}
	}
}

foreach ($player_array as $name => $info) {
	foreach ($info as $year => $data) {
		foreach ($data as $split_name => $stats) {
			if ($split_name == 'Total') {
				continue;
			}
			foreach ($stats as $stat_name => $number) {
				$player_stat_difference[$name][$year][$split_name][$stat_name] = ($number - $player_stat_difference[$name][$year][$split_name][$stat_name]);
				if ($player_stat_difference[$name][$year][$split_name][$stat_name] == 0) {
					$player_stat_difference[$name][$year][$split_name][$stat_name] = $average_player_stat_difference[$year][$split_name][$stat_name];
				}
			}
		}
	}
}

$player_stat_difference['stadium']['2012'] = $stadium_stats['2012'];
$player_stat_difference['stadium']['2013'] = $stadium_stats['2013'];
$player_array['stadium']['2012'] = $stadium_stats['2012'];
$player_array['stadium']['2013'] = $stadium_stats['2013'];

foreach ($player_array as $name => $info) {
	$final_player_array[$name] = array($name, json_encode($info));
}

foreach ($player_stat_difference as $name => $info) {
	$magic_player_array[$name] = array($name, json_encode($info));
}

foreach ($final_player_array as $player) {
	$data = array();
	$data['player_name'] = $player[0];
	$data['stats'] = $player[1];
	insert($database, 'batting_final_nomagic_2013', $data);
}

/*
foreach ($magic_player_array as $player) {
	$data = array();
	$data['player_name'] = $player[0];
	$data['stats'] = $player[1];
	insert($database, 'batting_final_magic_2013', $data);
}

foreach ($starting_pitcher_era_map_2013 as $player) {
	$data = array();
	$data['era'] = $player['era'];
	$data['hand'] = $player['hand'];
	$data['innings'] = $player['innings'];
	$data['name'] = $player['name'];
	$data['2013bin'] = $player['2013bin'];
	$data['2012bin'] = $player['2012bin'];
	insert($database, 'era_map_2013', $data);
}
 */

?>
