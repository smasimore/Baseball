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

const MIN_AT_BATS = 18;
const PITCHER = 'pitcher';
const BATTER = 'batter';
const SEASON = 'season';
const PREV_SEASON = 'previous';
const CAREER = 'career';
const BASIC = 'basic';
const HOME = 'home';
const AWAY = 'away';
const HOME_ABBR = 'h';
const AWAY_ABBR = 'a';
const BAT_ID = 'BAT_ID';
const PIT_ID = 'PIT_ID';
const BAT_HAND_CD = 'BAT_HAND_CD';
const PIT_HAND_CD = 'PIT_HAND_CD';
const JOE_AVERAGE = 'joe_average';
const SIM_INPUT_TABLE = 'sim_input';
const GAMES_TABLE = 'games';
const EVENTS_TABLE = 'events';
const STAT_DIFFERENCE = .001;
const SEASON_GAP_EXCEPTION = 'Player Has A Gap Of > 5 Years';

$numTestDates = 10;
$maxYear = 1988;
$statsYear = CAREER;
            //SEASON;
            //PREV_SEASON;
$statsType = BASIC;
$silenceSuccess = true;
$skipJoeAverage = false;
$splitsTested = array();
$cache = array();
$splits = array(
    'Total',
    'Home',
    'Away',
    'VsLeft',
    'VsRight',
    'NoneOn',
    'RunnersOn',
    'ScoringPos',
    'ScoringPos2Out',
    'BasesLoaded'
);
$defaultMap = array(
    CAREER => array(
        RetrosheetDefaults::CAREER_TOTAL,
        RetrosheetDefaults::CAREER_JOE_AVERAGE_ACTUAL,
        RetrosheetDefaults::CAREER_JOE_AVERAGE_TOTAL
    ),
    PREV_SEASON => array(
        RetrosheetDefaults::PREV_YEAR_TOTAL,
        RetrosheetDefaults::CAREER_ACTUAL,
        RetrosheetDefaults::CAREER_TOTAL,
        RetrosheetDefaults::PREV_SEASON_JOE_AVERAGE_ACTUAL,
        RetrosheetDefaults::PREV_SEASON_JOE_AVERAGE_TOTAL,
        RetrosheetDefaults::CAREER_JOE_AVERAGE_ACTUAL,
        RetrosheetDefaults::CAREER_JOE_AVERAGE_TOTAL
    )
);

function pullSimInput($test_days, $seasons) {
    global $statsYear, $statsType;
    $sql = "SELECT *
        FROM " . SIM_INPUT_TABLE . "
        WHERE season in($seasons)
        AND game_date in($test_days)
        AND stats_type = '$statsType'
        AND stats_year = '$statsYear'";
    return exe_sql(DATABASE, $sql);
}

function pullRecentPlayers($game_id, $type) {
    global $cache;
    $season = substr($game_id, 3, 4);
    $ds = substr($game_id, 7, 4);
    $five_ago = $season - 4;
    $type_id = $type == BATTER ? BAT_ID : PIT_ID;
    $sql = "SELECT DISTINCT $type_id
        FROM " . EVENTS_TABLE . "
        WHERE (season < $season
        AND season >= $five_ago)
        OR (season = $season
        AND substr(game_id, 8, 4) < $ds)";
    if (isset($cache['recentPlayers'][$sql])) {
        $data = $cache['recentPlayers'][$sql];
    } else {
        $data = exe_sql(DATABASE, $sql);
        $cache['recentPlayers'][$sql] = $data;
    }
    $players = array();
    foreach ($data as $player) {
        $name = $player[$type_id];
        $players[] = "'$name'";
    }
    $players = implode(',', $players);
    return $players;
}

function pullPlateAppearances(
    $player_where,
    $game_id,
    $stats_year = null,
    $where = null
) {
    global $statsYear, $cache;
    $season = substr($game_id, 3, 4);
    $ds = substr($game_id, 7, 4);
    // Use $statsYear unless overwritten in function input.
    $stats_year = $stats_year ?: $statsYear;
    $season_where = getSeasonWhere($stats_year, $season, $ds);
    // If a where clause is given in function input use it, otherwise
    // omit where clause using WHERE = 'TRUE'.
    $where = $where ?: 'TRUE';
    $sql = "SELECT count(1) as plate_appearances
        FROM events
        WHERE $player_where
        AND $where
        AND event_cd in(" . implode(',', RetrosheetBatting::getAllEvents()) . ")
        AND $season_where";
    if (isset($cache['plateAppearances'][$sql])) {
        $data = $cache['plateAppearances'][$sql];
    } else {
        $data = reset(exe_sql(DATABASE, $sql));
        $cache['plateAppearances'][$sql] = $data;
    }
    $pas = $data ? $data['plate_appearances'] : 0;
    return $pas;
}

