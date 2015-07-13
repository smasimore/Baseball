<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

/*
* The purpose of this file is to allow us to call one file and have
* access to all classes in the Include folder. Make sure to add new
* class names to thie file when you add them.
 */

ini_set('memory_limit', '-1');
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');

include_once __DIR__ .'/../Utils/DateTimeUtils.php';
include_once __DIR__ .'/../Utils/ExceptionUtils.php';
include_once __DIR__ .'/../Utils/ArrayUtils.php';
include_once __DIR__ .'/../Utils/GlobalUtils.php';
include_once __DIR__ .'/../Utils/sweetfunctions.php';
include_once __DIR__ .'/../Utils/RetrosheetParseUtils.php';

include_once __DIR__ .'/../Constants/Tables.php';
include_once __DIR__ .'/../Constants/Teams.php';
include_once __DIR__ .'/../Constants/RetrosheetConstants.php';

?>
