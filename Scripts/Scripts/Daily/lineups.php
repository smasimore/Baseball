<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/Teams.php');
include(HOME_PATH.'Scripts/Include/RetrosheetPlayerMapping.php');

// Change this to backfill - backfill must be true
if ($argv[1]) {
	$backfill = true;
	$ds_override = $argv[1];
}

//global variables
$colheads = array(
	'time_est' => '!',
	'away' => '!',
	'home' => '!',
	'away_pitcher_name' => '!',
	'away_pitcher_id' => '?',
	'home_pitcher_name' => '!',
	'home_pitcher_id' => '?',
	'away_handedness' => '!',
	'home_handedness' => '!',
	'away_lineup' => '!',
	'home_lineup' => '!',
	'season' => '!',
	'ds' => '!'
);
$insert_data = array();

date_default_timezone_set('America/Los_Angeles');
if (!$backfill) {
	$month = date("n", time());
	$day = date("j", time());
	$ds = date("Y-m-d", time());
} else {
	$month = date("n", strtotime($ds_override));
	$day = date("j", strtotime($ds_override));
	$ds = date("Y-m-d", strtotime($ds_override));
}

function pullLineups($month, $day, $ds) {
    //array to be filled
	$lineups = array();
	$game_info = array();

	$year = substr($ds, 0, 4);
	$day = "$month/$day/$year";
	$target = "http://baseballpress.com/lineup.php?d=$day";
	$source_code = scrape($target);

	$games_start = '<div class="game-';
	$games_end = '<a class="mobile-lineup"';
	$games = parse_array_clean($source_code, $games_start, $games_end);

	foreach ($games as $game_num => $game) {
		$time_start = 'time';
		$time_end = '</div>';
		$time_staging = parse_array_clean($game, $time_start, $time_end);
		$time = format_for_mysql(substr($time_staging[0], 2));
		$ampm = trim(return_between($time, "_", "_", EXCL));
		$hour = trim(split_string($time, ":", BEFORE, EXCL));
		$minute = trim(return_between($time, ":", "_", EXCL));
		if ($ampm == 'pm' && $hour != 12) {
        	$hour += 12;
    	}
    	if ($hour < 10) {
        	$hour = "0$hour";
    	}
		$time = "$hour:$minute:00";
		if (strpos($time, "elayed")) {
			$time = "Delayed";
		} else if (strpos($time, "ppd")) {
			$time = "Postponed";
		}

		$teams_start = '/team-lineups/';
		$teams_end = '">';
		$teams = parse_array_clean($game, $teams_start, $teams_end);
		$home_team = null;
		$away_team = null;
		foreach ($teams as $j => $team) {
			$team = Teams::getStandardTeamAbbr($team);
			$teams[$j] = $team;
		}
        $away_team = $teams[0];
		$home_team = $teams[1];

		$pitchers_start = '<div class="text">';
		$pitchers_end =  '<div class="lineup">';
		$pitchers_staging = parse_array_clean($game, $pitchers_start, $pitchers_end);
		$pitchers = array();
		foreach ($pitchers_staging as $j => $pitcher_stg) {
			if (!strpos($pitcher_stg, 'data-mlb="')) {
				continue;
			}
			$stg1 = substr($pitcher_stg, strpos($pitcher_stg, 'data-mlb="'));
			$stg2 = substr($stg1, strpos($stg1, '">'));
			$stg3 = substr($stg2, 2);
			$stg_pieces = explode('</a> (', $stg3);
			$name = explode(' ', $stg_pieces[0]);

			$handedness = substr($stg_pieces[1], 0, 1);
			$pitchers[$j]['handedness'] = format_for_mysql($handedness);
			$pitchers[$j]['first_name'] = format_for_mysql(str_replace("'", '', $name[0]));
			$pitchers[$j]['last_name'] = format_for_mysql(str_replace("'", '', $name[1]));
			$pitchers[$j]['player_name'] =
				$pitchers[$j]['first_name'] . '_' . $pitchers[$j]['last_name'];
		}

		$away_pitcher_data = $pitchers[0];
		$home_pitcher_data = $pitchers[1];

		$away_pitcher_data['player_id'] = RetrosheetPlayerMapping::getIDFromFirstLast(
			$away_pitcher_data['first_name'],
			$away_pitcher_data['last_name'],
			$away_team
		);
		$home_pitcher_data['player_id'] = RetrosheetPlayerMapping::getIDFromFirstLast(
			$home_pitcher_data['first_name'],
			$home_pitcher_data['last_name'],	
			$home_team
		);

		$game_info[$day][$game_num] = array(
			'time_est' => $time,
			'away' => $away_team,
			'home' => $home_team,
			'away_pitcher_name' => $away_pitcher_data['player_name'],
			'away_pitcher_id' => $away_pitcher_data['player_id'],
			'home_pitcher_name' => $home_pitcher_data['player_name'],
			'home_pitcher_id' => $home_pitcher_data['player_id'],
			'away_handedness' => $away_pitcher_data['handedness'],
			'home_handedness' => $home_pitcher_data['handedness']
		);

		$lineups_start = '"players">';
		$lineups_end = '</div></div>';
		$lineups_staging = parse_array_clean($game, $lineups_start, $lineups_end);

		// hacky solution to not picking up 9th batter
		foreach ($lineups_staging as $j => $lineup_staging) {
			if ($lineup_staging) {
				$lineups_staging[$j] = $lineup_staging . '</div><';
			}
		}

		if (!$lineups_staging) {
			$lineups = array($day => array($time => array($away_team => 'Unknown', $home_team => 'Unknown')));
		}
		foreach ($lineups_staging as $j => $lineup_staging) {
			if (!$lineup_staging) {
				if ($j == 0) {
					$lineups[$day][$time][$away_team] = 'Unknown';
				} else {
					$lineups[$day][$time][$home_team] = 'Unknown';
				}
				continue;
			}
			$batters_start = 'data-mlb="';
			$batters_end = '</div><';
			$batters_staging = parse_array_clean($lineup_staging, $batters_start, $batters_end);

			foreach ($batters_staging as $l => $batter_staging) {
				$batter_stg = explode(' ', $batter_staging);
				if (count($batter_stg) > 4) {
					$batter = return_between($batter_staging, '">', '</a>', EXCL);
					$batting = return_between($batter_staging, '(', ')', EXCL);
					$position = split_string($batter_staging, ')', AFTER, EXCL); 
				} else {
					$batter = substr($batter_stg[0], strpos($batter_stg[0], '">') + 2) . ' ' .
						substr($batter_stg[1], 0, -4);
					$batting = substr($batter_stg[2], 1, 1);
					$position = $batter_stg[3];
				}

				$num = $l + 1;
				$l_num = "L$num";
				if ($j == 0) {
					$lineups[$day][$time][$away_team][$l_num] = array(
						'player_name' => format_for_mysql($batter),
						'player_id' => RetrosheetPlayerMapping::getIDFromFirstLast(
							split_string(format_for_mysql($batter), '_', BEFORE, EXCL),
							split_string(format_for_mysql($batter), '_', AFTER, EXCL),
							$away_team
						),
						'position' => format_for_mysql($position),
						'batting' => format_for_mysql($batting),
					);
				} else {
					$lineups[$day][$time][$home_team][$l_num] = array(
						'player_name' => format_for_mysql($batter),
						'player_id' => RetrosheetPlayerMapping::getIDFromFirstLast(
							split_string(format_for_mysql($batter), '_', BEFORE, EXCL),
							split_string(format_for_mysql($batter), '_', AFTER, EXCL),
							$home_team
						),
                        'position' => format_for_mysql($position),
                        'batting' => format_for_mysql($batting),
					);
				}
			}
		}
	}

    return array($lineups, $game_info);
}