function checkException($player, $type_id, $exception_where) {
    // 1) Does this player have a 5+ year gap in their history?
    $sql = "SELECT DISTINCT season
        FROM " . EVENTS_TABLE .
        " WHERE ($type_id = '$player'
        OR $exception_where)
        ORDER BY season";
    $data = exe_sql(DATABASE, $sql);
    $seasons = array();
    foreach ($data as $row) {
        $season = $row['season'];
        $seasons[$season] = $season;
    }
    $prev_season = null;
    foreach ($seasons as $season) {
        $season_gap = $prev_season ? $season - $prev_season : 0;
        $prev_season = $season;
        if ($season_gap > 5) {
            return SEASON_GAP_EXCEPTION;
        }
    }
}

function assertTrue(
    $truth,
    $game_id,
    $player,
    $stat,
    $error = null,
    $type_id = null
) {
    global $silenceSuccess, $splitsTested, $cache;
    $message = "GAMEID: $game_id => $stat \t \t \t";
    if (!$truth) {
        $exception_where = $type_id ? 'FALSE' : "PIT_ID = '$player'";
        $type_id = elvis($type_id, BAT_ID);
        $exception = checkException($player, $type_id, $exception_where);
        if ($exception) {
            echo "Exception for $player: $exception \n";
        } else {
            print_r($cache['Query1']);
            print_r($cache['Query2']);
            exit("\n \n \n $message FAILED -- $error \n \n \n");
        }
    } else {
        $splitsTested[$stat] =
            isset($splitsTested[$stat]) ? ($splitsTested[$stat] + 1) : 1;
        if (!$silenceSuccess) {
            echo "$message SUCCESS \n";
        }
    }
}

function pullLineup($game_id) {
    $sql = "SELECT *
        FROM " . GAMES_TABLE .
        " WHERE game_id = '$game_id'";
    return reset(exe_sql(DATABASE, $sql));
}

function validateBatter($lineup, $batter_id, $home_away, $lineup_pos) {
    $home_away = ($home_away == HOME_ABBR) ? HOME : AWAY;
    $game_id = $lineup['GAME_ID'];
    $batter_index =
        strtoupper($home_away) . '_LINEUP' . $lineup_pos . '_BAT_ID';
    $actual_batter = $lineup[$batter_index];
    // For Joe Average ensure that batter doesn't meet MIN_AT_BATS.
    if ($batter_id == JOE_AVERAGE) {
        $pas = pullPlateAppearances(
            "bat_id = '$actual_batter'",
            $game_id
        );
        assertTrue(
            ($pas < MIN_AT_BATS),
            $game_id,
            $actual_batter,
            'batter_joe_average',
            "$actual_batter !== 'joe_average'"
        );
    } else {
        assertTrue(
            ($actual_batter == $batter_id),
            $game_id,
            $actual_batter,
            "batter_$home_away",
            "$actual_batter !== $batter_id"
        );
    }
}

function validatePitcher($game_id, $pitcher_id, $home_away) {
    $pitcher =
        ($home_away == HOME_ABBR) ? 'HOME_START_PIT_ID' : 'AWAY_START_PIT_ID';
    $sql = "SELECT $pitcher as pitcher
        FROM " . GAMES_TABLE .
        " WHERE game_id = '$game_id'";
    $data = reset(exe_sql(DATABASE, $sql));
    $actual_pitcher = $data['pitcher'];
    // For Joe Average ensure that pitcher doesn't meet MIN_AT_BATS.
    if ($pitcher_id == JOE_AVERAGE) {
        $pas = pullPlateAppearances(
            "pit_id = '$actual_pitcher'",
            $game_id
        );
        assertTrue(
            ($pas < MIN_AT_BATS),
            $game_id,
            $actual_pitcher,
            'pitcher_joe_average',
            "$actual_pitcher !== 'joe_average'"
        );
    } else {
        assertTrue(
            ($actual_pitcher == $pitcher_id),
            $game_id,
            $actual_pitcher,
            "pitcher_$home_away",
            "$actual_pitcher !== $pitcher_id"
        );
    }
}

