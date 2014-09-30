<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'Include/sweetfunctions.php');
$database = 'baseball';

// Modify date if used for backfilling
 if ($argv[1]) {
     $date = $argv[1];
 }

$all_players = exe_sql($database,
	'SELECT *
	FROM batting_vspitcher_2014
	WHERE ds = "'.$date.'"'
	);
$player_array = array(array('pitcher_name','at_bats','hits','doubles','triples','home_runs','walks','strikeouts','opponents','stats'));
$countup = 0;

foreach ($all_players as $player) {
	$vs_pitcher = $player['vs_pitcher'];
	$vs_team = $player['vs_team'];
	$vs_pitcher = checkDuplicatePlayers($vs_pitcher, $vs_team, $duplicate_names);
	$player_array[$vs_pitcher]['pitcher_name'] = $vs_pitcher;
	$player_array[$vs_pitcher]['at_bats'] += $player['at_bats'];
	$player_array[$vs_pitcher]['hits'] += $player['hits'];
	$player_array[$vs_pitcher]['doubles'] += $player['doubles'];
	$player_array[$vs_pitcher]['triples'] += $player['triples'];
	$player_array[$vs_pitcher]['home_runs'] += $player['home_runs'];
	$player_array[$vs_pitcher]['walks'] += $player['walks'];
	$player_array[$vs_pitcher]['strikeouts'] += $player['strikeouts'];
	$player_array[$vs_pitcher]['opponents'] += 1;

	// Now add the stats for the Joe Average player
	$player_array['joe_average']['pitcher_name'] = 'joe_average';
	$player_array['joe_average']['opponents'] = null;
	$player_array['joe_average']['at_bats'] += $player['at_bats'];
    $player_array['joe_average']['hits'] += $player['hits'];
    $player_array['joe_average']['doubles'] += $player['doubles'];
    $player_array['joe_average']['triples'] += $player['triples'];
    $player_array['joe_average']['home_runs'] += $player['home_runs'];
    $player_array['joe_average']['walks'] += $player['walks'];
    $player_array['joe_average']['strikeouts'] += $player['strikeouts'];
}

foreach ($player_array as $player_name => $player) {
	if (!$countup) {
		$countup++;
		continue;
	}
	$at_bats_walks = $player['at_bats'] + $player['walks'];
	$hits = $player['hits'];
	$doubles = $player['doubles'];
	$triples = $player['triples'];
	$home_runs = $player['home_runs'];
	$singles = $hits - $doubles - $triples - $home_runs;
	$strikeouts = $player['strikeouts'];
	$walks = $player['walks'];
	$field_outs = $at_bats_walks - $walks - $hits - $strikeouts;
	$stats_array = array(
		'pitcher_name' => $player_name,
		'pct_single' => $singles / $at_bats_walks,
		'pct_double' => $doubles / $at_bats_walks,
		'pct_triple' => $triples / $at_bats_walks,
		'pct_home_run' => $home_runs / $at_bats_walks,
		'pct_walk' => $walks / $at_bats_walks,
		'pct_strikeout' => $strikeouts / $at_bats_walks,
		'pct_field_out' => $field_outs / $at_bats_walks
	);
	$player_array[$player_name]['stats'] = json_encode($stats_array);
}
//print_r($player_array);

// Run function to export the data to mysql, backup a copy to csv,
// and leave a record in table_status -> in sweetfunctions.php
$table_name = 'batting_vspitcher_aggregate_2014';
export_and_save($database, $table_name, $player_array, $date);

?>
