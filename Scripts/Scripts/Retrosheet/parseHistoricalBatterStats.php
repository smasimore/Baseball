<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');
include(HOME_PATH.'Scripts/Include/RetrosheetConstants.php');
include(HOME_PATH.'Scripts/Include/RetrosheetParseUtils.php');

const MIN_PLATE_APPEARANCE = 18;
const NUM_DECIMALS = 3;
const TOTAL = 'Total';
const BATTING = 'batting';
const PITCHING = 'pitching';

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
                    $average_stats[$ds][$split][$stat_name] = $stat;
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
    $player_prev_season = null,
    $player_career = null,
    $average_prev_season = null,
    $average_career = null
) {
    global $splits;
    $defaults_only = isset($player_season) ? 0 : 1;
    if (isset($average_prev_season)) {
        $player_season['joe_average_previous'] = $average_prev_season;
    }
    if (isset($average_career)) {
        $player_season['joe_average_career'] = $average_career;
    }
    if ($defaults_only) {
        return $player_season;
    }
    $player_season['joe_average'] = $average_season;
    foreach ($player_season as $player_id => $dates) {
        foreach ($dates as $date => $split_data) {
            foreach ($splits as $split) {
                $default_step = 0;
                $is_filled = isset($player_season[$player_id][$date][$split]);
                while (!$is_filled) {
                    $player_season[$player_id][$date][$split] = addDefaultData(
                        $default_step,
                        $player_id,
                        $date,
                        $split,
                        $player_season,
                        $average_season,
                        $player_prev_season,
                        $player_career,
                        $average_prev_season,
                        $average_career
                    );
                    $is_filled = isset($player_season[$player_id][$date][$split]);
                    $default_step += 1;
                    $player_season[$player_id][$date][$split]['plate_appearances'] = 0;
                }
            }
        }
    }
    return $player_season;
}

function addDefaultData(
    $default_step,
    $player_id,
    $date,
    $split,
    $player_season,
    $average_season,
    $player_prev_season,
    $player_career,
    $average_prev_season,
    $average_career
) {

    $default_data = null;
    switch ($default_step) {
        case RetrosheetDefaults::SEASON_TOTAL:
            $default_data = elvis($player_season[$player_id][$date][TOTAL]);
            break;
        case RetrosheetDefaults::PREV_YEAR_ACTUAL:
            $default_data =
                elvis($player_prev_season[$player_id][$date][$split]);
            break;
        case RetrosheetDefaults::PREV_YEAR_TOTAL:
            $default_data =
                elvis($player_prev_season[$player_id][$date][TOTAL]);
            break;
        case RetrosheetDefaults::CAREER_ACTUAL:
            $default_data = elvis($player_career[$player_id][$date][$split]);
            break;
        case RetrosheetDefaults::CAREER_TOTAL:
            $default_data = elvis($player_career[$player_id][$date][TOTAL]);
            break;
        case RetrosheetDefaults::SEASON_JOE_AVERAGE_ACTUAL:
            $default_data = elvis($average_season[$date][$split]);
            break;
        case RetrosheetDefaults::SEASON_JOE_AVERAGE_TOTAL:
            $default_data = elvis($average_season[$date][TOTAL]);
            break;
        case RetrosheetDefaults::PREV_SEASON_JOE_AVERAGE_ACTUAL:
            $default_data = elvis($average_prev_season[$date][$split]);
            break;
        case RetrosheetDefaults::PREV_SEASON_JOE_AVERAGE_TOTAL:
            $default_data = elvis($average_prev_season[$date][TOTAL]);
            break;
        case RetrosheetDefaults::CAREER_JOE_AVERAGE_ACTUAL:
            $default_data = elvis($average_career[$date][$split]);
            break;
        case RetrosheetDefaults::CAREER_JOE_AVERAGE_TOTAL:
            $default_data = elvis($average_career[$date][TOTAL]);
            break;
        case 11:
            exit("$player_id GOT TO CASE 11");
    }
    $pas = idx($default_data, 'plate_appearances', 0);
    return $pas >= MIN_PLATE_APPEARANCE ? $default_data : null;
}

