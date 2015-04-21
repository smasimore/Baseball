<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include('/Users/constants.php');
include(HOME_PATH.'Scripts/Scripts/Daily/AggregateBattingStats.php');

$stats = new AggregateBattingStats;
$stats->aggregateStats();
$stats->write();

?>