function getSeasonWhere($stats_year, $season, $ds) {
    switch ($stats_year) {
        case SEASON:
            return "(season = $season AND substr(game_id,8,4) < $ds)";
            break;
        case CAREER:
            return "((season = $season AND substr(game_id,8,4) < $ds)
                OR season < $season)";
            break;
        case PREV_SEASON:
            $prev_season = $season - 1;
            return "season = $prev_season";
            break;
    }
}

function addDefaultData($default_step, $game_id, $sql_data, $type, $type_id) {
    switch ($default_step) {
        case RetrosheetDefaults::SEASON_TOTAL:
            // DEFAULT 0: Season Total Split
            $sql_data['where'] = 'TRUE';
            break;
        case RetrosheetDefaults::PREV_YEAR_ACTUAL:
            // DEFAULT 1: Prev Year Actual Split
            $sql_data['where'] = $sql_data['original_where'];
            $sql_data['stats_year'] = PREV_SEASON;
            break;
        case RetrosheetDefaults::PREV_YEAR_TOTAL:
            // DEFAULT 2: Prev Year Total Split
            $sql_data['where'] = 'TRUE';
            break;
        case RetrosheetDefaults::CAREER_ACTUAL:
            // DEFAULT 3: Career Actual Split
            $sql_data['where'] = $sql_data['original_where'];
            $sql_data['stats_year'] = CAREER;
            break;
        case RetrosheetDefaults::CAREER_TOTAL:
            // DEFAULT 4: Career Total Split
            $sql_data['where'] = 'TRUE';
            break;
        case RetrosheetDefaults::SEASON_JOE_AVERAGE_ACTUAL:
            // DEFAULT 5: Season Joe Average Actual Split
            $recent_players = pullRecentPlayers($game_id, $type);
            $sql_data['where'] = $sql_data['original_where'];
            $sql_data['stats_year'] = SEASON;
            $sql_data['player_where'] = "$type_id in($recent_players)";
            break;
        case RetrosheetDefaults::SEASON_JOE_AVERAGE_TOTAL:
            // DEFAULT 6: Season Joe Average Total Split
            $sql_data['where'] = 'TRUE';
            break;
        case RetrosheetDefaults::PREV_SEASON_JOE_AVERAGE_ACTUAL:
            // DEFAULT 7: Prev Season Joe Average Actual Split
            $recent_players = pullRecentPlayers($game_id, $type);
            $sql_data['where'] = $sql_data['original_where'];
            $sql_data['stats_year'] = PREV_SEASON;
            $sql_data['player_where'] = "$type_id in($recent_players)";
            break;
        case RetrosheetDefaults::PREV_SEASON_JOE_AVERAGE_TOTAL:
            // DEFAULT 8: Prev Season Joe Average Total Split
            $sql_data['where'] = 'TRUE';
            break;
        case RetrosheetDefaults::CAREER_JOE_AVERAGE_ACTUAL:
            // DEFAULT 9: Career Joe Average Actual Split
            $recent_players = pullRecentPlayers($game_id, $type);
            $sql_data['where'] = $sql_data['original_where'];
            $sql_data['stats_year'] = CAREER;
            $sql_data['player_where'] = "$type_id in($recent_players)";
            break;
        case RetrosheetDefaults::CAREER_JOE_AVERAGE_TOTAL:
            // DEFAULT 10: Career Joe Average Total Split
            $sql_data['where'] = 'TRUE';
            break;
    }
    return $sql_data;
}