list($lineups, $game_info) = pullLineups($month, $day, $ds);
// Grab today's lineup so we don't keep writing the same games to it.
$pitchers = array();
if ($ds === date('Y-m-d')) {
	$sql = sprintf(
		"SELECT home_pitcher_id
		FROM %s
		WHERE ds = '%s'",
		'lineups',
		$ds
	);
	$data = exe_sql(DATABASE, $sql);
	$pitchers = safe_array_column($data, 'home_pitcher_id');
}
foreach ($game_info as $date => $games) {
		$month = formatDayMonth(split_string($date, '/', BEFORE, EXCL));
		$day = formatDayMonth(return_between($date, '/', '/', EXCL));
		$year = substr($date, -4);
		$ds = "$year-$month-$day";
		foreach ($games as $i => $game) {
			// Skip games that are already logged.
        	$home_pit_id = $game['home_pitcher_id'];
			if (in_array($home_pit_id, $pitchers)) {
            	continue;
        	}
			$time = $game['time_est'];
			if ($lineups[$date][$time][$game['away']] == 'Unknown' ||
				$lineups[$date][$time][$game['home']] == 'Unknown') {
				continue;
			}
			$away_team = $game['away'];
			$home_team = $game['home'];
			$game_info[$date][$i]['away_lineup'] = json_encode($lineups[$date][$time][$away_team]);
			$game_info[$date][$i]['home_lineup'] = json_encode($lineups[$date][$time][$home_team]);
			$game_info[$date][$i]['season'] = $year;
			$game_info[$date][$i]['ds'] = $ds;
			array_push($insert_data, $game_info[$date][$i]);
		}
}

$insert_table = 'lineups';
if ($insert_data == null) {
	exit('No New Games');
} else {
	multi_insert(
		DATABASE,
		$insert_table,
		$insert_data,
		$colheads
	);
}

?>
