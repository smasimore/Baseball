<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/RetrosheetInclude.php');

$statsYears = array(
    RetrosheetStatsYear::SEASON,
    RetrosheetStatsYear::PREVIOUS,
    RetrosheetStatsYear::CAREER
);
$statsType =
    RetrosheetStatsType::BASIC;
    //RetrosheetStatsType::MAGIC;
$startScript = 1990;
$endScript  = 2014;
$joeAverage = null;

function updateSeasonVars($season) {
    $season_sql =
        "SELECT min(game_date) as start,
            max(game_date) as end
        FROM retrosheet_historical_lineups
        WHERE season = $season
        GROUP BY season";
    $season_dates = reset(exe_sql(DATABASE, $season_sql));
    $season_start = $season_dates['start'];
    $season_end = $season_dates['end'];
    return array($season_start, $season_end);
}

function pullSeasonLineup($season) {
    $sql = "SELECT *
        FROM retrosheet_historical_lineups
        WHERE season = $season";
    $data = exe_sql(DATABASE, $sql);
    $data = index_by_nonunique($data, 'game_date');
    return $data;
}

function pullSeasonData($season, $ds, $table) {
    $sql = "SELECT *
        FROM $table
        WHERE season = $season
        AND ds = '$ds'";
    $data = exe_sql(DATABASE, $sql);
    $data = index_by($data, 'player_id');
    return $data;
}

function getDefaultWaterfall($season, $previous, $career, $joeAverage) {
    if (isset($season)) {
        return json_decode($season, true);
    }
    $insert_data = array();
    $is_joe_average = null;
    switch (true) {
        case isset($previous):
            $data = json_decode($previous, true);
            break;
        case isset($career):
            $data = json_decode($career, true);
            break;
        default:
            $data = json_decode($joeAverage, true);
            $is_joe_average = 1;
            break;
    }
    foreach ($data as $name => $split) {
        if ($is_joe_average) {
            $split['player_name'] = 'joe_average';
        }
        $split['plate_appearances'] = 0;
        $insert_data[$name] = $split;
    }
    return $insert_data;
}

function getAverageInnings(
    $player_id,
    $stats,
    $home_away
) {
    // NOTE: I took care of ensuring earlier stats don't appear (i.e. season
    // won't be there if we are running on previous).
    $player_stats = array(
        idx($stats['season'], $player_id),
        idx($stats['previous'], $player_id),
        idx($stats['career'], $player_id)
    );
    foreach ($player_stats as $stats_year) {
        if (!$stats_year) {
            continue;
        }
        $stat = json_decode($stats_year['stats'], true);
        $avg_innings = $stat[ucfirst($home_away)]['avg_innings'] > 0
            ? $stat[ucfirst($home_away)]['avg_innings']
            : $stat['Total']['avg_innings'];
        if ($avg_innings > 0) {
            return $avg_innings;
        }
    }
    return null;
}

function fillPitchers($pitcher, $stats, $team, $home_away) {
    global $joeAverage;
    $pitcher = json_decode($pitcher, true);
    $player_id = $pitcher['id'];
    $avg_innings = getAverageInnings(
        $player_id,
        $stats['starter'],
        $home_away
    );
    return array(
        'player_id' =>
            isset($pitcher['id']) ? $pitcher['id'] : 'joe_average',
        'player_name' =>
            isset($pitcher['name']) ? $pitcher['name'] : $pitcher['id'],
        'handedness' =>
            isset($pitcher['hand']) ? $pitcher['hand'] : '?',
        'avg_innings' => $avg_innings,
        'pitcher_vs_batter' =>
            getDefaultWaterfall(
                idx($stats['starter']['season'], $player_id)
                    ? $stats['starter']['season'][$player_id]['stats'] : null,
                idx($stats['starter']['previous'], $player_id)
                    ? $stats['starter']['previous'][$player_id]['stats'] : null,
                idx($stats['starter']['career'], $player_id)
                    ? $stats['starter']['career'][$player_id]['stats'] : null,
                $joeAverage['starter_stats']
            ),
        'reliever_vs_batter' =>
            getDefaultWaterfall(
                idx($stats['reliever']['season'], $team)
                    ? $stats['reliever']['season'][$team]['stats'] : null,
                idx($stats['reliever']['previous'], $team)
                    ? $stats['reliever']['previous'][$team]['stats'] : null,
                idx($stats['reliever']['career'], $team)
                    ? $stats['reliever']['career'][$team]['stats'] : null,
                $joeAverage['reliever_stats']
            )
    );
}

function fillLineups($lineup, $stats) {
    global $joeAverage;
    $lineup = json_decode($lineup, true);
    $filled_lineups = array();
    foreach ($lineup as $lpos => $player) {
        $pos = trim($lpos, "L");
        $player_id = $player['player_id'];
        $batter_v_pitcher =
            getDefaultWaterfall(
                idx($stats['season'], $player_id)
                    ? $stats['season'][$player_id]['stats'] : null,
                idx($stats['previous'], $player_id)
                    ? $stats['previous'][$player_id]['stats'] : null,
                idx($stats['career'], $player_id)
                    ? $stats['career'][$player_id]['stats'] : null,
                $joeAverage['batter_stats']
            );
        $batter_v_pitcher['hand'] = idx($player, 'hand');
        $filled_lineups[$pos] = $batter_v_pitcher;
    }
    return $filled_lineups;
}

