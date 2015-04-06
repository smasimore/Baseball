<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');
include(HOME_PATH.'Scripts/Include/Teams.php');

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

// Function to map standard abbreviations to ones used on the odds site.
function getOddsAbbr($team) {
	$team = strtoupper($team);
    switch ($team) {
        case 'WSH':
            return 'WAS';
		case 'TB':
			return 'TAM';
		case 'KC':
			return 'KAN';
    }
    return $team;
}

function pullCasinoOdds(
	$source_code,
	$stats_stg,
	$start,
	$end,
	$home_abbr,
	$away_abbr
) {

	$casino_html = return_between($source_code, $start, $end, INCL);
	// If the casino name is not in the HTML we don't want to scrape.
	if (strpos($casino_html, $start) === false) {
		return $stats_stg;
	}
	$game_date = trim(split_string(return_between($source_code, "Game Date:",  "</TD>", EXCL), ",", AFTER, EXCL));
	$game_year = substr($game_date, -4);
	$game_month = trim(split_string($game_date, " ", BEFORE, EXCL));
	$game_month = date('m',strtotime($game_month));
	$game_day = trim(return_between($game_date, " ", ",", EXCL));
	$game_date = "$game_year-$game_month-$game_day";
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
	$stats = parse_array_clean($casino_html, $stats_start, $stats_end);
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
				$date = "$game_year-$month-$day";
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
				$stats_stg[$home_abbr][$game_date][$game_time][$casino][$k]['home'] =
					Teams::getStandardTeamAbbr($home_abbr);
				$stats_stg[$home_abbr][$game_date][$game_time][$casino][$k]['away'] =
					Teams::getStandardTeamAbbr($away_abbr);
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

$colheads = array(
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
	'away_pct_win',
	'season',
	'ds',
	'ts'
);

// NOTE: THIS SCRIPT SHOULD BE RUN THE DAY AFTER GAMES ARE PLAYED TO GET ODDS
// FROM VARIOUS CASINOS. FOR LIVE ODDS USE THE LIVE_ODDS SCRIPT.

$final_odds = array();
$stats_stg = array();
$date = ds_modify($date, '-1 Day');
$test = false;
$test_ds = '2015-03-19';

if ($argv[1]) {
	$date = $argv[1];
	if ($argv[1] === 'test') {
		$test = true;
		$date = $test_ds;
	}
}	

$year = substr(split_string($date, "-", BEFORE, EXCL), -2);
$month = return_between($date, "-", "-", EXCL);
$day = substr($date, -2);

if ($test) {
	$games = array(
		array(
			'home' => 'atl',
			'away' => 'mia',
			'time_est' => 1305
		)
	);
} else {
	$games_sql =
		"SELECT time_est, away, home
		FROM lineups
		WHERE ds = '$date'";
	$games = exe_sql(DATABASE, $games_sql);
	if (!$games) {
		exit("No Games");
	}
}

foreach ($games as $game) {
	$home_team = Teams::getTeamNameFromAbbr($game['home']);
	$away_team = Teams::getTeamNameFromAbbr($game['away']);
	// Since the odds are listed under non-standard abbreviations create
	// odds_abbrs for parsing purposes.
	$home_odds_abbr = getOddsAbbr($game['home']);
	$away_odds_abbr = getOddsAbbr($game['away']);
	$hour = substr($game['time_est'], 0, 2);

	$vegas =
		"http://www.vegasinsider.com/mlb/odds/las-vegas/line-movement/$away_team-@-$home_team.cfm/date/$month-$day-$year/time/$hour";
	$offshore =
		"http://www.vegasinsider.com/mlb/odds/offshore/line-movement/$away_team-@-$home_team.cfm/date/$month-$day-$year/time/$hour";
	$vegas_source_code = get_html($vegas);
	if (strpos($vegas_source_code, "Sports Betting and Gambling News") ||
		strpos($vegas_source_code, "unexpected error") ||
		strpos($vegas_source_code, "cannot be found")) {
			exit('Invalid URL');
	}
	$offshore_source_code = get_html($offshore);

	// Add casinos to parse html in the form of casino => next casino in html.
	$vegas_casinos = array(
		'CAESARS' => 'CG TECHNOLOGY',
		'MGM' => 'PEPPERMILL',
		'WYNN' => 'Footer'
	);
	$offshore_casinos = array(
		'SPORTSBOOK.AG' => 'SPORTSINTERACTION',
		'5DIMES' => 'BET HORIZON'
	);
	foreach ($vegas_casinos as $start_text => $end_text) {
		$stats_stg = pullCasinoOdds(
			$vegas_source_code,
			$stats_stg,
			$start_text,
			$end_text,
			$home_odds_abbr,
			$away_odds_abbr
		);
	}
	foreach ($offshore_casinos as $start_text => $end_text) {
		$stats_stg = pullCasinoOdds(
			$offshore_source_code,
			$stats_stg,
			$start_text,
			$end_text,
			$home_odds_abbr,
			$away_odds_abbr
		);
	}
}

foreach ($stats_stg as $home_team) {
	foreach ($home_team as $casino) {
		foreach ($casino as $game_date) {
			foreach ($game_date as $game) {
				foreach ($game as $odds) {
					if (!$odds['home_odds']) {
						continue;
					}
					$odds['ds'] = date('Y-m-d');
					$odds['ts'] = date('Y-m-d H:i:s');
					$odds['season'] = date('Y');
					array_push($final_odds, $odds);
				}
			}
		}
	}
}

$insert_table = 'odds';
if ($test) {
	$test_array = array_slice($final_odds, 0, 5);
	print_r($test_array);
	exit();
}
multi_insert(
	DATABASE,
	$insert_table,
	$final_odds,
	$colheads
);

?>
