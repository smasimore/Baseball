<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/sweetfunctions.php');

const PATH  = HOME_PATH.'';

$folder = $argv[1];
$path = PATH.$folder."/";
$scripts = array_slice($argv, 2);

echo "\n"."Let's do some backfilling..."."\n";
echo "Enter Start Date (or hit ENTER for 2014-04-01): ";
$handle = fopen("php://stdin","r");
$input = trim(fgets($handle));
if ($input == "") {
	$start = '2014-04-01';
} else {
	$start = $input;
}
$today = date("Y-m-d");
echo "Enter End Date (or hit ENTER for $today): ";
$handle = fopen("php://stdin","r");
$input = trim(fgets($handle));
if ($input == "") {
	$end = $today;
} else {
	$end = $input;
}
echo "Press ENTER to backfill the sim, otherwise type 'n': ";
$handle = fopen("php://stdin","r");
$input = trim(fgets($handle));
if ($input == '') {
	$folder = 'Simulation';
	$path = PATH.$folder."/";
	$scripts = array(
		'sql_to_csv.php',
		'backfill_main.py',
		'csv_to_sql.php'
	);
	while (!$correct_year) {
		echo "2014 or 2013? ";
		$handle = fopen("php://stdin","r");
		$model_year = trim(fgets($handle));
		if ($model_year == '2013') {
			$correct_year = 1;
			$model_pct = 0;
		} else if ($model_year == '2014') {
			$correct_year = 1;
			$model_pct = 100;
		}
	}
	while (!$correct_magic) {
		echo "magic or nomagic? ";
		$handle = fopen("php://stdin","r");
		$magic = strtolower(trim(fgets($handle)));
		if ($magic == 'magic' || $magic == 'nomagic') {
			$correct_magic = 1;
		}
	}
	echo "How else can we identify the model? (i.e. 50_total_50_pitcher) ";
	$handle = fopen("php://stdin","r");
	$model_id = strtolower(trim(fgets($handle)));
	$model_input = "sim_$magic"."_2014";
	$model_output = "sim_output_$magic"."_$model_id"."_$model_year";
	$exists_model_output = exe_sql('baseball',
		"SELECT count(1) as test
		FROM $model_output"
	);
	if (!$exists_model_output) {
		echo "\n"."Error message leads me to believe this doesn't exist yet...want to create it? (y/n) ";
		$handle = fopen("php://stdin","r");
		$confirm = strtolower(trim(fgets($handle)));
		if ($confirm == 'y') {
			$create_table = exe_sql('baseball',
				"CREATE TABLE $model_output 
				LIKE sim_output_nomagic_50total_50pitcher_histrunning_$model_year",
				'create'
			);
			echo "$model_output has been created!"."\n";
		} else {
			exit();
		}
	} else {
		echo 'this isnt a new table';
	}
	echo "How many times should we run the model per game? ";
	$handle = fopen("php://stdin","r");
	$model_runs = strtolower(trim(fgets($handle)));
	echo "Perfect, to confirm, we'll call the model $model_output and will pull from $model_input with params $model_runs $model_pct? (y/n) ";
	$handle = fopen("php://stdin","r");
	$confirm = strtolower(trim(fgets($handle)));
	if ($confirm != 'y') {
		echo "Sorry to hear that...restarting"."\n";
		exit();
	}
} else if ($input == 'n') {
	echo 'dan needs to build this out';
} else {
	exit('You Should Follow Directions Better');
}

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
		echo "Now Backfilling..."."\n";
		$date = "2014-$month-$day"."\n";
		echo $date."\n";
		$prefix = null;
		foreach ($scripts as $script_name) {
			switch ($script_name) {
				case "sql_to_csv.php":
					$script_name = "$script_name -t $model_input -d $date";
					break;
				case "csv_to_sql.php":
					$script_name = $script_name." -t $model_output -d $date";
					break;
				case "backfill_main.py":
					$script_name = $script_name." ".$model_runs." ".$model_pct;
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
			shell_exec($script);
		}
	}
}

send_email('Backfill Complete',' ', 'd');

?>
