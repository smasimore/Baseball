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

function updateSeasonVars($season, $season_vars) {
    if ($season > $season_vars['start_script']) {
        $season_vars['previous_end'] =
            ds_modify($season_vars['season_end'], '+1 day');
        $season_vars['previous'] = $season - 1;
    }
    $season_sql =
        "SELECT min(ds) as start,
            max(ds) as end,
            season
        FROM retrosheet_historical_batting
        WHERE season = '$season'
        GROUP BY season";
    $season_dates = reset(exe_sql(DATABASE, $season_sql));
    $season_vars['season_start'] = $season_dates['start'];
    $season_vars['season_end'] = $season_dates['end'];
    return $season_vars;
}

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
    $season_data = index_by($season_data, 'player_id', 'split');
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
        if (!isset($average_stats[$ds][$split]['plate_appearances'])) {
            $average_stats[$ds][$split]['plate_appearances'] =
                $plate_appearances;
        } else {
            $average_stats[$ds][$split]['plate_appearances'] +=
                $plate_appearances;
        }
        foreach ($batting_instance as $stat_name => $stat) {
            if (in_array($stat_name, $pctStats)) {
                if (!isset($average_stats[$ds][$split][$stat_name])) {
                    $average_stats[$ds][$split][$stat_name] = $stat
                } else {
                    $average_stats[$ds][$split][$stat_name] += $stat;
                }
            }
        }
        return array($player_stats, $average_stats);
    }
    $player_stats[$player_id][$ds][$split]['plate_appearances'] =
        $plate_appearances;
    if (!isset($average_stats[$ds][$split]['plate_appearances'])) {
        $average_stats[$ds][$split]['plate_appearances'] = $plate_appearances;
    } else {
        $average_stats[$ds][$split]['plate_appearances'] += $plate_appearances;
    }

    foreach ($batting_instance as $stat_name => $stat) {
        if (in_array($stat_name, $pctStats)) {
            $stat_pct_name = array_search($stat_name, $pctStats);
            $stat_pct = number_format($stat / $plate_appearances, NUM_DECIMALS);
            $player_stats[$player_id][$ds][$split][$stat_pct_name] = $stat_pct;
            if (!isset($average_stats[$ds][$split][$stat_name])) {
                $average_stats[$ds][$split][$stat_name] = $stat;
            } else {
                $average_stats[$ds][$split][$stat_name] += $stat;
            }
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
    if (!isset($player_season)) {
        return null;
    }
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
    $player_season['joe_average'] = $average_season;
    return $player_season;
}

function prepareMultiInsert($player_season, $season) {
    if (!isset($player_season)) {
        return null;
    }
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
                if (!$defaults) {
                    $defaults = $pas ? 0 : 1;
                } else {
                    $defaults += $pas ? 0 : 1;
                }
                $max_appearances =
                    $pas > $max_appearances ? $pas : $max_appearances;
                $final_splits[$split_name] = $split;
            }
            $player_insert[$player][$date]['defaults'] = $defaults;
            // HACK BEFORE ADDING ERA BANDS
            if ($player == 'joe_average') {
                $player_insert[$player][$date]['defaults'] = 16;
            }
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
$type = 'batting';

$season_vars = array(
    'start_script' => 1950,
    'end_script' => 2014,
    'season_start' => null,
    'season_end' => null,
);

$colheads = array(
    'player_id',
    'defaults',
    'plate_appearances',
    'stats',
    'season',
    'ds'
);
$season_insert_table = "historical_season_$type";
$career_insert_table = "historical_career_$type";

$season_table = "retrosheet_historical_$type";
$career_table = "retrosheet_historical_$type"."_career";

for ($season = $season_vars['start_script'];
    $season < $season_vars['end_script'];
    $season++) {

    $season_vars = updateSeasonVars($season, $season_vars);
    for ($ds = $season_vars['season_start'];
        $ds <= $season_vars['season_end'];
        $ds = ds_modify($ds, '+1 day')) {
        echo $ds."\n";
        $player_season = null;
        $player_career = null;
        $average_season = null;
        $average_career = null;
        $season_data = getBattingData($season, $ds, $season_table);
        $career_data = getBattingData($season, $ds, $career_table);
        if (!$career_data) {
            echo "No Data For $ds \n";
            continue;
        }
        foreach ($career_data as $index => $career_split) {
            list($player_career, $average_career) = updateBattingArray(
                $career_split,
                $player_career,
                $average_career
            );
            $season_split =
                isset($season_data[$index]) ? $season_data[$index] : null;
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
        $player_career =
            updateMissingSplits(
                $player_career,
                $average_career
            );
        $player_season =
            updateMissingSplits(
                $player_season,
                $average_season,
                $player_career
            );
        $player_season = prepareMultiInsert($player_season, $season);
        $player_career = prepareMultiInsert($player_career, $season);
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
}

?>
