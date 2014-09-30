<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php'); 
include(HOME_PATH.'Include/sweetfunctions.php');

// Modify date if used for backfilling
if ($argv[1]) {
	$date = $argv[1];
}

// Pull 2013 Data //
$all_players_2013 = exe_sql($database,
	'SELECT *
	FROM players_2013'
	);   
checkSQLError($all_players_2013, 'players_2013');          
$battingstats_expanded_2013 = exe_sql($database,
	'SELECT *
	FROM batting_total_2013'
	);  
checkSQLError($battingstats_expanded_2013, 'batting_total_2013'); 
$battingstats_expanded_home_2013 = exe_sql($database,
	'SELECT *
	FROM batting_home_2013'
	); 
checkSQLError($battingstats_expanded_home_2013, 'batting_home_2013'); 
$battingstats_expanded_away_2013 = exe_sql($database,
	'SELECT *
	FROM batting_away_2013'
	); 
checkSQLError($battingstats_expanded_away_2013, 'batting_away_2013'); 
$battingstats_expanded_vsleft_2013 = exe_sql($database,
	'SELECT *
	FROM batting_vsleft_2013'
	); 
checkSQLError($battingstats_expanded_vsleft_2013, 'batting_vsleft_2013'); 
$battingstats_expanded_vsright_2013 = exe_sql($database,
	'SELECT *
	FROM batting_vsright_2013'
	); 
checkSQLError($battingstats_expanded_vsright_2013, 'batting_vsright_2013'); 
$battingstats_expanded_noneon_2013 = exe_sql($database,
	'SELECT *
	FROM batting_noneon_2013'
	); 
checkSQLError($battingstats_expanded_noneon_2013, 'batting_noneon_2013');
$battingstats_expanded_runnerson_2013 = exe_sql($database,
	'SELECT *
	FROM batting_runnerson_2013'
	); 
checkSQLError($battingstats_expanded_runnerson_2013, 'batting_runnerson_2013');
$battingstats_expanded_scoringpos_2013 = exe_sql($database,
	'SELECT *
	FROM batting_scoringpos_2013'
	); 
checkSQLError($battingstats_expanded_scoringpos_2013, 'batting_scoringpos_2013');
$battingstats_expanded_basesloaded_2013 = exe_sql($database,
	'SELECT *
	FROM batting_basesloaded_2013'
	);
checkSQLError($battingstats_expanded_basesloaded_2013, 'batting_basesloaded_2013');
$battingstats_expanded_scoringpos2out_2013 = exe_sql($database,
	'SELECT *
	FROM batting_scoringpos2out_2013'
	); 
checkSQLError($battingstats_expanded_scoringpos2out_2013, 'batting_scoringpos2out_2013');
$pitchingstats_2013 = exe_sql($database,
	'SELECT *
	FROM pitching_2013'
	);
checkSQLError($pitchingstats_2013, 'pitching_2013');
$battingstats_vpitcher_2013 = exe_sql($database,
    'SELECT *
    FROM batting_vspitcher_2013'
    ); 
checkSQLError($battingstats_vpitcher_2013, 'batting_vspitcher_2013');
$stadiumstats_2013 = exe_sql($database,
	'SELECT *
	FROM batting_byfield_magic_2013'
	); 
checkSQLError($stadiumstats_2013, 'batting_byfield_magic_2013');
$stadiumstats_nomagic_2013 = exe_sql($database,
    'SELECT *
    FROM batting_byfield_nomagic_2013'
    );
checkSQLError($stadiumstats_nomagic_2013, 'batting_byfield_nomagic_2013');

// Pull 2014 Data //
$all_players_2014 = exe_sql($database,
	'SELECT *
	FROM players_2014
	WHERE ds = "'.$date.'"'
	);   
checkSQLError($all_players_2014, 'players_2014');          
/*
$startingpitchers2014 = exe_sql($database,
	'SELECT away, home, away_pitcher_first,
	away_pitcher_last, home_pitcher_first,
	home_pitcher_last, away_handedness,
	home_handedness
	FROM lineups_2014
	WHERE ds = "'.$date.'"'
	);  
checkSQLError($startingpitchers2014, 1, 15); 
 */
$battingstats_expanded_2014 = exe_sql($database,
	'SELECT *
	FROM batting_total_2014 
	WHERE ds = "'.$date.'"'
); 
checkSQLError($battingstats_expanded_2014, 'batting_total_2014'); 
$battingstats_expanded_home_2014 = exe_sql($database,
	'SELECT *
	FROM batting_home_2014
	WHERE ds = "'.$date.'"'
	); 
