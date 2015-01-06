<?php
//Copyright 2014, Saber Tooth Ventures, LLC

if (!defined('HOME_PATH')) {
    include('/Users/constants.php');
}
ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include(HOME_PATH.'Scripts/Include/http.php');
include(HOME_PATH.'Scripts/Include/parse.php');
include(HOME_PATH.'Scripts/Include/mysql.php');
// Leaving this in until I can test whether we need it or not.
date_default_timezone_set('America/Los_Angeles');
$date = date('Y-m-d');
$ts = date("Y-m-d H:i:s");

$database = 'baseball';

$team_mapping = array(
    'Atlanta' => 'ATL',
    'Philadelphia' => 'PHI',
    'Washington' => 'WSH',
    'NY Mets' => 'NYM',
    'Miami' => 'MIA',
    'St. Louis' => 'STL',
    'Cincinnati' => 'CIN',
    'Pittsburgh' => 'PIT',
    'Chicago Cubs' => 'CHC',
    'Milwaukee' => 'MIL',
    'Arizona' => 'ARI',
    'San Francisco' => 'SF',
    'Colorado' => 'COL',
    'San Diego' => 'SD',
    'LA Dodgers' => 'LAD',
    'Boston' => 'BOS',
    'Baltimore' => 'BAL',
    'NY Yankees' => 'NYY',
    'Tampa Bay' => 'TB',
    'Toronto' => 'TOR',
    'Detroit' => 'DET',
    'Cleveland' => 'CLE',
    'Kansas City' => 'KC',
    'Minnesota' => 'MIN',
    'Chicago Sox' => 'CHW',
    'Oakland' => 'OAK',
    'Texas' => 'TEX',
    'Seattle' => 'SEA',
    'LA Angels' => 'LAA',
    'Houston' => 'HOU'
);

$team_names = array(
    'Atlanta' => 'Braves',
    'Philadelphia' => 'Phillies',
    'Washington' => 'Nationals',
    'NY Mets' => 'Mets',
    'Miami' => 'Marlins',
    'St. Louis' => 'Cardinals',
    'Cincinnati' => 'Reds',
    'Pittsburgh' => 'Pirates',
    'Chicago Cubs' => 'Cubs',
    'Milwaukee' => 'Brewers',
    'Arizona' => 'Diamondbacks',
    'San Francisco' => 'Giants',
    'Colorado' => 'Rockies',
    'San Diego' => 'Padres',
    'LA Dodgers' => 'Dodgers',
    'Boston' => 'Red-Sox',
    'Baltimore' => 'Orioles',
    'NY Yankees' => 'Yankees',
    'Tampa Bay' => 'Rays',
    'Toronto' => 'Blue-Jays',
    'Detroit' => 'Tigers',
    'Cleveland' => 'Indians',
    'Kansas City' => 'Royals',
    'Minnesota' => 'Twins',
    'Chicago Sox' => 'White-Sox',
    'Oakland' => 'Athletics',
    'Texas' => 'Rangers',
    'Seattle' => 'Mariners',
    'LA Angels' => 'Angels',
    'Houston' => 'Astros'
);

$stadiums = array(
    'ARI' => 'Chase Field',
    'HOU' => 'Minute Maid Park',
    'STL' => 'Busch Stadium II',
    'SD' => 'PETCO Park',
    'CIN' => 'Great American',
    'MIL' => 'Miller Park',
    'ATL' => 'Turner Field',
    'CHC' => 'Wrigley Field',
    'PHI' => 'Citizens Bank Park',
    'SF' => 'AT&T Park',
    'PIT' => 'PNC Park',
    'NYY' => 'Yankee Stadium',
    'LAD' => 'Dodger Stadium',
    'BAL' => 'Camden Yards',
    'CLE' => 'Progressive Field',
    'CHW' => 'U.S. Cellular Field',
    'TEX' => 'Rangers Ballpark in Arlington',
    'TOR' => 'Rogers Centre',
    'SEA' => 'Safeco Field',
    'COL' => 'Coors Field',
    'DET' => 'Comerica Park',
    'KC' => 'Kauffman Stadium',
    'MIN' => 'Target Field',
    'LAA' => 'Angel Stadium',
    'NYM' => 'Citi Field',
    'WSH' => 'Nationals Park',
    'OAK' => 'Overstock.com Coliseum',
    'BOS' => 'Fenway Park',
    'TB' => 'Tropicana Field',
    'MIA' => 'Marlins Park'
);

