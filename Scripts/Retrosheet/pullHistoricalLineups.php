<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include_once('/Users/constants.php');
include_once(HOME_PATH.'Scripts/Include/sweetfunctions.php');

const PCT_START_THRESH = .5;
const INF_ERA = 99;
const ERA_25 = 25;
const ERA_50 = 50;
const ERA_75 = 75;
const ERA_100 = 100;
$positionMap = array();

function convertRetroDateToDs($season, $date) {
    $month = substr($date, 0, 2);
    $day = substr($date, -2);
    $ds = "$season-$month-$day";
    return $ds;
}

function pullPitcherMap($season) {
    $sql =
        "SELECT DISTINCT
            lower(concat(a.first_name_tx, '_', a.last_name_tx)) as name,
            a.player_id,
            a.bat_hand_cd,
            a.pit_hand_cd,
            a.team_id,
            d.team_id as updated_team_id,
            b.starter_bucket as season_starter_bucket,
            c.starter_bucket as career_starter_bucket,
            b.starter_era as season_starter_era,
            c.starter_era as career_starter_era,
            b.avg_innings_starter as season_avg_innings_starter,
            c.avg_innings_starter as career_avg_innings_starter,
            b.starter_innings as season_starter_innings,
            c.starter_innings as career_starter_innings,
            b.reliever_innings as season_reliever_innings,
            c.reliever_innings as career_reliever_innings,
            b.reliever_earned_runs as season_reliever_runs,
            c.reliever_earned_runs as career_reliever_runs,
            b.games as season_games,
            c.games as career_games,
            b.pct_start as season_pct_start,
            c.pct_start as career_pct_start,
            c.ds
        FROM
            (SELECT min(team_id) as team_id,
                player_id,
                first_name_tx,
                last_name_tx,
                bat_hand_cd,
                pit_hand_cd
            FROM rosters
            WHERE year_id = $season
            GROUP BY player_id,
                first_name_tx,
                last_name_tx,
                bat_hand_cd,
                pit_hand_cd) a
        LEFT OUTER JOIN retrosheet_historical_eras_career c
        ON a.player_id = c.player_id
        AND c.season = $season
        LEFT OUTER JOIN retrosheet_historical_eras b
        ON a.player_id = b.player_id
        AND b.season = $season
        AND b.ds = c.ds
        LEFT OUTER JOIN retrosheet_historical_pitching_rosters d
        ON a.player_id = d.player_id
        AND d.season = $season
        AND d.ds = c.ds";
    $id_map = exe_sql(DATABASE, $sql);
    $pitcher_map = array();
    $era_map = array();
    foreach ($id_map as $data) {
        $ds = $data['ds'];
        $player_id = $data['player_id'];
        $season_era = $data['season_starter_era'];
        $career_era = $data['career_starter_era'];
        if (isset($season_era) && $season_era !== INF_ERA) {
            $era_map[$ds]['season'][] = $season_era;
        }
        if (isset($career_era) && $career_era !== INF_ERA) {
            $era_map[$ds]['career'][] = $career_era;
        }
        $pitcher_map[$ds][$player_id] = $data;
    }
    $ordered_era_map = array();
    foreach ($era_map as $era_ds => $eras) {
        foreach ($eras as $type => $data) {
            sort($data);
            $ordered_era_map[$era_ds][$type] = $data;
        }
    }
    return array($pitcher_map, $ordered_era_map);
}

function pullPositionMap() {
    $sql = "SELECT value_cd as id,
            shortname_tx as position
        FROM lkup_cd_fld";
    $position_map = exe_sql(DATABASE, $sql);
    $position_map = index_by($position_map, 'id');
    return $position_map;
}

function calculateBucket($era, $era_25, $era_50, $era_75) {
    switch (true) {
        case $era >= $era_75:
            return ERA_100;
            break;
        case $era >= $era_50:
            return ERA_75;
            break;
        case $era >= $era_25:
            return ERA_50;
            break;
        case $era < $era_25:
            return ERA_25;
            break;
    }
}

