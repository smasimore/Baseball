<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'AggregateBattingStats.php';

$stats = new AggregateBattingStats;
$stats->aggregateStats();
$stats->write();

?>
