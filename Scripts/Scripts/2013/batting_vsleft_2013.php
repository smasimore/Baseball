<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/LIB_http.php');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/LIB_parse.php');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/LIB_mysql_updatedbyus.php');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/sweetfunctions.php');

$countup = 0;
$player_stats = array();

for ($id = 1; $id < 2500; $id += 30) {
	//Here we will do three pulls: one for regular stats, one for expanded stats,
	//and one for sabermetric stats.
	$target = "http://espn.go.com/mlb/stats/batting/_/split/31/count/".$id."/qualified/false";
	$target_expanded = "http://espn.go.com/mlb/stats/batting/_/split/31/count/".$id."/qualified/false/type/expanded";
	$target_saber = "http://espn.go.com/mlb/stats/batting/_/split/31/count/".$id."/qualified/false/type/sabermetric";
	$source_code = scrape($target);
	$source_code_expanded = scrape($target_expanded);
	$source_code_saber = scrape($target_saber);

	$stats_start = "<td align=\"right\">";
	$stats_end = "</td>";
	$stats = parse_array_clean($source_code, $stats_start, $stats_end);
	$stats_expanded = parse_array_clean($source_code_expanded, $stats_start, $stats_end);
	$stats_saber = parse_array_clean($source_code_saber, $stats_start, $stats_end);
	//Have to pull Batting Average seperately because this is the column the
	//page sorts by so it is stored differently.
	$player_ba_start = "class=\"sortcell\">";
	$player_ba_end = "</td>";
	$player_ba = parse_array_clean($source_code, $player_ba_start, $player_ba_end);

	$team_start = "</a></td><td align=\"left\">";
	$team_end = "</td><td";
	$team_array = parse_array_clean($source_code, $team_start, $team_end);

	if ($countup == 0) {
		$colheads = getBattingColheads($source_code);
		$colheads = array_merge(array('player_name'), array('team'), $colheads, array('batting_average'));
		$colheads_expanded = getBattingColheads($source_code_expanded);
		$colheads_saber = getBattingColheads($source_code_saber);
		$countup += 1;

		$colheads_final = array_merge($colheads, $colheads_expanded, $colheads_saber);
		array_push($player_stats, $colheads_final);	
	}

	$rowheads_start = "href=\"http://espn.go.com/mlb/player/_/id/[\s\S]+\">";
	$rowheads_end = "</a></td><td align=\"left\"";
	$rowheads = parse_array_clean($source_code, $rowheads_start, $rowheads_end);

	//Adjust the logic below since each page has a different amount of columns.
	$numcols_reg = 0;
	$numcols_expanded = 0;
	$numcols_saber = 0;

	for ($i=0; $i<count($rowheads); $i++) {
		$clean_row = split_string($rowheads[$i], "\">", AFTER, EXCL);
		$team = $team_array[$i];
		if (strpos($team, "/")) {
			$team = split_string($team, "/", BEFORE, EXCL);
		}
		$player_name = format_for_mysql($clean_row);
		$player_name = checkDuplicatePlayers($player_name, $team, $duplicate_names);
		$regular_row = array($player_name, $team);
		$expanded_row = array();
		$saber_row = array();

		for ($k = $numcols_reg; $k < $numcols_reg + 14; $k++) {
			array_push($regular_row, $stats[$k]);
		}
		for ($k = $numcols_expanded; $k < $numcols_expanded + 11; $k++) {
			array_push($expanded_row, $stats_expanded[$k]);
		}
		for ($k = $numcols_saber; $k < $numcols_saber + 10; $k++) {
			array_push($saber_row, $stats_saber[$k]);
		}
		$final_row = array_merge($regular_row, array($player_ba[$i]), $expanded_row, $saber_row);

		if (isset($player_stats[$player_name])) {
			$numcols_reg += 14;
			$numcols_expanded += 11;
			$numcols_saber += 10;
			continue;
		}
		$player_stats[$player_name] = $final_row;
		$numcols_reg += 14;
		$numcols_expanded += 11;
		$numcols_saber += 10;
	}
}

$database = 'baseball';
$sql_colheads = $player_stats[0];
foreach ($player_stats as $stats) {
	if ($stats[0] == 'player_name') {
		continue;
	} 
	$data = array();
	for ($k = 0; $k < count($stats); $k++) {
		$data[$sql_colheads[$k]] = $stats[$k];
	}
	insert($database, 'batting_vsleft_2013', $data);
}

?>
