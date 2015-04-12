<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');

# Input 1 = Start Date
# Input 2 = End Date
# Inout 3 = Path
# Input 4 = Script #1
# Input 5,6,etc. Script X (optional)

const PATH  = HOME_PATH.'Scripts/';

// $argv[0] is '/path/to/script.php'
$start = $argv[1];
$end = $argv[2];
$folder = $argv[3];
$path = PATH.$folder."/";
$scripts = array_slice($argv, 4);

$year = split_string($start, "-", BEFORE, EXCL);
$start_month = return_between($start, "-", "-", EXCL);
$start_day = substr($start, -2);
$end_month = return_between($end, "-", "-", EXCL);
$end_day = substr($end, -2);
$months = $months;
$days = $days;

foreach ($months as $month) {
	if ($month > $end_month || $month < $start_month) {
		continue;
	}
	foreach ($days as $day) {
		if ($month == $end_month && $day > $end_day) {
			continue;
		} else if ($month == $start_month && $day < $start_day) {
			continue;
		} else if (($month == '04' || $month == '06' || $month == '09') && $day == '31') {
			continue;
		// All star break
		} else if ($month == '07' && ($day > 13 && $day < 18)) {
			$date = "2014-$month-$day";
			echo $date." All Star Break"."\n";
			continue;
		}
		$date = "2014-$month-$day"."\n";
		echo $date."\n";
		$prefix = null;
		foreach ($scripts as $script_name) {
			switch ($script_name) {
				case "sql_to_csv.php":
					$table = "sim_nomagic_2014";	
					$script_name = "$script_name -t $table -d $date";
					break;
				case "csv_to_sql.php":
					$table = "sim_output_nomagic_hist_2013";
					$script_name = $script_name." -t $table -d $date";
					break;
				case "main.py":
					$script_name = $script_name." 5000 100";
					break;
				// As of now copy main is 50/50 batter pitcher
				case "copy_main.py":
					$script_name = $script_name." 1000 0";
					break;
				// As of now 2copy_main is what we are using for
				// pitchertotal_nomagic
				case "2copy_main.py":
					$script_name = $script_name." 1000 100";
					break;
				default:
					$script_name = $script_name . " $date";
					break;
			}
			if (strpos($script_name, "py")) {
				$prefix = '/usr/local/bin/python ';
			} else {
				$prefix = '/usr/bin/php ';
			}
			$script = $prefix.$path.$script_name;
			//echo $script."\n";
			shell_exec($script);
		}
	}
}

send_email('Backfill Complete',' ', 'd');

?>
