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
const CAREER = 'career';
const BASIC = 'basic';
const HOME = 'home';
const AWAY = 'away';
const HOME_ABBR = 'h';
const AWAY_ABBR = 'a';
const JOE_AVERAGE = 'joe_average';
const SIM_INPUT_TABLE = 'sim_input';
const GAMES_TABLE = 'games';
const EVENTS_TABLE = 'events';
const STAT_DIFFERENCE = .001;

$numTestDates = 1;
$maxYear = 1962;
$statsYear = CAREER;
            //SEASON;
$statsType = BASIC;
$silenceSuccess = true;
$skipJoeAverage = false;
$splitsTested = array();
$recentPlayers = array();
$joeAverages = array();
$cacheQuery1 = null;
$cacheQuery2 = null;
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

function pullSimInput($test_days, $seasons) {
    global $statsYear, $statsType;
    $season = substr($ds, 0, 4);
    $sql = "SELECT *
        FROM " . SIM_INPUT_TABLE . "
        WHERE season in($seasons)
        AND game_date in($test_days)
        AND stats_type = '$statsType'
        AND stats_year = '$statsYear'";
    return exe_sql(DATABASE, $sql);
}

function pullRecentPlayers($game_id, $type) {
    global $recentPlayers;
    $season = substr($game_id, 3, 4);
    $ds = substr($game_id, 7, 4);
    $five_ago = $season - 4;
    $type_id = $type == BATTER ? 'BAT_ID' : 'PIT_ID';
    $sql = "SELECT DISTINCT $type_id
        FROM " . EVENTS_TABLE . "
        WHERE (season < $season
        AND season >= $five_ago)
        OR (season = $season
        AND substr(game_id, 8, 4) < $ds)";
    if (isset($recentPlayers[$sql])) {
        $data = $recentPlayers[$sql];
    } else {
        $data = exe_sql(DATABASE, $sql);
        $recentPlayers[$sql] = $data;
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
    global $statsYear;
    $season = substr($game_id, 3, 4);
    $ds = substr($game_id, 7, 4);
    // Use $statsYear unless overwritten in function input.
    $stats_year ?: $statsYear;
    // If stats_year == 'career' add previous season(s) to query.
    $prev_season =
        ($stats_year == CAREER)
        ? "season < $season" : 'FALSE';
    // If a where clause is given in function input use it, otherwise
    // omit where clause using WHERE = 'TRUE'.
    $where = $where ?: 'TRUE';
    $sql = "SELECT count(1) as plate_appearances
        FROM events
        WHERE $player_where
        AND $where
        AND event_cd in(" . implode(',', RetrosheetBatting::getAllEvents()) . ")
        AND ($prev_season
        OR (season = $season && substr(game_id,8,4) < $ds))";
    $data = reset(exe_sql(DATABASE, $sql));
    $pas = $data ? $data['plate_appearances'] : 0;
    return $pas;
}

function assertTrue($truth, $game_id, $stat, $error = null) {
    global $silenceSuccess, $splitsTested, $cacheQuery1, $cacheQuery2;
    $message = "GAMEID: $game_id => $stat \t \t \t";
    if (!$truth) {
        print_r($cacheQuery1);
        print_r($cacheQuery1);
        exit("\n \n \n $message FAILED -- $error \n \n \n");
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
            'batter_joe_average',
            "$actual_batter !== 'joe_average'"
        );
    } else {
        assertTrue(
            ($actual_batter == $batter_id),
            $game_id,
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
            'pitcher_joe_average',
            "$actual_pitcher !== 'joe_average'"
        );
    } else {
        assertTrue(
            ($actual_pitcher == $pitcher_id),
            $game_id,
            "pitcher_$home_away",
            "$actual_pitcher !== $pitcher_id"
        );
    }
}

