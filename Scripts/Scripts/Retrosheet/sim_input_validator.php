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
const HOME_ABBR = 'h';
const AWAY_ABBR = 'a';
const SIM_INPUT = 'sim_input';
const STAT_DIFFERENCE = .001;

$numTestDates = 5;
$statsYear = //CAREER;
            SEASON;
$statsType = BASIC;
$silenceSuccess = true;
$splitsTested = array();

function pullSimInput($test_days) {
    global $statsYear, $statsType;
    $season = substr($ds, 0, 4);
    $sql = "SELECT *
        FROM " . SIM_INPUT . "
        WHERE game_date in($test_days)
        AND stats_type = '$statsType'
        AND stats_year = '$statsYear'";
    return exe_sql(DATABASE, $sql);
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
    $data = exe_sql(DATABASE, $sql);
    $pas = $data ? reset($data)['plate_appearances'] : 0;
    return $pas;
}

function assertTrue($truth, $game_id, $stat, $error = null) {
    global $silenceSuccess, $splitsTested;
    $message = "GAMEID: $game_id => $stat \t \t \t";
    if (!$truth) {
        exit("\n \n \n $message FAILED -- $error \n \n \n");
    } else {
        $splitsTested[$stat] =
            isset($splitsTested[$stat]) ? ($splitsTested[$stat] + 1) : 1;
        if (!$silenceSuccess) {
            echo "$message SUCCESS \n";
        }
    }
}

function validatePitcher($game_id, $pitcher_id, $home_away) {
    $pitcher =
        ($home_away == HOME_ABBR) ? "HOME_START_PIT_ID" : "AWAY_START_PIT_ID";
    $sql = "SELECT $pitcher as pitcher
        FROM games
        WHERE game_id = '$game_id'";
    $actual_pitcher = reset(exe_sql(DATABASE, $sql))['pitcher'];
    // For Joe Average ensure that pitcher doesn't meet MIN_AT_BATS.
    if ($pitcher_id == 'joe_average') {
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
    global $statsYear;
    $season = substr($game_id, 3, 4);
    $ds = substr($game_id, 7, 4);
    $prev_season =
        ($statsYear == CAREER)
        ? "season < $season" : 'FALSE';
    // Unlike batters, when a pitcher is home the bat_id = 0 since it
    // refers to the opposing batter.
    switch ($type) {
        case PITCHER:
            $player_where = "PIT_ID = '$player_id'";
            $opp_hand = 'BAT_HAND_CD';
            $bat_home_id = RetrosheetHomeAway::AWAY;
            break;
        case BATTER:
            $player_where = "BAT_ID = '$player_id'";
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
        case "VsUnknown":
            $where = "$opp_hand = '?'";
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
    $pas = $stats['plate_appearances'];
    // Check to see if player has enough PA's to have stats, otherwise check
    // defaulting logic.
    if (is_null($pas) || $pas < MIN_AT_BATS) {
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
            if (is_null($pas) || $pas < MIN_AT_BATS) {
    // DEFAULT 2: Season - Total Split
    // Set $prev_season back to FALSE and remove the WHERE (i.e. = TRUE)
                $prev_season = 'FALSE';
                $where = 'TRUE';
                $pas = pullPlateAppearances($player_where, $game_id);
                if (is_null($pas) || $pas < MIN_AT_BATS) {
    // DEFAULT 3: Career - Total Split
    // Set $prev_season back to career
                    $prev_season = "season < $season";
                    $pas = pullPlateAppearances(
                        $player_where,
                        $game_id,
                        CAREER
                    );
                    if (is_null($pas) || $pas < MIN_AT_BATS) {
    // DEFAULT 4: Season - Joe Average - Original Split
    // Set $where back to $original_where (for original split) and remove
    // $player_where (i.e. search all players to get joe_average)
                        $where = $original_where;
                        $player_where = 'TRUE';
                        $prev_season = 'FALSE';
                        $pas = pullPlateAppearances(
                            $player_where,
                            $game_id,
                            SEASON,
                            $where
                        );
                        if (is_null($pas) || $pas < MIN_AT_BATS) {
    // DEFAULT 5 - Career - Joe Average - Original Split
                            $prev_season = "season < $season";
                            $pas = pullPlateAppearances(
                                $player_where,
                                $game_id,
                                CAREER,
                                $where
                            );
                            if (is_null($pas) || $pas < MIN_AT_BATS) {
    // DEFAULT 6: Season - Joe Average - Total
    // Remove the $where to revert back to total split
                                $where = 'TRUE';
                                $prev_season = 'FALSE';
                                $pas =
                                    pullPlateAppearances($player_where, $game_id);
                                if (is_null($pas) || $pas < MIN_AT_BATS) {
    // DEFAULT 7: Career - Joe Average - Total
    // Add back $prev_season for career stats
                                    $prev_season = "season < $season";
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
            if (is_null($pas) || $pas < MIN_AT_BATS) {
    // DEFAULT 2: Career - Joe Average - Original Split
                $where = $original_where;
                $player_where = 'TRUE';
                $pas = pullPlateAppearances(
                    $player_where,
                    $game_id,
                    CAREER,
                    $where
                );
                if (is_null($pas) || $pas < MIN_AT_BATS) {
    // DEFAULT 3: Career - Joe Average - Total Split
                    $where = 'TRUE';
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
    $data = exe_sql(DATABASE, $sql);
    $data = index_by($data, 'event_name');
    foreach ($stats as $split_name => $stat) {
        if ($split_name == 'plate_appearances' || $split_name == 'player_id') {
            continue;
        }
        $split_index = substr($split_name, 4);
        $sql_stat = $data[$split_index]['pct'];
        $difference = abs($sql_stat - $stat);
        assertTrue(
            ($difference < STAT_DIFFERENCE),
            $game_id,
            $split,
            "$split_index error: $sql_stat !== $stat for $player_id in $season"
        );
    }
}

function validatePitching($game, $home_away) {
    $game_id = $game['gameid'];
    $pitching_data = json_decode($game["pitching_$home_away"], true);
    $pitcher_id = $pitching_data['player_id'];
    validatePitcher($game_id, $pitcher_id, $home_away);
    // Validate Pitcher Splits
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
    foreach ($splits as $split) {
        $stats = $pitching_data['pitcher_vs_batter'][$split];
        validateSplit($stats, $pitcher_id, $split, $game_id, PITCHER);
    }
}

$days_tested = array();
$test_days = array();
for ($i = 0; $i < $numTestDates; $i++) {
    $rand_season = rand(1950, 2013);
    $rand_month = formatDayMonth(rand(4, 9));
    $rand_day = formatDayMonth(rand(1, 31));
    $rand_ds = "'$rand_season-$rand_month-$rand_day'";
    $test_days[$rand_ds] = $rand_ds;
}
// Override $test_days for now since all data isn't filled
$test_days = array(
    //"'1950-04-27'" => "'1950-04-27'",
    "'1950-05-02'" => "'1950-05-02'"
);
//////////////////////////////////////////////////////////

$test_days = implode(',', $test_days);
$sim_input = pullSimInput($test_days);
if (!isset($sim_input)) {
    exit("No Valid Game Days in $test_days \n");
}
foreach ($sim_input as $game) {
    // Validate pitchers.
    validatePitching($game, HOME_ABBR);
    validatePitching($game, AWAY_ABBR);
    $days_tested[$game['game_date']] = $game['game_date'];
}

echo "\n \n VARS VALIDATED: ";
asort($splitsTested);
print_r($splitsTested);
$days_tested = count($days_tested);
echo "\n \n \n SUCCESS => $days_tested DAYS TESTED \n \n \n \n";

?>
