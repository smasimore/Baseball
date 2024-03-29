<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ .'/../../Models/Include/RetrosheetInclude.php';
include_once __DIR__ .'/../../Models/Utils/RetrosheetPlayerMappingUtils.php';

const SOURCE = 'ESPN';

$colheads = array(
	'retrosheet_id',
	'first',
	'last',
	'unixname',
	'team',
	'espn_id',	
	'season',
	'ds'
);

$season = 2016;
$players = array();
for ($id = 1; $id < 2500; $id += 30) {
	$target = "http://espn.go.com/mlb/stats/batting/_/year/$season/count/".$id."/qualified/false";
	$source_code = scrape($target);

	$player_start = 'http://espn.go.com/mlb/player/_/';
	$player_end = '</td><td align="right">';
	$players = array_merge($players, parse_array_clean($source_code, $player_start, $player_end));
}

$player_arr = array();
foreach ($players as $player) {
	$espn_id = return_between($player, "id/", "/", EXCL);
	$team = split_string($player, '</a></td><td align="left">', AFTER, EXCL);
	if (strpos($team, '/') !== false) {
		// Just pick the first team in this case.
		$team = split_string($team, '/', BEFORE, EXCL);
	}
	try {
		$team = Teams::getStandardTeamAbbr($team);
	} catch (Exception $e) {
		ExceptionUtils::logDisplayEmailException($e, 'd');
	}
	$unixname = return_between($player, "id/" . $espn_id . "/", "\"", EXCL);
	$first_name = split_string($unixname, '-', BEFORE, EXCL);
	$first_name = format_for_mysql($first_name);
	$last_name = split_string($unixname, '-', AFTER, EXCL);
	$last_name = format_for_mysql($last_name);
	$name_index = $first_name . $last_name;
	$player_arr[$name_index] = array(
		'first' => $first_name,
		'last' => $last_name,
		'firstlast' => $name_index,
		'team' => $team,
		'espn_id' => $espn_id
	);
}

RetrosheetPlayerMappingUtils::createPlayerIDMap($player_arr);

?>
