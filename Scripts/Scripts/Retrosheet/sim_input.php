<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/RetrosheetParseUtils.php');

const SEASON = 'season';
const PREVIOUS = 'previous';
const CAREER = 'career';
const BASIC = 'basic';
const MAGIC = 'magic';
const INPUT_TABLE = 'sim_input';

$statsYears = array(
    SEASON,
    PREVIOUS,
    CAREER
);
$statsType =
    BASIC;
    //MAGIC;
$startScript = 1951;
$endScript  = '2014';
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

function fillPitchers($pitcher, $stats, $type) {
    global $joeAverage;
    $pitcher = json_decode($pitcher, true);
    $player_id = $pitcher['id'];
    return  array(
        'player_id' =>
            isset($pitcher['id']) ? $pitcher['id'] : 'joe_average',
        'player_name' =>
            isset($pitcher['name']) ? $pitcher['name'] : $pitcher['id'],
        'handedness' =>
            isset($pitcher['hand']) ? $pitcher['hand'] : '?',
        'era' =>
            isset($pitcher[$type.'_era'])
            ? $pitcher[$type.'_era'] : null,
        'bucket' =>
            isset($pitcher[$type.'_bucket'])
            ? $pitcher[$type.'_bucket'] : null,
        'avg_innings' =>
            isset($pitcher[$type.'_avg_innings'])
            ? $pitcher[$type.'_avg_innings'] : null,
        'pitcher_vs_batter' => isset($stats[$player_id])
            ? json_decode($stats[$player_id]['stats'], true)
            : json_decode($joeAverage['stats'], true),
        'reliever_vs_batter' => null
    );
}

function fillLineups($lineup, $stats) {
    global $joeAverage;
    $lineup = json_decode($lineup, true);
    $filled_lineups = array();
    foreach ($lineup as $lpos => $player) {
        $pos = trim($lpos, "L");
        $player_id = $player['player_id'];
        $batter_v_pitcher = isset($stats[$player_id])
            ? json_decode($stats[$player_id]['stats'], true)
            : json_decode($joeAverage['stats'], true);
        $batter_v_pitcher['hand'] = idx($player, 'hand');
        $filled_lineups[$pos] = $batter_v_pitcher;
    }
    return $filled_lineups;
}

$test = false;

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
            'pitcher' => "historical_$stats_year"."_pitching"
        );
        // Drop and re-add partitions for this season/type/year combo.
        $partitions = array(
            $season => 'int',
            $statsType => 'string',
            $stats_year => 'string'
        );
        if (!$test) {
            drop_partition(DATABASE, INPUT_TABLE, $partitions);
            add_partition(DATABASE, INPUT_TABLE, $partitions);
        }
        list($season_start, $season_end) = updateSeasonVars($season);
        $season_lineup = pullSeasonLineup($season);

        for ($ds = $season_start;
            $ds <= $season_end;
            $ds = ds_modify($ds, '+1 day')) {

            $sim_input_data = array();
            $batter_stats = pullSeasonData($season, $ds, $tables['batter']);
            $pitcher_stats = pullSeasonData($season, $ds, $tables['pitcher']);
            if (!$batter_stats || !$pitcher_stats) {
                echo "Missing Data For $ds \n";
                continue;
            }
            if (!isset($season_lineup[$ds])) {
                echo "No Games On $ds \n";
                continue;
            }
            echo "$ds \n";
            foreach ($season_lineup[$ds] as $i => $lineup) {
                $sim_input_data[$i] = array(
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
                                $pitcher_stats,
                                $stats_year
                            )
                        ),
                    'pitching_a' =>
                        json_encode(
                            fillPitchers(
                                $lineup['pitcher_a'],
                                $pitcher_stats,
                                $stats_year
                            )
                        ),
                    'batting_h' =>
                        json_encode(
                            fillLineups($lineup['lineup_h'], $batter_stats)
                        ),
                    'batting_a' =>
                        json_encode(
                            fillLineups($lineup['lineup_a'], $batter_stats)
                        )
                );
            }

            if (!$test && isset($sim_input_data)) {
                multi_insert(
                    DATABASE,
                    INPUT_TABLE,
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
