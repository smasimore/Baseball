<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('mysqli.reconnect', '1');
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

	//Check to make sure this person batted at all
	$target = "http://espn.go.com/mlb/player/batvspitch/_/id/".$id;
	$source_code = scrape($target);
	$playerbatted = strpos($source_code, "Batter vs Pitching Stats");
	if ($playerbatted == false) {
		continue;
	}
	//Now check to see if they are a pitcher - if so this page shows
	//pitching stats vs. opposing team which isn't what we want
	$ispitcher_start = "<tr class=\"stathead\"><td colspan=\"13\">";
	$ispitcher = substr(split_string($source_code, $ispitcher_start, AFTER, EXCL), 0, 6);
	if ($ispitcher !== 'Career') {
		continue;
	}

	for ($p = 1; $p<31; $p++) {
		$teamid = $p;
		$target = "http://espn.go.com/mlb/player/batvspitch/_/id/".$id."/teamId/".$teamid;
		$source_code = scrape($target);

		//Make sure this person batted against this team
		$nostats = strpos($source_code, "No statistics available");
		if ($nostats == true) {
			continue;
		}
		$teamname = return_between($source_code, "selected=\"selected\">", "</option>", EXCL);
		$team_abbr = $team_mapping[$teamname];

		$stats_start = "<td class=\"textright\">";
		$stats_end = "</td>";
		$stats = parse_array_clean($source_code, $stats_start, $stats_end);

		if ($countup == 0) {
			$colheads = array('id','player_name', 'vs_pitcher', 'vs_team');
			$colheads_start = "<td class=\"textright\" title=\"";
			$colheads_end = "\">";
			$colheads_stg = parse_array_clean($source_code, $colheads_start, $colheads_end);
			foreach ($colheads_stg as $head) {
				$head = format_header($head);
  	  			$head = str_replace("ops_=_obp_+_slg", "ops", $head);
 	 		    if (in_array($head, $colheads)) {
 	  		 	    continue;
  	  		    }
  	  			array_push($colheads, $head);
			}
			array_push($player_array, $colheads);
			$countup += 1;
		}

		$rowheads_start =  "row player[\s\S]+href=\"http://espn.go.com/mlb/player[\s\S]+\">";
		$rowheads_end = "</a></td><td class=\"textright\">";
		$rowheads = parse_array_clean($source_code, $rowheads_start, $rowheads_end);

		$numcols = 0;
		for ($i=0; $i<count($rowheads); $i++) {
			$clean_row = split_string($rowheads[$i], "\">", AFTER, EXCL);
			$clean_row = format_header($clean_row);
			$row = array($id, $name, $clean_row, $team_abbr);
			for ($k = $numcols; $k < $numcols + 12; $k++) {
				array_push($row, $stats[$k]);
			}
			array_push($player_array, $row);
			$numcols += 12;
		}
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
	insert($database, 'batting_vspitcher_2013', $data);
}

?>