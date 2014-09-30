<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php'); 
include(HOME_PATH.'Include/sweetfunctions.php');

$all_stats_13 = exe_sql($database,
	'SELECT *
	FROM batting_simple_2014
	WHERE ds = "'.$date.'"'
	);

function getMarlinsStats($stats, $marlins) {

	$split = $stats['split'];
	$hits = $stats['hits'];
	$doubles = $stats['doubles'];
	$triples = $stats['triples'];
	$home_runs = $stats['home_runs'];
	$hit_by_pitch = $stats['hit_by_pitch'];
	$walks = $stats['walks'];
	$all_walks = $walks + $hit_by_pitch;
	$strikeouts = $stats['strikeouts'];
	$singles = $hits - $doubles - $triples - $home_runs;
	$at_bats = $stats['at_bats'];
	$fielding_outs = $at_bats - $hits - $strikeouts;
	$at_bats_walks = $at_bats + $all_walks;

	if ($split == 'Away') {
		$marlins['single'] += $singles;
		$marlins['double'] += $doubles;
		$marlins['triple'] += $triples;
		$marlins['home_run'] += $home_runs;
		$marlins['walk'] += $all_walks;
		$marlins['strikeout'] += $strikeouts;
		$marlins['ground_out'] += ($fielding_outs / 2);
		$marlins['fly_out'] += ($fielding_outs / 2);
		$marlins['at_bats_walks'] += $at_bats_walks;
	} else {
		$marlins['single'] -= $singles;
		$marlins['double'] -= $doubles;
		$marlins['triple'] -= $triples;
		$marlins['home_run'] -= $home_runs;
		$marlins['walk'] -= $all_walks;
		$marlins['strikeout'] -= $strikeouts;
		$marlins['ground_out'] -= ($fielding_outs / 2);
		$marlins['fly_out'] -= ($fielding_outs / 2);
		$marlins['at_bats_walks'] -= $at_bats_walks;
	}
	return $marlins;
}

function getMarlinsFinal($marlins, $marlins_final) {

	$at_bats_walks = $marlins['at_bats_walks'];
	$marlins_final['pct_single'] = $marlins['single'] / $at_bats_walks;
	$marlins_final['pct_double'] = $marlins['double'] / $at_bats_walks;
	$marlins_final['pct_triple'] = $marlins['triple'] / $at_bats_walks;
	$marlins_final['pct_home_run'] = $marlins['home_run'] / $at_bats_walks;
	$marlins_final['pct_walk'] = $marlins['walk'] / $at_bats_walks;
	$marlins_final['pct_strikeout'] = $marlins['strikeout'] / $at_bats_walks;
	$marlins_final['pct_ground_out'] = $marlins['ground_out'] / $at_bats_walks;
	$marlins_final['pct_fly_out'] = $marlins['fly_out'] / $at_bats_walks;

	return $marlins_final;
}

function getFieldStats($stats, $player_batting) {

	$split = $stats['split'];
	$hits = $stats['hits'];
	$doubles = $stats['doubles'];
	$triples = $stats['triples'];
	$home_runs = $stats['home_runs'];
	$hit_by_pitch = $stats['hit_by_pitch'];
	$walks = $stats['walks'];
	$all_walks = $walks + $hit_by_pitch;
	$strikeouts = $stats['strikeouts'];
	$singles = $hits - $doubles - $triples - $home_runs;
	$at_bats = $stats['at_bats'];
	$fielding_outs = $at_bats - $hits - $strikeouts;

	$player_batting['pct_single'] += $singles;
	$player_batting['pct_double'] += $doubles;
	$player_batting['pct_triple'] += $triples;
	$player_batting['pct_home_run'] += $home_runs;
	$player_batting['pct_walk'] += $all_walks;
	$player_batting['pct_strikeout'] += $strikeouts;
	$player_batting['pct_ground_out'] += ($fielding_outs / 2);
	$player_batting['pct_fly_out'] += ($fielding_outs / 2);

	return $player_batting;
}

$stadium_array = array();
$marlins = array();
$home_name = null;
$home_at_bats = null;

foreach ($all_stats_13 as $stats) {
	$split = $stats['split'];
	if ($split == 'Home') {
		$home_name = $stats['player_name'];
		$home_at_bats = $stats['at_bats'];
		continue;
	}
	if (in_array($split, $stadiums) || $split == 'Away') {
		if ($home_name == $stats['player_name'] && $home_at_bats == $stats['at_bats']) {
			continue;
		}
		$stadium_array[$split] = getFieldStats($stats, $stadium_array[$split]);
		$marlins = getMarlinsStats($stats, $marlins);
	} else {
		continue;
	}
}

$marlins_final = array();
$marlins_final = getMarlinsFinal($marlins, $marlins_final);

$stadium_final = array();
$stadium_final['Marlins Park'] = $marlins_final;
foreach ($stadium_array as $split_name => $stat) {
	foreach ($stat as $stat_name => $data) {
		$stat_denom = array_sum($stadium_array[$split_name]);
		$avg_stat = $data / $stat_denom;
		$stadium_final[$split_name][$stat_name] = $avg_stat;
	}
}

$stadium_delta = array(array(
	'pct_single',
	'pct_double',
	'pct_triple',
	'pct_home_run',
	'pct_walk',
	'pct_strikeout',
	'pct_ground_out',
	'pct_fly_out',
	'stadium'
	));
foreach ($stadiums as $stadium) {
	$stadium_delta[$stadium] = $stadium_final['Away'];
}
foreach ($stadium_final as $split_name => $stat) {
	if ($split_name == 'Away') {
		continue;
	}

	foreach ($stat as $stat_name => $data) {
		$stadium_delta[$split_name][$stat_name] = ($data - $stadium_delta[$split_name][$stat_name]);
		$stadium_delta[$split_name]['stadium'] = $split_name;
	}
}

// Run function to export the data to mysql, backup a copy to csv,
// and leave a record in table_status -> in sweetfunctions.php
$table_name = 'batting_byfield_magic_2014';
export_and_save($database, $table_name, $stadium_delta);

?>
