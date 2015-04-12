<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/Teams.php');
include(HOME_PATH.'Scripts/Include/RetrosheetInclude.php');
include(HOME_PATH.'Scripts/Include/RetrosheetPlayerMapping.php');

$splitMap = array(
	'Total' => 0,
	'Away' => 34,
	'BasesLoaded' => 94,
	'Home' => 33,
	'NoneOn' => 37,
	'RunnersOn' => 38,
	'ScoringPos2Out' => 185,
	'ScoringPos' => 39,
	'VsLeft' => 31,
	'VsRight' => 32
);
$season = date('Y');
if (idx($argv, 1) !== null) {
	$season = $argv[1];
}
$insert_table = 'espn_batting';

foreach ($splitMap as $split_name => $split) {
	$player_stats = array();
	$countup = 0;
	for ($id = 1; $id < 2000; $id += 30) {
		// Here we will do three pulls: one for regular stats, one for expanded stats,
		// and one for sabermetric stats.
		$target = "http://espn.go.com/mlb/stats/batting/_/year/$season/split/$split/count/$id/qualified/false";
		$target_expanded = "http://espn.go.com/mlb/stats/batting/_/year/$season/split/$split/count/$id/qualified/false/type/expanded";
		$target_saber = "http://espn.go.com/mlb/stats/batting/_/year/$season/split/$split/count/$id/qualified/false/type/sabermetric";
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
		}

		$rowheads_start = "href=\"http://espn.go.com/mlb/player/_/id/";
		$rowheads_end = "</a></td><td align=\"left\"";
		$rowheads = parse_array_clean($source_code, $rowheads_start, $rowheads_end);

		//Adjust the logic below since each page has a different amount of columns.
		$numcols_reg = 0;
		$numcols_expanded = 0;
		$numcols_saber = 0;

		for ($i=0; $i<count($rowheads); $i++) {
			$espn_id = split_string($rowheads[$i], '/', BEFORE, EXCL);
			$player_name = format_for_mysql(
				split_string($rowheads[$i], "\">", AFTER, EXCL)
			);
			$team = $team_array[$i];
			if (strpos($team, '/') !== false) {
				// Just pick the first team in this case.
				$team = split_string($team, '/', BEFORE, EXCL);
			}
			$team = Teams::getStandardTeamAbbr($team);
			$regular_row = array($player_name, $team);
			$expanded_row = array();
			$saber_row = array();

			// Check to see if WAR is there - otherwise exclude by setting cols to
			// 14 instead of 15 (only for the total metric though)
			$war = strpos($source_code, 'Wins Above Replacement');
			if ($war) {
				$num_cols = 15;
			} else {
				$num_cols = 14;
			}

			for ($k = $numcols_reg; $k < $numcols_reg + $num_cols; $k++) {
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
				$numcols_reg += $num_cols;
				$numcols_expanded += 11;
				$numcols_saber += 10;
				continue;
			}
			$insert_row = array_combine($colheads_final, $final_row);
			$insert_row['player_id'] = RetrosheetPlayerMapping::getIDFromESPNID($espn_id);
			$insert_row['espn_id'] = $espn_id;
			$insert_row['ds'] = date('Y-m-d');
			$insert_row['season'] = $season;
			$insert_row['split'] = $split_name;
			$insert_row['ts'] = date('Y-m-d H:i:s');
			$player_stats[$player_name] = $insert_row;
			$numcols_reg += $num_cols;
			$numcols_expanded += 11;
			$numcols_saber += 10;
		}
	}
	$insert_colheads = array();
	foreach ($colheads_final as $colhead) {
		$insert_colheads[$colhead] = '!';
	}
	$insert_colheads = array_merge(
		$insert_colheads,
		array(
			'player_id' => '?',
			'espn_id' => '!',
			'split' => '!',
			'season' => '!',
			'ds' => '!',
			'ts' => '!'
		)
	);
	multi_insert(
		DATABASE,
		$insert_table,
		$player_stats,
		$insert_colheads
	);
}

?>