function getSeasonStartEnd($season) {
    $season_sql =
        "SELECT min(substr(game_id,8,4)) as start,
            max(substr(game_id,8,4)) as end
        FROM games
        WHERE substr(game_id,4,4) = '$season'
        GROUP BY substr(game_id,4,4)";
    $season_dates = reset(exe_sql(DATABASE, $season_sql));
    $season_start = convertRetroDateToDs($season, $season_dates['start']);
    $season_end = convertRetroDateToDs($season, $season_dates['end']);
    return array($season_start, $season_end);
}

function pullLineupData($season, $ds) {
    $season_data = null;
    $sql =
        "SELECT game_id,
            substr(game_id,8,4) as retro_ds,
            start_game_tm as game_time,
            away_team_id as away,
            home_team_id as home,
            home_start_pit_id as pitcher_h,
            away_start_pit_id as pitcher_a,
            away_score_ct as away_score,
            home_score_ct as home_score,
            away_lineup1_bat_id,
            away_lineup1_fld_cd,
            away_lineup2_bat_id,
            away_lineup2_fld_cd,
            away_lineup3_bat_id,
            away_lineup3_fld_cd,
            away_lineup4_bat_id,
            away_lineup4_fld_cd,
            away_lineup5_bat_id,
            away_lineup5_fld_cd,
            away_lineup6_bat_id,
            away_lineup6_fld_cd,
            away_lineup7_bat_id,
            away_lineup7_fld_cd,
            away_lineup8_bat_id,
            away_lineup8_fld_cd,
            away_lineup9_bat_id,
            away_lineup9_fld_cd,
            home_lineup1_bat_id,
            home_lineup1_fld_cd,
            home_lineup2_bat_id,
            home_lineup2_fld_cd,
            home_lineup3_bat_id,
            home_lineup3_fld_cd,
            home_lineup4_bat_id,
            home_lineup4_fld_cd,
            home_lineup5_bat_id,
            home_lineup5_fld_cd,
            home_lineup6_bat_id,
            home_lineup6_fld_cd,
            home_lineup7_bat_id,
            home_lineup7_fld_cd,
            home_lineup8_bat_id,
            home_lineup8_fld_cd,
            home_lineup9_bat_id,
            home_lineup9_fld_cd
        FROM games
        WHERE substr(game_id,4,4) = '$season'
        AND substr(game_id,8,4) = '$ds'";
    $season_data = exe_sql(DATABASE, $sql);
    return $season_data;
}

function getStartingPitcherArray($pitcher_name, $pitcher) {
    $stats = array(
        'id' => 
            isset($pitcher['player_id']) ? $pitcher['player_id'] : null,
        'name' => 
            isset($pitcher['name']) ? format_for_mysql($pitcher['name']) : null,
        'hand' => 
            isset($pitcher['pit_hand_cd']) ? $pitcher['pit_hand_cd'] : null,
        'season_era' => 
            isset($pitcher['season_starter_era']) 
            ? $pitcher['season_starter_era'] : null,
        'career_era' => 
            isset($pitcher['career_starter_era']) 
            ? $pitcher['career_starter_era'] : null,
        'season_bucket' => 
            isset($pitcher['season_starter_bucket']) 
            ? $pitcher['season_starter_bucket'] : null,
        'career_bucket' => 
            isset($pitcher['career_starter_bucket']) 
            ? $pitcher['career_starter_bucket'] : null,
        'season_innings' => 
            isset($pitcher['season_starter_innings'])
            ? $pitcher['season_starter_innings'] : null,
        'career_innings' => 
            isset($pitcher['career_starter_innings'])
            ? $pitcher['career_starter_innings'] : null,
        'season_avg_innings' => 
            isset($pitcher['season_avg_innings_starter'])
            ? $pitcher['season_avg_innings_starter'] : null,
        'career_avg_innings' => 
            isset($pitcher['career_avg_innings_starter'])
            ? $pitcher['career_avg_innings_starter'] : null
    );
    return $stats;
}

