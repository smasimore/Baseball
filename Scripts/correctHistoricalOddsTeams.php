<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ .'/../Models/Include/RetrosheetInclude.php';

$sql = 'SELECT * FROM historical_odds';
$odds = exe_sql(
	'baseball',
	$sql
);

foreach ($odds as $game) {
	$gameid = $game['gameid'];
	$team = preg_replace('#\d.*$#', '', $gameid);
	$date = split_string($gameid, $team, AFTER, EXCL);
	$season = substr($date, 0, 4);
	$corrected_team = Teams::getRetrosheetTeamAbbr($team, $season);
	if ($team !== $corrected_team) {
		$corrected_gameid = $corrected_team.$date;
		$update = sprintf(
			"UPDATE historical_odds
			SET gameid = '%s'
			WHERE gameid = '%s'",
			$corrected_gameid,
			$gameid
		);
		echo $gameid."\n";
		exe_sql(
			'baseball',
			$update
		);
	}
}


?>
