<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/sweetfunctions.php');

function get_html($url) {
    $ch = curl_init();
    $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
    $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
    $header[] = "Cache-Control: max-age=0";
    $header[] = "Connection: keep-alive";
    $header[] = "Keep-Alive: 300";
    $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
    $header[] = "Accept-Language: en-us,en;q=0.5";
    $header[] = "Pragma: "; //browsers keep this blank.
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows;U;Windows NT 5.0;en-US;rv:1.4) Gecko/20030624 Netscape/7.1 (ax)');
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE);
    curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE);
    $result = curl_exec ($ch);
    curl_close ($ch);
    return($result);
}

function correctAbbr($team) {
    switch ($team) {
        case "WSH":
            $team = "WAS";
			break;
		case "TB":
			$team = "TAM";
			break;
		case "KC":
			$team = "KAN";
			break;
    }
    return $team;
}

function uncorrectAbbr($team) {
	switch ($team) {
		case "WAS":
			$team = 'WSH';
			break;
		case "TAM":
			$team = 'TB';
			break;
		case "KAN":
			$team = 'KC';
			break;
	}
	return $team;
}

function pullCasinoOdds($source_code, $stats_stg, $start, $end, $home_abbr, $away_abbr) {

				$full_month_mapping = array(
                    'March' => '03',
                    'April' => '04',
                    'May' => '05',
                    'June' => '06',
                    'July' => '07',
                    'August' => '08',
                    'September' => '09',
                    'October' => '10'
                );	
				$caesars = return_between($source_code, $start, $end, EXCL);
				$game_date = trim(split_string(return_between($source_code, "Game Date:",  "</TD>", EXCL), ",", AFTER, EXCL));
				$game_month = trim(split_string($game_date, " ", BEFORE, EXCL));
				$game_month = $full_month_mapping[$game_month];
				$game_day = trim(return_between($game_date, " ", ",", EXCL));
				$game_date = "2014-$game_month-$game_day";
				$game_time = trim(split_string(return_between($source_code, "Game Time:",  "</TD>", EXCL), "p;&nbsp;&nbsp;", AFTER, EXCL));
				$game_ampm = format_for_mysql(substr($game_time, -2));
                $game_hour = trim(split_string($game_time, ":", BEFORE, EXCL));
                $game_minute = trim(return_between($game_time, ":", $game_ampm, EXCL));
                if ($game_ampm == 'pm' && $game_hour != 12) {
                    $game_hour += 12;
                }
                if ($game_hour < 10) {
                    $game_hour = "0$game_hour";
                }
                $game_time = "$game_hour:$game_minute:00";
                $stats_start = "<TD class=\"bg2\"";
                $stats_end = "</TD>";
				$stats = parse_array_clean($caesars, $stats_start, $stats_end);
                $k = 0;
                $stats_date = 0;
                $stats_time = 1;
                $stats_fav = 2;
                $stats_dog = 3;
                $stats_break = 11;
                for ($i = 0; $i < count($stats); $i++) {
                    $data = trim(split_string($stats[$i], "nowrap>", AFTER, EXCL));
                    if (strpos($data, "</font><font")) {
                        $data_pre = return_between($data, "<font color=\"b20000\">", "</font><font color", EXCL);
                        $data = $data_pre.return_between($data, "</font><font color=\"b20000\">", "</font>", EXCL);
                    } else if (strpos($data, "font color")) {
                        $data_pre = split_string($data, "<font color", BEFORE, EXCL);
                        $data = $data_pre.return_between($data, "\">", "</font>", EXCL);
                    }
                    if (strpos($data, "+")) {
                        $odds = split_string($data, "+", AFTER, INCL);
                    } else if (strpos($data, "-")) {
                        $odds = split_string($data, "-", AFTER, INCL);
					}
					$casino = strtolower($start);
					switch (true) {
						case ($i == $stats_date):
							$month = split_string($data, "/", BEFORE, EXCL);
							$day = split_string($data, "/", AFTER, EXCL);
							$date = "2014-$month-$day";
							$stats_stg[$home_abbr][$game_date][$game_time][$casino][$k]['game_date'] = $game_date;
							$stats_stg[$home_abbr][$game_date][$game_time][$casino][$k]['game_time'] = $game_time;
							$stats_stg[$home_abbr][$game_date][$game_time][$casino][$k]['odds_date'] = $date;
                            $stats_date += 12;
                            break;
						case ($i == $stats_time):
							$ampm = format_for_mysql(substr($data, -2));
							$hour = split_string($data, ":", BEFORE, EXCL);
							$minute = return_between($data, ":", $ampm, EXCL);
							if ($ampm == 'pm' && $hour != 12) {
								$hour += 12;
							}
							if ($hour < 10) {
								$hour = "0$hour";
							}
							$time = "$hour:$minute:00";
							$stats_stg[$home_abbr][$game_date][$game_time][$casino][$k]['odds_time'] = $time;
							$stats_stg[$home_abbr][$game_date][$game_time][$casino][$k]['home'] = uncorrectAbbr($home_abbr);
							$stats_stg[$home_abbr][$game_date][$game_time][$casino][$k]['away'] = uncorrectAbbr($away_abbr);
							$stats_stg[$home_abbr][$game_date][$game_time][$casino][$k]['casino'] = $casino;
                            $stats_time += 12;
                            break;
                        case ($i == $stats_fav):
                            if (strpos($data, $home_abbr) === 0) {
								$stats_stg[$home_abbr][$game_date][$game_time][$casino][$k]['home_odds'] = $odds;
								$stats_stg[$home_abbr][$game_date][$game_time][$casino][$k]['home_pct_win'] = convertOddsToPct($odds);
                                $marked_home = 1;
                            } else if (strpos($data, $away_abbr) === 0) {
								$stats_stg[$home_abbr][$game_date][$game_time][$casino][$k]['away_odds'] = $odds;
								$stats_stg[$home_abbr][$game_date][$game_time][$casino][$k]['away_pct_win'] = convertOddsToPct($odds);
                                $marked_away = 1;
                            }
                            $stats_fav += 12;
                            break;
                        case ($i == $stats_dog):
                            if (strpos($data, $home_abbr) === 0) {
								$stats_stg[$home_abbr][$game_date][$game_time][$casino][$k]['home_odds'] = $odds;
								$stats_stg[$home_abbr][$game_date][$game_time][$casino][$k]['home_pct_win'] = convertOddsToPct($odds);
                            } else if (strpos($data, $away_abbr) === 0) {
								$stats_stg[$home_abbr][$game_date][$game_time][$casino][$k]['away_odds'] = $odds;
								$stats_stg[$home_abbr][$game_date][$game_time][$casino][$k]['away_pct_win'] = convertOddsToPct($odds);
                            }
                            $stats_dog += 12;
                            break;
                        case ($i == $stats_break):
							$k++;
                            $stats_break += 12;
                            break;
                    }
                }
                return $stats_stg;
}