$months = array(
    '03',
    '04',
    '05',
    '06',
    '07',
    '08',
    '09',
    '10'
);

$days = array(
    '01',
    '02',
    '03',
    '04',
    '05',
    '06',
    '07',
    '08',
    '09',
    '10',
    '11',
    '12',
    '13',
    '14',
    '15',
    '16',
    '17',
    '18',
    '19',
    '20',
    '21',
    '22',
    '23',
    '24',
    '25',
    '26',
    '27',
    '28',
    '29',
    '30',
    '31'
);

$month_mapping = array(
    'Mar' => 3,
    'Apr' => 4,
    'May' => 5,
    'Jun' => 6,
    'Jul' => 7,
    'Aug' => 8,
    'Sep' => 9,
    'Oct' => 10
);

$position_mapping = array(
    'DH' => 0,
    'P' => 0,
    '1B' => .06,
    'LF' => .08,
    'RF' => .09,
    '3B' => .11,
    'CF' => .13,
    '2B' => .16,
    'SS' => .18,
    'C' => .19
);

$duplicate_names = array(
    'miguel_gonzalez'  => array(
        'CHW' => 'miguel_gonzalez_30',
        30599 => 'miguel_gonzalez_30',
        'BAL' => 'miguel_gonzalez_29',
        29310 => 'miguel_gonzalez_29'
    ),
    'juan_perez' => array(
        'SF' => 'juan_perez_32',
        32011 => 'juan_perez_32',
        'TOR' => 'juan_perez_28',
        'MIL' => 'juan_perez_28',
        28605 => 'juan_perez_28'
    ),
    'henry_rodriguez' => array(
        'CIN' => 'henry_rodriguez_31',
        31331 => 'henry_rodriguez_31',
        'WAS' => 'henry_rodriguez_30',
        'CHC' => 'henry_rodriguez_30',
        30007 => 'henry_rodriguez_30' 
    ),
    'david_carpenter' => array(
        'TOR' => 'david_carpenter_29',
        'HOU' => 'david_carpenter_29',
        'ATL' => 'david_carpenter_29',
        29698 => 'david_carpenter_29',
        'LAA' => 'david_carpenter_31',
        31305 => 'david_carpenter_31'
    ),
    'chris_carpenter' => array(
        'STL' => 'chris_carpenter_36',
        3610 => 'chris_carpenter_36',
        'BOS' => 'chris_carpenter_31',
        31088 => 'chris_carpenter_31'
    ),
    'rich_thompson' => array(
        'TB' => 'rich_thompson_58',
        5886 => 'rich_thompson_58',
        'OAK' => 'rich_thompson_28',
        'LAA' => 'rich_thompson_28',
        28887 => 'rich_thompson_28'
    ),
    'chris_young' => array(
        'OAK' => 'chris_young_65',
        'ARI' => 'chris_young_65',
        'NYM' => 'chris_young_65',
        6514 => 'chris_young_65',
        // Quite an unfortunate trade haha
        //'NYM' => 'chris_young_60',
        'SEA' => 'chris_young_60',
        6073 => 'chris_young_60'
    ),
    'francisco_rodriguez' => array(
        'MIL' => 'francisco_rodriguez_53',
        'NYM' => 'francisco_rodriguez_53',
        'BAL' => 'francisco_rodriguez_53',
        5357 => 'francisco_rodriguez_53',
        'LAA' => 'francisco_rodriguez_30',
        30176 => 'francisco_rodriguez_30'
    ),
// Section for nicknames on pitching site
    'nate_karns' => array(
        'WSH' => 'nathan_karns'
    ),
    'sam_deduno' => array(
        'MIN' => 'samuel_deduno'
    ),
    'nathan_eovaldi' => array(
        'LAD' => 'nate_eovaldi',
        'MIA' => 'nate_eovaldi'
    ),
    'charles_leesman' => array(
        'CHW' => 'charlie_leesman'
    ),
    'mike_kickham' => array(
        'SF' => 'michael_kickham'
    ),
    'jerry_hairston' => array(
        'LAD' => 'jerry_hairston_jr.'
    ),
    'steve_lombardozzi' => array(
        'WSH' => 'stephen_lombardozzi',
        'BAL' => 'stephen_lombardozzi'
    ),
    'john_mayberry' => array(
        'PHI' => 'john_mayberry_jr.'
    ),
    'jr_murphy' => array(
        'NYY' => 'j.r._murphy'
    ),
    'eric_young' => array(
        'COL' => 'eric_young_jr.',
        'NYM' => 'eric_young_jr.'
    ),
    'jon_niese' => array(
        'NYM' => 'jonathon_niese'
    ),
    'philip_gosselin' => array(
        'ATL' => 'phil_gosselin'
    ),
    'tony_gywnn_jr.' => array(
        'PHI' => 'tony_gwynn',
        'LAD' => 'tony_gwynn'
    )
);

