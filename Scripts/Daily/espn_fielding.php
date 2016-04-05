<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ .'/../../Models/Include/ScrapingInclude.php';

$all_players = exe_sql(
	DATABASE,
	'SELECT * FROM players'
);
$season = date('Y');
$insert_array = array();
$player_array = array();
$filled_colheads = false;
$colheads = array();

foreach ($all_players as $i => $player) {
	$player_id = $player['player_id'];
	$espn_id = $player['espn_id'];
	$target = "http://espn.go.com/mlb/player/stats/_/id/$espn_id/type/fielding";
	$source_code = scrape($target);

	// Make sure they fielded this season.
	$season_fielder = strpos($source_code, "<td>$season</td><td>");
	if ($season_fielder === false) {
		continue;
	}

	$player_name = return_between($source_code, "var playerName = '", "';", EXCL);
	$player_name = format_for_mysql($player_name);

	// Filter source code down to the career stats.
	$start_season = "CAREER BY POS";
	$end_season = "sponsored";
	$source_code = return_between($source_code, $start_season, $end_season, INCL);

	// Only get colheads the first time around.
	if (!$filled_colheads) {
		$colheads = array(
			'player_id',
			'player_name',
			'espn_id',
			'pos',
			'season',
			'ds',
			'ts'
		);
		$colheads_start = "<td class=\"textright\" title=\"";
		$colheads_end = "\">";
		$colheads_stg = parse_array_clean(
			$source_code,
			$colheads_start,
			$colheads_end
		);
		foreach ($colheads_stg as $head) {
			$head = format_for_mysql($head);
 	 	    if (in_array($head, $colheads)) {
 	  	 	    continue;
  	  	    }
  	  		array_push($colheads, $head);
		}
		$filled_colheads = true;
	}

	$stats_start = "<td class=\"textright\">";
    $stats_end = "</td>";
	$stats = parse_array_clean($source_code, $stats_start, $stats_end);
	$stats = array_map(
		function ($x) {
		   return $x === "--" ? null : $x;
		},
		$stats
	);

	$positions = parse_array_clean($source_code, 'Total as ', '</td>');
	$stats_per_position = count($stats) / count($positions);
	$stats = array_chunk($stats, $stats_per_position);
	foreach ($positions as $j => $position) {
		$insert_stats = array_merge(
			array(
				$player_id,
				$player_name,
				$espn_id,
				$position,
				$season,
				date('Y-m-d'),
				date('Y-m-d H:i:s')
			),
			$stats[$j]
		);
		$insert_array[] = array_combine($colheads, $insert_stats);
	}
}

$insert_colheads = array();
// Make all columns nullable since they vary by player.
foreach ($colheads as $col) {
	$insert_colheads[$col] = '?';
}

$insert_table = 'espn_fielding';

multi_insert(
	DATABASE,
	$insert_table,
	$insert_array,
	$insert_colheads
);
logInsert($insert_table);

?>
