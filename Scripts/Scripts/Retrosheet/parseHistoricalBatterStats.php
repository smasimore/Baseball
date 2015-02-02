<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/Include.php');

$test = true;
$joeAverage = null;
$runTypes = array(
    RetrosheetConstants::BATTING,
    RetrosheetConstants::PITCHING
);

function getBattingData($season, $ds, $table) {
    $query =
        "SELECT
            player_name,
            player_id,
            singles,
            doubles,
            triples,
            home_runs,
            walks,
            strikeouts,
            ground_outs,
            fly_outs,
            plate_appearances,
            split,
            season,
            ds
        FROM $table
        WHERE season = $season
        AND ds = '$ds'";
    $season_data = exe_sql(DATABASE, $query);
    return index_by($season_data, 'player_id', 'split');
}

function updateBattingArray($batting_instance, $player_stats) {
    global $pctStats;
    $player_id = $batting_instance['player_id'];
    $ds = $batting_instance['ds'];
    $split = $batting_instance['split'];
    $plate_appearances = $batting_instance['plate_appearances'];

    if ($plate_appearances < RetrosheetDefaults::MIN_PLATE_APPEARANCE) {
        return $player_stats;
    }
    $player_stats[$player_id][$ds][$split]['plate_appearances'] =
        $plate_appearances;
    foreach ($batting_instance as $stat_name => $stat) {
        if (in_array($stat_name, $pctStats)) {
            $stat_pct_name = array_search($stat_name, $pctStats);
            $stat_pct = number_format(
                $stat / $plate_appearances,
                RetrosheetConstants::NUM_DECIMALS
            );
            $player_stats[$player_id][$ds][$split][$stat_pct_name] = $stat_pct;
        }
    }
    return $player_stats;
}

$season_vars = array(
    'start_script' => 1950,
    'end_script' => 2014,
    'season_start' => null,
    'season_end' => null,
    'previous_season_end' => null
);
$colheads = array(
    'player_id',
    'stats',
    'season',
    'ds'
);

foreach ($runTypes as $runType) {

    $season_insert_table = "historical_season_$runType";
    $previous_insert_table = "historical_previous_$runType";
    $career_insert_table = "historical_career_$runType";
    $season_table = "retrosheet_historical_$runType";
    $career_table = "retrosheet_historical_$runType"."_career";

    for ($season = $season_vars['start_script'];
        $season < $season_vars['end_script'];
        $season++) {

        $season_vars = RetrosheetParseUtils::updateSeasonVars(
            $season,
            $season_vars,
            $career_table
        );
        // Season = 1950 is left in just for the above funtion to register
        // previous_season_end.
        if ($season == 1950) {
            continue;
        }
        $joeAverage = RetrosheetParseUtils::getJoeAverageStats($season);
        $previous_data = null;
        $previous = $season - 1;
        $previous_data = getBattingData(
            $previous,
            $season_vars['previous_season_end'],
            $season_table
        );
        for ($ds = $season_vars['season_start'];
            $ds <= $season_vars['season_end'];
            $ds = ds_modify($ds, '+1 day')) {
            echo $ds."\n";
            $player_season = null;
            $player_previous = null;
            $player_career = null;
            $season_data = getBattingData($season, $ds, $season_table);
            $career_data = getBattingData($season, $ds, $career_table);
            if (!$career_data) {
                echo "No Data For $ds \n";
                continue;
            }
            foreach ($career_data as $index => $career_split) {
                $player_career = updateBattingArray(
                    $career_split,
                    $player_career
                );
                $previous_split = idx($previous_data, $index);
                if ($previous_split) {
                    $player_previous =
                        updateBattingArray($previous_split, $player_previous);
                }
                $season_split = idx($season_data, $index);
                if ($season_split) {
                    $player_season =
                        updateBattingArray($season_split, $player_season);
                }
            }

            $player_career = RetrosheetParseUtils::updateMissingSplits(
                $player_career,
                $joeAverage,
                $runType,
                /* player_previous */ null,
                /* player_career */ null
            );
            $player_previous = RetrosheetParseUtils::updateMissingSplits(
                $player_previous,
                $joeAverage,
                $runType,
                /* player_previous */ null,
                $player_career
            );
            $player_season = RetrosheetParseUtils::updateMissingSplits(
                $player_season,
                $joeAverage,
                $runType,
                $player_previous,
                $player_career
            );

            $player_season = RetrosheetParseUtils::prepareStatsMultiInsert(
                $player_season,
                $season,
                $ds
            );
            $player_previous = RetrosheetParseUtils::prepareStatsMultiInsert(
                $player_previous,
                $season,
                $ds
            );
            $player_career = RetrosheetParseUtils::prepareStatsMultiInsert(
                $player_career,
                $season,
                $ds
            );

            if (!$test && isset($player_season)) {
                multi_insert(
                    DATABASE,
                    $season_insert_table,
                    $player_season,
                    $colheads
                );
            }
            if (!$test && isset($player_previous)) {
                multi_insert(
                    DATABASE,
                    $previous_insert_table,
                    $player_previous,
                    $colheads
                );
            }
            if (!$test && isset($player_career)) {
                multi_insert(
                    DATABASE,
                    $career_insert_table,
                    $player_career,
                    $colheads
                );
            }
            if ($test && isset($player_season)) {
                print_r($player_season); exit();
            }
        }
    }
}

?>
