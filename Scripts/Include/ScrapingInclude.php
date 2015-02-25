<?php
//Copyright 2014, Saber Tooth Ventures, LLC

/*
* The purpose of this file is to allow us to call one file and have
* access to all classes in the Include folder. Make sure to add new
* class names to thie file when you add them.
 */

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
if (!defined('HOME_PATH')) {
    include('/Users/constants.php');
}
include(HOME_PATH.'Scripts/Include/Enum.php');
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');
include(HOME_PATH.'Scripts/Include/Teams.php');

?>
