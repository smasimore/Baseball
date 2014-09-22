<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('mysqli.reconnect', '1');
ini_set('mysqli.connect_timeout', '-1');
ini_set('default_socket_timeout', '-1');
ini_set('max_execution_time', '-1');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/sweetfunctions.php');
$database = 'baseball';

// Change this to backfill - backfill must be true
if ($argv[1]) {
	$backfill = true;
	$ds_override = $argv[1];
}

//global variables
$header = array('time_est', 'away', 'home', 'away_pitcher_first', 'away_pitcher_last', 'home_pitcher_first', 'home_pitcher_last', 'away_handedness', 'home_handedness', 'away_lineup', 'home_lineup');
$data = array($header);

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

function pullLineups() {
	global $month;
	global $day;
	global $ds;

    //array to be filled
	$lineups = array();
	$game_info = array();

	$day = $month . '/' .  $day . '/2014';
	$target = 'http://baseballpress.com/lineup.php?d=' . $day;
	$source_code = scrape($target);

	// DELETE ME
	//$source_code = file_get_contents(HOME_PATH.'Scripts/2014/lineup_331_source_2.txt');
	//$source_code = trim(preg_replace('/\s+/', ' ', $source_code));

	$games_start = '<div class="game-';
	$games_end = '<a class="mobile-lineup"';
	$games = parse_array_clean($source_code, $games_start, $games_end);

	foreach ($games as $game_num => $game) {
		$time_start = 'time';
		$time_end = '</div>';
		$time_staging = parse_array_clean($game, $time_start, $time_end);
		$time = format_header(substr($time_staging[0], 2));
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
			$team = strtolower($team);
			if ($team == 'cws') {
				$team = 'chw';
			}
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
			$pitchers[$j]['handedness'] = $handedness;
			$pitchers[$j]['first_name'] = str_replace("'", '', $name[0]);
			$pitchers[$j]['last_name'] = str_replace("'", '', $name[1]);
		}
		$away_pitcher_data = $pitchers[0];
		$home_pitcher_data = $pitchers[1];

		if (!$away_pitcher_data) {
			$away_pitcher_data = array(
				'first_name' => 'Unknown',
				'last_name' => 'Unknown',
				'handedness' => 'Unknown',
			);
		}
        if (!$home_pitcher_data) {
            $home_pitcher_data = array(
                'first_name' => 'Unknown',
                'last_name' => 'Unknown',
                'handedness' => 'Unknown',
			);
		}
		$game_info[$day][$game_num]['time_est'] = $time;
		$game_info[$day][$game_num]['away'] = $away_team;
		$game_info[$day][$game_num]['home'] = $home_team;
		$game_info[$day][$game_num]['away_pitcher_first'] = format_header($away_pitcher_data['first_name']);
		$game_info[$day][$game_num]['away_pitcher_last'] = format_header($away_pitcher_data['last_name']);
		$game_info[$day][$game_num]['home_pitcher_first'] = format_header($home_pitcher_data['first_name']);
		$game_info[$day][$game_num]['home_pitcher_last'] = format_header($home_pitcher_data['last_name']);
		$game_info[$day][$game_num]['away_handedness'] = format_header($away_pitcher_data['handedness']);
		$game_info[$day][$game_num]['home_handedness'] = format_header($home_pitcher_data['handedness']);


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
						'name' => format_header($batter),
						'position' => format_header($position),
						'batting' => format_header($batting),
					);
				} else {
					$lineups[$day][$time][$home_team][$l_num] = array(
                        'name' => format_header($batter),
                        'position' => format_header($position),
                        'batting' => format_header($batting),
					);
				}
			}
		}
	}

    return array($lineups, $game_info);
}

list($lineups, $game_info) = pullLineups();
foreach ($game_info as $date => $games) {
		$subject = null;
		foreach ($games as $i => $game) {
			$time = $game['time_est'];
			if ($game['away_pitcher_first'] == 'Unknown' ||
				$game['home_pitcher_first'] == 'Unknown' ||
				$lineups[$date][$time][$game['away']] == 'Unknown' ||
				$lineups[$date][$time][$game['home']] == 'Unknown') {
					$subject .= $game['away'] . ' @ ' . $game['home'] . "\n";
					continue;
				}
			$away_team = $game['away'];
			$home_team = $game['home'];
			$game_info[$date][$i]['away_lineup'] = json_encode($lineups[$date][$time][$away_team]);
			$game_info[$date][$i]['home_lineup'] = json_encode($lineups[$date][$time][$home_team]);
			array_push($data, $game_info[$date][$i]);
		}
		if ($subject) {
			//send_email('Missing Lineup or Starting Pitcher', $subject);
		}
}

//print_r($data);
export_and_save('baseball', 'lineups_2014', $data, $ds);

?>
