<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC
ini_set('memory_limit', '-1');
ini_set('mysqli.reconnect', '1');
ini_set('mysqli.connect_timeout', '-1');
ini_set('default_socket_timeout', '-1');
ini_set('max_execution_time', '-1');
include_once('/Users/constants.php');
include_once(HOME_PATH.'Scripts/Include/sweetfunctions.php');

shell_exec("git pull");

?>
