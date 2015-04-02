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

$colheads = array(
	'game_time',
	'game_date',
	'away',
	'home',
	'home_odds',
	'away_odds',
	'season',
	'ts',
	'ds'
);

date_default_timezone_set('America/Los_Angeles');
$date = date('Y-m-d');
$ts = date("Y-m-d H:i:s");

$target = "http://www.vegasinsider.com/mlb/odds/las-vegas/";
$source_code = get_html($target);

$teams_start = "class=tabletext>";
$teams_end = "</a></b>";
$teams = parse_array_clean($source_code, $teams_start, $teams_end);

$times_start = "<span class=\"cellTextHot\">";
$times_end = "</span><br>";
$times = parse_array_clean($source_code, $times_start, $times_end);

$stats_start = "cellBorderL1\" width=\"56\" nowrap style=\"text-align:center";
$stats_end = "</td>";
$stats = parse_array_clean($source_code, $stats_start, $stats_end);

$clean_stats = array();
foreach ($stats as $i => $stat) {
	$stat = str_replace("+", "", $stat);
	$away_odds = trim(return_between($stat, "<br>", "<br>", EXCL));
	$home_odds = return_between($stat, "<br>", "</a>", EXCL);
	$home_odds = trim(split_string($home_odds, "<br>", AFTER, EXCL));
	$clean_stats[$i]['away'] = str_replace("XX", null, $away_odds);
	$clean_stats[$i]['home'] = str_replace("XX", null, $home_odds);
	if (strpos($home_odds, "nbsp") || strpos($away_odds, "nbsp")) {  
		$clean_stats[$i]['away'] = null;
		$clean_stats[$i]['home'] = null;
	}
}

$final_array = array();
foreach ($times as $i => $time) {
	$game_date = split_string($time, "  ", BEFORE, EXCL);
	$month = split_string($game_date, "/", BEFORE, EXCL);
	$day = split_string($game_date, "/", AFTER, EXCL);
	$year = date('Y');
	$game_time = split_string($time, "  ", AFTER, EXCL);
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
	$final_array[$i]['game_time'] = $game_time;
	$final_array[$i]['game_date'] = "$year-$month-$day";
	$final_array[$i]['season'] = $year;
	$final_array[$i]['ts'] = $ts;
	$final_array[$i]['ds'] = date('Y-m-d');
}

// Since teams are grouped in twos only put Home team into the array.
$team_num = 0;
$odd = 0;
foreach ($teams as $i => $team) {
	$team = Teams::getTeamAbbreviationFromCity($team);
	if ($i % 2 === 0) {
		$final_array[$team_num]['away'] = $team;
	} else {
		$final_array[$team_num]['home'] = $team;
		$team_num++;
	}
}

/* Key
 * 0 = Opening Odds
 * 1 = Consensus Odds
 * 8 = Sportsbook.ag Odds
 * TODO(cert): Switch to Sportsbook.ag once season starts.
 * TODO(cert): Change these to constants during rewrite.
 */
$j = 1;
for ($i = 0; $i < count($final_array); $i++) {
	$final_array[$i]['home_odds'] = $clean_stats[$j]['home'];
	$final_array[$i]['away_odds'] = $clean_stats[$j]['away'];
	// There are 9 different columns so skip over odds accordingly.
	$j += 9;
}

$insert_table = 'live_odds';
multi_insert(
	DATABASE,
	$insert_table,
	$final_array,
	$colheads
);

?>
