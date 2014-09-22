<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/sweetfunctions.php');

const DEFAULT_BIN = 75;
// Using this for website debugging (actual ERA is not used in model now)
const DEFAULT_ERA = 999;
const DEFAULT_HAND = 'Unknown';
const MIN_PITCHER_INNINGS = 18;

function checkGameID($gameid, &$gamearray) {

	$i = 10;
	$new_gameid = $gameid.$i;
	while (in_array($new_gameid, $gamearray)) {
		$new_gameid += 1;
	}
	array_push($gamearray, $new_gameid);
	return $new_gameid;
}

function addPitcherBatting($name, $pitcher_i) {
	global $player_batting_2014;
	global $pitcher_batting_2014;
	// Default pitcher stats if there is no data or < 18 at bats
	if (!$pitcher_batting_2014[$pitcher_i] || $pitcher_batting_2014[$pitcher_i]['at_bats'] < MIN_PITCHER_INNINGS) {
		$pitcher_i = 'joe_average';
	}
	$pitcher_stats = json_decode($pitcher_batting_2014[$pitcher_i]['stats'], true);

	// Run through pitcher stats 2x for each year's GBR
	$pitcher_stats_2013 = $pitcher_stats;
	$gbr_2013 = $player_batting_2014['2013'][$name]['Total']['gbr'];
	$pitcher_stats_2013['gbr'] = $gbr_2013;
	$pitcher_stats_2013['pct_ground_out'] = $pitcher_stats['pct_field_out'] * $gbr_2013;
	$pitcher_stats_2013['pct_fly_out'] = $pitcher_stats['pct_field_out'] * (1 - $gbr_2013);
	$pitcher_stats_2013['gbr_owner'] = 'batter';

	$pitcher_stats_2014 = $pitcher_stats;
	$gbr_2014 = $player_batting_2014['2014'][$name]['Total']['gbr'];
	$pitcher_stats_2014['gbr'] = $gbr_2014;
    $pitcher_stats_2014['pct_ground_out'] = $pitcher_stats['pct_field_out'] * $gbr_2014;
	$pitcher_stats_2014['pct_fly_out'] = $pitcher_stats['pct_field_out'] * (1 - $gbr_2014);
	$pitcher_stats_2014['gbr_owner'] = 'batter';

	$player_batting_2014['2013'][$name]['PitcherTotal'] =  $pitcher_stats_2013;
	$player_batting_2014['2014'][$name]['PitcherTotal'] =  $pitcher_stats_2014;
}

function addLineupDefaults($name, $year, $lineup_defaults) {
	global $player_batting_2014;
	if (!$player_batting_2014[$year][$name]['Total']) {
		return $lineup_defaults;
	}
	foreach ($player_batting_2014[$year][$name] as $split_name => $split) {
		$default = $split['default'];
		// Default == 2 represents defaulting to Total (so only want to count 
		// as half of a default for now)
		if ($default == 2) {
			$default = .5;
		}
		$lineup_defaults += $default;
	}
	return $lineup_defaults;
}

function addTotalLineupDefaults($name, $year, $lineup_defaults) {
    global $player_batting_2014;
    if (!$player_batting_2014[$year][$name]['Total']) {
        return $lineup_defaults;
    }
    $default = $player_batting_2014[$year][$name]['Total']['default'];
    $lineup_defaults += $default;
    return $lineup_defaults;
}

function checkDefault($input) {

	if (isset($input)) {
		return $input;
	} else {
		return 1;
	}
}

/////////////////
//START SCRIPT //
/////////////////

// Modify date if used for backfilling
 if ($argv[1]) {
     $date = $argv[1];
 }
$batting_script = 'batting_final_nomagic_2014';

$lineups_2014 = pullAllData('lineups_2014', $date);
$pitcher_batting_stats_2014 = pullAllData('batting_vspitcher_aggregate_2014', $date);
$player_batting_expanded_2014 = pullAllData($batting_script, $date);
$pitchingstats_2014 = pullAllData('pitching_2014', $date);
$starting_pitcher_era_map_2014 = pullAllData('era_map_2014', $date);
$fieldingstats_2013 = pullAllData('fielding_2013');
$fieldingstats_2014 = pullAllData('fielding_2014', $date);

