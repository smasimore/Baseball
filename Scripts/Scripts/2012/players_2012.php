<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/LIB_http.php');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/LIB_parse.php');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/LIB_mysql_updatedbyus.php');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/sweetfunctions.php');

$playerids = array();
$players = array();
$masterplayers = array();

for ($id = 1; $id < 2500; $id += 30) {
	$target = "http://espn.go.com/mlb/stats/batting/_/year/2012/count/".$id."/qualified/false";
	$source_code = scrape($target);

	$playerids_start = "http://espn.go.com/mlb/player/_/";
	$playerids_end = ">";
	$playerids = parse_array_clean($source_code, $playerids_start, $playerids_end);

	for ($xx=0; $xx<count($playerids); $xx++) {
		array_push($masterplayers, $playerids[$xx]);
	}
}

for ($xx=0; $xx<count($masterplayers); $xx++) {
	$id = return_between($masterplayers[$xx], "id/", "/", EXCL);
	$start  = "id/".$id."/";
	$unixname = return_between($masterplayers[$xx], $start, "\"", EXCL);
	$unixname = format_header($unixname);
	$unixname = checkDuplicatePlayers($unixname, $id, $duplicate_names);
	if (isset($players[$id])) {
		continue;
	}
	$players[$id] = array($unixname, $id);
}

$database = 'baseball';
foreach ($players as $player) {
	$data = array();
	$data['unixname'] = $player[0];
	$data['id'] = $player[1];
	insert($database, 'players_2012', $data);
}

?>
