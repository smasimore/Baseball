<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');

const MIN_PITCHER_INNINGS = 18;
const MIN_AT_BATS = 9;
const ERA_BIN_DEFAULT = 75;

// Modify date if used for backfilling
if ($argv[1]) {
	$date = $argv[1];
}

// Pull 2013 Data //
$all_players_2013 = pullAllData('players_2013');
$battingstats_expanded_2013 = pullAllData('batting_total_2013');
$battingstats_expanded_home_2013 = pullAllData('batting_home_2013');
$battingstats_expanded_away_2013 = pullAllData('batting_away_2013');
$battingstats_expanded_vsleft_2013 = pullAllData('batting_vsleft_2013');
$battingstats_expanded_vsright_2013 = pullAllData('batting_vsright_2013');
$battingstats_expanded_noneon_2013 = pullAllData('batting_noneon_2013');
$battingstats_expanded_runnerson_2013 = pullAllData('batting_runnerson_2013');
$battingstats_expanded_scoringpos_2013 = pullAllData('batting_scoringpos_2013');
$battingstats_expanded_basesloaded_2013 = pullAllData('batting_basesloaded_2013');
$battingstats_expanded_scoringpos2out_2013 = pullAllData('batting_scoringpos2out_2013');
$pitchingstats_2013 = pullAllData('pitching_2013');
$battingstats_vpitcher_2013 = pullAllData('batting_vspitcher_2013');
$stadiumstats_magic_2013 = pullAllData('batting_byfield_magic_2013');
$stadiumstats_nomagic_2013 = pullAllData('batting_byfield_nomagic_2013');

// Pull 2014 Data //
$all_players_2014 = pullAllData('players_2014', $date);
$battingstats_expanded_2014 = pullAllData('batting_total_2014', $date);
$battingstats_expanded_home_2014 = pullAllData('batting_home_2014', $date);
$battingstats_expanded_away_2014 = pullAllData('batting_away_2014', $date);
$battingstats_expanded_vsleft_2014 = pullAllData('batting_vsleft_2014', $date);
$battingstats_expanded_vsright_2014 = pullAllData('batting_vsright_2014', $date);
$battingstats_expanded_noneon_2014 = pullAllData('batting_noneon_2014', $date);
$battingstats_expanded_runnerson_2014 = pullAllData('batting_runnerson_2014', $date);
$battingstats_expanded_scoringpos_2014 = pullAllData('batting_scoringpos_2014', $date);
$battingstats_expanded_basesloaded_2014 = pullAllData('batting_basesloaded_2014', $date);
$battingstats_expanded_scoringpos2out_2014 = pullAllData('batting_scoringpos2out_2014', $date);
$battingstats_vpitcher_2014 = pullAllData('batting_vspitcher_2014', $date);
$pitchingstats_2014 = pullAllData('pitching_2014', $date);
$stadiumstats_magic_2014 = pullAllData('batting_byfield_magic_2014', $date);
$stadiumstats_nomagic_2014 = pullAllData('batting_byfield_nomagic_2014', $date);
$pitcher_batting_2014 = pullAllData('batting_vspitcher_aggregate_2014', $date);

// FOR TESTING //
//$all_players_2013 = pullTestData('players_2013', 'unixname', 'derek_jeter');
//$all_players_2014 = pullTestData('players_2014', 'unixname', 'derek_jeter', $date);

function updateAverageBattingStats($stats, $year, $split) {
	global $player_average;

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
	$player_average[$year][$split]['pct_single'] += $singles / $at_bats_walks;
	$player_average[$year][$split]['pct_double'] += $doubles / $at_bats_walks;
	$player_average[$year][$split]['pct_triple'] += $triples / $at_bats_walks;
	$player_average[$year][$split]['pct_home_run'] += $home_runs / $at_bats_walks;
	$player_average[$year][$split]['pct_walk'] += $all_walks / $at_bats_walks;
	$player_average[$year][$split]['pct_strikeout'] += $strikeouts / $at_bats_walks;
	$player_average[$year][$split]['pct_ground_out'] += ($fielding_outs * $ground_ball_rate) / $at_bats_walks;
	$player_average[$year][$split]['pct_fly_out'] += ($fielding_outs * (1 - $ground_ball_rate)) / $at_bats_walks;
}

