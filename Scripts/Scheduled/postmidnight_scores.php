<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC
ini_set('memory_limit', '-1');
ini_set('mysqli.reconnect', '1');
ini_set('mysqli.connect_timeout', '-1');
ini_set('default_socket_timeout', '-1');
ini_set('max_execution_time', '-1');
include('/Users/constants.php'); 
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');

$date = ds_modify($date, '-1 Day');

const SCRIPTS_2014 = HOME_PATH.'Scripts/Scripts/2014/';
const SCRIPTS = HOME_PATH.'Scripts/Scripts/';
const SIM = HOME_PATH.'Scripts/Simulation/';

$scores = '/usr/bin/php ' . SCRIPTS . 'pullScores.php'." $date";
shell_exec($scores);

$betting = '/usr/bin/php ' . SIM . 'betting_decision.php'." $date";
shell_exec($betting);

?>
