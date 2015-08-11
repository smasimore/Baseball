<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'RetrosheetHistoricalBatting.php';

$startSeason = 1950;
$endSeason = 2014;

for ($season = $startSeason; $season < $endSeason; $season++) {
    echo "STARTING SEASON $season \n";
    list($season_start, $season_end) =
        RetrosheetParseUtils::getSeasonStartEnd($season);
    $script = new RetrosheetHistoricalBatting();
    $script->setStartDate($season_start);
    $script->setEndDate($season_end);
    $script->run();
}

?>
