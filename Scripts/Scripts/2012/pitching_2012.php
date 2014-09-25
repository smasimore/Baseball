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

$all_players = exe_sql($database,
	'SELECT *
	FROM players_2012'
	);
$player_array = array();
$countup = 0;

foreach ($all_players as $player) {
	$name = $player['unixname'];
	$id = $player['id'];

	$target = "http://espn.go.com/mlb/player/splits/_/id/".$id."/type/pitching/year/2012/";
	$source_code = scrape($target);

	$not_pitcher = strpos($source_code, "No statistics available.");
	if ($not_pitcher == true) { 
		continue;
	}

	$handedness = return_between($source_code, "Throws: ", ", Bats", EXCL);
	if ($handedness !== 'R' && $handedness !== 'L') {
		$handedness = 'Unknown';
	}
	//Parse down $source_code to just include the pitcher specific stats since
	//column heads are different for these (will need 2 tables)
	$p_stats = array();
	$pitching_stats_start = "title=\"Earned Run Average\">";
	$pitching_stats_end  = "title=\"At Bats\">";
	$pitching_stats = parse_array_clean($source_code, $pitching_stats_start, $pitching_stats_end);

	//Get final "pitching stats" if there is a Post Season
	$postseason = strpos($source_code, "Postseason</td>");
	if ($postseason == true) {
		$final_pstats_start = "Postseason</td>";
		$final_pstats_end = "\"sponsored\"";
		$final_pitching_stats = return_between($source_code, $final_pstats_start, $final_pstats_end, EXCL);
		array_push($pitching_stats, $final_pitching_stats);
	}

	foreach ($pitching_stats as $stats) {
		$stats_start = "<td class=\"textright\">";
		$stats_end = "</td>";
		$stats_stg = parse_array_clean($stats, $stats_start, $stats_end);
		foreach ($stats_stg as $stats) {
			if ($stats == 'INF') {
				$stats = null;
			}
			array_push($p_stats, $stats);
		}
	}

	if ($countup == 0) {
		$colheads = array('id','player_name', 'handedness', 'split');
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
		$colheads = array_slice($colheads,0,20);
		array_push($player_array, $colheads);
		$countup += 1;
	} 

	$rowheads = array();
	foreach ($pitching_stats as $stats) {
		$p_row_start = "w\">.<td width=\"15%\">";
		$p_row_end = "</td>";
		$p_row_stg = parse_array_clean($stats, $p_row_start, $p_row_end);
		foreach ($p_row_stg as $p_row) {
			array_push($rowheads, $p_row);
		}
	}

	$numcols = 0;
	for ($i=0; $i<count($rowheads); $i++) {
		$row = array($id, $name, $handedness, $rowheads[$i]);
		for ($k = $numcols; $k < $numcols + 16; $k++) {
			array_push($row, $p_stats[$k]);
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
	insert($database, 'pitching_2012', $data);
}

?>