function prepareMultiInsert($player_season, $season, $ds) {
    if (!isset($player_season)) {
        return null;
    }
    $final_insert = array();
    foreach ($player_season as $player => $dates) {
        $player_insert = array();
        foreach ($dates as $date => $splits) {
            $player_insert[$player][$ds] = array(
                'player_id' => $player,
                'ds' => $ds,
                'season' => $season
            );
            $final_splits = array();
            foreach ($splits as $split_name => $split) {
                $split['player_id'] = $player;
                $final_splits[$split_name] = $split;
            }
            $player_insert[$player][$ds]['stats'] =
                json_encode($final_splits);
            $final_insert[] = $player_insert[$player][$ds];
        }
    }
    return $final_insert;
}

function convertSeasonToPct($average_season) {
    global $pctStats;
    if (!$average_season) {
        return null;
    }
    foreach ($average_season as $ds => $splits) {
        foreach ($splits as $split_name => $split) {
            $plate_appearances =
                $average_season[$ds][$split_name]['plate_appearances'];
            foreach ($split as $stat_name => $stat) {
                if (in_array($stat_name, $pctStats)) {
                    $stat_pct_name = array_search($stat_name, $pctStats);
                        $pct_stat = $stat > 0 ? $stat / $plate_appearances : 0;
                        $average_pcts[$ds][$split_name][$stat_pct_name] =
                            number_format($pct_stat, NUM_DECIMALS);
                }
                $average_pcts[$ds][$split_name]['plate_appearances'] =
                    $plate_appearances;
            }
        }
    }
    return $average_pcts;
}

$test = false;
$type =
    BATTING;
    //PITCHING;

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
$season_insert_table = "historical_season_$type";
$prev_season_insert_table = "historical_previous_$type";
$career_insert_table = "historical_career_$type";
$season_table = "retrosheet_historical_$type";
$career_table = "retrosheet_historical_$type"."_career";

for ($season = $season_vars['start_script'];
    $season < $season_vars['end_script'];
    $season++) {

    $season_vars = RetrosheetParseUtils::updateSeasonVars(
        $season,
        $season_vars,
        $career_table
    );
    $prev_season_data = null;
    if ($season > 1950) {
        $prev_season = $season - 1;
        $prev_season_data = getBattingData(
            $prev_season,
            $season_vars['previous_season_end'],
            $season_table
        );
    }
    for ($ds = $season_vars['season_start'];
        $ds <= $season_vars['season_end'];
        $ds = ds_modify($ds, '+1 day')) {
        echo $ds."\n";
        $player_season = null;
        $player_prev_season = null;
        $player_career = null;
        $average_season = null;
        $average_prev_season = null;
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
            $prev_season_split = idx($prev_season_data, $index);
            if ($prev_season_split) {
                list($player_prev_season, $average_prev_season) =
                    updateBattingArray(
                        $prev_season_split,
                        $player_prev_season,
                        $average_prev_season
                    );
            }
            $season_split = idx($season_data, $index);
            if ($season_split) {
                list($player_season, $average_season) =
                    updateBattingArray(
                        $season_split,
                        $player_season,
                        $average_season
                    );
            }
        }
        $average_season = convertSeasonToPct($average_season);
        $average_prev_season = convertSeasonToPct($average_prev_season);
        $average_career = convertSeasonToPct($average_career);

        $player_career = updateMissingSplits(
            $player_career,
            $average_career,
            /* prev_season */ null,
            /* player_career */ null,
            /* avg_prev_season */ null,
            $average_career
        );
        $player_prev_season = updateMissingSplits(
            $player_prev_season,
            $average_prev_season,
            /* prev_season */ null,
            $player_career,
            /* avg_prev_season */ null,
            $average_career
        );
        $player_season = updateMissingSplits(
            $player_season,
            $average_season,
            $player_prev_season,
            $player_career,
            $average_prev_season,
            $average_career
        );

        $player_season = RetrosheetParseUtils::prepareStatsMultiInsert(
            $player_season,
            $season,
            $ds
        );
        $player_prev_season = RetrosheetParseUtils::prepareStatsMultiInsert(
            $player_prev_season,
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
        if (!$test && isset($player_prev_season)) {
            multi_insert(
                DATABASE,
                $prev_season_insert_table,
                $player_prev_season,
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

?>
