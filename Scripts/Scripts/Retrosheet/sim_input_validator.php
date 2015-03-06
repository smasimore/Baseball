<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/RetrosheetInclude.php');
include(HOME_PATH.'Scripts/Include/Teams.php');

const HOME = 'home';
const AWAY = 'away';
const HOME_ABBR = 'h';
const AWAY_ABBR = 'a';
const STAT_DIFFERENCE = .001;
const SEASON_GAP_EXCEPTION = 'Player Has A Gap Of > 5 Years';

$numTestDates = 5;
$maxYear = 2014;
$minYear = 1990;
$statsYears = array(
    RetrosheetStatsYear::CAREER,
    RetrosheetStatsYear::SEASON,
    RetrosheetStatsYear::PREVIOUS
);
$statsYear = null;
$statsType = RetrosheetStatsType::BASIC;
$silenceSuccess = true;
$skipJoeAverage = false;
$joeAverage = null;
$splitsTested = array();
$cache = array();
$splits = RetrosheetSplits::getSplits();
$defaultMap = array(
    RetrosheetStatsYear::CAREER => array(
        RetrosheetDefaults::CAREER_TOTAL,
        RetrosheetDefaults::JOE_AVERAGE_ACTUAL,
        RetrosheetDefaults::JOE_AVERAGE_TOTAL
    ),
    RetrosheetStatsYear::PREVIOUS => array(
        RetrosheetDefaults::PREVIOUS_TOTAL,
        RetrosheetDefaults::CAREER_ACTUAL,
        RetrosheetDefaults::CAREER_TOTAL,
        RetrosheetDefaults::JOE_AVERAGE_ACTUAL,
        RetrosheetDefaults::JOE_AVERAGE_TOTAL
    )
);

function pullSimInput($test_days, $seasons) {
    global $statsYear, $statsType;
    echo "Pulling Sim Data... \n";
    $sql = "SELECT *
        FROM " . RetrosheetTables::SIM_INPUT . "
        WHERE season in($seasons)
        AND game_date in($test_days)
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
    global $statsYear, $cache;
    $season = substr($game_id, 3, 4);
    $ds = substr($game_id, 7, 4);
    // Use $statsYear unless overwritten in function input.
    $stats_year = $stats_year ?: $statsYear;
    $season_where = RetrosheetParseUtils::getSeasonWhere(
        $stats_year,
        $season,
        $ds
    );
    // If a where clause is given in function input use it, otherwise
    // omit where clause using WHERE = 'TRUE'.
    $where = $where ?: 'TRUE';
    $sql = RetrosheetParseUtils::getPlateAppearanceQuery(
        $player_where,
        $where,
        $season_where
    );
    $sql_hash = md5($sql);
    if (isset($cache['plateAppearances'][$sql_hash])) {
        $data = $cache['plateAppearances'][$sql_hash];
    } else {
        $data = reset(exe_sql(DATABASE, $sql));
        $cache['plateAppearances'][$sql_hash] = $data;
    }
    return idx($data, 'plate_appearances', 0);
}

function checkPlayerYearGap($player, $type_id, $exception_where) {
    $sql = "SELECT DISTINCT season
        FROM " . RetrosheetTables::EVENTS .
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
    return null;
}

