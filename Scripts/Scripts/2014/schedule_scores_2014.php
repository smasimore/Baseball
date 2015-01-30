<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('mysqli.reconnect', '1');
ini_set('mysqli.connect_timeout', '-1');
ini_set('default_socket_timeout', '-1');
ini_set('max_execution_time', '-1');
include('/Users/constants.php'); 
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');
include(HOME_PATH.'Scripts/Include/Teams.php');
$database = 'baseball';

//global variables
$header = array('date', 'home', 'away', 'pitcher_h', 'pitcher_a', 'winner', 'runs_h', 'runs_a', 'lineup_h', 'lineup_a', 'home_odds');
$data = array($header);

function getTeams() {
    //grab source code
    $team_target = 'http://espn.go.com/mlb/teams';
    $source_code = scrape($team_target);

    //pull team links
    $links_start = '<a href="http://espn.go.com/mlb/team/_/name/';
    $links_end = '"';
    $links = parse_array_clean($source_code, $links_start, $links_end);

    //go through team links and parse into prettier team array
    foreach ($links as $link) {
	    $teams[substr($link, 0, strpos($link, '/'))] = substr($link, strpos($link, '/') + 1, strlen($link));
    }
    return ($teams);
}


function pullSchedules(array $data, array $teams, array $lineups, array $odds) {
    //to be used later when converting date format to get lineups
    $month_num = array('Mar' => '3', 'Apr' => '4', 'May' => '5', 'Jun' => '6', 'Jul' => '7', 'Aug' => '8', 'Sep' => '9', 'Oct' => '10');

    //go through teams and pull schedule info
    foreach ($teams as $id => $team) {
	    //go through 3 pages per team
	    for ($j = 0; $j < 3; $j++) {
	        $target = null;
            switch ($j) {
		    //first half
		    case 0:
		        $target = 'http://espn.go.com/mlb/team/schedule/_/name/' . $id . '/year/2014/seasontype/2/half/1/' . $team;
		        break;
    		    //second half
	    	    case 1:
                        $target = 'http://espn.go.com/mlb/team/schedule/_/name/' . $id . '/year/2014/seasontype/2/' . $team;
		        break;
    		    //post season
	    	    case 2:
                        $target = 'http://espn.go.com/mlb/team/schedule/_/name/' . $id . '/year/2014/' . $team;
		        break;
			}
	        $source_code = scrape($target);

            //get array elements
            $elements_start = '"><td><nobr>';
            $elements_end = '</tr>';
            $elements = parse_array_clean($source_code, $elements_start, $elements_end);

            //go through raw elements and parse out data
            $dates = array();
            for ($i = 0; $i < count($elements); $i++) {
                $row = array();

                //add dates
                $date_end = strpos($elements[$i], '<');
                $date = substr($elements[$i], 0, $date_end);
                array_push($row, $date);

                //get vs or @
                $vs = return_between($elements[$i], '<li class="game-status">', '</li><li class="team-logo', EXCL); 

                //get opponenet
                $opponent = return_between($elements[$i], 'class="team-name"><a href="http://espn.go.com/mlb/team/_/name/', '/', EXCL);

                //get home and away
                if ($vs == 'vs') {
                    $home_team = $id;
                    $away_team = $opponent;
                } else {
                    $home_team = $opponent;
                    $away_team = $id;
                }
                array_push($row, $home_team, $away_team);

                //deal with postponed games
                if (strpos($elements[$i], 'POSTPONED')) {
                    array_push($row, 'postponed', 'postponed', 'postponed', 'postponed', 'postponed');
                    array_push($data, $row);
                    continue;
                }

                //get winner
                $winner_staging = return_between($elements[$i], '<li class="game-status ', '"><span class=', EXCL);
                if ($winner_staging == 'win') {
                    $winner = $id;
                } else {
                    $winner = $opponent;
                }

                //get score
                $runs_staging = return_between($elements[$i], 'class="score"><a href=', '<', EXCL);
                $win_runs = return_between($runs_staging, '>', '-', EXCL);
                $loss_runs = substr($runs_staging, strpos($runs_staging, '-') + 1, 1);

                //get pitchers
                $pitchers_start = '<td><nobr><a href="http://espn.go.com/mlb/player/_/id/';
                $pitchers_end = '">';
                $pitchers_staging = parse_array_clean($elements[$i], $pitchers_start, $pitchers_end);
		$pitchers = array();
		foreach ($pitchers_staging as $pitcher) {
		    $pitchers[] = str_replace("-", "_", substr($pitcher, strpos($pitcher, '/') + 1));
		}


                //add home and away pitchers as well as winner
                if ($winner == $home_team) {
                    array_push($row, $pitchers[0], $pitchers[1], $winner, $win_runs, $loss_runs);
                } else {
                    array_push($row, $pitchers[1], $pitchers[0], $winner, $loss_runs, $win_runs);
                }

                //find and add lineups
                $month = $month_num[substr($date, 5, 3)];
                $day = substr($date, 9);
                $lineup_date = $month . '/' . $day . '/2014';
                $lineup_h = json_encode($lineups[$lineup_date][$home_team]);
                $lineup_a = json_encode($lineups[$lineup_date][$away_team]);
                array_push($row, $lineup_h, $lineup_a);

                //add odds
                $home_odds = json_encode($odds[$lineup_date][$home_team]);
                array_push($row, $home_odds);

                //push into data if row not already there
                if (!in_array($row, $data)) {
                    array_push($data, $row);
                }
            }
        }
    }
    return ($data);
}

