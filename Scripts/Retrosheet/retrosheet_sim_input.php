<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'RetrosheetSimInput.php';

$startSeason = 1999;
$endSeason = 2014;

for ($season = $startSeason; $season < $endSeason; $season++) {
    echo "STARTING SEASON $season \n";
    list($season_start, $season_end) =
        RetrosheetParseUtils::getSeasonStartEnd($season);
    $script = (new RetrosheetSimInput())
        ->setStartDate($season_start)
        ->setEndDate($season_end)
        ->setStatsYear(StatsYears::CAREER)
        ->setStatsType(StatsTypes::PITCHER_ADJUSTMENT)
        ->setTest()
        ->run();
}

?>