checkSQLError($battingstats_expanded_home_2014, 'batting_home_2014'); 
$battingstats_expanded_away_2014 = exe_sql($database,
	'SELECT *
	FROM batting_away_2014
	WHERE ds = "'.$date.'"'
	); 
checkSQLError($battingstats_expanded_away_2014, 'batting_away_2014'); 
$battingstats_expanded_vsleft_2014 = exe_sql($database,
	'SELECT *
	FROM batting_vsleft_2014
	WHERE ds = "'.$date.'"'
	); 
checkSQLError($battingstats_expanded_vsleft_2014, 'batting_vsleft_2014'); 
$battingstats_expanded_vsright_2014 = exe_sql($database,
	'SELECT *
	FROM batting_vsright_2014
	WHERE ds = "'.$date.'"'
	); 
checkSQLError($battingstats_expanded_vsright_2014, 'batting_vsright_2014'); 
$battingstats_expanded_noneon_2014 = exe_sql($database,
	'SELECT *
	FROM batting_noneon_2014
	WHERE ds = "'.$date.'"'
	); 
checkSQLError($battingstats_expanded_noneon_2014, 'batting_noneon_2014');
$battingstats_expanded_runnerson_2014 = exe_sql($database,
	'SELECT *
	FROM batting_runnerson_2014
	WHERE ds = "'.$date.'"'
	); 
checkSQLError($battingstats_expanded_runnerson_2014, 'batting_runnerson_2014');
$battingstats_expanded_scoringpos_2014 = exe_sql($database,
	'SELECT *
	FROM batting_scoringpos_2014
	WHERE ds = "'.$date.'"'
	); 
checkSQLError($battingstats_expanded_scoringpos_2014, 'batting_scoringpos_2014');
$battingstats_expanded_basesloaded_2014 = exe_sql($database,
	'SELECT *
	FROM batting_basesloaded_2014
	WHERE ds = "'.$date.'"'
	);
checkSQLError($battingstats_expanded_basesloaded_2014, 'batting_basesloaded_2014');
$battingstats_expanded_scoringpos2out_2014 = exe_sql($database,
	'SELECT *
	FROM batting_scoringpos2out_2014
	WHERE ds = "'.$date.'"'
	); 
checkSQLError($battingstats_expanded_scoringpos2out_2014, 'batting_scoringpos2out_2014');
$battingstats_vpitcher_2014 = exe_sql($database,
	'SELECT *
	FROM batting_vspitcher_2014
	WHERE ds = "'.$date.'"'
	); 
checkSQLError($battingstats_vpitcher_2014, 'batting_vspitcher_2014');
$pitchingstats_2014 = exe_sql($database,
	'SELECT *
	FROM pitching_2014
	WHERE ds = "'.$date.'"'
	); 
checkSQLError($pitchingstats_2014, 'pitching_2014');
$stadiumstats_2014 = exe_sql($database,
	'SELECT *
	FROM batting_byfield_magic_2014
	WHERE ds = "'.$date.'"'
); 
checkSQLError($stadiumstats_2014, 'batting_byfield_magic_2014');
$stadiumstats_nomagic_2014 = exe_sql($database,
    'SELECT *
    FROM batting_byfield_nomagic_2014
    WHERE ds = "'.$date.'"'
);
//checkSQLError($stadiumstats_nomagic_2014, 'batting_byfield_nomagic_2014');
$pitcher_batting_2014 = exe_sql($database,
	"SELECT * 
	FROM batting_vspitcher_aggregate_2014
	WHERE ds = '$date'"
);

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

function getAverageGBR($stats, $average_gbr) {
	$ground_balls = $stats['ground_balls'];
	$fly_balls = $stats['fly_balls'];
	$ground_ball_rate = ($ground_balls / ($ground_balls + $fly_balls)) ?: .5;
	return $ground_ball_rate;
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

function getAverageBattingSplit($name, $player_average, $average_gbr, $year, $battingstats, $split) {

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
			$average_gbr[$year][$split]['rate'] += getAverageGBR($stats, $average_gbr[$year][$split]);
			$average_gbr[$year][$split]['count'] += 1;
			break;
		}
	}
	return array('stats' => $player_average, 'gbr' => $average_gbr);
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

