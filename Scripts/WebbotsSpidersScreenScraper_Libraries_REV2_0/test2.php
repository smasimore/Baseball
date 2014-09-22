<?php
include_once 'sweetfunctions.php';

$db = 'baseball';

$test = exe_sql($db, 'select home_i from sim_output');
echo $test;
