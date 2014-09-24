<?php
include_once 'includes/functions.php';
include_once 'includes/db_connect.php';

phpinfo();

$db = 'baseball';

$test = exe_sql($db, 'select home_i from sim_output');
s_log('hi jessica');