$splits = array(
    'Total',
    'Home', 
    'Away', 
    'VsLeft', 
    'VsRight', 
    'NoneOn', 
    'RunnersOn', 
    'ScoringPos', 
    'ScoringPos2Out', 
    'BasesLoaded', 
    25, 
    50, 
    75, 
    100
);

$pctStats = array(
    'pct_single' => 'singles',
    'pct_double' => 'doubles',
    'pct_triple' => 'triples',
    'pct_home_run' => 'home_runs',
    'pct_walk' => 'walks',
    'pct_strikeout' => 'strikeouts',
    'pct_ground_out' => 'ground_outs',
    'pct_fly_out' => 'fly_outs'
);

function is_assoc($array) {
    foreach (array_keys($array) as $k => $v) {
        if ($k !== $v)
            return true;
    }
    return false;
}

function checkDuplicatePlayers($name, $team, $duplicate_names) {
    if (!in_array($name, array_keys($duplicate_names))) {
        return $name;
    } else {
        return $duplicate_names[$name][$team];
    }
}

function fixSQLjson($data) {

    $data = str_replace('""', '"', $data);
    $data = substr($data, 1);
    $data = substr($data, 0, -1);

    return $data;

}

function convertStartingPitcherTeams($abbr) {

    if ($abbr == 'WAS') {
        $abbr = 'WSH';
    } else if ($abbr == 'NYN') {
        $abbr = 'NYM';
    } else if ($abbr == 'SLN') {
        $abbr = 'STL';
    } else if ($abbr == 'CHN') {
        $abbr = 'CHC';
    } else if ($abbr == 'SFN') {
        $abbr = 'SF';
    } else if ($abbr == 'SDN') {
        $abbr = 'SD';
    } else if ($abbr == 'LAN') {
        $abbr = 'LAD'; 
    } else if ($abbr == 'NYA') {
        $abbr = 'NYY';
    } else if ($abbr == 'TBA') {
        $abbr = 'TB';
    } else if ($abbr == 'KCA') {
        $abbr = 'KC';
    } else if ($abbr == 'CHA') {
        $abbr = 'CHW';
    } else if ($abbr == 'ANA') {
        $abbr = 'LAA';
    } 
    return $abbr;
}

function checkSQLError($table_name, $name, $min = 1, $max = 5000) {

    if (count($table_name) < $min) {
        echo $name." has less than ".$min." rows."."\n";
    }
    if (count($table_name) > $max) {
        echo $name." has more than ".$max." rows."."\n";
    }
}

