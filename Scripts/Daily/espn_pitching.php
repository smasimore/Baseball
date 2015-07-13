<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ .'/../../Models/Include/RetrosheetInclude.php';
include_once __DIR__ .'/../../Models/Utils/RetrosheetPlayerMappingUtils.php';

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
$countup = 0;
$insert_table = 'espn_pitching';

foreach ($splitMap as $split_name => $split) {
	$player_stats = array();
	for ($id = 1; $id < 2000; $id += 30) {
		//Here we will do fix pulls for each tab on pitching expanded stats.
		$target = "http://espn.go.com/mlb/stats/pitching/_/year/$season/split/$split/count/$id/qualified/false/order/false";
		$target_expanded = "http://espn.go.com/mlb/stats/pitching/_/year/$season/split/$split/count/$id/qualified/false/type/expanded/order/false";
		$target_expanded2 = "http://espn.go.com/mlb/stats/pitching/_/year/$season/split/$split//count/$id/qualified/false/type/expanded-2/order/false";
		$target_saber = "http://espn.go.com/mlb/stats/pitching/_/year/$season/split/$split/count/$id/qualified/false/type/sabermetric/order/false";
		$target_opponent = "http://espn.go.com/mlb/stats/pitching/_/year/$season/split/$split/count/$id/qualified/false/type/opponent-batting/order/false";
		$source_code = scrape($target);
		$source_code_expanded = scrape($target_expanded);
		$source_code_expanded2 = scrape($target_expanded2);
		$source_code_saber = scrape($target_saber);
		$source_code_opponent = scrape($target_opponent);

		$team_start = "</a></td><td align=\"left\">";
        $team_end = "</td><td";
        $team_array = parse_array_clean($source_code, $team_start, $team_end);

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
			$colheads = array_merge(array('player_name', 'team'), $colheads, array('earned_run_average'));
			$colheads_expanded = getBattingColheads($source_code_expanded, $colheads_exclude);
			$colheads_expanded2 = getBattingColheads($source_code_expanded2, $colheads_exclude, true);
			$colheads_saber = getBattingColheads($source_code_saber, $colheads_exclude, true);
			$colheads_opponent = getBattingColheads($source_code_opponent, $colheads_exclude);
			$countup += 1;

			$colheads_final = array_merge($colheads, $colheads_expanded, $colheads_expanded2, $colheads_saber, $colheads_opponent);
		}

		$rowheads_start = "href=\"http://espn.go.com/mlb/player/_/id/";
		$rowheads_end = "</a></td><td align=\"left\"";
		$rowheads = parse_array_clean($source_code, $rowheads_start, $rowheads_end);

		//Adjust the logic below since each page has a different amount of columns.
		$numcols_reg = 0;
		$numcols_expanded = 0;
		$numcols_expanded2 = 0;
		$numcols_saber = 0;
		$numcols_opponent = 0;

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
			$numcols_reg += 14;
			$numcols_expanded += 13;
			$numcols_expanded2 += 12;
			$numcols_saber += 9;
			$numcols_opponent += 13;

			$insert_row = array_combine($colheads_final, $final_row);
			$insert_row['player_id'] = RetrosheetPlayerMappingUtils::getIDFromESPNID($espn_id);
			$insert_row['espn_id'] = $espn_id;
			$insert_row['ds'] = date('Y-m-d');
			$insert_row['season'] = $season;
			$insert_row['split'] = $split_name;
			$insert_row['ts'] = date('Y-m-d H:i:s');
			$player_stats[$espn_id] = $insert_row;
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
	logInsert($insert_table);
}

?>
