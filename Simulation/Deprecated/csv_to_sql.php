<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('mysqli.reconnect', '1');
ini_set('mysqli.connect_timeout', '-1');
ini_set('default_socket_timeout', '-1');
ini_set('max_execution_time', '-1');
include('/Users/constants.php'); 
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');
$database = 'baseball';

// t => table, d => date
$arguments = getopt("t:d:");

if (!isset($arguments['t'])) {
    exit("ERROR: Must provide a table name, e.g. -t sim_output_nomagic_2014. \n");
}

$table = $arguments['t'];

// Pull Data //
$data = csv_to_array('output.csv');
date_default_timezone_set('America/Los_Angeles');
$ds = date('Y-m-d');

// date override
if (isset($arguments['d'])) {
    $ds = $arguments['d'];
}

foreach ($data as $i => $row) {
    unset($data[$i]['ds']);
}
$header = (array_keys($data[0]));
//print_r($data);
array_unshift($data, $header);
export_and_save($database, $table, $data, $ds);
?>