function decode_schedule($csv_name) {

    $schedule_2012 = csv_to_array($csv_name);

    foreach ($schedule_2012 as $i => $schedule) {
        $lineup_h = json_decode($schedule["lineup_h"]);
        $lineup_a = json_decode($schedule["lineup_a"]);
        $lineup_h_array = array();
        $lineup_a_array = array();

        foreach ($lineup_h as $l => $lineup) {
            $lineup_h_array[$l] = json_decode($lineup, true);
        }
        $schedule_2012[$i]["lineup_h"] = $lineup_h_array;

        foreach ($lineup_a as $l => $lineup) {
            $lineup_a_array[$l] = json_decode($lineup, true);
        }
        $schedule_2012[$i]["lineup_a"] = $lineup_a_array;
    }
    return $schedule_2012;
}

function decode_schedule2($csv_name) {

    $schedule_2012 = csv_to_array($csv_name);

    foreach ($schedule_2012 as $i => $schedule) {
        $lineup_h = json_decode($schedule["lineup_h"], true);
        $lineup_a = json_decode($schedule["lineup_a"], true);
        $home_odds = json_decode($schedule["home_odds"], true);

        $schedule_2012[$i]["lineup_h"] = $lineup_h;
        $schedule_2012[$i]["lineup_a"] = $lineup_a;
        $schedule_2012[$i]["home_odds"] = $home_odds;
    }
    return $schedule_2012;
}

function decode_expandedstats($csv_name) {

    $stats_2012 = csv_to_array($csv_name);
    foreach ($stats_2012 as $i => $stats) {
        $batting_stats = json_decode($stats["batting_stats"], true);
        $stats_2012[$i]["batting_stats"] = $batting_stats;
    }
    return $stats_2012;
}

function csv_to_array($filename='', $delimiter=',', $maxchar=0) {

    if(!file_exists($filename) || !is_readable($filename)) {
        echo "################################################"."\n";
        echo 'Cannot read '.$filename.'...try again fool'."\n";
        echo "################################################"."\n";
        return FALSE;
    }

    echo 'Converting '.$filename.' to array'."\n";

    $header = NULL;
    $data = array();
    if (($handle = fopen($filename, 'r')) !== FALSE)
    {
        while (($row = fgetcsv($handle, $maxchar, $delimiter)) !== FALSE)
        {
            if(!$header)
                $header = $row;
            else
                $data[] = array_combine($header, $row);
        }
        fclose($handle);
    }
    return $data;
}

function scrape($target) {

    $web_page = http_get($target, $referer="");
    $source_code = $web_page['FILE'];

    //This spits out all the URLs in terminal that have been scraped.
    $url = $web_page['STATUS']['url'];
    echo $url."\n";
    //$error = $web_page['ERROR'];
    //print_r($error);

    return $source_code;
}

