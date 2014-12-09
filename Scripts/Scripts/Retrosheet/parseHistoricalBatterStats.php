<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');

const MIN_PLATE_APPEARANCE = 18;
const NUM_DECIMALS = 3;

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
    // If a player hasn't batted more than the min threshold don't fill
    // his personal stats, but add him to the averages.
    if ($plate_appearances < MIN_PLATE_APPEARANCE) {
        $average_stats[$ds][$split]['plate_appearances'] += $plate_appearances;
        foreach ($batting_instance as $stat_name => $stat) {
            if (in_array($stat_name, $pctStats)) {
                $average_stats[$ds][$split][$stat_name] += $stat;
            }
        }
        return array($player_stats, $average_stats);
    }
    $player_stats[$player_id][$ds][$split]['plate_appearances'] =
        $plate_appearances;
    $average_stats[$ds][$split]['plate_appearances'] += $plate_appearances;

    foreach ($batting_instance as $stat_name => $stat) {
        if (in_array($stat_name, $pctStats)) {
            $stat_pct_name = array_search($stat_name, $pctStats);
            $stat_pct = number_format($stat / $plate_appearances, NUM_DECIMALS);
            $player_stats[$player_id][$ds][$split][$stat_pct_name] = $stat_pct;
            $average_stats[$ds][$split][$stat_name] += $stat;
        }
    }
    return array($player_stats, $average_stats);
}

function updateMissingSplits(
    $player_season,
    $average_season,
    $player_career = null
) {
    global $splits;
    foreach ($player_season as $player_id => $dates) {
        foreach ($dates as $date => $split_data) {
            // Note: We are cycling through ALL splits (per the global var).
            foreach ($splits as $split) {
                // OPTION 1: Use Batter's Total Split.
                // OPTION 2: Use Batter's Career Split.
                // OPTION 3: User Batter' Career Total Split.
                // OPTION 4: Use Average Stats For That Split.
                if ($player_season[$player_id][$date][$split]) {
                    continue;
                } else {
                    $player_season[$player_id][$date][$split] =
                        $player_season[$player_id][$date]['Total'];
                }
                if (!$player_season[$player_id][$date][$split] &&
                    $player_career[$player_id][$date]) {
                    $player_season[$player_id][$date][$split] =
                        $player_career[$player_id][$date][$split] ?
                        $player_career[$player_id][$date][$split] :
                        $player_career[$player_id][$date]['Total'];
                }
                if (!$player_season[$player_id][$date][$split]) {
                    $player_season[$player_id][$date][$split] =
                        $average_season[$date][$split];
                }
                $player_season[$player_id][$date][$split]
                    ['plate_appearances'] = 0;
            }
        }
    }
    return $player_season;
}

function prepareMultiInsert($player_season, $season) {
    $final_insert = array();
    foreach ($player_season as $player => $dates) {
        $player_insert = array();
        foreach ($dates as $date => $splits) {
            $player_insert[$player][$date]['player_id'] = $player;
            $player_insert[$player][$date]['ds'] = $date;
            $player_insert[$player][$date]['season'] = $season;
            $final_splits = array();
            $defaults = 0;
            $max_appearances = 0;
            foreach ($splits as $split_name => $split) {
                $split['player_id'] = $player;
                $pas = $split['plate_appearances'];
                $defaults += $pas ? 0 : 1;
                $max_appearances =
                    $pas > $max_appearances ? $pas : $max_appearances;
                $final_splits[$split_name] = $split;
            }
            $player_insert[$player][$date]['defaults'] = $defaults;
            $player_insert[$player][$date]['plate_appearances'] =
                $max_appearances;
            $player_insert[$player][$date]['stats'] =
                json_encode($final_splits);
            $final_insert[] = $player_insert[$player][$date];
        }
    }
    return $final_insert;
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
                            number_format($pct_stat, NUM_DECIMALS);
                    } else {
                        $average_pcts[$ds][$split_name][$stat_pct_name] = 0;
                    }
                }
            }
        }
    }
    return $average_pcts;
}

$test = false;
$colheads = array(
    'player_id',
    'defaults',
    'plate_appearances',
    'stats',
    'season',
    'ds'
);
$season_insert_table = "historical_season_batting";
$career_insert_table = "historical_career_batting";

$season_table = "retrosheet_historical_batting";
$career_table = "retrosheet_historical_batting_career";

for ($season = 1950; $season < 2014; $season++) {
    $player_season = null;
    $player_career = null;
    $average_season = null;
    $average_career = null;
    $season_data = getBattingData($season, $season_table);
    $career_data = getBattingData($season, $career_table);
    if (!$career_data) {
        continue;
    }
    foreach ($career_data as $index => $career_split) {
        list($player_career, $average_career) = updateBattingArray(
            $career_split,
            $player_career,
            $average_career
        );
        $season_split = $season_data[$index];
        if ($season_split) {
            list($player_season, $average_season) = updateBattingArray(
                $season_split,
                $player_season,
                $average_season
            );
        }
    }
    // TODO(cert): Add bucket splits here (right now always defaulting).
    $average_season = convertSeasonToPct($average_season);
    $average_career = convertSeasonToPct($average_career);
    // Go through the splits and fill in gaps with Totals or Averages.
    $player_career = updateMissingSplits($player_career, $average_career);
    $player_season =
        updateMissingSplits($player_season, $average_season, $player_career);
    $player_season = prepareMultiInsert($player_season, $season);
    $player_career = prepareMultiInsert($player_career, $season);
    print_r($player_season);
    if (!$test && isset($player_season)) {
        multi_insert(
            DATABASE,
            $season_insert_table,
            $player_season,
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
}

?>