function pullLineups() {
    //vars
	$game_days = array(3 => 31, 4 => 30, 5 => 31, 6 => 30, 7 => 31, 8 => 31, 9 => 30, 10 => 31);

    //array to be filled
    $lineups = array();

    //go through days
    foreach ($game_days as $month => $max_day) {
        for ($i = 1; $i <= $max_day; $i++) {

            $day = $month . '/' .  $i . '/2014';
            $target = 'http://baseballpress.com/lineup.php?d=' . $day;
            $source_code = scrape($target);

            //get array of team source code
            $teams_sc_start = '<td><a href=';
            $teams_sc_end = '</div></td>';
            $teams_sc = parse_array_clean($source_code, $teams_sc_start, $teams_sc_end);

            foreach ($teams_sc as $team_sc) {
                //get team id for key
                $team_id = strtolower(return_between($team_sc, 'team=', '"', EXCL));

		//take into account cws -> chw
		if ($team_id == 'cws') {
		    $team_id = 'chw';
		}

                $lineup_start = strpos($team_sc, '</td><td><div>');
                $lineup_staging = substr($team_sc, $lineup_start, strlen($team_sc));
                $lineup = parse_array_clean($lineup_staging, '">', '</a>');
                $lineup_assoc = array();

                for ($xx=0; $xx<9; $xx++) {
                    //parse out name v. position v. hitting style (R/L/S)
                    $lineup_order = $xx+1;
                    $player_name = format_for_mysql(split_string($lineup[$xx], " (", BEFORE, EXCL));
                    $player_position = split_string($lineup[$xx], ") ", AFTER, EXCL);
                    $player_batting = return_between($lineup[$xx], "(", ")", EXCL);

                    $player_info = array('name' => $player_name, 'position' => $player_position, 'batting' => $player_batting);
                    $lineup_assoc["L".$lineup_order] = $player_info;
                }
                $lineups[$day][$team_id] = $lineup_assoc;
            }
        }
    }
    return($lineups);
}

function pullOdds(Teams::$teamAbbreviations) {

   $odds = array();

   //cycle through months
   for ($month = 3; $month <= 10; $month++) {
       //cycle through days teams
       for ($day = 1; $day <= 31; $day++) {

            $target = "http://contests.sportsbettingstats.com/Odds.aspx?date=".$month."/".$day."/2014&sport=MLB";
            $source_code = scrape($target);

            if ((strpos($source_code, "Runtime Error")) || (strpos($source_code, "Odds History")) == false) {
                continue;
            }

            //parse
            $stats_start = "<td class=\"scoreodds\">";
            $stats = parse_array_clean($source_code, $stats_start, "</");
            $stats_format = array();

            $under_start = "</SPAN>/<SPAN class=\"scoreodds\">";
            $under_end = "</SPAN>";
            $under_stats = parse_array_clean($source_code, $under_start, $under_end);
            $under_counter = 0;

            $odds_team_start = "class=\"scoreoddsteam";
            $odds_team = parse_array_clean($source_code, $odds_team_start, "</td>");
            $odds_team_format = array();

            foreach ($stats as $stat) {

                if (strpos($stat, "SPAN")) {
                    $stat = substr($stat, -4);
                    array_push($stats_format, $stat, $under_stats[$under_counter]);
                    $under_counter++;
                } elseif (strpos($stat, "otal")) {
                    $stat = split_string($stat, "Total: ", AFTER, EXCL);
                    array_push($stats_format, $stat);
                } else {
                    array_push($stats_format, $stat);
                }
            }

            foreach ($odds_team as $team) {
                if (strpos($team, "(")) {
                    $team = return_between($team, ">", " (", EXCL);
                } else {
                    $team = split_string($team, ">", AFTER, EXCL);
                }
                if ($team == "Chi Cubs") {
                    $team = "Chicago Cubs";
                } elseif ($team == "Chi White Sox") {
                    $team = "Chicago Sox";
                }
                $team = Teams::$teamAbbreviations[$team];
                array_push($odds_team_format, $team);
            }

            //format and push into odds array
            $colheads = array(
                'score_a',
                'spread_a',
                'odds_a',
                'over_odds',
                'under_odds',
                'score_h',
                'spread_h',
                'odds_h',
                'over_under'
                );
            $date = $month . '/' . $day . '/2014';
            $numcols = 0;
            for ($i = 1; $i<=count($odds_team_format); $i+=2) {
                $game_odds_row = array();
                $start_loop = 0;
                for ($k = $numcols; $k < $numcols + 9; $k++) {
                    $skip = 0;
                    if (((strpos($stats_format[$k], '-') === 0) || (strpos($stats_format[$k], '+') === 0)) && $start_loop === 0) {
                        $numcols += 7;
                        $skip = 1;
                        break;
                    }
                    $start_loop = 1;
                    array_push($game_odds_row, $stats_format[$k]);
                }
                if ($skip == 1) {
                    $i+=2;
                    continue;
                }
                $home_team_format = strtolower($odds_team_format[$i]);
                $game_odds_row = array_combine($colheads, $game_odds_row);
                $odds[$date][$home_team_format] = $game_odds_row;
                $numcols += 9;
            }
        }
    }
    return $odds;
}


//run code
$teams = getTeams();
$odds = pullOdds(Teams::$teamAbbreviations);
$lineups = pullLineups();
$schedules = pullSchedules($data, $teams, $lineups, $odds);

$sql_colheads = $schedules[0];
foreach ($schedules as $stats) {
    if ($stats[0] == 'date') {
        continue;
    }
    $data = array();
    for ($k = 0; $k < count($stats); $k++) {
        $data[$sql_colheads[$k]] = $stats[$k];
    }
    insert($database, 'schedule_scores_2014', $data);
}

$date_label = str_replace('-','_',$date);
$csv_name = '/Volumes/Sarah Masimore/Baseball/Backups/2014/'.$date_label.'_schedule_scores_2014.csv';
export_csv($csv_name, $schedules);

?>
