<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');
include(HOME_PATH.'Scripts/Include/RetrosheetParseUtils.php');

const MIN_PLATE_APPEARANCE = 18;
const NUM_DECIMALS = 3;
const STARTER_THRESH = .5;
const TOTAL = 'Total';
const GAMES_TABLE = 'games';
const ROSTERS_TABLE = 'rosters';
const ERA_TABLE = 'retrosheet_historical_eras';
const ERA_CAREER_TABLE = 'retrosheet_historical_eras_career';
const DAILY_ROSTERS_TABLE = 'retrosheet_historical_pitching_rosters';
const SEASON_RELIEVER_TABLE = 'historical_season_relievers';
const CAREER_RELIEVER_TABLE = 'historical_career_relievers';
const SEASON_PITCHING_TABLE = 'retrosheet_historical_pitching';
const CAREER_PITCHING_TABLE = 'retrosheet_historical_pitching_career';

function getPitchingRosters($season, $ds) {
    $sql = "SELECT a.player_id,
                COALESCE(b.team_id, a.team_id) as team_id
                FROM
                    (SELECT player_id,
                        team_id
                    FROM " . ROSTERS_TABLE .
                    " WHERE year_id = $season) a
                LEFT OUTER JOIN " . DAILY_ROSTERS_TABLE . " b
                ON a.player_id = b.player_id
                AND season = $season
                AND b.ds = '$ds'";
    $data = exe_sql(DATABASE, $sql);
    $data = index_by($data, 'player_id');
    return $data;
}

function getStartingPitchers($season) {
    $sql = "SELECT AWAY_START_PIT_ID,
        HOME_START_PIT_ID,
        GAME_ID
        FROM " . GAMES_TABLE .
        " WHERE substr(GAME_ID, 4, 4)  = '$season'";
    $data = exe_sql(DATABASE, $sql);
    $pitchers = array();
    foreach ($data as $game) {
        $month = substr($game['GAME_ID'], 7, 2);
        $day = substr($game['GAME_ID'], 9, 2);
        $ds = "$season-$month-$day";
        $pitchers[$ds][] = "'" . $game['AWAY_START_PIT_ID'] . "'";
        $pitchers[$ds][] = "'" . $game['HOME_START_PIT_ID'] . "'";
    }
    return $pitchers;
}

function aggregateTeamData($team_data) {
    $team_stats = array();
    $aggregate_stats = array();
    $initial_stats = array(
        'singles' => 0,
        'doubles' => 0,
        'triples' => 0,
        'home_runs' => 0,
        'walks' => 0,
        'strikeouts' => 0,
        'ground_outs' => 0,
        'fly_outs' => 0,
        'plate_appearances' => 0
    );
    foreach ($team_data as $team_name => $team) {
        foreach ($team as $split_name => $split) {
            foreach ($split as $player) {
                if (!isset($team_stats[$team_name][$split_name])) {
                    $team_stats[$team_name][$split_name] = array(
                        'player_id' => $team_name,
                        'split' => $split_name,
                        'ds' => $player['ds']
                    );
                    $team_stats[$team_name][$split_name] =
                        array_merge(
                            $team_stats[$team_name][$split_name],
                            $initial_stats
                        );
                }
                $exclude_stats = array('ds','season','player_id','split');
                foreach ($player as $stat_name => $stat) {
                    if (in_array($stat_name, $exclude_stats)) {
                        continue;
                    }
                    $team_stats[$team_name][$split_name][$stat_name] +=
                        $stat;
                }
            }
            $aggregate_stats[] = $team_stats[$team_name][$split_name];
        }
    }
    return $aggregate_stats;
}

function getPitchingData(
    $season,
    $ds,
    $rosters,
    $starters,
    $pitching_data,
    $pitching_pct_data
) {
    $starters = implode(',', $starters[$ds]);
    $query =
        "SELECT a.*
        FROM (SELECT
                a.player_id,
                a.singles,
                a.doubles,
                a.triples,
                a.home_runs,
                a.walks,
                a.strikeouts,
                a.ground_outs,
                a.fly_outs,
                a.plate_appearances,
                a.split,
                a.season,
                a.ds
            FROM $pitching_data a
            WHERE a.season = $season
            AND a.ds = '$ds'
            AND NOT a.player_id in($starters)) a
        LEFT OUTER JOIN $pitching_pct_data b
        ON a.player_id = b.player_id
        AND b.season = $season
        AND b.ds = '$ds'
        WHERE (b.pct_start is null
        OR b.pct_start < " . STARTER_THRESH . ')';
    $season_data = exe_sql(DATABASE, $query);
    $team_data = array();
    foreach ($season_data as $player) {
        $team = $rosters[$player['player_id']]['team_id'];
        $team_data[$team][$player['split']][] = $player;
    }
    $aggregate_data = aggregateTeamData($team_data);
    return $aggregate_data;
}

function updatePitchingArray(
    $pitching_instance,
    $player_stats,
    $average_stats
) {
    global $pctStats;
    $player_id = $pitching_instance['player_id'];
    $ds = $pitching_instance['ds'];
    $split = $pitching_instance['split'];
    $plate_appearances = $pitching_instance['plate_appearances'];

    if ($plate_appearances < MIN_PLATE_APPEARANCE) {
        if (!isset($average_stats[$ds][$split]['plate_appearances'])) {
            $average_stats[$ds][$split]['plate_appearances'] =
                $plate_appearances;
        } else {
            $average_stats[$ds][$split]['plate_appearances'] +=
                $plate_appearances;
        }
        foreach ($pitching_instance as $stat_name => $stat) {
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

    foreach ($pitching_instance as $stat_name => $stat) {
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
    $player_season['joe_average'] = $average_season;
    foreach ($player_season as $player_id => $dates) {
        foreach ($dates as $date => $split_data) {
            // Note: We are cycling through ALL splits (per the global var).
            foreach ($splits as $split) {
                // OPTION 1: Use Batter's Total Split.
                // OPTION 2: Use Batter's Career Split.
                // OPTION 3: User Batter' Career Total Split.
                // OPTION 4: Use Average Stats For That Split.
                if (isset($player_season[$player_id][$date][$split])) {
                    continue;
                } else {
                    $player_season[$player_id][$date][$split] =
                        $player_season[$player_id][$date][TOTAL];
                }
                if (!$player_season[$player_id][$date][$split] &&
                    $player_career[$player_id][$date]) {
                    $player_season[$player_id][$date][$split] =
                        $player_career[$player_id][$date][$split] ?
                        $player_career[$player_id][$date][$split] :
                        $player_career[$player_id][$date][TOTAL];
                }
                if (!$player_season[$player_id][$date][$split]) {
                    $player_season[$player_id][$date][$split] =
                        $average_season[$date][$split]['plate_appearances'] >=
                            MIN_PLATE_APPEARANCE
                        ? $average_season[$date][$split]
                        : $average_season[$date][TOTAL];
                }
                $player_season[$player_id][$date][$split]
                    ['plate_appearances'] = 0;
            }
        }
    }
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
            $player_insert[$player][$date] = array(
                'team_id' => $player,
                'ds' => $date,
                'season' => $season
            );
            $final_splits = array();
            foreach ($splits as $split_name => $split) {
                $split['team_id'] = $player;
                $final_splits[$split_name] = $split;
            }
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
                    $pct_stat = $stat ? $stat / $plate_appearances : 0;
                    $average_pcts[$ds][$split_name][$stat_pct_name] =
                        number_format($pct_stat, NUM_DECIMALS);
                }
            }
        }
    }
    return $average_pcts;
}

$test = false;

$season_vars = array(
    'start_script' => 1950,
    'end_script' => 2014,
    'season_start' => null,
    'season_end' => null,
);

$colheads = array(
    'team_id',
    'stats',
    'season',
    'ds'
);

$season_insert_table = SEASON_RELIEVER_TABLE;
$career_insert_table = CAREER_RELIEVER_TABLE;
$season_table = SEASON_PITCHING_TABLE;
$career_table = CAREER_PITCHING_TABLE;

for ($season = $season_vars['start_script'];
    $season < $season_vars['end_script'];
    $season++) {

    $starting_pitchers = getStartingPitchers($season);
    $season_vars = RetrosheetParse::updateSeasonVars(
        $season,
        $season_vars,
        $season_table
    );

    for ($ds = $season_vars['season_start'];
        $ds <= $season_vars['season_end'];
        $ds = ds_modify($ds, '+1 day')) {
        echo $ds."\n";
        $player_season = null;
        $player_career = null;
        $average_season = null;
        $average_career = null;

        $team_rosters = getPitchingRosters($season, $ds);
        $season_data = getPitchingData(
            $season,
            $ds,
            $team_rosters,
            $starting_pitchers,
            $season_table,
            ERA_TABLE
        );
        $career_data = getPitchingData(
            $season,
            $ds,
            $team_rosters,
            $starting_pitchers,
            $career_table,
            ERA_CAREER_TABLE
        );

        if (!$career_data) {
            echo "No Data For $ds \n";
            continue;
        }

        foreach ($career_data as $index => $career_split) {
            list($player_career, $average_career) = updatePitchingArray(
                $career_split,
                $player_career,
                $average_career
            );
            $season_split =
                isset($season_data[$index]) ? $season_data[$index] : null;
            if ($season_split) {
                list($player_season, $average_season) = updatePitchingArray(
                    $season_split,
                    $player_season,
                    $average_season
                );
            }
        }
        $average_season = convertSeasonToPct($average_season);
        $average_career = convertSeasonToPct($average_career);
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
        if ($test && isset($player_season)) {
            print_r($player_season);
            exit();
        }
    }
}

?>