$test = true;

$colheads = array(
    'rand_bucket' => '!',
    'gameid' => '!',
    'home' => '!',
    'away' => '!',
    'pitching_h' => '!',
    'pitching_a' => '!',
    'batting_h' => '!',
    'batting_a' => '!',
    'error_rate_h' => '?',
    'error_rate_a' => '?',
    'stats_type' => '!',
    'stats_year' => '!',
    'season' => '!',
    'game_date' => '!'
);

for ($season = $startScript;
    $season < $endScript;
    $season++) {

    $joeAverage = RetrosheetParseUtils::getJoeAverageStats($season);
    foreach ($statsYears as $stats_year) {
        $tables = array(
            'batter' => "historical_$stats_year"."_batting",
            'starter' => "historical_$stats_year"."_starter_pitching",
            'reliever' => "historical_$stats_year"."_reliever_pitching"
        );
        // Drop and re-add partitions for this season/type/year combo.
        $partitions = array(
            $season => 'int',
            $statsType => 'string',
            $stats_year => 'string'
        );
        if (!$test) {
            drop_partition(DATABASE, RetrosheetTables::SIM_INPUT, $partitions);
            add_partition(DATABASE, RetrosheetTables::SIM_INPUT, $partitions);
        }
        list($season_start, $season_end) = updateSeasonVars($season);
        $season_lineup = pullSeasonLineup($season);

        for ($ds = $season_start;
            $ds <= $season_end;
            $ds = ds_modify($ds, '+1 day')) {

            $sim_input_data = array();
            $batting_stats['season'] =
                pullSeasonData($season, $ds, $tables['batter']);
            $pitching_stats['starter']['season'] =
                pullSeasonData($season, $ds, $tables['starter']);
            $pitching_stats['reliever']['season'] =
                pullSeasonData($season, $ds, $tables['reliever']);

            if ($stats_year === RetrosheetStatsYear::SEASON) {
                $batting_stats['previous'] = pullSeasonData(
                    $season,
                    $ds,
                    RetrosheetTables::HISTORICAL_PREVIOUS_BATTING
                );
                $pitching_stats['starter']['previous'] = pullSeasonData(
                    $season,
                    $ds,
                    RetrosheetTables::HISTORICAL_PREVIOUS_STARTER_PITCHING
                );
                $pitching_stats['reliever']['previous'] = pullSeasonData(
                    $season,
                    $ds,
                    RetrosheetTables::HISTORICAL_PREVIOUS_RELIEVER_PITCHING
                );
            }
            if ($stats_year !== RetrosheetStatsYear::CAREER) {
                $batting_stats['career'] = pullSeasonData(
                    $season,
                    $ds,
                    RetrosheetTables::HISTORICAL_CAREER_BATTING
                );
                $pitching_stats['starter']['career'] = pullSeasonData(
                    $season,
                    $ds,
                    RetrosheetTables::HISTORICAL_CAREER_STARTER_PITCHING
                );
                $pitching_stats['reliever']['career'] = pullSeasonData(
                    $season,
                    $ds,
                    RetrosheetTables::HISTORICAL_CAREER_RELIEVER_PITCHING
                );
            }

            if (!isset($season_lineup[$ds])) {
                echo "No Games On $ds \n";
                continue;
            }
            echo "$ds \n";
            foreach ($season_lineup[$ds] as $i => $lineup) {
                $index = $i."_$ds";
                $sim_input_data[$index] = array(
                    'rand_bucket' => $lineup['rand_bucket'],
                    'gameid' => $lineup['game_id'],
                    'home' => $lineup['home'],
                    'away' => $lineup['away'],
                    'season' => $lineup['season'],
                    'game_date' => $lineup['game_date'],
                    'stats_year' => $stats_year,
                    'stats_type' => $statsType,
                    'error_rate_h' => null,
                    'error_rate_a' => null,
                    'pitching_h' =>
                        json_encode(
                            fillPitchers(
                                $lineup['pitcher_h'],
                                $pitching_stats,
                                $lineup['home'],
                                RetrosheetSplits::HOME
                            )
                        ),
                    'pitching_a' =>
                        json_encode(
                            fillPitchers(
                                $lineup['pitcher_a'],
                                $pitching_stats,
                                $lineup['away'],
                                RetrosheetSplits::AWAY
                            )
                        ),
                    'batting_h' =>
                        json_encode(
                            fillLineups(
                                $lineup['lineup_h'],
                                $batting_stats
                            )
                        ),
                    'batting_a' =>
                        json_encode(
                            fillLineups(
                                $lineup['lineup_a'],
                                $batting_stats
                            )
                        )
                );
            }
        if (!$test && isset($sim_input_data)) {
            multi_insert(
                DATABASE,
                RetrosheetTables::SIM_INPUT,
                $sim_input_data,
                $colheads
            );
        } else if ($test) {
            print_r($sim_input_data); exit();
        }
        }
    }
}


?>