$final_odds = array(array(
	'game_date',
	'game_time',
	'odds_date',
	'odds_time',
	'home',
	'away',
	'casino',
	'home_odds',
	'away_odds',
	'home_pct_win',
	'away_pct_win'
));
$stats_stg = array();
$date = ds_modify($date, '-1 Day');
if ($argv[1]) {
	$date = $argv[1];
}	
$year = substr(split_string($date, "-", BEFORE, EXCL), -2);
$month = return_between($date, "-", "-", EXCL);
$day = substr($date, -2);
$already_checked = array();

$games_sql =
	"SELECT time_est, away, home
	FROM lineups_2014 
	WHERE ds = '$date'";
$games = exe_sql('baseball', $games_sql);
if (!$games) {
	exit("No Games");
}

foreach ($games as $game) {
	$home_abbr = strtoupper($game['home']);
	$away_abbr = strtoupper($game['away']);
	$home_city = array_search($home_abbr, $team_mapping);
	$away_city = array_search($away_abbr, $team_mapping);
	$home_team = strtolower($team_names[$home_city]);
	$away_team = strtolower($team_names[$away_city]);
	// Correct the abbreviations AFTER all the above mapping
	$home_abbr = correctAbbr($home_abbr);
	$away_abbr = correctAbbr($away_abbr);
	$hour = substr($game['time_est'], 0, 2);

	$vegas = "http://www.vegasinsider.com/mlb/odds/las-vegas/line-movement/$away_team-@-$home_team.cfm/date/$month-$day-$year/time/$hour";
	$offshore = "http://www.vegasinsider.com/mlb/odds/offshore/line-movement/$away_team-@-$home_team.cfm/date/$month-$day-$year/time/$hour";
	$vegas_source_code = get_html($vegas);
	if (strpos($vegas_source_code, "Sports Betting and Gambling News") ||
		strpos($vegas_source_code, "unexpected error") ||
		strpos($vegas_source_code, "cannot be found")) {
			echo "SKIP THIS SHOULD NOT HAPPEN";
			continue;
	}
	$offshore_source_code = get_html($offshore);
	$start = "CAESARS";
	$end = "CG TECHNOLOGY";
	$stats_stg = pullCasinoOdds($vegas_source_code, $stats_stg, $start, $end, $home_abbr, $away_abbr);
	$start = "MGM";
	$end = "PEPPERMILL";
	$stats_stg = pullCasinoOdds($vegas_source_code, $stats_stg, $start, $end, $home_abbr, $away_abbr);
	$start = "WYNN";
	$end = "Footer";
	$stats_stg = pullCasinoOdds($vegas_source_code, $stats_stg, $start, $end, $home_abbr, $away_abbr);
	$start = "SPORTSBOOK.AG";
	$end = "SPORTSINTERACTION";
	$stats_stg = pullCasinoOdds($offshore_source_code, $stats_stg, $start, $end, $home_abbr, $away_abbr);
	$start = "5DIMES";
	$end = "BET HORIZON";
	$stats_stg = pullCasinoOdds($offshore_source_code, $stats_stg, $start, $end, $home_abbr, $away_abbr);
}

foreach ($stats_stg as $home_team) {
	foreach ($home_team as $casino) {
		foreach ($casino as $game_date) {
			foreach ($game_date as $game) {
				foreach ($game as $odds) {
					if (!$odds['home_odds']) {
						continue;
					}
					array_push($final_odds, $odds);
				}
			}
		}
	}
}

// Run function to export the data to mysql, backup a copy to csv,
// and leave a record in table_status -> in sweetfunctions.php
$table_name = 'odds_2014';
export_and_save($database, $table_name, $final_odds, $date);

?>