$keys = array_keys($lineups_2014);
if (!is_numeric($keys[0])) {
    $lineup_h = json_decode($lineups_2014["home_lineup"], true);
    $lineup_a = json_decode($lineups_2014["away_lineup"], true);
    $lineups_2014 = array($lineups_2014);
    $lineups_2014[0]["home_lineup"] = $lineup_h;
    $lineups_2014[0]["away_lineup"] = $lineup_a;

} else {
    foreach ($lineups_2014 as $i => $schedule) {
        $lineup_h = json_decode($schedule["home_lineup"], true);
        $lineup_a = json_decode($schedule["away_lineup"], true);
        $lineups_2014[$i]["home_lineup"] = $lineup_h;
        $lineups_2014[$i]["away_lineup"] = $lineup_a;
    }
}

$player_batting_2014 = array();
foreach ($player_batting_expanded_2014 as $name) {
	$player_name = $name['player_name'];
	$stats = json_decode($name['stats'], true);
	foreach ($stats as $year => $data) {
		$player_batting_2014[$year][$player_name] = $data;
	}		
}

$pitcher_batting_2014 = array();
foreach ($pitcher_batting_stats_2014 as $pitcher) {
	$name = $pitcher['pitcher_name'];
	$pitcher_batting_2014[$name]['pitcher_name'] = $name;
	$pitcher_batting_2014[$name]['at_bats'] = $pitcher['at_bats'];
	$pitcher_batting_2014[$name]['stats'] = $pitcher['stats'];
}

$starting_pitcher_info_2014 = array();
foreach ($starting_pitcher_era_map_2014 as $pitcher) {
	$player_name = $pitcher['name'];
	$starting_pitcher_info_2014[$player_name]['hand'] = $pitcher['hand'];
	$starting_pitcher_info_2014[$player_name]['2013default'] = $pitcher['2013default'];
	$starting_pitcher_info_2014[$player_name]['2014default'] = $pitcher['2014default'];
	$starting_pitcher_info_2014[$player_name]['2014bin'] = $pitcher['2014bin'];
	$starting_pitcher_info_2014[$player_name]['2013bin'] = $pitcher['2013bin'];
	$starting_pitcher_info_2014[$player_name]['2013era'] = $pitcher['2013era'];
	$starting_pitcher_info_2014[$player_name]['2014era'] = $pitcher['era'];
}

// TO DO: ONCE YOU WANT TO USE FIGURE OUT WHY USING +=
// DOESN'T LOOK LIKE IT'LL MATTER IT'S JUST WIERD
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

$fieldingstats_2014_total = array();
foreach ($fieldingstats_2014 as $stats) {
	$fieldingstats_2014_total[$stats['player_name']]['putouts'] += $stats['putouts']; 
	$fieldingstats_2014_total[$stats['player_name']]['assists'] += $stats['assists'];
	$fieldingstats_2014_total[$stats['player_name']]['innings'] += $stats['innings'];  
	$fieldingstats_2014_total[$stats['player_name']]['errors'] += $stats['errors']; 
}
$fieldingstats_2014_average = array();
foreach ($fieldingstats_2014_total as $name => $stats) {
	$fieldingstats_2014_average['errors'] += $stats['errors'];
	$fieldingstats_2014_average['total'] += $stats['errors'] + $stats['putouts'] + $stats['assists'];
	$fieldingstats_2014_average['count'] += 1;
}

$fieldingstats_average['2013']['errors'] = $fieldingstats_2013_average['errors'] / $fieldingstats_2013_average['count'];
$fieldingstats_average['2013']['total'] = $fieldingstats_2013_average['total'] / $fieldingstats_2013_average['count'];
$fieldingstats_average['2014']['errors'] = $fieldingstats_2014_average['errors'] / $fieldingstats_2014_average['count'];
$fieldingstats_average['2014']['total'] = $fieldingstats_2014_average['total'] / $fieldingstats_2014_average['count'];

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
	'pitcher_h_2013_era_bucket_i',
	'pitcher_a_2013_era_bucket_i',
	'pitcher_h_2014_era_bucket_i',
	'pitcher_a_2014_era_bucket_i',
	'pitcher_h_era',
	'pitcher_a_era',
	'pitcher_era_delta',
	'fielding_mult_2013_home',
	'fielding_mult_2014_home',
	'fielding_mult_2013_away',
	'fielding_mult_2014_away',
	'lineup_h_stats',
	'lineup_a_stats',
	'stadium_stats',
	'gamenumber'
);
array_push($master_table, $col_heads);