function getAverageBattingVPitcherSplit($name, $player_array, $player_average, $average_gbr, $year, $battingstats, $split) {

	$player_gbr = $player_array[$name][$year]['Total']['gbr'];
	if (!isset($player_gbr)) {
		return array('stats' => $player_average, 'gbr' => $average_gbr);
	}
	foreach ($battingstats as $stats) {
		if ($stats['player_name'] != $name) {
			continue;
		}
		$player_batting = getAveragePlayerVPitcherBattingStats($stats, $player_gbr, $player_average[$year][$split]);
		$player_average[$year][$split] = $player_batting;
		//$average_gbr[$year] += 1;
		break;
	}
	return array('stats' => $player_average, 'gbr' => $average_gbr);	
}

$stadium_stats = array();
$stadium_stats_nomagic = array();
foreach ($stadiumstats_2013 as $stadium) {
	$stadium_name = $stadium['stadium'];
	$stadium_stats['2013'][$stadium_name] = $stadium;
}
foreach ($stadiumstats_2014 as $stadium) {
	$stadium_name = $stadium['stadium'];
	$stadium_stats['2014'][$stadium_name] = $stadium;
}

foreach ($stadiumstats_nomagic_2013 as $stadium) {
    $stadium_name = $stadium['stadium'];
    $stadium_stats_nomagic['2013'][$stadium_name] = $stadium;
}
foreach ($stadiumstats_nomagic_2014 as $stadium) {
    $stadium_name = $stadium['stadium'];
    $stadium_stats_nomagic['2014'][$stadium_name] = $stadium;
}

