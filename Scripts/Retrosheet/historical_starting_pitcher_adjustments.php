<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'HistoricalStartingPitcherAdjustments.php';

$startSeason = 1999;
$endSeason = 2014;

for ($season = $startSeason; $season < $endSeason; $season++) {
    echo "STARTING SEASON $season \n";
    list($season_start, $season_end) =
        RetrosheetParseUtils::getSeasonStartEnd($season);
    $script = new HistoricalStartingPitcherAdjustments();
    $script->setStartDate($season_start);
    $script->setEndDate($season_end);
    $script->setStatsYear(StatsYears::CAREER);
    $script->setTest();
    $script->run();
}

?>