function scrape_backup($target) {

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

function format_double($number, $dec) {
    $type = gettype($number);
    return
        $type == 'double' ? number_format($number, $dec, ".", "") : $number;
}

function getBattingColheads($source_code, $exclude = 'batting_average', $dupe = false)  {

    if ($countup == 0) {
        $colheads = array();
        $colheads_start = " title=\"";
        $colheads_end = "\">";
        $colheads_stg = parse_array_clean($source_code, $colheads_start, $colheads_end);
        foreach ($colheads_stg as $head) {
            $head = format_for_mysql($head);
            $head = str_replace("ops_=_obp_+_slg", "ops", $head);
            $head = str_replace("/", "_", $head);
            $head = str_replace("(per_start)", "per_start", $head);
            if (in_array($head, $colheads) || $head == $exclude) {
                continue;
            }
            // Rename WHIP and K/9 since they're in there twice
            if ($whip) {
                if ($head == 'walks_and_hits_per_innings_pitched') {
                    $head = 'walks_and_hits_per_innings_pitched_dupe';
                } else if ($head == ' strikeouts_per_nine_innings') {
                    $head = ' strikeouts_per_nine_innings_dupe';
                }
            }
            array_push($colheads, $head);
        }
        $colheads = array_slice($colheads, 3);
        return $colheads;
    }
}

function pullAllData($table, $date = null) {
    if (!$date) {
        $result = exe_sql('baseball',
            "SELECT *
            FROM $table"
        );
    } else {
        $result = exe_sql('baseball',
            "SELECT *
            FROM $table
            WHERE ds = '$date'"
        );
    }
    if (!$result) {
        exit("$table IS MISSING");
    }
    return $result;
}

function pullTestData($table, $var_name, $var, $date = null) {
    echo "YOU ARE USING TEST DATA"."\n";
    if (!$date) {
        $result = exe_sql('baseball',
            "SELECT *
            FROM $table 
            WHERE $var_name = '$var'"
        );
    } else {
        $result = exe_sql('baseball',
            "SELECT *
            FROM $table
            WHERE ds = '$date' 
            AND $var_name = '$var'"
        );
    }
    return array($result);
}

function parse_array_clean($source_code, $opentag, $closingtag) {

    $clean_array = array();
    $parsedarray = parse_array($source_code, $opentag, $closingtag);

    foreach ($parsedarray as $x) {
        $temp = return_between($x, $opentag, $closingtag, EXCL);
        array_push($clean_array, $temp);
    }
    return $clean_array;
}

function export_csv($csv, $arrayname) {
    //NOTE - $arrayname has to be an array of arrays for this to work
    $f = fopen($csv, "w");
    foreach ($arrayname as $x) {
        fputcsv($f, $x);
    }
    fclose($f);
}

function index_by($data, $index, $index_2 = null, $index_3 = null) {
    if (!$data) {
        return array();
    }

    $indexed_table = array();
    foreach ($data as $row) {
        $i1 = $row[$index];
        if ($index_3) {
            $i3 = $row[$index_3];
            $i2 = $row[$index_2];
            $indexed_table[$i1.$i2.$i3] = $row;
        } else if ($index_2) {
            $i2 = $row[$index_2];
            $indexed_table[$i1.$i2] = $row;
        } else {
            $indexed_table[$i1] = $row;
        }
    }
    return $indexed_table;
}

function index_by_nonunique($data, $index) {
    $indexed_table = array();
    foreach ($data as $row) {
        $i1 = $row[$index];
        $indexed_table[$i1][] = $row;
    }
    return $indexed_table;
}

function export_sql_to_csv($csv_name, $rows) {
    $header = array_keys($rows[0]);
    $data = array_merge(array($header), $rows);
    export_csv($csv_name, $data);
}

function format_for_mysql($header) {
    $header = strtolower(str_replace(" ", "_", $header));
    $header = str_replace("-", "_", $header);
    $header = str_replace("'", "_", $header);
    return $header;
}

function send_email($subject, $body, $people = "a") {
    switch ($people) {
        case "a":
            $to = '" sarahsmasimore@gmail.com, dan700and2@gmail.com ';
            break;
        case "d":
            $to = '" dan700and2@gmail.com ';
            break;
        case "s":
            $to = '" sarahsmasimore@gmail.com ';
            break;
    }
    $cmd = 'mail -s "' .$subject .
      $to . '<<EOF' . "\n"
      . $body . "\n" . 'EOF' . "\n";
    shell_exec($cmd);


    // TEMP WHILE EMAIL BROKEN - text
    /*if ($people == 'a') {
        $cmd = "curl http://textbelt.com/text -d number=9545628549 -d message='$subject'";
        shell_exec($cmd);
        $cmd = "curl http://textbelt.com/text -d number=6505212142 -d message='$subject'";
        shell_exec($cmd);
    }*/
}

// Format 08, 09, etc. in day/month looping
function formatDayMonth($x) {
    if ($x < 10) {
        $x = "0$x";
    }
    return $x;
}

function insertData($player_stats, $sql_colheads, $date, $database, $table_name) {
    $counter = 0;
    $player_stats_final = array();
    foreach ($player_stats as $i => $stats) {
        if ($counter == 0) {
              $counter++;
              continue;
          }
        $data = array();
            for ($k = 0; $k < count($stats); $k++) {
              $key = $k;
              if (!array_key_exists($k, $stats)) {
                  $key = $sql_colheads[$k];
              }
              $data[$sql_colheads[$k]] = $stats[$key];
          }
        $data['ds'] = $date;
        $success = insert($database, $table_name, $data);
        if (!$success) {
            return false;
          }
    }
    return true;
}

function export_and_save($database, $table_name, $player_stats, $ds = null) {
    date_default_timezone_set('America/Los_Angeles');

    if (!$ds) {
        $date = date('Y-m-d');
    } else {
        $date = $ds;
    }

    $ts = date("Y-m-d H:i:s");
    $sql_colheads = $player_stats[0];

    // Delete any data from this day's partition
    $delete_me = exe_sql($database,
            'DELETE
            FROM '.$table_name.'
            WHERE ds = "'.$date.'"',
            'delete'
            );

    $success = insertData($player_stats, $sql_colheads, $date, $database, $table_name);
    if (!$success) {
        $table_status['table_name'] = $table_name;
        $table_status['num_rows'] = 0;
        $table_status['ts'] = $ts;
        $table_status['ds'] = $date;
        $successful_fail_log = insert($database, 'table_status', $table_status);
        if (!$successful_fail_log) {
            send_email("$table_name FAILED", "Due to mysql error - most likely server went away"); 
        }
        return;
    }
    $date_label = str_replace('-','_',$date);
    $csv_name = '/Volumes/Sarah Masimore/Baseball/Backups/2014/'.$date_label.'_'.$table_name.'.csv';
    export_csv($csv_name, $player_stats);

    // Check to see if there is data in today's partition
    $test_file = exe_sql($database,
        'SELECT count(1) as countup
        FROM '.$table_name.'
        WHERE ds = "'.$date.'"'
        );

    $table_status['table_name'] = $table_name;
    $table_status['num_rows'] = $test_file['countup'];
    $table_status['ts'] = $ts;
    $table_status['ds'] = date('Y-m-d');

    insert($database, 'table_status', $table_status);
}

function convertPctToOdds($pct) {
    if ($pct > .5) {
        $odds = (100 * $pct) / ($pct - 1);
    } else {
        $odds = (100*(1-$pct)) / $pct;
    }
    return $odds;
}

function idx($array, $key, $default = null) {
    if (isset($array[$key])) {
        return $array[$key];
    }
    return $default;
}

function convertOddsToPct($odds) {
    if ($odds < 0) {
        $pct = (-1)*$odds / ((-1)*$odds + 100);
    } else {
        $pct = 100 / (100 + $odds);
    }
    return $pct * 100;
}

function arrayToString($array) {
    $string = null;
    foreach ($array as $key => $value) {
        $string .= "$key : $value" . "\n";
    }
    return $string;
}

function findSimilarName($name, $date) {
    $split_name = split_string($name, "_", AFTER, EXCL);
    $sql = 
        'SELECT player_name
        FROM batting_final_nomagic_2014
        WHERE player_name like "%'.$split_name.'%"  
        AND ds = "'.$date.'"';
    $likely_name = exe_sql('baseball', $sql);
    if (!$likely_name) {
        $likely_name['player_name'] = $name;
    }
    return $likely_name['player_name'];
}

function elvis($var, $default = null) {
    return isset($var) ? $var : $default;
}

function ds_modify($date, $day_change) {
    // Format of $day_change is '+1 day'
    $dateOneDayAdded = strtotime($date.$day_change);
    $new_date = date('Y-m-d', $dateOneDayAdded);
    return $new_date;
}

function median($array) {
    rsort($array); 
    $middle = round(count($array) / 2); 
    $total = $array[$middle-1]; 
    return $total;
}

?>