function getStartingLineup($lineup, $team) {
    global $positionMap;
    $lineup_array = array();
    for ($i = 1; $i < 10; $i++) {
        $index = "L$i";
        $bat_id = $team."_lineup$i"."_bat_id";
        $field_id = $team."_lineup$i"."_fld_cd";
        $position = $positionMap[$lineup[$field_id]]['position'];
        $lineup_array[$index] = array(
            'player_id' => $lineup[$bat_id],
            'position' => $position
        );
    }
    return $lineup_array;
}

function calculateERA($runs, $innings) {
    return ($runs / $innings) * 9;
}

function getRelieverData($pitcher_map, $era_map, $lineup, $ds, $home_away) {
    $team = $lineup[$home_away];
    $pitcher_key = 'pitcher_'.substr($home_away, 0, 1);
    $initial_values = array(
        'era' => 0,
        'runs' => 0,
        'innings' => 0,
        'pitchers' => 0
    );
    $pitchers = array(
        'season' => $initial_values,
        'career' => $initial_values
    );
    // Loop through all pitchers (player_map) to find the pitchers
    // on the team that is currently playing.
    if (!isset($pitcher_map[$ds])) {
        return array(
            'season_era' => null,
            'season_weighted_era' => null,
            'season_bucket' => null,
            'career_era' => null,
            'career_weighted_era' => null,
            'career_bucket' => null
        );
    }
    foreach ($pitcher_map[$ds] as $pitcher) {
        $pitcher_map_team =
            $pitcher['updated_team_id'] ?: $pitcher['team_id'];
        if ($pitcher_map_team == $team
            && $pitcher['player_id'] != $lineup[$pitcher_key]) {
            if ($pitcher['season_reliever_innings'] > 0
            && $pitcher['season_pct_start'] < PCT_START_THRESH) {
                $pitchers['season']['era'] += calculateERA(
                    $pitcher['season_reliever_runs'],
                    $pitcher['season_reliever_innings']
                );
                $pitchers['season']['runs'] += $pitcher['season_reliever_runs'];
                $pitchers['season']['innings'] +=
                    $pitcher['season_reliever_innings'];
                $pitchers['season']['pitchers'] += 1;
            }
            if ($pitcher['career_reliever_innings'] > 0
            && $pitcher['career_pct_start'] < PCT_START_THRESH) {
               $pitchers['career']['era'] += calculateERA(
                    $pitcher['career_reliever_runs'],
                    $pitcher['career_reliever_innings']
                );
                $pitchers['career']['runs'] += $pitcher['career_reliever_runs'];
                $pitchers['career']['innings'] +=
                    $pitcher['career_reliever_innings'];
                $pitchers['career']['pitchers'] += 1;
            }
        }
    }
    if (isset($era_map['career'])) {
        $career_era_divider = count($era_map['career']) / 4;
        $era_25['career'] = $era_map['career'][$career_era_divider];
        $era_50['career'] = $era_map['career'][$career_era_divider * 2];
        $era_75['career'] = $era_map['career'][$career_era_divider * 3];
    }
    if (isset($era_map['season'])) {
        $season_era_divider = count($era_map['season']) / 4;
        $era_25['season'] = $era_map['season'][$season_era_divider];
        $era_50['season'] = $era_map['season'][$season_era_divider * 2];
        $era_75['season'] = $era_map['season'][$season_era_divider * 3];
    }
    $final_pitcher_array = array();
    foreach ($pitchers as $type => $data) {
        $final_pitcher_array[$type.'_era'] =
            $data['pitchers'] ?
            format_double($data['era'] / $data['pitchers'], 2) : 0;
        $final_pitcher_array[$type.'_weighted_era'] =
            $data['innings'] ?
            format_double(calculateERA($data['runs'], $data['innings']), 2)
            : null;
        $final_pitcher_array[$type.'_bucket'] =
            isset($era_map[$type])
            ? calculateBucket(
                $final_pitcher_array[$type.'_weighted_era'],
                $era_25[$type],
                $era_50[$type],
                $era_75[$type]
            ) : null;
    }
    return $final_pitcher_array;
}

