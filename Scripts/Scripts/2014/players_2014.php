<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/sweetfunctions.php');

$playerids = array();
$players = array(array('unixname', 'id', 'ds'));
$masterplayers = array();

for ($id = 1; $id < 2500; $id += 30) {
	$target = "http://espn.go.com/mlb/stats/batting/_/year/2014/count/".$id."/qualified/false";
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

// Run function to export the data to mysql, backup a copy to csv,
// and leave a record in table_status -> in sweetfunctions.php
$table_name = 'players_2014';
export_and_save($database, $table_name, $players);

?>