foreach ($lineups_2014 as $i => $game) {

	$team_stats = array();
	$master_row = array();
	$date_i = $game['ds'];
	$month = substr($game['ds'], -5, 2);
	$day = substr($game['ds'], -2);
	$date_map = $month.$day;
	$time = $game['time_est'];
	$gameid = 20140000+$date_map;
	$gameid = checkGameID($gameid, $gamearray);
	$away_i = strtoupper($game['away']);
	$home_i = strtoupper($game['home']);
	$stadium = $stadiums[$home_i];

	$pitcher_h_i = checkDuplicatePlayers($game['home_pitcher_first']."_".$game['home_pitcher_last'], $home_i, $duplicate_names);
	$pitcher_a_i = checkDuplicatePlayers($game['away_pitcher_first']."_".$game['away_pitcher_last'], $away_i, $duplicate_names);
	$pitcher_h_handedness_i = $starting_pitcher_info_2014[$pitcher_h_i]['hand'] ?: DEFAULT_HAND;
	$pitcher_a_handedness_i = $starting_pitcher_info_2014[$pitcher_a_i]['hand'] ?: DEFAULT_HAND;
	$pitcher_h_2013_era_bucket_i = elvis($starting_pitcher_info_2014[$pitcher_h_i]['2013bin'], DEFAULT_BIN);
	$pitcher_a_2013_era_bucket_i = elvis($starting_pitcher_info_2014[$pitcher_a_i]['2013bin'], DEFAULT_BIN);
	$pitcher_h_2014_era_bucket_i = elvis($starting_pitcher_info_2014[$pitcher_h_i]['2014bin'], DEFAULT_BIN);
	$pitcher_a_2014_era_bucket_i = elvis($starting_pitcher_info_2014[$pitcher_a_i]['2014bin'], DEFAULT_BIN);
	$pitcher_h_2013_default = checkDefault($starting_pitcher_info_2014[$pitcher_h_i]['2013default']);
	$pitcher_h_2014_default = checkDefault($starting_pitcher_info_2014[$pitcher_h_i]['2014default']);
	$pitcher_a_2013_default = checkDefault($starting_pitcher_info_2014[$pitcher_a_i]['2013default']);
	$pitcher_a_2014_default = checkDefault($starting_pitcher_info_2014[$pitcher_a_i]['2014default']);

	$pitcher_h_era_2014 = elvis($starting_pitcher_info_2014[$pitcher_h_i]['2014era'], DEFAULT_ERA);
	$pitcher_a_era_2014 = elvis($starting_pitcher_info_2014[$pitcher_a_i]['2014era'], DEFAULT_ERA);
	$pitcher_h_era_2013 = elvis($starting_pitcher_info_2014[$pitcher_h_i]['2013era'], DEFAULT_ERA);
	$pitcher_a_era_2013 = elvis($starting_pitcher_info_2014[$pitcher_a_i]['2013era'], DEFAULT_ERA);
	$pitcher_era_delta_2013 = $pitcher_h_era_2013 - $pitcher_a_era_2013;
	$pitcher_era_delta_2014 = $pitcher_h_era_2014 - $pitcher_a_era_2014;

	if (!$pitcher_h_i || !$pitcher_a_i) {
		echo 'missing pitcher!';
		error_log("Missing Pitchers: ".$date_map."   ".$home_i."\n", 3, 
			"/Users/baseball/Desktop/Baseball/Scripts/Errors/battingsim_missingpitchers.log");
	}

	$stadium_stats = array();
	$stadium_stats['2013']['Away'] = $player_batting_2014['2013']['stadium'][$stadium];
	$stadium_stats['2014']['Away'] = $player_batting_2014['2014']['stadium'][$stadium];
	// TO DO: Figure out what is going on here too :(
	$stadium_stats['2013']['Home'] = $stadium_stats['2013']['Away'];
	$stadium_stats['2014']['Home'] = $stadium_stats['2014']['Away'];

	if (!$stadium_stats['2014']['Home'] || !$stadium_stats['2014']['Away']) {
      		$stadium_stats['2014']['Home'] = array(
                	'pct_single' => 0,
                	'pct_double' => 0,
                	'pct_triple' => 0,
                	'pct_home_run' => 0,
                	'pct_walk' => 0,
                	'pct_strikeout' => 0,
                	'pct_ground_out' => 0,
					'pct_fly_out' => 0,
					'stadium' => 'Unknown'
                	);
		$stadium_stats['2014']['Away'] = $stadium_stats['2014']['Home'];
	}
	$stadium_stats = json_encode($stadium_stats);

	$fielding_stats = array();
	// Subject is for e-mail that is sent out
	$subject = null;

	$lineup_h = array();
	$lineup_h_defaults = array('2013' => 0, '2014' => 0);
    $lineup_h_total_defaults = array('2013' => 0, '2014' => 0);
	foreach ($game['home_lineup'] as $lineup_position => $player) {
		$name = checkDuplicatePlayers($player['name'], $home_i, $duplicate_names);
		$position = $player['position'];
		if (!$player_batting_2014['2013'][$name] && !$player_batting_2014['2014'][$name]) {
            $name = findSimilarName($name, $date);
        }
		// Unpack player batting json and add pitcher data
		addPitcherBatting($name, $pitcher_a_i);
		$lineup_h['2013'][$lineup_position] = $player_batting_2014['2013'][$name];
		$lineup_h['2014'][$lineup_position] = $player_batting_2014['2014'][$name];
		$lineup_h_total_defaults['2013'] = addTotalLineupDefaults($name, '2013', $lineup_h_total_defaults['2013']);
		$lineup_h_total_defaults['2014'] = addTotalLineupDefaults($name, '2014', $lineup_h_total_defaults['2014']);
		$lineup_h_defaults['2013'] = addLineupDefaults($name, '2013', $lineup_h_defaults['2013']);
		$lineup_h_defaults['2014'] = addLineupDefaults($name, '2014', $lineup_h_defaults['2014']);

		// If there is no player info at this point use joe_average and send e-mail
		if (!$player_batting_2014['2013'][$name]['Total'] && !$player_batting_2014['2014'][$name]['Total']) {
			$subject .= arrayToString($player);
			addPitcherBatting('joe_average', $pitcher_a_i);
			$lineup_h['2013'][$lineup_position] = $player_batting_2014['2013']['joe_average'];
			$lineup_h['2014'][$lineup_position] = $player_batting_2014['2014']['joe_average'];
			$lineup_h_total_defaults['2013'] = addTotalLineupDefaults('joe_average', '2013', $lineup_h_total_defaults['2013']);
        	$lineup_h_total_defaults['2014'] = addTotalLineupDefaults('joe_average', '2014', $lineup_h_total_defaults['2014']);
        	$lineup_h_defaults['2013'] = addLineupDefaults('joe_average', '2013', $lineup_h_defaults['2013']);
        	$lineup_h_defaults['2014'] = addLineupDefaults('joe_average', '2014', $lineup_h_defaults['2014']);
			foreach ($splits as $split) {
				$lineup_h['2013'][$lineup_position][$split]['player_name'] = $name;
				$lineup_h['2014'][$lineup_position][$split]['player_name'] = $name;
			}
        }
		if (isset($fieldingstats_2013_total[$name])) {
			$fielding_stats['2013']['Home'] += ( 1 - ($fieldingstats_2013_total[$name]['errors'] / ($fieldingstats_2013_total[$name]['errors'] + $fieldingstats_2013_total[$name]['assists'] + $fieldingstats_2013_total[$name]['putouts']))) * $position_mapping[$position];
		} else {
			$fielding_stats['2013']['Home'] += (1 - ($fieldingstats_average['2013']['errors'] / $fieldingstats_average['2013']['total'])) * $position_mapping[$position];
		}
		if (isset($fieldingstats_2014_total[$name])) {
			$fielding_stats['2014']['Home'] += ( 1 - ($fieldingstats_2014_total[$name]['errors'] / ($fieldingstats_2014_total[$name]['errors'] + $fieldingstats_2014_total[$name]['assists'] + $fieldingstats_2014_total[$name]['putouts']))) * $position_mapping[$position];
		} else {
			$fielding_stats['2014']['Home'] += (1 - ($fieldingstats_average['2014']['errors'] / $fieldingstats_average['2014']['total'])) * $position_mapping[$position];
		}
		// TO DO - why am I doing this here?
		if (!isset($player_batting_2014['2013'][$name])) {
			error_log("Missing Batter: ".$name." 2013 "."\n", 3, "/Users/baseball/Desktop/Baseball/Scripts/Errors/nullbatter_error.log");
		}
		if (!isset($player_batting_2014['2014'][$name])) {
			error_log("Missing Batter: ".$name." 2014 "."\n", 3, "/Users/baseball/Desktop/Baseball/Scripts/Errors/nullbatter_error.log");
		}
	}
	$lineup_h = json_encode($lineup_h);

	$lineup_a = array();
	$lineup_a_defaults = array('2013' => 0, '2014' => 0);
    $lineup_a_total_defaults = array('2013' => 0, '2014' => 0);
	foreach ($game['away_lineup'] as $p => $player) {
		$name = checkDuplicatePlayers($player['name'], $home_i, $duplicate_names);
        $position = $player['position'];
        $lineup_position = $p;
        if (!$player_batting_2014['2013'][$name] && !$player_batting_2014['2014'][$name]) {
            $name = findSimilarName($name, $date);
        }
        // Unpack player batting json and add pitcher data
        addPitcherBatting($name, $pitcher_h_i);
        $lineup_a['2013'][$lineup_position] = $player_batting_2014['2013'][$name];
        $lineup_a['2014'][$lineup_position] = $player_batting_2014['2014'][$name];
		$lineup_a_total_defaults['2013'] = addTotalLineupDefaults($name, '2013', $lineup_a_total_defaults['2013']);
        $lineup_a_total_defaults['2014'] = addTotalLineupDefaults($name, '2014', $lineup_a_total_defaults['2014']);
        $lineup_a_defaults['2013'] = addLineupDefaults($name, '2013', $lineup_a_defaults['2013']);
        $lineup_a_defaults['2014'] = addLineupDefaults($name, '2014', $lineup_a_defaults['2014']);

        // If there is no player info at this point use joe_average and send e-mail
        if (!$player_batting_2014['2013'][$name]['Total'] && !$player_batting_2014['2014'][$name]['Total']) {
			$subject .= arrayToString($player);
            addPitcherBatting('joe_average', $pitcher_h_i);
            $lineup_a['2013'][$lineup_position] = $player_batting_2014['2013']['joe_average'];
            $lineup_a['2014'][$lineup_position] = $player_batting_2014['2014']['joe_average'];
			$lineup_a_total_defaults['2013'] = addTotalLineupDefaults('joe_average', '2013', $lineup_a_total_defaults['2013']);
        	$lineup_a_total_defaults['2014'] = addTotalLineupDefaults('joe_average', '2014', $lineup_a_total_defaults['2014']);
        	$lineup_a_defaults['2013'] = addLineupDefaults('joe_average', '2013', $lineup_a_defaults['2013']);
        	$lineup_a_defaults['2014'] = addLineupDefaults('joe_average', '2014', $lineup_a_defaults['2014']);
            foreach ($splits as $split) {
                $lineup_a['2013'][$lineup_position][$split]['player_name'] = $name;
                $lineup_a['2014'][$lineup_position][$split]['player_name'] = $name;
            }
        }
		if (isset($fieldingstats_2013_total[$name])) {
			$fielding_stats['2013']['Away'] += ( 1 - ($fieldingstats_2013_total[$name]['errors'] / ($fieldingstats_2013_total[$name]['errors'] + $fieldingstats_2013_total[$name]['assists'] + $fieldingstats_2013_total[$name]['putouts']))) * $position_mapping[$position];
		} else {
			$fielding_stats['2013']['Away'] += (1 - ($fieldingstats_average['2013']['errors'] / $fieldingstats_average['2013']['total'])) * $position_mapping[$position];
		}
		if (isset($fieldingstats_2014_total[$name])) {
			$fielding_stats['2014']['Away'] += ( 1 - ($fieldingstats_2014_total[$name]['errors'] / ($fieldingstats_2014_total[$name]['errors'] + $fieldingstats_2014_total[$name]['assists'] + $fieldingstats_2014_total[$name]['putouts']))) * $position_mapping[$position];
		} else {
			$fielding_stats['2014']['Away'] += (1 - ($fieldingstats_average['2014']['errors'] / $fieldingstats_average['2014']['total'])) * $position_mapping[$position];
		}
		if (!isset($player_batting_2014['2013'][$name])) {
			error_log("Missing Batter: ".$name." 2013 "."\n", 3, "/Users/baseball/Desktop/Baseball/Scripts/Errors/nullbatter_error.log");
		}
		if (!isset($player_batting_2014['2014'][$name])) {
			error_log("Missing Batter: ".$name." 2014 "."\n", 3, "/Users/baseball/Desktop/Baseball/Scripts/Errors/nullbatter_error.log");
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
	$pitcher_batting_stats['2013']['Home'] = $pitcher_magic_final['2013'][$pitcher_h_i];
	if (!isset($pitcher_batting_stats['2013']['Home'])) {
		$pitcher_batting_stats['2013']['Home'] = $pitcher_no_change;
	}
	$pitcher_batting_stats['2014']['Home'] = $pitcher_magic_final['2014'][$pitcher_h_i];
	if (!isset($pitcher_batting_stats['2014']['Home'])) {
		$pitcher_batting_stats['2014']['Home'] = $pitcher_no_change;
	}
	$pitcher_batting_stats['2013']['Away'] = $pitcher_magic_final['2013'][$pitcher_a_i];
	if (!isset($pitcher_batting_stats['2013']['Away'])) {
		$pitcher_batting_stats['2013']['Away'] = $pitcher_no_change;
	}
	$pitcher_batting_stats['2014']['Away'] = $pitcher_magic_final['2014'][$pitcher_a_i];
	if (!isset($pitcher_batting_stats['2014']['Away'])) {
		$pitcher_batting_stats['2014']['Away'] = $pitcher_no_change;
	}
	$pitcher_batting_stats = json_encode($pitcher_batting_stats);
*/

	$master_row['gameid'] = $gameid;
	$master_row['month_i'] = $month;
	$master_row['day_i'] = $day;							
	$master_row['time'] = $time;
	$master_row['home_i'] = $home_i;
	$master_row['away_i'] = $away_i;
	$master_row['pitcher_h_i'] = $pitcher_h_i;
	$master_row['pitcher_a_i'] = $pitcher_a_i;
	$master_row['pitcher_h_handedness_i'] = $pitcher_h_handedness_i;
	$master_row['pitcher_a_handedness_i'] = $pitcher_a_handedness_i;
	$master_row['pitcher_h_2013_era_bucket_i'] = $pitcher_h_2013_era_bucket_i;
	$master_row['pitcher_a_2013_era_bucket_i'] = $pitcher_a_2013_era_bucket_i;
	$master_row['pitcher_h_2014_era_bucket_i'] = $pitcher_h_2014_era_bucket_i;
	$master_row['pitcher_a_2014_era_bucket_i'] = $pitcher_a_2014_era_bucket_i;
	$master_row['pitcher_h_era_2013'] = $pitcher_h_era_2013;
	$master_row['pitcher_a_era_2013'] = $pitcher_a_era_2013;
	$master_row['pitcher_h_era_2014'] = $pitcher_h_era_2014;
	$master_row['pitcher_a_era_2014'] = $pitcher_a_era_2014;
	$master_row['pitcher_era_delta_2013'] = $pitcher_era_delta_2013;
	$master_row['pitcher_era_delta_2014'] = $pitcher_era_delta_2014;
	$master_row['pitcher_h_2013_default'] = $pitcher_h_2013_default;
	$master_row['pitcher_h_2014_default'] = $pitcher_h_2014_default;
	$master_row['pitcher_a_2013_default'] = $pitcher_a_2013_default;
	$master_row['pitcher_a_2014_default'] = $pitcher_a_2014_default;
	$master_row['lineup_a_2013_total_defaults'] = $lineup_a_total_defaults['2013'];
	$master_row['lineup_a_2014_total_defaults'] = $lineup_a_total_defaults['2014'];
	$master_row['lineup_h_2013_total_defaults'] = $lineup_h_total_defaults['2013'];
	$master_row['lineup_h_2014_total_defaults'] = $lineup_h_total_defaults['2014'];
	$master_row['lineup_a_2013_defaults'] = $lineup_a_defaults['2013'];
    $master_row['lineup_a_2014_defaults'] = $lineup_a_defaults['2014'];
    $master_row['lineup_h_2013_defaults'] = $lineup_h_defaults['2013'];
    $master_row['lineup_h_2014_defaults'] = $lineup_h_defaults['2014'];
	$master_row['fielding_mult_2013_home'] = $fielding_stats['2013']['Home'];
	$master_row['fielding_mult_2014_home'] = $fielding_stats['2014']['Home'];
	$master_row['fielding_mult_2013_away'] = $fielding_stats['2013']['Away'];
	$master_row['fielding_mult_2014_away'] = $fielding_stats['2014']['Away'];
	$master_row['lineup_h_stats'] = $lineup_h;
	$master_row['lineup_a_stats'] = $lineup_a;
	$master_row['stadium_stats'] = $stadium_stats;

	$master_table[$gameid] = $master_row;
}

ksort($master_table);
$final_table = array(array(
    'gameid',
    'month_i',
	'day_i',
	'time',
    'home_i',
    'away_i',
    'pitcher_h_i',
    'pitcher_a_i',
    'pitcher_h_handedness_i',
    'pitcher_a_handedness_i',
    'pitcher_h_2013_era_bucket_i',
    'pitcher_a_2013_era_bucket_i',
    'pitcher_h_2014_era_bucket_i',
    'pitcher_a_2014_era_bucket_i',
    'pitcher_h_era_2013',
	'pitcher_a_era_2013',
	'pitcher_h_era_2014',
	'pitcher_a_era_2014',
	'pitcher_era_delta_2013',
	'pitcher_era_delta_2014',
	'pitcher_h_2013_default',
	'pitcher_h_2014_default',
	'pitcher_a_2013_default',
	'pitcher_a_2014_default',
	'lineup_a_2013_total_defaults',
	'lineup_a_2014_total_defaults',
	'lineup_h_2013_total_defaults',
	'lineup_h_2014_total_defaults',
	'lineup_a_2013_defaults',
	'lineup_a_2014_defaults',
	'lineup_h_2013_defaults',
	'lineup_h_2014_defaults',
    'fielding_mult_2013_home',
    'fielding_mult_2014_home',
    'fielding_mult_2013_away',
    'fielding_mult_2014_away',
    'lineup_h_stats',
    'lineup_a_stats',
    'stadium_stats',
    'gamenumber'
));
$key = 0;
foreach ($master_table as $game => $data) {
	// Skip the row of ColHeads
	if ($key == 0) {
		$key++;
		continue;
		//$final_table[$key] = $data;
		//$key++;
		//continue;
	}
	$data['gamenumber'] = $key;
	$final_table[$key] = $data;
	$key++;
}
//print_r($final_table);

// Send e-mail out for those that have no 2013 (and 2014) data
// Should dissappear after first few days of games
if ($subject) {
	//send_email('Confirm No 2013 Data', $subject, "d");
}
// Run function to export the data to mysql, backup a copy to csv,
// and leave a record in table_status -> in sweetfunctions.php
$table_name = 'sim_nomagic_2014';
export_and_save($database, $table_name, $final_table, $date);

?>
