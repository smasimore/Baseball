<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php'); 
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');

$countup = 0;
$player_stats = array();

for ($id = 1; $id < 2500; $id += 30) {
	//Here we will do fix pulls for each tab on pitching expanded stats.
	$target = "http://espn.go.com/mlb/stats/pitching/_/year/2014/count/".$id."/qualified/false/order/false";
	$target_expanded = "http://espn.go.com/mlb/stats/pitching/_/year/2014/count/".$id."/qualified/false/type/expanded/order/false";
	$target_expanded2 = "http://espn.go.com/mlb/stats/pitching/_/year/2014/count/".$id."/qualified/false/type/expanded-2/order/false";
	$target_saber = "http://espn.go.com/mlb/stats/pitching/_/year/2014/count/".$id."/qualified/false/type/sabermetric/order/false";
	$target_opponent = "http://espn.go.com/mlb/stats/pitching/_/year/2014/count/".$id."/qualified/false/type/opponent-batting/order/false";
	$source_code = scrape($target);
	$source_code_expanded = scrape($target_expanded);
	$source_code_expanded2 = scrape($target_expanded2);
	$source_code_saber = scrape($target_saber);
	$source_code_opponent = scrape($target_opponent);

	$stats_start = "<td align=\"right\">";
	$stats_end = "</td>";
	$stats = parse_array_clean($source_code, $stats_start, $stats_end);
	$stats_expanded = parse_array_clean($source_code_expanded, $stats_start, $stats_end);
	$stats_expanded2 = parse_array_clean($source_code_expanded2, $stats_start, $stats_end);
	$stats_saber = parse_array_clean($source_code_saber, $stats_start, $stats_end);
	$stats_opponent = parse_array_clean($source_code_opponent, $stats_start, $stats_end);
	//Have to pull Earned Run Average seperately because this is the column the
	//page sorts by so it is stored differently.
	$player_era_start = "class=\"sortcell\">";
	$player_era_end = "</td>";
	$player_era = parse_array_clean($source_code, $player_era_start, $player_era_end);

	if ($countup == 0) {
		$colheads_exclude = 'earned_run_average';
		$colheads = getBattingColheads($source_code, $colheads_exclude);
		$colheads = array_merge(array('player_name'), $colheads, array('earned_run_average'));
		$colheads_expanded = getBattingColheads($source_code_expanded, $colheads_exclude);
		$colheads_expanded2 = getBattingColheads($source_code_expanded2, $colheads_exclude, true);
		$colheads_saber = getBattingColheads($source_code_saber, $colheads_exclude, true);
		$colheads_opponent = getBattingColheads($source_code_opponent, $colheads_exclude);
		$countup += 1;

		$colheads_final = array_merge($colheads, $colheads_expanded, $colheads_expanded2, $colheads_saber, $colheads_opponent);
		array_push($player_stats, $colheads_final);	
	}

	$rowheads_start = "href=\"http://espn.go.com/mlb/player/_/id/[\s\S]+\">";
	$rowheads_end = "</a></td><td align=\"left\"";
	$rowheads = parse_array_clean($source_code, $rowheads_start, $rowheads_end);

	//Adjust the logic below since each page has a different amount of columns.
	$numcols_reg = 0;
	$numcols_expanded = 0;
	$numcols_expanded2 = 0;
	$numcols_saber = 0;
	$numcols_opponent = 0;

	for ($i=0; $i<count($rowheads); $i++) {
		$clean_row = split_string($rowheads[$i], "\">", AFTER, EXCL);
		$player_name = format_for_mysql($clean_row);
		$regular_row = array($player_name);
		$expanded_row = array();
		$expanded2_row = array();
		$saber_row = array();
		$opponent_row = array();

		for ($k = $numcols_reg; $k < $numcols_reg + 14; $k++) {
			array_push($regular_row, $stats[$k]);
		}
		for ($k = $numcols_expanded; $k < $numcols_expanded + 13; $k++) {
			array_push($expanded_row, $stats_expanded[$k]);
		}
		for ($k = $numcols_expanded2; $k < $numcols_expanded2 + 12; $k++) {
			array_push($expanded2_row, $stats_expanded2[$k]);
		}
		for ($k = $numcols_saber; $k < $numcols_saber + 9; $k++) {
			array_push($saber_row, $stats_saber[$k]);
		}
		for ($k = $numcols_opponent; $k < $numcols_opponent + 13; $k++) {
			array_push($opponent_row, $stats_opponent[$k]);
		}
		$final_row = array_merge($regular_row, array($player_era[$i]), $expanded_row, $expanded2_row, $saber_row, $opponent_row);

		if (isset($player_stats[$player_name])) {
			$numcols_reg += 14;
			$numcols_expanded += 13;
			$numcols_expanded2 += 12;
			$numcols_saber += 9;
			$numcols_opponent += 13;
			continue;
		}
		$player_stats[$player_name] = $final_row;
		$numcols_reg += 14;
		$numcols_expanded += 13;
		$numcols_expanded2 += 12;
		$numcols_saber += 9;
		$numcols_opponent += 13;
	}
}

//print_r($player_stats);
// Run function to export the data to mysql, backup a copy to csv,
// and leave a record in table_status -> in sweetfunctions.php
$table_name = 'pitching_expanded_2014';
export_and_save($database, $table_name, $player_stats);

?>
