<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'Include/sweetfunctions.php');

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

function correctTeam($team) {
	switch ($team) {
		case "N.Y. Mets":
			$team = "NY Mets";
			break;
		case "Chi. White Sox":
			$team = "Chicago Sox";
			break;
		case "L.A. Angels":
			$team = "LA Angels";
			break;
		case "Chi. Cubs":
			$team = "Chicago Cubs";
			break;
		case "L.A. Dodgers":
			$team = "LA Dodgers";
			break;
		case "N.Y. Yankees":
			$team = "NY Yankees";
			break;
	}
	return $team;
}

$countup = 0;
$player_stats = array();

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

$final_array = array(array('time','date','away','home','home_odds','away_odds','ts'));
foreach ($times as $i => $time) {
	$game_date = split_string($time, "  ", BEFORE, EXCL);
	$month = split_string($game_date, "/", BEFORE, EXCL);
	$day = split_string($game_date, "/", AFTER, EXCL);
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
	$final_array[$i+1]['time'] = $game_time;
	$final_array[$i+1]['date'] = '2014-'.$month.'-'.$day;
	$final_array[$i+1]['ts'] = $ts;
}

// Since teams are grouped in twos only put Home team
// into the array
$team_num = 1;
$odd = 0;
foreach ($teams as $team) {
	$team = correctTeam($team);
	$team = $team_mapping[$team];
	if ($odd === 0) {
		$final_array[$team_num]['away'] = $team;
		$odd = 1;
	} else {
		$final_array[$team_num]['home'] = $team;
		$odd = 0;
		$team_num++;
	}
}

// Starting with 1 since that what corresponds to current consensus odds
$j = 8;
for ($i = 1; $i < count($final_array); $i++) {
	$final_array[$i]['home_odds'] = $clean_stats[$j]['home'];
	$final_array[$i]['away_odds'] = $clean_stats[$j]['away'];
	$j += 9;
}

// Run function to export the data to mysql, backup a copy to csv,
// and leave a record in table_status -> in sweetfunctions.php
$table_name = 'live_odds_2014';
export_and_save($database, $table_name, $final_array);
//print_r($final_array);

?>
