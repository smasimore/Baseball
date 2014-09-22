<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('mysqli.reconnect', '1');
ini_set('mysqli.connect_timeout', '-1');
ini_set('default_socket_timeout', '-1');
ini_set('max_execution_time', '-1');
include('/Users/constants.php'); include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/sweetfunctions.php');
$database = 'baseball';

// t => table, d => date
$arguments = getopt("t:d:");

if (!isset($arguments['t'])) {
    exit("ERROR: Must provide a table name, e.g. -t sim_nomagic_2014. \n");
}

// Pull Data //
date_default_timezone_set('America/Los_Angeles');
$ds = date('Y-m-d');

$table = $arguments['t'];

// date override
if (isset($arguments['d'])) {
    $ds = $arguments['d'];
}

$sql = "SELECT * ".
       "FROM $table ".
       "WHERE ds = '$ds'";
$rows = exe_sql($database, $sql);
checkSQLError($rows, 1, 1);
$keys = array_keys($rows);
if (!is_numeric($keys[0])) {
    export_sql_to_csv('input.csv', array($rows));
} else {
    export_sql_to_csv('input.csv', $rows);
}
?>
