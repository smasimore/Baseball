<?php

include_once('/Users/constants.php');
//include_once(HOME_PATH.'Scripts/Scripts/Daily/AggregateBattingStats.php');
include_once(HOME_PATH.'Scripts/Scripts/Daily/SimInput.php');

/*
$stats = new AggregateBattingStats;
//$stats->setBackFillRetrosheet();
//$stats->setTest();
$stats->setStartDate('2015-04-16');
$stats->setEndDate('2015-04-18');
$stats->aggregateStats();
$stats->writeToCareerBatting();
*/

///*
$simInput = new SimInput;
//$simInput->setTest();
$simInput->getLineups();
//$simInput->write();
 //*/

?>
