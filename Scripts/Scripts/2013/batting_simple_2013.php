<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/LIB_http.php');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/LIB_parse.php');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/LIB_mysql_updatedbyus.php');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/sweetfunctions.php');
$database = 'baseball';

$all_players = exe_sql($database,
	'SELECT *
	FROM players_2013'
	);
$player_array = array();
$countup = 0;

foreach ($all_players as $player) {
	$name = $player['unixname'];
	$id = $player['id'];

	$target = "http://espn.go.com/mlb/player/splits/_/id/".$id."/type/batting/";
	$source_code = scrape($target);

	$no_batter = strpos($source_code, "No statistics available.");
	if ($no_batter == true) { 
		continue;
	}

	$stats_start = "<td class=\"textright\">";
	$stats_end = "</td>";
	$stats = parse_array_clean($source_code, $stats_start, $stats_end);

	if ($countup == 0) {
		$colheads = array('id','player_name', 'split');
		$colheads_start = "<td class=\"textright\" title=\"";
		$colheads_end = "\">";
		$colheads_stg = parse_array_clean($source_code, $colheads_start, $colheads_end);
		foreach ($colheads_stg as $head) {
			$head = format_for_mysql($head);
  	  		$head = str_replace("ops_=_obp_+_slg", "ops", $head);
 	 	    if (in_array($head, $colheads)) {
 	  	 	    continue;
  	  	    }
  	  		array_push($colheads, $head);
		}
		array_push($player_array, $colheads);
		$countup += 1;
	}

	$rowheads_start = "<td width=\"115\">";
	$rowheads_end = "</td>";
	$rowheads = parse_array_clean($source_code, $rowheads_start, $rowheads_end);

	$numcols = 0;
	for ($i=0; $i<count($rowheads); $i++) {
		$row = array($id, $name, $rowheads[$i]);
		for ($k = $numcols; $k < $numcols + 16; $k++) {
			array_push($row, $stats[$k]);
		}
		array_push($player_array, $row);
		$numcols += 16;
	}
}

$sql_colheads = $player_array[0];
foreach ($player_array as $stats) {
	if ($stats[0] == 'id') {
		continue;
	} 
	$data = array();
	for ($k = 0; $k < count($stats); $k++) {
		$data[$sql_colheads[$k]] = $stats[$k];
	}
	insert($database, 'batting_simple_2013', $data);
}

?>