function validateSplit($stats, $player_id, $split, $game_id, $type) {
    global
        $statsYear, $joeAverages, $skipJoeAverage, $cacheQuery1, $cacheQuery2;
    $season = substr($game_id, 3, 4);
    $ds = substr($game_id, 7, 4);
    $prev_season =
        ($statsYear == CAREER)
        ? "season < $season" : 'FALSE';
    // Unlike batters, when a pitcher is home the bat_id = 0 since it
    // refers to the opposing batter.
    switch ($type) {
        case PITCHER:
            $type_id = 'PIT_ID';
            $player_where = "$type_id = '$player_id'";
            $opp_hand = 'BAT_HAND_CD';
            $bat_home_id = RetrosheetHomeAway::AWAY;
            break;
        case BATTER:
            $type_id = 'BAT_ID';
            $player_where = "$type_id = '$player_id'";
            $opp_hand = 'PIT_HAND_CD';
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
    $original_where = $where;
    $is_joe_average = null;
    $pas =
        isset($stats['plate_appearances'])
        ? $stats['plate_appearances'] : 0;
    // Check to see if player has enough PA's to have stats, otherwise check
    // defaulting logic.
    if ($pas < MIN_AT_BATS) {
        if ($statsYear == 'season') {
    // DEFAULT 1: Career - Original Split
    // Move to career stats (set $prev_season to include all previous years)
            $prev_season = "season < $season";
            $pas = pullPlateAppearances(
                $player_where,
                $game_id,
                CAREER,
                $where
            );
            if ($pas < MIN_AT_BATS) {
    // DEFAULT 2: Season - Total Split
    // Set $prev_season back to FALSE and remove the WHERE (i.e. = TRUE)
                $prev_season = 'FALSE';
                $where = 'TRUE';
                $pas = pullPlateAppearances($player_where, $game_id);
                if ($pas < MIN_AT_BATS) {
    // DEFAULT 3: Career - Total Split
    // Set $prev_season back to career
                    $prev_season = "season < $season";
                    $pas = pullPlateAppearances(
                        $player_where,
                        $game_id,
                        CAREER
                    );
                    if ($pas < MIN_AT_BATS) {
    // DEFAULT 4: Season - Joe Average - Original Split
    // Set $where back to $original_where (for original split) and remove
    // $player_where (i.e. search all players to get joe_average)
                        $is_joe_average = 1;
                        if (!$skipJoeAverage) {
                            $recent_players =
                                pullRecentPlayers($game_id, $type);
                            $player_where = "$type_id in($recent_players)";
                            $where = $original_where;
                            $prev_season = 'FALSE';
                            $pas = pullPlateAppearances(
                                $player_where,
                                $game_id,
                                SEASON,
                                $where
                            );
                            if ($pas < MIN_AT_BATS) {
    // DEFAULT 5 - Career - Joe Average - Original Split
                                $prev_season = "season < $season";
                                $pas = pullPlateAppearances(
                                    $player_where,
                                    $game_id,
                                    CAREER,
                                    $where
                                );
                                if ($pas < MIN_AT_BATS) {
    // DEFAULT 6: Season - Joe Average - Total
    // Remove the $where to revert back to total split
                                    $where = 'TRUE';
                                    $prev_season = 'FALSE';
                                    $pas = pullPlateAppearances(
                                        $player_where,
                                        $game_id
                                    );
                                    if ($pas < MIN_AT_BATS) {
    // DEFAULT 7: Career - Joe Average - Total
    // Add back $prev_season for career stats
                                        $prev_season = "season < $season";
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
    // DEFAULT 1: Career - Total Split
            $where = 'TRUE';
            $pas = pullPlateAppearances($player_where, $game_id);
            if ($pas < MIN_AT_BATS) {
    // DEFAULT 2: Career - Joe Average - Original Split
                $is_joe_average = 1;
                if (!$skipJoeAverage) {
                    $where = $original_where;
                    $recent_players = pullRecentPlayers($game_id, $type);
                    $player_where = "$type_id in($recent_players)";
                    $is_joe_average = 1;
                    $pas = pullPlateAppearances(
                        $player_where,
                        $game_id,
                        CAREER,
                        $where
                    );
                    if ($pas < MIN_AT_BATS) {
    // DEFAULT 3: Career - Joe Average - Total Split
                        $where = 'TRUE';
                    }
                }
            }
        }
    }
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
            AND ((season = $season AND substr(game_id,8,4) < $ds)
                OR $prev_season)
                AND event_cd in(" .
                implode(',', RetrosheetBatting::getAllEvents()) . ")
            GROUP BY event_name, dummy) a
        JOIN
          (SELECT count(1) AS denom,
                  1 AS dummy
           FROM events a
           WHERE $player_where
           AND $where
           AND ((season = $season AND substr(game_id,8,4) < $ds)
                OR $prev_season)
           AND event_cd in(" . implode(',', RetrosheetBatting::getAllEvents()) . ")
           ) b
        ON a.dummy = b.dummy
        ORDER BY 1-pct";
    // To save processing time don't re-run Joe Average queries
    if ($is_joe_average && isset($joeAverages[$sql])) {
        $data = $joeAverages[$sql];
    } else if ($is_joe_average && $skipJoeAverage) {
        // do nothing
    } else {
        $data = exe_sql(DATABASE, $sql);
        $data = index_by($data, 'event_name');
        if ($is_joe_average && !isset($joeAverages[$sql])) {
            $joeAverages[$sql] = $data;
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
            $cacheQuery1 = $data;
            $cacheQuery2 = $stats;
            assertTrue(
                ($difference < STAT_DIFFERENCE),
                $game_id,
                $split,
                "$split_index error: $sql_stat !== $stat for
                    $player_id in $season"
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
    // Validate pitchers.
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
$days_tested = count($days_tested);
echo "\n \n \n SUCCESS => $days_tested DAYS TESTED \n \n \n \n";

?>