function updateAverageGBR($stats, $year, $split) {
	global $average_gbr;
	$ground_balls = $stats['ground_balls'];
	$fly_balls = $stats['fly_balls'];
	$average_gbr[$year][$split]['rate'] += ($ground_balls / ($ground_balls + $fly_balls)) ?: .5;
	$average_gbr[$year][$split]['count'] += 1;
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
	// TODO(cert): ADD Sac flies + sac bunts to get data in line with Retrosheet
	$fielding_outs = $at_bats - $hits - $strikeouts;
	$at_bats_walks = $at_bats + $all_walks;

	if (!$at_bats_walks) {
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

// Function to update $player_array + $player_average + $average_gbr
function updatePlayerBatting($name, $year, $battingstats, $split) {
	global $player_array;
	foreach ($battingstats as $j => $stats) {
		if ($stats['player_name'] !== $name) {
			continue;
		}
		if ($stats['at_bats'] >= MIN_AT_BATS) {
			$player_stats = getPlayerBattingStats($stats);
			$player_array[$name][$year][$split] = $player_stats;
			updateAverageBattingStats($stats, $year, $split);
			updateAverageGBR($stats, $year, $split);
			break;
		}
	}
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

function updatePlayerVPitcherBattingStats($stats, $player_gbr, $name, $year, $split) {
	global $player_array;
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
	$player_array[$name][$year][$split]['player_name'] = $player_name;
	$player_array[$name][$year][$split]['pct_single'] = $singles / $at_bats_walks;
	$player_array[$name][$year][$split]['pct_double'] = $doubles / $at_bats_walks;
	$player_array[$name][$year][$split]['pct_triple'] = $triples / $at_bats_walks;
	$player_array[$name][$year][$split]['pct_home_run'] = $home_runs / $at_bats_walks;
	$player_array[$name][$year][$split]['pct_walk'] = $walks / $at_bats_walks;
	$player_array[$name][$year][$split]['pct_strikeout'] = $strikeouts / $at_bats_walks;
	$player_array[$name][$year][$split]['pct_ground_out'] = ($fielding_outs * $ground_ball_rate) / $at_bats_walks;
	$player_array[$name][$year][$split]['pct_fly_out'] = ($fielding_outs * (1 - $ground_ball_rate)) / $at_bats_walks;
	$player_array[$name][$year][$split]['gbr'] = $player_gbr;
}

function updateAveragePlayerVPitcherBattingStats($stats, $player_gbr, $year, $split) {
	global $player_average;
	$singles = $stats['single'];
	$doubles = $stats['double'];
	$triples = $stats['triple'];
	$home_runs = $stats['home_run'];
	$walks = $stats['walk'];
	$strikeouts = $stats['strikeout'];
	$ground_ball_rate = $player_gbr;
	$at_bats_walks = $stats['at_bats_walks'];
	$fielding_outs = $stats['fielding_outs'];

	if (!$at_bats_walks) {
	  return null;
	}
	$player_average[$year][$split]['pct_single'] += $singles / $at_bats_walks;
	$player_average[$year][$split]['pct_double'] += $doubles / $at_bats_walks;
	$player_average[$year][$split]['pct_triple'] += $triples / $at_bats_walks;
	$player_average[$year][$split]['pct_home_run'] += $home_runs / $at_bats_walks;
	$player_average[$year][$split]['pct_walk'] += $walks / $at_bats_walks;
	$player_average[$year][$split]['pct_strikeout'] += $strikeouts / $at_bats_walks;
	$player_average[$year][$split]['pct_ground_out']+= ($fielding_outs * $ground_ball_rate) / $at_bats_walks;
	$player_average[$year][$split]['pct_fly_out'] += ($fielding_outs * (1 - $ground_ball_rate)) / $at_bats_walks;
}

function updateBattingVPitcherSplit($name, $year, $battingstats, $split) {
	global $player_array;
	$player_gbr = $player_array[$name][$year]['Total']['gbr'];
	if (!isset($player_gbr)) {
		break;
	}
	foreach ($battingstats as $stats) {
		if ($stats['player_name'] !== $name) {
			continue;
		}
		if ($stats['at_bats_walks'] >= MIN_AT_BATS) {
			updatePlayerVPitcherBattingStats($stats, $player_gbr, $name, $year, $split);
			updateAveragePlayerVPitcherBattingStats($stats, $player_gbr, $year, $split);
			break;
		}
	}
}

//////////////////
// START SCRIPT //
//////////////////

// Index the stadium (i.e. batting_byfield) scripts
$stadium_stats_magic['2013'] = index_by($stadiumstats_magic_2013, 'stadium');
$stadium_stats_magic['2014'] = index_by($stadiumstats_magic_2014, 'stadium');
$stadium_stats_nomagic['2013'] = index_by($stadiumstats_nomagic_2013, 'stadium');
$stadium_stats_nomagic['2014'] = index_by($stadiumstats_nomagic_2014, 'stadium');

// Now index the pitcher information
$starting_pitcher_era_map_2014 = array(
	array(
		'era',
		'hand',
		'innings',
		'name',
		'2014bin',
		'2013bin', 
		'2013innings',
		'2013era',
		'2013default',
		'2014default'
	)
);
$era_values_2014 = array();
foreach ($pitchingstats_2014 as $j => $stats) {
	$player_name = $stats['player_name'];
	$innings = $stats['innings'];
	if (isset($starting_pitcher_era_map_2014[$player_name])) {
		continue;
	} else if ($stats['split'] !== 'Total') {
		continue;
	} else if (!$innings || $innings < MIN_PITCHER_INNINGS) {
		$starting_pitcher_era_map_2014[$player_name]['name'] = $player_name;
		$starting_pitcher_era_map_2014[$player_name]['hand'] = $stats['handedness'];
		$starting_pitcher_era_map_2014[$player_name]['innings'] = $stats['innings'];
		$starting_pitcher_era_map_2014[$player_name]['default'] = 1;
		continue;
	} else {
		$starting_pitcher_era_map_2014[$player_name]['name'] = $player_name;
		$starting_pitcher_era_map_2014[$player_name]['era'] = $stats['earned_run_average'];
		$starting_pitcher_era_map_2014[$player_name]['hand'] = $stats['handedness'];
		$starting_pitcher_era_map_2014[$player_name]['innings'] = $stats['innings'];
		$starting_pitcher_era_map_2014[$player_name]['default'] = 0;
		// Add values only to array for ERA bucket sorting
        array_push($era_values_2014, $stats['earned_run_average']);
	}
}

$era_values_2013 = array();
foreach ($pitchingstats_2013 as $j => $stats) {
	$player_name = $stats['player_name'];
	$innings = $stats['innings'];
	if ($stats['split'] !== 'Total') {
        continue;
    } else if (!$innings || $innings < MIN_PITCHER_INNINGS) {
		$starting_pitcher_era_map_2014[$player_name]['name'] = $player_name;
		$starting_pitcher_era_map_2014[$player_name]['hand'] = $stats['handedness'];
		$starting_pitcher_era_map_2014[$player_name]['2013innings'] = $stats['innings'];
		$starting_pitcher_era_map_2014[$player_name]['2013default'] = 1;
        continue;
    } else {
		$starting_pitcher_era_map_2014[$player_name]['name'] = $player_name;
		$starting_pitcher_era_map_2014[$player_name]['hand'] = $stats['handedness'];
        $starting_pitcher_era_map_2014[$player_name]['2013era'] = $stats['earned_run_average'];
        $starting_pitcher_era_map_2014[$player_name]['2013innings'] = $stats['innings'];
		$starting_pitcher_era_map_2014[$player_name]['2013default'] = 0;
		// Add values only to array for ERA bucket sorting
		array_push($era_values_2013, $stats['earned_run_average']);
    }
}

$batter_vs_era_25_2013 = array();
$batter_vs_era_50_2013 = array();
$batter_vs_era_75_2013 = array();
$batter_vs_era_100_2013 = array();
sort($era_values_2013);
$era_divider_2013 = count($era_values_2013) / 4;
$era_25_2013 = $era_values_2013[$era_divider_2013];
$era_50_2013 = $era_values_2013[$era_divider_2013 * 2];
$era_75_2013 = $era_values_2013[$era_divider_2013 * 3];

// Build 2013 ERA batting arrays using batter_vspitcher data
foreach ($battingstats_vpitcher_2013 as $batter) {
	$pitcher_name = $batter['vs_pitcher'];
	$pitcher_era = $starting_pitcher_era_map_2014[$pitcher_name]['2013era'];
	if (!isset($pitcher_era)) {
		continue;
	}
	switch (true) {
		case ($pitcher_era < $era_25_2013):
			$batter_vs_era_25_2013 = getBatterVPitcherStats($batter, $batter_vs_era_25_2013);
			break;
		case ($pitcher_era >= $era_25_2013 && $pitcher_era < $era_50_2013):
			$batter_vs_era_50_2013 = getBatterVPitcherStats($batter, $batter_vs_era_50_2013);
			break;
		case ($pitcher_era >= $era_50_2013 && $pitcher_era < $era_75_2013):
			$batter_vs_era_75_2013 = getBatterVPitcherStats($batter, $batter_vs_era_75_2013);
			break;
		case ($pitcher_era >= $era_75_2013):
			$batter_vs_era_100_2013 = getBatterVPitcherStats($batter, $batter_vs_era_100_2013);
			break;
	}
}

// Add 2013 Pitcher Bin data to ERA Map
foreach ($starting_pitcher_era_map_2014 as $name => $pitcher) {	
	$pitcher_era = $pitcher['2013era'];
	$innings = $pitcher['2013innings'];
	if (!$innings || $innings < MIN_PITCHER_INNINGS) {
		$starting_pitcher_era_map_2014[$name]['2013bin'] = ERA_BIN_DEFAULT;
		$starting_pitcher_era_map_2014[$name]['2013innings'] = 0;
        $starting_pitcher_era_map_2014[$name]['2013era'] = null;
		$starting_pitcher_era_map_2014[$name]['2013default'] = 1;
		continue;
	}
	switch (true) {
		case ($pitcher_era < $era_25_2013):
			$starting_pitcher_era_map_2014[$name]['2013bin'] = 25;
			break;
		case ($pitcher_era >= $era_25_2013 && $pitcher_era < $era_50_2013):
			$starting_pitcher_era_map_2014[$name]['2013bin'] = 50;
			break;
		case ($pitcher_era >= $era_50_2013 && $pitcher_era < $era_75_2013):
			$starting_pitcher_era_map_2014[$name]['2013bin'] = 75;
			break;
		case ($pitcher_era >= $era_75_2013):
			$starting_pitcher_era_map_2014[$name]['2013bin'] = 100;
			break;
	}
}

$batter_vs_era_25_2014 = array();
$batter_vs_era_50_2014 = array();
$batter_vs_era_75_2014 = array();
$batter_vs_era_100_2014 = array();
sort($era_values_2014);
$era_divider_2014 = count($era_values_2014) / 4;
$era_25_2014 = $era_values_2014[$era_divider_2014];
$era_50_2014 = $era_values_2014[$era_divider_2014 * 2];
$era_75_2014 = $era_values_2014[$era_divider_2014 * 3];

foreach ($battingstats_vpitcher_2014 as $batter) {	
	$pitcher_name = $batter['vs_pitcher'];
    $pitcher_era = $starting_pitcher_era_map_2014[$pitcher_name]['era'];
    if (!isset($pitcher_era)) {
        continue;
    }
	switch (true) {
        case ($pitcher_era < $era_25_2014):
            $batter_vs_era_25_2014 = getBatterVPitcherStats($batter, $batter_vs_era_25_2014);
            break;
        case ($pitcher_era >= $era_25_2014 && $pitcher_era < $era_50_2014):
            $batter_vs_era_50_2014 = getBatterVPitcherStats($batter, $batter_vs_era_50_2014);
            break;
        case ($pitcher_era >= $era_50_2014 && $pitcher_era < $era_75_2014):
            $batter_vs_era_75_2014 = getBatterVPitcherStats($batter, $batter_vs_era_75_2014);
            break;
        case ($pitcher_era >= $era_75_2014):
            $batter_vs_era_100_2014 = getBatterVPitcherStats($batter, $batter_vs_era_100_2014);
            break;
    }
}

foreach ($starting_pitcher_era_map_2014 as $name => $pitcher) {	
	if ($pitcher[0] == 'era') {
		continue;
	}
	if (!$pitcher['era']) {
		$starting_pitcher_era_map_2014[$name]['innings'] = 0;
		$starting_pitcher_era_map_2014[$name]['era'] = null;
		$starting_pitcher_era_map_2014[$name]['2014bin'] = ERA_BIN_DEFAULT;
		$starting_pitcher_era_map_2014[$name]['2014default'] = 1;
		continue;
	}
	$pitcher_era = $pitcher['era'];
	$starting_pitcher_era_map_2014[$name]['2014default'] = 0;
	switch (true) {
        case ($pitcher_era < $era_25_2014):
            $starting_pitcher_era_map_2014[$name]['2014bin'] = 25;
            break;
        case ($pitcher_era >= $era_25_2014 && $pitcher_era < $era_50_2014):
            $starting_pitcher_era_map_2014[$name]['2014bin'] = 50;
            break;
        case ($pitcher_era >= $era_50_2014 && $pitcher_era < $era_75_2014):
            $starting_pitcher_era_map_2014[$name]['2014bin'] = 75;
            break;
        case ($pitcher_era >= $era_75_2014):
            $starting_pitcher_era_map_2014[$name]['2014bin'] = 100;
            break;
    }
}

$player_stat_difference = array();
$average_player_stat_difference = array();
$player_array = array();
$final_player_array = array(array('player_name', 'stats'));
$magic_player_array = array(array('player_name', 'stats'));
$player_defaults = array();
$players_2013 = array();
$player_average = array();
$average_gbr['2013'] = array();
$average_gbr['2014'] = array();

foreach ($all_players_2013 as $player) {
	$id = $player['id'];
	$name = $player['unixname'];
	//Make an array of 2013 names to x-check to 2014 players
	array_push($players_2013, $name);
	updatePlayerBatting($name, '2013', $battingstats_expanded_2013, 'Total');
	if (!$player_array[$name]['2013']['Total']) {
		error_log("Missing Batter Total: ".$name." 2013 "."\n", 3, 
			"/Users/baseball/Desktop/Baseball/Scripts/Errors/missing_batting_splits_error.log");
		foreach ($splits as $split) {
			$player_defaults[$name]['2013'][$split] = 1;
		}
		// Don't bother grabbing their other splits in this case...
		continue;
	}
	// Now just update the array for the other batting splits
	updatePlayerBatting($name, '2013', $battingstats_expanded_home_2013, 'Home');
	updatePlayerBatting($name, '2013', $battingstats_expanded_away_2013, 'Away');
	updatePlayerBatting($name, '2013', $battingstats_expanded_vsleft_2013, 'VsLeft');
	updatePlayerBatting($name, '2013', $battingstats_expanded_vsright_2013, 'VsRight');
	updatePlayerBatting($name, '2013', $battingstats_expanded_noneon_2013, 'NoneOn');
	updatePlayerBatting($name, '2013', $battingstats_expanded_runnerson_2013, 'RunnersOn');
	updatePlayerBatting($name, '2013', $battingstats_expanded_scoringpos_2013, 'ScoringPos');
	updatePlayerBatting($name, '2013', $battingstats_expanded_scoringpos2out_2013, 'ScoringPos2Out');
	updatePlayerBatting($name, '2013', $battingstats_expanded_basesloaded_2013, 'BasesLoaded');
	updateBattingVPitcherSplit($name, '2013', $batter_vs_era_25_2013, 25);
	updateBattingVPitcherSplit($name, '2013', $batter_vs_era_50_2013, 50);
	updateBattingVPitcherSplit($name, '2013', $batter_vs_era_75_2013, 75);
	updateBattingVPitcherSplit($name, '2013', $batter_vs_era_100_2013, 100);
	foreach ($splits as $split) {
		$player_defaults[$name]["2013"][$split] = 0;
		if (!isset($player_array[$name]['2013'][$split])) {
			error_log("Missing Batter: ".$name." 2013 ".$split."\n", 3, 
				"/Users/baseball/Desktop/Baseball/Scripts/Errors/missing_batting_splits_error_extra.log");
			$player_array[$name]['2013'][$split] = $player_array[$name]['2013']['Total'];
			// Default 2 signifies that a player's stats were defauled to his Total
			$player_defaults[$name]["2013"][$split] = 2;
		}
	}
}

foreach ($all_players_2014 as $player) {
	$id = $player['id'];
	$name = $player['unixname'];
	// Add in null 2013 array for new players
	if (!in_array($name, $players_2013)) {
		foreach ($splits as $split) {
			$player_defaults[$name]["2013"][$split] = 1;
		}
		$player_array[$name]['2013'] = null;
	}
	updatePlayerBatting($name, '2014', $battingstats_expanded_2014, 'Total');
	if (!$player_array[$name]['2014']['Total']) {
		error_log("Missing Batter Total: ".$name." 2014 "."\n", 3, 
			"/Users/baseball/Desktop/Baseball/Scripts/Errors/missing_batting_splits_error.log");
		foreach ($splits as $split) {
			$player_defaults[$name]["2014"][$split] = 1;
		}
		// Don't bother grabbing their other splits in this case...
		continue;
	}
	updatePlayerBatting($name, '2014', $battingstats_expanded_home_2014, 'Home');
    updatePlayerBatting($name, '2014', $battingstats_expanded_away_2014, 'Away');
    updatePlayerBatting($name, '2014', $battingstats_expanded_vsleft_2014, 'VsLeft');
    updatePlayerBatting($name, '2014', $battingstats_expanded_vsright_2014, 'VsRight');
    updatePlayerBatting($name, '2014', $battingstats_expanded_noneon_2014, 'NoneOn');
    updatePlayerBatting($name, '2014', $battingstats_expanded_runnerson_2014, 'RunnersOn');
    updatePlayerBatting($name, '2014', $battingstats_expanded_scoringpos_2014, 'ScoringPos');
    updatePlayerBatting($name, '2014', $battingstats_expanded_scoringpos2out_2014, 'ScoringPos2Out');
    updatePlayerBatting($name, '2014', $battingstats_expanded_basesloaded_2014, 'BasesLoaded');
    updateBattingVPitcherSplit($name, '2014', $batter_vs_era_25_2014, 25);
    updateBattingVPitcherSplit($name, '2014', $batter_vs_era_50_2014, 50);
    updateBattingVPitcherSplit($name, '2014', $batter_vs_era_75_2014, 75);
    updateBattingVPitcherSplit($name, '2014', $batter_vs_era_100_2014, 100);
	foreach ($splits as $split) {
		$player_defaults[$name]["2014"][$split] = 0;
		if (!isset($player_array[$name]['2014'][$split])) {
			error_log("Missing Batter: ".$name." 2014 ".$split."\n", 3, 
				"/Users/baseball/Desktop/Baseball/Scripts/Errors/missing_batting_splits_error_extra.log");
			$player_array[$name]['2014'][$split] = $player_array[$name]['2014']['Total'];
			$player_defaults[$name]["2014"][$split] = 2;
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

foreach ($average_gbr as $year_name => $year) {
	foreach ($year as $split_name => $stats) {
		$gbr = $stats['rate'] / $stats['count'];
		$average_gbr[$year_name][$split_name]['gbr'] = $gbr;
		// Add gbr to player average final aka Joe Average later on
		$player_average_final[$year_name][$split_name]['gbr'] = $gbr;
	}
}

// Add default info for any player that is missing either year of data
foreach ($player_array as $name => $info) {
	foreach ($info as $year => $data) {
		if (isset($data)) {
			continue;
		} else {
			foreach ($splits as $split) {
				if (isset($player_average_final[$year][$split])) {
					$player_array[$name][$year][$split] = $player_average_final[$year][$split];
					$player_array[$name][$year][$split]['player_name'] = $name;
					$player_defaults[$name][$year][$split] = 1;
				} else {
					$player_array[$name][$year][$split] = $player_average_final[$year]['Total'];
					$player_array[$name][$year][$split]['player_name'] = $name;
					$player_defaults[$name][$year][$split] = 1;
				}
			}
		}
	}
}

// Hack - early season make sure all players have 2014 data
foreach ($all_players_2013 as $player) {
		$name = $player['unixname']; 
		if (isset($player_array[$name]['2014'])) {
            continue;
        } else {
            foreach ($splits as $split) {
                if (isset($player_average_final['2014'][$split])) {
					$player_array[$name]['2014'][$split] = $player_average_final['2014'][$split];
					$player_array[$name]['2014'][$split]['player_name'] = $name;
					$player_defaults[$name]['2014'][$split] = 1;
                } else {
					$player_array[$name]['2014'][$split] = $player_average_final['2014']['Total'];
					$player_array[$name]['2014'][$split]['player_name'] = $name;
					$player_defaults[$name]['2014'][$split] = 1;
                }
            }
        }
}
// Hack - add joe_average for early in the season for use in sim
// when players haven't been added to players_2014..sorry its not that scalable
foreach ($splits as $split) {
    if (isset($player_average_final['2013'][$split])) {
        $player_array['joe_average']['2013'][$split] = $player_average_final['2013'][$split];
    } else {
        $player_array['joe_average']['2013'][$split] = $player_average_final['2013']['Total'];
    }
	if (isset($player_average_final['2014'][$split])) {
        $player_array['joe_average']['2014'][$split] = $player_average_final['2014'][$split];
    } else {
        $player_array['joe_average']['2014'][$split] = $player_average_final['2014']['Total'];
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
			// Add in default information here
			if (isset($player_defaults[$name][$year][$split_name])) {
				$player_array[$name][$year][$split_name]['default'] = $player_defaults[$name][$year][$split_name];
				$player_stat_difference[$name][$year][$split_name]['default'] = $player_defaults[$name][$year][$split_name];
			} else {
				$player_array[$name][$year][$split_name]['default'] = 1;
				$player_stat_difference[$name][$year][$split_name]['default'] = 1;
			}
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

$player_stat_difference['stadium']['2013'] = $stadium_stats_magic['2013'];
$player_stat_difference['stadium']['2014'] = $stadium_stats_magic['2014'];
$player_array['stadium']['2013'] = $stadium_stats_nomagic['2013'];
$player_array['stadium']['2014'] = $stadium_stats_nomagic['2014'];

foreach ($player_array as $name => $info) {
	$final_player_array[$name] = array($name, json_encode($info));
}

foreach ($player_stat_difference as $name => $info) {
	$magic_player_array[$name] = array($name, json_encode($info));
}

// Run function to export the data to mysql, backup a copy to csv,
// and leave a record in table_status -> in sweetfunctions.php
$table_name = 'batting_final_nomagic_2014';
//export_and_save($database, $table_name, $final_player_array, $date);

// Run function to export the data to mysql, backup a copy to csv,
// and leave a record in table_status -> in sweetfunctions.php
$table_name = 'batting_final_magic_2014';
//export_and_save($database, $table_name, $magic_player_array, $date);

// Run function to export the data to mysql, backup a copy to csv,
// and leave a record in table_status -> in sweetfunctions.php
$table_name = 'era_map_2014';
//export_and_save($database, $table_name, $starting_pitcher_era_map_2014, $date);

?>
