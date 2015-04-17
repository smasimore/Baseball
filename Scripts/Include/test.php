<?php

include('/Users/constants.php');
include(HOME_PATH.'Scripts/Classes/BattingStats.php');

$test = new BattingStats;
//$test->setBackFillRetrosheet();
//$test->setTest();
$test->aggregateStats();
$test->writeToCareerBatting();


?>