function checkException($game_id, $player, $type_id, $exception_where) {
    $season = substr($game_id, 3, 4);
    $retro_ds = substr($game_id, 7, 4);
    // Is the exception for a (reliever) team? If so...
    // 1) Do any players have a 5+ year gap in their history?
    $teams = Teams::getAllRetrosheetTeamAbbrs($season);
    if (in_array($player, $teams)) {
        $relievers = RetrosheetParseUtils::getRelieversByTeam(
            $player,
            $season,
            $retro_ds
        );
        $relievers = $relievers ? array_keys($relievers) : null;
        foreach ($relievers as $reliever) {
            $exception =
                checkPlayerYearGap($reliever, $type_id, $exception_where);
            // If there is an exception return that, otherwise keep checking.
            if ($exception) {
                return $exception;
            }
        }
        return null;
    } else {
        // If exception is for a player...
        // 2) Does this player have a 5+ year gap in their history?
        $exception = checkPlayerYearGap($player, $type_id, $exception_where);
        return $exception;
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
        $type_id = elvis($type_id, RetrosheetEventColumns::BAT_ID);
        $exception = checkException(
            $game_id,
            $player,
            $type_id,
            $exception_where
        );
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
        FROM " . RetrosheetTables::GAMES .
        " WHERE game_id = '$game_id'";
    return reset(exe_sql(DATABASE, $sql));
}

function validateBatter($lineup, $batter_id, $home_away, $lineup_pos) {
    $home_away = ($home_away == HOME_ABBR) ? HOME : AWAY;
    $game_id = $lineup['GAME_ID'];
    $batter_index =
        strtoupper($home_away) . '_LINEUP' . $lineup_pos . '_BAT_ID';
    $actual_batter = $lineup[$batter_index];
    // For Joe Average ensure that batter doesn't meet MIN_PLATE_APPEARANCE.
    if ($batter_id == RetrosheetJoeAverage::JOE_AVERAGE) {
        $pas = pullPlateAppearances(
            "bat_id = '$actual_batter'",
            $game_id
        );
        assertTrue(
            ($pas < RetrosheetDefaults::MIN_PLATE_APPEARANCE),
            $game_id,
            $actual_batter,
            'batter_joe_average',
            "$actual_batter !== 'joe_average'",
            RetrosheetEventColumns::BAT_ID
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
        ($home_away == HOME_ABBR)
        ? RetrosheetEventColumns::HOME_START_PIT_ID : 'AWAY_START_PIT_ID';
    $sql = "SELECT $pitcher as pitcher
        FROM " . RetrosheetTables::GAMES .
        " WHERE game_id = '$game_id'";
    $data = reset(exe_sql(DATABASE, $sql));
    $actual_pitcher = $data['pitcher'];
    // For Joe Average ensure that pitcher doesn't meet MIN_PLATE_APPEARANCE.
    if ($pitcher_id == RetrosheetJoeAverage::JOE_AVERAGE) {
        $pas = pullPlateAppearances(
            "pit_id = '$actual_pitcher'",
            $game_id
        );
        assertTrue(
            ($pas < RetrosheetDefaults::MIN_PLATE_APPEARANCE),
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

function validateSplit(
    $stats,
    $player_id,
    $split,
    $game_id,
    $type
) {
    global $statsYear, $skipJoeAverage, $cache, $defaultMap;
    $is_joe_average = $player_id == RetrosheetJoeAverage::JOE_AVERAGE;
    $season = substr($game_id, 3, 4);
    $ds = substr($game_id, 7, 4);
    // Unlike batters, when a pitcher is home the bat_id = 0 since it
    // refers to the opposing batter.
    switch ($type) {
        case RetrosheetConstants::STARTER:
            $type_id = RetrosheetEventColumns::PIT_ID;
            $player_where = "$type_id = '$player_id'";
            $opp_hand = RetrosheetEventColumns::BAT_HAND_CD;
            $bat_home_id = RetrosheetHomeAway::AWAY;
            break;
        case RetrosheetConstants::RELIEVER:
            $type_id = RetrosheetEventColumns::PIT_ID;
            // Note: $player_id is LAST_TEAM for the reliever pull.
            $relievers =
                RetrosheetParseUtils::getRelieversByTeam(
                    $player_id,
                    $season,
                    $ds
                );
            $relievers = $relievers ? implode(',', $relievers) : null;
            if ($relievers) {
                $player_where = "$type_id in($relievers)";
            } else {
                $player_where = 'TRUE';
                $is_joe_average = 1;
            }
            $opp_hand = RetrosheetEventColumns::BAT_HAND_CD;
            $bat_home_id = RetrosheetHomeAway::AWAY;
            break;
        case RetrosheetConstants::BATTER:
            $type_id = RetrosheetEventColumns::BAT_ID;
            $player_where = "$type_id = '$player_id'";
            $opp_hand = RetrosheetEventColumns::PIT_HAND_CD;
            $bat_home_id = RetrosheetHomeAway::HOME;
            break;
    }
    // Create a WHERE statement based on split
    $pitcher_type = null;
    if ($type !== RetrosheetConstants::BATTER) {
        $pitcher_type = $type === RetrosheetConstants::STARTER ? 'S' : 'R';
    }
    $where = RetrosheetParseUtils::getWhereBySplit(
        $split,
        $bat_home_id,
        $opp_hand,
        $pitcher_type
    );
    $pas = idx($stats, 'plate_appearances', 0);
    $default_step = 0;
    $sql_data = array(
        'player_where' => $player_where,
        'stats_year' => $statsYear,
        'where' => $where,
        'original_where' => $where
    );
    $is_filled =
        ($pas >= RetrosheetDefaults::MIN_PLATE_APPEARANCE && !$is_joe_average);
    while (!$is_filled) {
        $default_routing = $statsYear == RetrosheetStatsYear::SEASON
            ? $default_step : $defaultMap[$statsYear][$default_step];
        // If player is Joe Average skip non Joe Average Defaults
        if ($is_joe_average) {
            if ($default_routing <
                RetrosheetDefaults::JOE_AVERAGE_ACTUAL) {
                $default_step += 1;
                continue;
            }
        }
        $sql_data = RetrosheetParseUtils::getDefaultVars(
            $default_routing,
            $game_id,
            $sql_data,
            $pitcher_type
        );
        $pas = pullPlateAppearances(
            $sql_data['player_where'],
            $game_id,
            $sql_data['stats_year'],
            $sql_data['where']
        );
        $is_filled = $pas >= RetrosheetDefaults::MIN_PLATE_APPEARANCE;
        $default_step += 1;
    }
    // Update SQL params if they've changed in defaulting.
    $where = $sql_data['where'];
    $player_where = $sql_data['player_where'];
    $season_where = RetrosheetParseUtils::getSeasonWhere(
        $sql_data['stats_year'],
        $season,
        $ds
    );
    $sql = RetrosheetParseUtils::getEventsByBatterQuery(
        $player_where,
        $where,
        $season_where
    );
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
            if ($split_name === 'plate_appearances'
                || $split_name === 'player_id'
                || $split_name === 'avg_innings'
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
    $home_away_full = $home_away === HOME_ABBR ? HOME : AWAY;
    $team = $game[$home_away_full];
    $pitching_data = json_decode($game["pitching_$home_away"], true);
    $pitcher_id = $pitching_data['player_id'];
    validatePitcher($game_id, $pitcher_id, $home_away);
    foreach ($splits as $split) {
        $starter_stats = $pitching_data['pitcher_vs_batter'][$split];
        validateSplit(
            $starter_stats,
            $pitcher_id,
            $split,
            $game_id,
            RetrosheetConstants::STARTER
        );
        $reliever_stats = $pitching_data['reliever_vs_batter'][$split];
        validateSplit(
            $reliever_stats,
            $team,
            $split,
            $game_id,
            RetrosheetConstants::RELIEVER
        );
    }
}

function validateBatting($game, $home_away) {
    global $splits;
    $game_id = $game['gameid'];
    $batting_data = json_decode($game["batting_$home_away"], true);
    $lineup = pullLineup($game_id);
    for ($i = 1; $i < 10; $i++) {
        $player_data = $batting_data[$i];
        $batter_id = isset($player_data['Total']['player_id'])
            ? $player_data['Total']['player_id']
            : RetrosheetJoeAverage::JOE_AVERAGE;
        validateBatter($lineup, $batter_id, $home_away, $i);
        foreach ($splits as $split) {
            $stats = $player_data[$split];
            validateSplit(
                $stats,
                $batter_id,
                $split,
                $game_id,
                RetrosheetConstants::BATTER
            );
        }
    }
}

$days_tested = array();
$test_days = array();
for ($test_season = $minYear; $test_season < $maxYear; $test_season++) {
    echo "Season is $test_season \n";
    $test_days = RetrosheetParseUtils::getValidatorDates($test_season);
    $test_days = implode(',', $test_days);
    foreach ($statsYears as $type) {
        $statsYear = $type;
        echo "Stats Year is $statsYear \n";
        $sim_input = pullSimInput($test_days, $test_season);
        if (!isset($sim_input)) {
            exit("No Valid Game Days in $test_days \n");
        }
        $start_message = "\n Testing...";
        foreach ($sim_input as $game) {
            echo $start_message . $game['gameid'] . "\n";
            $joeAverage =
                RetrosheetParseUtils::getJoeAverageStats($game['season']);
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
    }
}

echo "\n \n VARS VALIDATED: ";
asort($splitsTested);
print_r($splitsTested);
$num_days_tested = count($days_tested);
echo "\n \n \n SUCCESS => $num_days_tested DAYS TESTED \n \n";
print_r($days_tested);

?>
