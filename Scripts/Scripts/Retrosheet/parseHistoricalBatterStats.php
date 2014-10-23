<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');

const MIN_PLATE_APPEARANCE = 0;

function getRetrosheetQuery($season, $table) {
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
        WHERE season = $season";
    return $query;
}

function getBattingData($season, $table) {
    $season_data = exe_sql(
        DATABASE,
        getRetrosheetQuery($season, $table)
    );
    $season_data = index_by($season_data, 'player_id', 'ds', 'split');
    return $season_data;
}

function updateBattingArray($batting_instance, $player_stats, $average_stats) {
    global $pctStats;
    $player_id = $batting_instance['player_id'];
    $ds = $batting_instance['ds'];
    $split = $batting_instance['split'];
    $plate_appearances = $batting_instance['plate_appearances'];
    if ($plate_appearances < MIN_PLATE_APPEARANCE) {
        // TODO(cert): DO SOMETHING HERE
        return array($player_stats, $average_stats);
    }
    $player_stats[$player_id][$ds][$split]['plate_appearances'] =
        $plate_appearances;
    $average_stats[$ds][$split]['plate_appearances'] += $plate_appearances;

    foreach ($batting_instance as $stat_name => $stat) {
        if (in_array($stat_name, $pctStats)) {
            $stat_pct_name = array_search($stat_name, $pctStats);
            $stat_pct = $stat / $plate_appearances;
            $player_stats[$player_id][$ds][$split][$stat_pct_name] = $stat_pct;
            $average_stats[$ds][$split][$stat_name] += $stat;
        }
    }
    return array($player_stats, $average_stats);
}

function convertSeasonToPct($average_season) {
    global $pctStats;
    foreach ($average_season as $ds => $splits) {
        foreach ($splits as $split_name => $split) {
            $plate_appearances =
                $average_season[$ds][$split_name]['plate_appearances'];
            foreach ($split as $stat_name => $stat) {
                if (in_array($stat_name, $pctStats)) {
                    $stat_pct_name = array_search($stat_name, $pctStats);
                    if ($stat > 0) {
                        $pct_stat = $stat / $plate_appearances;
                        $average_pcts[$ds][$split_name][$stat_pct_name] =
                            $pct_stat;
                    } else {
                        $average_pcts[$ds][$split_name][$stat_pct_name] = 0;
                    }
                }
            }
        }
    }
    return $average_pcts;
}

echo 'testing: change back to 1950'."\n";
$test = 1;
$season_table = "retrosheet_historical_batting";
$career_table = "retrosheet_historical_batting_career";
for ($season = 1951; $season < 2014; $season++) {
    $player_season = null;
    $player_career = null;
    $average_season = null;
    $average_career = null;
    $season_data = getBattingData($season, $season_table);
    $career_data = getBattingData($season, $career_table);
    foreach ($season_data as $index => $season_split) {
        list($player_season, $average_season) = updateBattingArray(
            $season_split,
            $player_season,
            $average_season
        );
        $career_split = $career_data[$index];
        list($player_career, $average_career) = updateBattingArray(
            $career_split,
            $player_career,
            $average_career
        );
        $test++;
        if ($test == 3) {
            print_r($player_season);
            print_r($player_career);
            break;
        }
    }
    $average_season = convertSeasonToPct($average_season);
    $average_career = convertSeasonToPct($average_career);
    print_r($average_season);
    print_r($average_career);
    exit();
/*
    multi_insert(
        DATABASE,
        $daily_table,
        $player_season_daily_insert,
        $colheads
    );
*/
}

?>