$pitcher_era_map_2014 = array();
$starting_pitcher_era_map_2014 = array(array('era','hand','innings','name','2014bin','2013bin', '2013innings','2013era','2013default','2014default'));
foreach ($pitchingstats_2014 as $j => $stats) {
	if (isset($pitcher_era_map_2014[$stats['player_name']])) {
		continue;
	} else if ($stats['split'] == 'Total') {
		$starting_pitcher_era_map_2014[$stats['player_name']]['era'] = $stats['earned_run_average'];
		$starting_pitcher_era_map_2014[$stats['player_name']]['hand'] = $stats['handedness'];
		$starting_pitcher_era_map_2014[$stats['player_name']]['innings'] = $stats['innings'];
		if ($stats['innings'] >= 18) {
			$pitcher_era_map_2014[$stats['player_name']] = $stats['earned_run_average'];
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
        // Hack to get their 2013 stats in 2014 at beginning of season
        $starting_pitcher_era_map_2014[$stats['player_name']]['hand'] = $stats['handedness'];
        if ($stats['innings'] >= 18) {
            $pitcher_era_map_2013[$stats['player_name']] = $stats['earned_run_average'];
        }
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
}

$era_values_2014 = array_values($pitcher_era_map_2014);
sort($era_values_2014);
$era_divider_2014 = count($era_values_2014) / 4;
$batter_vs_era_25_2014 = array();
$batter_vs_era_50_2014 = array();
$batter_vs_era_75_2014 = array();
$batter_vs_era_100_2014 = array();
$era_25_2014 = $era_values_2014[$era_divider_2014];
$era_50_2014 = $era_values_2014[$era_divider_2014 * 2];
$era_75_2014 = $era_values_2014[$era_divider_2014 * 3];

foreach ($battingstats_vpitcher_2014 as $batter) {	
	$pitcher_era = $pitcher_era_map_2014[$batter['vs_pitcher']];
	if (!isset($pitcher_era)) {
		continue;
	}
	if ($pitcher_era < $era_25_2014) {
		$batter_vs_era_25_2014 = getBatterVPitcherStats($batter, $batter_vs_era_25_2014);
	}
	if ($pitcher_era >= $era_25_2014 && $pitcher_era < $era_50_2014) {
		$batter_vs_era_50_2014 = getBatterVPitcherStats($batter, $batter_vs_era_50_2014);
	}
	if ($pitcher_era >= $era_50_2014 && $pitcher_era < $era_75_2014) {
		$batter_vs_era_75_2014 = getBatterVPitcherStats($batter, $batter_vs_era_75_2014);
	}
	if ($pitcher_era >= $era_75_2014) {
		$batter_vs_era_100_2014 = getBatterVPitcherStats($batter, $batter_vs_era_100_2014);
	}
}

foreach ($starting_pitcher_era_map_2014 as $name => $pitcher) {	

	if ($pitcher[0] == 'era') {
		continue;
	}

	$starting_pitcher_era_map_2014[$name]['name'] = $name;

	if (isset($starting_pitcher_era_map_2013[$name]['2013bin'])) {
        $starting_pitcher_era_map_2014[$name]['2013bin'] = $starting_pitcher_era_map_2013[$name]['2013bin'];
        $starting_pitcher_era_map_2014[$name]['2013innings'] = $starting_pitcher_era_map_2013[$name]['innings'];
		$starting_pitcher_era_map_2014[$name]['2013era'] = $starting_pitcher_era_map_2013[$name]['era'];
		$starting_pitcher_era_map_2014[$name]['2013default'] = 0;
		if ($starting_pitcher_era_map_2013[$name]['innings'] < 18) {
			$starting_pitcher_era_map_2014[$name]['2013default'] = 1;
		}
	} else {
		$starting_pitcher_era_map_2014[$name]['2013bin'] = 75;
		$starting_pitcher_era_map_2014[$name]['2013innings'] = 0;
		$starting_pitcher_era_map_2014[$name]['2013era'] = null;
		$starting_pitcher_era_map_2014[$name]['2013default'] = 1;
	}

	// This will happen in the beginning of season when players have not played games yet
	if (!$pitcher['era']) {
		$starting_pitcher_era_map_2014[$name]['innings'] = 0;
		$starting_pitcher_era_map_2014[$name]['era'] = null;
		$starting_pitcher_era_map_2014[$name]['2014bin'] = 75;
		$starting_pitcher_era_map_2014[$name]['2014default'] = 1;
		continue;
	}

	$pitcher_era = $pitcher['era'];
	$innings = $pitcher['innings'];
	$starting_pitcher_era_map_2014[$name]['2014default'] = 0;
	if ($pitcher_era < $era_25_2014) {
		$starting_pitcher_era_map_2014[$name]['2014bin'] = 25;
	}
	if ($pitcher_era >= $era_25_2014 && $pitcher_era < $era_50_2014) {
		$starting_pitcher_era_map_2014[$name]['2014bin'] = 50;
	}
	if ($pitcher_era >= $era_50_2014 && $pitcher_era < $era_75_2014) {
		$starting_pitcher_era_map_2014[$name]['2014bin'] = 75;
	}
	if ($pitcher_era >= $era_75_2014) {
		$starting_pitcher_era_map_2014[$name]['2014bin'] = 100;
	}
	if ($innings < 18) {
		$starting_pitcher_era_map_2014[$name]['2014bin'] = 75;
		$starting_pitcher_era_map_2014[$name]['2014default'] = 1;
	}
}

$player_stat_difference = array();
$average_player_stat_difference = array();
$player_array = array();
$final_player_array = array(array('player_name', 'stats'));
$magic_player_array = array(array('player_name', 'stats'));
$player_defaults = array();
$players_2013 = array();
$players_2014 = array();
$player_average = array();
$average_gbr[2013] = array();
$average_gbr[2014] = array();

foreach ($all_players_2013 as $player) {
	$id = $player['id'];
	$name = $player['unixname'];
	//Make an array of 2013 names to x-check to 2014 players
	array_push($players_2013, $name);
	$player_array = getBattingSplit($player_array, $name, '2013', $battingstats_expanded_2013, 'Total');
	$averages = getAverageBattingSplit($name, $player_average, $average_gbr, '2013', $battingstats_expanded_2013, 'Total');
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	if (!$player_array[$name]['2013']['Total']) {
		error_log("Missing Batter Total: ".$name." 2013 "."\n", 3, "/Users/baseball/Desktop/Baseball/Scripts/Errors/missing_batting_splits_error.log");
		$player_array[$name]['2013'] = null;
		foreach ($splits as $split) {
			$player_defaults[$name]["2013"][$split] = 1;
		}
		continue;
	}
	// I can probably make a function and clean this up...
	$player_array = getBattingSplit($player_array, $name, '2013', $battingstats_expanded_home_2013, 'Home');
	$averages = getAverageBattingSplit($name, $player_average, $average_gbr, '2013', $battingstats_expanded_home_2013, 'Home');
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingSplit($player_array, $name, '2013', $battingstats_expanded_away_2013, 'Away');
	$averages = getAverageBattingSplit($name, $player_average, $average_gbr, '2013', $battingstats_expanded_away_2013, 'Away');
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingSplit($player_array, $name, '2013', $battingstats_expanded_vsleft_2013, 'VsLeft');
	$averages = getAverageBattingSplit($name, $player_average, $average_gbr, '2013', $battingstats_expanded_vsleft_2013, 'VsLeft');
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingSplit($player_array, $name, '2013', $battingstats_expanded_vsright_2013, 'VsRight');
	$averages = getAverageBattingSplit($name, $player_average, $average_gbr, '2013', $battingstats_expanded_vsright_2013, 'VsRight');
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingSplit($player_array, $name, '2013', $battingstats_expanded_noneon_2013, 'NoneOn');
	$averages = getAverageBattingSplit($name, $player_average, $average_gbr, '2013', $battingstats_expanded_noneon_2013, 'NoneOn');
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingSplit($player_array, $name, '2013', $battingstats_expanded_runnerson_2013, 'RunnersOn');
	$averages = getAverageBattingSplit($name, $player_average, $average_gbr, '2013', $battingstats_expanded_runnerson_2013, 'RunnersOn');
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingSplit($player_array, $name, '2013', $battingstats_expanded_scoringpos_2013, 'ScoringPos');
	$averages = getAverageBattingSplit($name, $player_average, $average_gbr, '2013', $battingstats_expanded_scoringpos_2013, 'ScoringPos');
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingSplit($player_array, $name, '2013', $battingstats_expanded_scoringpos2out_2013, 'ScoringPos2Out');
	$averages = getAverageBattingSplit($name, $player_average, $average_gbr, '2013', $battingstats_expanded_scoringpos2out_2013, 'ScoringPos2Out');
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingSplit($player_array, $name, '2013', $battingstats_expanded_basesloaded_2013, 'BasesLoaded');
	$averages = getAverageBattingSplit($name, $player_average, $average_gbr, '2013', $battingstats_expanded_basesloaded_2013, 'BasesLoaded');
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingVPitcherSplit($player_array, $name, '2013', $batter_vs_era_25_2013, 25);
	$averages = getAverageBattingVPitcherSplit($name, $player_array, $player_average, $average_gbr, '2013', $batter_vs_era_25_2013, 25);
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingVPitcherSplit($player_array, $name, '2013', $batter_vs_era_50_2013, 50);
	$averages = getAverageBattingVPitcherSplit($name, $player_array, $player_average, $average_gbr, '2013', $batter_vs_era_50_2013, 50);
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingVPitcherSplit($player_array, $name, '2013', $batter_vs_era_75_2013, 75);
	$averages = getAverageBattingVPitcherSplit($name, $player_array, $player_average, $average_gbr, '2013', $batter_vs_era_75_2013, 75);
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingVPitcherSplit($player_array, $name, '2013', $batter_vs_era_100_2013, 100);
	$averages = getAverageBattingVPitcherSplit($name, $player_array, $player_average, $average_gbr, '2013', $batter_vs_era_100_2013, 100);
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	foreach ($splits as $split) {
		$player_defaults[$name]["2013"][$split] = 0;
		if (!isset($player_array[$name]['2013'][$split])) {
			error_log("Missing Batter: ".$name." 2013 ".$split."\n", 3, "/Users/baseball/Desktop/Baseball/Scripts/Errors/missing_batting_splits_error_extra.log");
			$player_array[$name]['2013'][$split] = $player_array[$name]['2013']['Total'];
			$player_defaults[$name]["2013"][$split] = 2;
		}
	}
}

foreach ($all_players_2014 as $player) {
	$id = $player['id'];
	$name = $player['unixname'];
	if (!in_array($name, $players_2013)) {
		foreach ($splits as $split) {
			$player_defaults[$name]["2013"][$split] = 1;
		}
		$player_array[$name]['2013'] = null;
	}
	array_push($players_2014, $name);
	$player_array = getBattingSplit($player_array, $name, '2014', $battingstats_expanded_2014, 'Total');
	$averages = getAverageBattingSplit($name, $player_average, $average_gbr, '2014', $battingstats_expanded_2014, 'Total');
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	if (!$player_array[$name]['2014']['Total']) {
		error_log("Missing Batter Total: ".$name." 2014 "."\n", 3, "/Users/baseball/Desktop/Baseball/Scripts/Errors/missing_batting_splits_error.log");
		$player_array[$name]['2014'] = null;
		foreach ($splits as $split) {
			$player_defaults[$name]["2014"][$split] = 1;
		}
		continue;
	}
	$player_array = getBattingSplit($player_array, $name, '2014', $battingstats_expanded_home_2014, 'Home');
	$averages = getAverageBattingSplit($name, $player_average, $average_gbr, '2014', $battingstats_expanded_home_2014, 'Home');
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingSplit($player_array, $name, '2014', $battingstats_expanded_away_2014, 'Away');
	$averages = getAverageBattingSplit($name, $player_average, $average_gbr, '2014', $battingstats_expanded_away_2014, 'Away');
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingSplit($player_array, $name, '2014', $battingstats_expanded_vsleft_2014, 'VsLeft');
	$averages = getAverageBattingSplit($name, $player_average, $average_gbr, '2014', $battingstats_expanded_vsleft_2014, 'VsLeft');
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingSplit($player_array, $name, '2014', $battingstats_expanded_vsright_2014, 'VsRight');
	$averages = getAverageBattingSplit($name, $player_average, $average_gbr, '2014', $battingstats_expanded_vsright_2014, 'VsRight');
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingSplit($player_array, $name, '2014', $battingstats_expanded_noneon_2014, 'NoneOn');
	$averages = getAverageBattingSplit($name, $player_average, $average_gbr, '2014', $battingstats_expanded_noneon_2014, 'NoneOn');
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingSplit($player_array, $name, '2014', $battingstats_expanded_runnerson_2014, 'RunnersOn');
	$averages = getAverageBattingSplit($name, $player_average, $average_gbr, '2014', $battingstats_expanded_runnerson_2014, 'RunnersOn');
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingSplit($player_array, $name, '2014', $battingstats_expanded_scoringpos_2014, 'ScoringPos');
	$averages = getAverageBattingSplit($name, $player_average, $average_gbr, '2014', $battingstats_expanded_scoringpos_2014, 'ScoringPos');
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingSplit($player_array, $name, '2014', $battingstats_expanded_scoringpos2out_2014, 'ScoringPos2Out');
	$averages = getAverageBattingSplit($name, $player_average, $average_gbr, '2014', $battingstats_expanded_scoringpos2out_2014, 'ScoringPos2Out');
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingSplit($player_array, $name, '2014', $battingstats_expanded_basesloaded_2014, 'BasesLoaded');
	$averages = getAverageBattingSplit($name, $player_average, $average_gbr, '2014', $battingstats_expanded_basesloaded_2014, 'BasesLoaded');
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingVPitcherSplit($player_array, $name, '2014', $batter_vs_era_25_2014, 25);
	$averages = getAverageBattingVPitcherSplit($name, $player_array, $player_average, $average_gbr, '2014', $batter_vs_era_25_2014, 25);
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingVPitcherSplit($player_array, $name, '2014', $batter_vs_era_50_2014, 50);
	$averages = getAverageBattingVPitcherSplit($name, $player_array, $player_average, $average_gbr, '2014', $batter_vs_era_50_2014, 50);
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingVPitcherSplit($player_array, $name, '2014', $batter_vs_era_75_2014, 75);
	$averages = getAverageBattingVPitcherSplit($name, $player_array, $player_average, $average_gbr, '2014', $batter_vs_era_75_2014, 75);
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	$player_array = getBattingVPitcherSplit($player_array, $name, '2014', $batter_vs_era_100_2014, 100);
	$averages = getAverageBattingVPitcherSplit($name, $player_array, $player_average, $average_gbr, '2014', $batter_vs_era_100_2014, 100);
	$player_average = $averages['stats'];
	$average_gbr = $averages['gbr'];
	foreach ($splits as $split) {
		$player_defaults[$name]["2014"][$split] = 0;
		if (!isset($player_array[$name]['2014'][$split])) {
			error_log("Missing Batter: ".$name." 2014 ".$split."\n", 3, "/Users/baseball/Desktop/Baseball/Scripts/Errors/missing_batting_splits_error_extra.log");
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

$player_stat_difference['stadium']['2013'] = $stadium_stats['2013'];
$player_stat_difference['stadium']['2014'] = $stadium_stats['2014'];
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
export_and_save($database, $table_name, $final_player_array, $date);

// Run function to export the data to mysql, backup a copy to csv,
// and leave a record in table_status -> in sweetfunctions.php
$table_name = 'batting_final_magic_2014';
export_and_save($database, $table_name, $magic_player_array, $date);

// Run function to export the data to mysql, backup a copy to csv,
// and leave a record in table_status -> in sweetfunctions.php
$table_name = 'era_map_2014';
export_and_save($database, $table_name, $starting_pitcher_era_map_2014, $date);

?>
