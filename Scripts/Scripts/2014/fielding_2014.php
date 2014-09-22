<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/sweetfunctions.php');

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

	$target = "http://espn.go.com/mlb/player/stats/_/id/".$id."/type/fielding";
	$source_code = scrape($target);

	//Make sure they fielded in 2014 (i.e. DH)
	$fielder_2014 = strpos($source_code, "<td>2014</td><td>");
	if ($fielder_2014 == false) {
		continue;
	}

	$start_2014 = "<td>2014</td><td>";
	$end_2014 = "sponsored";
	
	$source_code_2014 = return_between($source_code, $start_2014, $end_2014, INCL);

	$stats_start = "<td class=\"textright\">";
	$stats_end = "</td>";
	$stats = parse_array_clean($source_code_2014, $stats_start, $stats_end);

	if ($countup == 0) {
		$colheads = array('id','player_name','season','team','pos');
		$colheads_start = "<td class=\"textright\" title=\"";
		$colheads_end = "\">";
		$colheads_stg = parse_array_clean($source_code, $colheads_start, $colheads_end);
		foreach ($colheads_stg as $head) {
			$head = format_header($head);
 	 	    if (in_array($head, $colheads)) {
 	  	 	    continue;
  	  	    }
  	  		array_push($colheads, $head);
		}
		array_push($player_array, $colheads);
		$countup += 1;
	}

	$positions_start = "</li></ul></td><td>";
	$positions_end = "</td>";
	$positions = parse_array_clean($source_code_2014, $positions_start, $positions_end);

	$team_start = "href=\"[\s\S]+\">";
	$team_end = "</a></li></ul>";
	$teams = parse_array_clean($source_code_2014, $team_start, $team_end);

	//Sometimes if a person has played more than one position there will
	//be a total column. This teases out which column that is so we can skip it.
	$total_positions_start = "<td>2014</td><td>";
	$total_positions_end = "</td><td class=\"textright\">";
	$total_positions = parse_array_clean($source_code_2014, $total_positions_start, $total_positions_end);
	$real_position = array();
	foreach ($total_positions as $i => $position) {
		if (preg_match('/logo/', $position)) {
			array_push($real_position, 1);
		} else {
			array_push($real_position, 0);
		}
	}

	$numcols = 0;
	$t = 0;
	for ($i=0; $i<count($real_position); $i++) {
		if ($real_position[$i] == 0) {
			$numcols += 17;
			continue;
		}

		$team_abbr = ltrim(substr($teams[$t], -3),">");
		$row = array($id, $name, 2014, $team_abbr, $positions[$t]);
		for ($k = $numcols; $k < $numcols + 17; $k++) {
			$stats[$k] = str_replace("--", "", $stats[$k]);
			array_push($row, $stats[$k]);
		}
		array_push($player_array, $row);
		$numcols += 17;
		$t +=1;
	}
}


// Run function to export the data to mysql, backup a copy to csv,
// // and leave a record in table_status -> in sweetfunctions.php
$table_name = 'fielding_2014';
export_and_save($database, $table_name, $player_array);

?>
