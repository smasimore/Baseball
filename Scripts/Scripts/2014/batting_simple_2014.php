<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php'); 
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');

$all_players = exe_sql($database,
	'SELECT *
	FROM players_2014
	WHERE ds = "'.$date.'"'
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

// Run function to export the data to mysql, backup a copy to csv,
// and leave a record in table_status -> in sweetfunctions.php
$table_name = 'batting_simple_2014';
export_and_save($database, $table_name, $player_array);

?>
