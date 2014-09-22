<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/LIB_http.php');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/LIB_parse.php');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/LIB_mysql_updatedbyus.php');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/sweetfunctions.php');

$final_pitchers = array();
$starting_pitchers = array();
$teams = array();
$countup = 0;

foreach ($team_mapping as $team => $abbr) {
	if ($abbr == 'WSH') {
		$abbr = 'WAS';
	} else if ($abbr == 'NYM') {
		$abbr = 'NYN';
	} else if ($abbr == 'STL') {
		$abbr = 'SLN';
	} else if ($abbr == 'CHC') {
		$abbr = 'CHN';
	} else if ($abbr == 'SF') {
		$abbr = 'SFN';
	} else if ($abbr == 'SD') {
		$abbr = 'SDN';
	} else if ($abbr == 'LAD') {
		$abbr = 'LAN'; 
	} else if ($abbr == 'NYY') {
		$abbr = 'NYA';
	} else if ($abbr == 'TB') {
		$abbr = 'TBA';
	} else if ($abbr == 'KC') {
		$abbr = 'KCA';
	} else if ($abbr == 'CHW') {
		$abbr = 'CHA';
	} else if ($abbr == 'LAA') {
		$abbr = 'ANA';
	}
	array_push($teams, $abbr);
}

foreach ($teams as $team) {
	for ($month = 3; $month < 11; $month++) {
		$url_month = $month;
		if ($month < 10) {
        	$url_month = '0'.$month;
        }
		for ($day = 1; $day < 32; $day++) {
			$url_day = $day;
			if ($day < 10) {
        		$url_day = '0'.$day;
        	}
            //Check for double headers (makes script 3x long, can we think of another way?)       	
        	for ($game = 0; $game < 3; $game++) {
        		$target = "http://www.baseball-reference.com/boxes/".$team."/".$team."2013".$url_month.$url_day.$game.".shtml";
				$source_code = scrape($target);

				$no_game = strpos($source_code, "View All Games played on a particular date");
				if ($no_game == true) { 
					continue;
				}
				$pitching_stats_start = ">Pitching</th>";
				$pitching_stats_end  = "</td>";
				$pitching_stats = parse_array_clean($source_code, $pitching_stats_start, $pitching_stats_end);
				$pitchers = array();
				foreach ($pitching_stats as $stats) {
					$pitcher = split_string($stats, "shtml\">", AFTER, EXCL);
					if (strpos($pitcher, "<")) {
						$pitcher = split_string($pitcher, "<", BEFORE, EXCL);
					}
					$pitcher = format_header($pitcher);
					array_push($pitchers, $pitcher);
				}
				$error_check = count($pitchers);
				if ($error_check != 2) {
					echo $url_month.$url_day.$team;
					error_log("Missing Pitchers: ".$url_month.$url_day." ".$team."\n", 3, "/Users/danielc/Desktop/Baseball/startingpitcher_error.log");
				}
				$starting_pitchers[$url_month.$url_day][$team][$game]['home'] = $pitchers[1];
				$starting_pitchers[$url_month.$url_day][$team][$game]['away'] = $pitchers[0];
				//Have it not loop through double headers if there is a game 0
				if ($game == 0) {
					$game = 3;
				} 
        	}
		}
	}
}

foreach ($starting_pitchers as $date => $info) {
	$final_pitchers[$date] = array($date, json_encode($info));
}

$database = 'baseball';
$sql_colheads = $player_stats[0];
foreach ($final_pitchers as $pitcher) {
	$data = array();
	$data['ds'] = $pitcher[0];
	$data['data'] = $pitcher[1];
	insert($database, 'startingpitchers_2013', $data);
}

?>