function formatLineups($lineup, $season, $pitcher_map, $era_map) {
    $game_id = $lineup['game_id'];
    $game_date = convertRetroDateToDs($season, $lineup['retro_ds']);
    $filled_lineup = array(
        'game_id' => $game_id,
        'season' => $season,
        'game_date' => $game_date,
        'game_time' => $lineup['game_time'],
        'home' => $lineup['home'],
        'away' => $lineup['away'],
        'home_score' => $lineup['home_score'],
        'away_score' => $lineup['away_score'],
        'home_team_winner' =>
            $lineup['home_score'] > $lineup['away_score'] ? 1 : 0,
        'lineup_h' =>
            json_encode(getStartingLineup($lineup, 'home')),
        'lineup_a' =>
            json_encode(getStartingLineup($lineup, 'away'))
    );
    $filled_lineup['pitcher_h'] =
        isset($pitcher_map[$game_date][$lineup['pitcher_h']])
        ? json_encode(
            getStartingPitcherArray(
                $lineup['pitcher_h'],
                $pitcher_map[$game_date][$lineup['pitcher_h']]
            )
        ) : json_encode(array("id" => $lineup['pitcher_h']));
    $filled_lineup['pitcher_a'] =
        isset($pitcher_map[$game_date][$lineup['pitcher_a']])
        ? json_encode(
            getStartingPitcherArray(
                $lineup['pitcher_a'],
                $pitcher_map[$game_date][$lineup['pitcher_a']]
            )
        ) : json_encode(array("id" => $lineup['pitcher_a']));
    $filled_lineup['reliever_h'] = json_encode(
        getRelieverData(
            $pitcher_map,
            $era_map,
            $lineup,
            $game_date,
            'home'
        )
    );
    $filled_lineup['reliever_a'] = json_encode(
        getRelieverData(
            $pitcher_map,
            $era_map,
            $lineup,
            $game_date,
            'away'
        )
    );
    return $filled_lineup;
}

// Prompt user to confirm since this will overwrite a full seasons' data.
echo "\n"."Are you sure you want to overwrite historical data? (y/n) ";
$handle = fopen("php://stdin","r");
$confirm = trim(fgets($handle));
if ($confirm !== 'y') {
    exit();
}

$test = false;
$colheads = array(
    'game_id',
    'home',
    'away',
    'home_score',
    'away_score',
    'home_team_winner',
    'pitcher_h',
    'pitcher_a',
    'lineup_h',
    'lineup_a',
    'reliever_h',
    'reliever_a',
    'game_time',
    'season',
    'game_date'
);
$daily_table = 'retrosheet_historical_lineups';
$positionMap = pullPositionMap();

for ($season = 1971; $season < 2014; $season++) {
    echo "$season \n";
    $start_time = time();
    echo "Start: $start_time \n";
    list($season_start, $season_end) = getSeasonStartEnd($season);
    list($pitcher_map, $era_map) = pullPitcherMap($season);
    $end_time = time();
    $time_taken = $end_time - $start_time;
    echo "End: $end_time \n";
    echo "Time Taken: $time_taken \n";

    for ($ds = $season_start;
        $ds <= $season_end;
        $ds = ds_modify($ds, '+1 day')
    ) {
        echo $ds."\n";
        $retro_ds = return_between($ds, "-", "-", EXCL).substr($ds, -2);
        // For MySQL insert we'll use the following day to simulate pulling
        // data at 12am the night before.
        $entry_ds = ds_modify($ds, '+1 day');
        $lineups = pullLineupData($season, $retro_ds);
        $daily_lineups = array();
        $daily_era_map =
            isset($era_map[$ds]) ? $era_map[$ds] : null;
        if ($lineups) {
            foreach ($lineups as $lineup) {
                $daily_lineups[] = formatLineups(
                    $lineup,
                    $season,
                    $pitcher_map,
                    $daily_era_map
                );
            }
            if (!$test) {
                multi_insert(
                    DATABASE,
                    $daily_table,
                    $daily_lineups,
                    $colheads
                );
            }
        }
    }
}

?>