function validateSplit($stats, $player_id, $split, $game_id, $type) {
    global $statsYear, $skipJoeAverage, $cache, $defaultMap;
    $season = substr($game_id, 3, 4);
    $ds = substr($game_id, 7, 4);
    // Unlike batters, when a pitcher is home the bat_id = 0 since it
    // refers to the opposing batter.
    switch ($type) {
        case PITCHER:
            $type_id = PIT_ID;
            $player_where = "$type_id = '$player_id'";
            $opp_hand = BAT_HAND_CD;
            $bat_home_id = RetrosheetHomeAway::AWAY;
            break;
        case BATTER:
            $type_id = BAT_ID;
            $player_where = "$type_id = '$player_id'";
            $opp_hand = PIT_HAND_CD;
            $bat_home_id = RetrosheetHomeAway::HOME;
            break;
    }
    // Create a WHERE statement based on split
    switch ($split) {
        case "Total":
            $where = 'TRUE';
            break;
        case "Home":
            $where = "BAT_HOME_ID = $bat_home_id";
            break;
        case "Away":
            $away_id = 1 - $bat_home_id;
            $where = "BAT_HOME_ID = $away_id";
            break;
        case "VsLeft":
            $where = "$opp_hand = 'L'";
            break;
        case "VsRight":
            $where = "$opp_hand = 'R'";
            break;
        case "NoneOn":
            $where = "START_BASES_CD = " . RetrosheetBases::BASES_EMPTY;
            break;
        case "RunnersOn":
            $where = "START_BASES_CD = " . RetrosheetBases::FIRST;
            break;
        case "ScoringPos":
            $where = "START_BASES_CD >= " . RetrosheetBases::SECOND .
                " AND START_BASES_CD != " . RetrosheetBases::BASES_LOADED .
                " AND OUTS_CT < 2";
            break;
        case "ScoringPos2Out":
            $where = "START_BASES_CD >= " . RetrosheetBases::SECOND .
            " AND OUTS_CT = 2";
            break;
        case "BasesLoaded":
            $where = "START_BASES_CD = " . RetrosheetBases::BASES_LOADED .
                " AND OUTS_CT < 2";
            break;
    }
    // Create second copy of $where for use in Joe Average logic.
    $is_joe_average =
        ($stats['player_id'] == JOE_AVERAGE || $player_id == JOE_AVERAGE)
        ? 1 : 0;
    $pas = elvis($stats['plate_appearances'], 0);
    $default_step = 0;
    $sql_data = array(
        'player_where' => $player_where,
        'stats_year' => $statsYear,
        'where' => $where,
        'original_where' => $where
    );
    $is_filled = ($pas >= MIN_AT_BATS && !$is_joe_average);
    while (!$is_filled) {
        $default_routing = $statsYear == SEASON
            ? $default_step : $defaultMap[$statsYear][$default_step];
        // If player is Joe Average skip non Joe Average Defaults
        if ($is_joe_average) {
            if ($default_routing <
                RetrosheetDefaults::SEASON_JOE_AVERAGE_ACTUAL) {
                $default_step += 1;
                continue;
            }
        }
        $sql_data = addDefaultData(
            $default_routing,
            $game_id,
            $sql_data,
            $type,
            $type_id
        );
        $pas = pullPlateAppearances(
            $sql_data['player_where'],
            $game_id,
            $sql_data['stats_year'],
            $sql_data['where']
        );
        $is_filled = $pas >= MIN_AT_BATS;
        $default_step += 1;
    }
    // Update SQL params if they've changed in defaulting.
    $where = $sql_data['where'];
    $player_where = $sql_data['player_where'];
    $season_where = getSeasonWhere($sql_data['stats_year'], $season, $ds);

    $sql =
        "SELECT a.event_name,
        b.denom AS plate_appearances,
        a.instances,
        a.instances/b.denom AS pct
        FROM
          (SELECT CASE
              WHEN event_cd
                  in(" . implode(',', RetrosheetBatting::getWalkEvents()) . ")
                  THEN 'walk'
              WHEN event_cd in(".
                  RetrosheetBatting::GENERIC_OUT . "," .
                  RetrosheetBatting::FIELDERS_CHOICE . "
              ) AND battedball_cd = 'G' THEN 'ground_out'
              WHEN event_cd in(" .
                  RetrosheetBatting::GENERIC_OUT . "," .
                  RetrosheetBatting::FIELDERS_CHOICE . "
              ) AND battedball_cd != 'G' THEN 'fly_out'
              WHEN event_cd = ". RetrosheetBatting::HOME_RUN .
              " THEN 'home_run'
              ELSE lower(longname_tx)
              END AS event_name,
              1 AS dummy,
              count(1) AS instances
            FROM events a
            JOIN lkup_cd_event b ON a.event_cd = b.value_cd
            AND $player_where
            AND $where
            AND $season_where
            AND event_cd in(" .
                implode(',', RetrosheetBatting::getAllEvents()) . ")
            GROUP BY event_name, dummy) a
        JOIN
          (SELECT count(1) AS denom,
                  1 AS dummy
           FROM events a
           WHERE $player_where
           AND $where
           AND $season_where
           AND event_cd in(" . implode(',', RetrosheetBatting::getAllEvents()) . ")
           ) b
        ON a.dummy = b.dummy
        ORDER BY 1-pct";
    // To save processing time don't re-run Joe Average queries
    if ($is_joe_average && isset($cache['joeAverages'][$sql])) {
        $data = $cache['joeAverages'][$sql];
    } else if ($is_joe_average && $skipJoeAverage) {
        // do nothing
    } else {
        $data = exe_sql(DATABASE, $sql);
        $data = index_by($data, 'event_name');
        if ($is_joe_average && !isset($cache['joeAverages'][$sql])) {
            $cache['joeAverages'][$sql] = $data;
        }
        foreach ($stats as $split_name => $stat) {
            if ($split_name == 'plate_appearances'
                || $split_name == 'player_id'
            ) {
                continue;
            }
            $split_index = substr($split_name, 4);
            $sql_stat =
                isset($data[$split_index]) ? $data[$split_index]['pct'] : 0;
            $difference = abs($sql_stat - $stat);
            $cache['Query1'] = $data;
            $cache['Query2'] = $stats;
            assertTrue(
                ($difference < STAT_DIFFERENCE),
                $game_id,
                $player_id,
                $split,
                "$split_index error: $sql_stat !== $stat for
                    $player_id in $season",
                $type_id
            );
        }
    }
}

function validatePitching($game, $home_away) {
    global $splits;
    $game_id = $game['gameid'];
    $pitching_data = json_decode($game["pitching_$home_away"], true);
    $pitcher_id = $pitching_data['player_id'];
    validatePitcher($game_id, $pitcher_id, $home_away);
    foreach ($splits as $split) {
        $stats = $pitching_data['pitcher_vs_batter'][$split];
        validateSplit($stats, $pitcher_id, $split, $game_id, PITCHER);
    }
}

function validateBatting($game, $home_away) {
    global $splits;
    $game_id = $game['gameid'];
    $batting_data = json_decode($game["batting_$home_away"], true);
    $lineup = pullLineup($game_id);
    for ($i = 1; $i < 10; $i++) {
        $player_data = $batting_data[$i];
        $batter_id = $player_data['Total']['player_id'];
        validateBatter($lineup, $batter_id, $home_away, $i);
        foreach ($splits as $split) {
            $stats = $player_data[$split];
            validateSplit($stats, $batter_id, $split, $game_id, BATTER);
        }
    }
}

$days_tested = array();
$test_days = array();
$test_seasons = array();
for ($i = 0; $i < $numTestDates; $i++) {
    $rand_season = rand(1950, $maxYear);
    $rand_month = formatDayMonth(rand(4, 9));
    $rand_day = formatDayMonth(rand(1, 31));
    $rand_ds = "'$rand_season-$rand_month-$rand_day'";
    $test_seasons[$rand_season] = $rand_season;
    $test_days[$rand_ds] = $rand_ds;
}
// Override $test_days for now since all data isn't filled
/* Leaving this in here for when I test on my computer :)
$test_days = array(
    "'1950-04-27'" => "'1950-04-27'",
    "'1950-05-02'" => "'1950-05-02'"
);
$test_seasons = array(1950 => 1950);
*/
//////////////////////////////////////////////////////////

$test_days = implode(',', $test_days);
$test_seasons = implode(',', $test_seasons);
$sim_input = pullSimInput($test_days, $test_seasons);
if (!isset($sim_input)) {
    exit("No Valid Game Days in $test_days \n");
}
$start_message = "\n Testing...";
foreach ($sim_input as $game) {
    echo $start_message . $game['gameid'] . "\n";
    echo '                        pitching_h ';
    validatePitching($game, HOME_ABBR);
    echo "SUCCESS! \n";
    echo '                        pitching_a ';
    validatePitching($game, AWAY_ABBR);
    echo "SUCCESS! \n";
    echo '                        batting_h ';
    validateBatting($game, HOME_ABBR);
    echo "SUCCESS! \n";
    echo '                        batting_a ';
    validateBatting($game, AWAY_ABBR);
    echo "SUCCESS! \n";
    $days_tested[$game['game_date']] = $game['game_date'];
    $start_message = '           ';
}

echo "\n \n VARS VALIDATED: ";
asort($splitsTested);
print_r($splitsTested);
$num_days_tested = count($days_tested);
echo "\n \n \n SUCCESS => $num_days_tested DAYS TESTED \n \n";
print_r($days_tested);

?>
