<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ .'/../Constants/Teams.php';

class RetrosheetParseUtils {

    private $gameidExceptions = array(
        'CLE2000-09-25' => 'MIN'
    );

    public static function convertRetroDateToDs($season, $date) {
        $month = substr($date, 0, 2);
        $day = substr($date, -2);
        return "$season-$month-$day";
    }

    public static function convertDsToRetroDate($ds) {
        return return_between($ds, "-", "-", EXCL) . substr($ds, -2);
    }

    public static function updateSeasonVars($season, $season_vars, $table) {
        $prev_season = $season - 1;
        if ($season > $season_vars['start_script']) {
            $season_vars['previous_end'] =
                ds_modify($season_vars['season_end'], '+1 day');
            $season_vars['previous'] = $prev_season;
        }

        $season_sql =
            "SELECT min(ds) as start,
                max(ds) as end,
                season
            FROM $table
            WHERE season in($season,$prev_season)
            GROUP BY season";
        $season_dates = exe_sql(DATABASE, $season_sql);
        $season_dates = index_by($season_dates, 'season');

        $season_vars = array_merge(
            $season_vars,
            array(
                'season_start' => $season_dates[$season]['start'],
                'season_end' => $season_dates[$season]['end']
            )
        );
        if (isset($season_dates[$prev_season])) {
            $season_vars['previous_season_start'] =
                $season_dates[$prev_season]['start'];
            $season_vars['previous_season_end'] =
                $season_dates[$prev_season]['end'];
        }
        return $season_vars;
    }

    public static function getJoeAverageStats($season) {
        $sql =
            "SELECT *
            FROM historical_joe_average
            WHERE season = $season";
        $data = exe_sql(DATABASE, $sql);
        return reset($data);
    }

    public static function getGameID(
        $ds, 
        $home, 
        $away,
        $gamenum = RetrosheetGameTypes::SINGLE_GAME
    ) {
        $season = substr($ds, 0, 4);
        $month = return_between($ds, "-", "-", EXCL);
        $day = substr($ds, -2);
        // Modify exception games (i.e. where there is a double
        // header for one team with different opponents.
        if (idx($gameidExceptions, $home.$ds)) {
            $gamenum = $away === $gameidExceptions[$home.$ds]
                ? RetrosheetGameTypes::DOUBLE_HEADER_SECOND
                : RetrosheetGameTypes::DOUBLE_HEADER_FIRST;
        }
        $home = Teams::getRetrosheetTeamAbbr($home, $season);
        $gameid = $home.$season.$month.$day.$gamenum;
        return $gameid;
    }

    public static function getPlateAppearanceQuery(
        $player_where,
        $where,
        $season_where
    ) {
        return
            "SELECT count(1) as plate_appearances
            FROM events
            WHERE $player_where
            AND $where
            AND event_cd in(" . implode(',', RetrosheetBatting::getAllEvents()) . ")
            AND $season_where";
    }

    public static function prepareStatsMultiInsert(
        $player_season,
        $season,
        $ds = null
    ) {
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

    // Get relief pitchers given a team, season and retro_ds. Returns a
    // comma separated list unless $output_array is set to true.
    public static function getRelieversByTeam(
        $team_id,
        $season,
        $ds
    ) {
        $day = substr($ds, -2);
        $month = substr($ds, 0, 2);
        $ds = "$season-$month-$day";
        $table = RetrosheetTables::RETROSHEET_HISTORICAL_PITCHING;
        $query =
            "SELECT DISTINCT player_id
            FROM $table
            WHERE last_team = '$team_id'
            AND pitcher_type = 'R'
            AND split = 'Total'
            AND season = $season
            AND ds = '$ds'";
        $pitchers = exe_sql(DATABASE, $query);
        $pitcher_array = array();
        foreach ($pitchers as $pitcher) {
            $pitcher_id = $pitcher['player_id'];
            $pitcher_array[$pitcher_id] = "'$pitcher_id'";
        }
        return $pitcher_array;
    }

    public static function getDefaultVars(
        $default_step,
        $game_id,
        $sql_data,
        $pitcher_type = null
    ) {
        $total_where = $pitcher_type
            ? "PITCHER_TYPE = '$pitcher_type'"
            : 'TRUE';
        switch ($default_step) {
            case RetrosheetDefaults::SEASON_TOTAL:
                // DEFAULT 0: Season Total Split
                $sql_data['where'] = $total_where;
                break;
            case RetrosheetDefaults::PREVIOUS_ACTUAL:
                // DEFAULT 1: Prev Year Actual Split
                $sql_data['where'] = $sql_data['original_where'];
                $sql_data['stats_year'] = RetrosheetStatsYear::PREVIOUS;
                break;
            case RetrosheetDefaults::PREVIOUS_TOTAL:
                // DEFAULT 2: Prev Year Total Split
                $sql_data['where'] = $total_where;
                break;
            case RetrosheetDefaults::CAREER_ACTUAL:
                // DEFAULT 3: Career Actual Split
                $sql_data['where'] = $sql_data['original_where'];
                $sql_data['stats_year'] = RetrosheetStatsYear::CAREER;
                break;
            case RetrosheetDefaults::CAREER_TOTAL:
                // DEFAULT 4: Career Total Split
                $sql_data['where'] = $total_where;
                break;
            case RetrosheetDefaults::JOE_AVERAGE_ACTUAL:
                // DEFAULT 5: Actual Joe Average
                $sql_data['where'] = $sql_data['original_where'];
                $sql_data['stats_year'] = RetrosheetStatsYear::PREVIOUS;
                $sql_data['player_where'] = 'TRUE';
                break;
            case RetrosheetDefaults::JOE_AVERAGE_TOTAL:
                $sql_data['where'] = $total_where;
                break;
        }
        return $sql_data;
    }

    public static function addDefaultData(
        $default_step,
        $player_id,
        $date,
        $split,
        $player_season,
        $player_previous,
        $player_career,
        $joeAverage,
        $joe_average_type
    ) {

        $default_data = null;
        $is_joe_average = false;
        switch ($default_step) {
            case RetrosheetDefaults::SEASON_TOTAL:
                $default_data =
                    elvis($player_season[$player_id][$date][RetrosheetSplits::TOTAL]);
                break;
            case RetrosheetDefaults::PREVIOUS_ACTUAL:
                $default_data =
                    elvis($player_previous[$player_id][$date][$split]);
                break;
            case RetrosheetDefaults::PREVIOUS_TOTAL:
                $default_data =
                    elvis($player_previous[$player_id][$date][RetrosheetSplits::TOTAL]);
                break;
            case RetrosheetDefaults::CAREER_ACTUAL:
                $default_data =
                    elvis($player_career[$player_id][$date][$split]);
                break;
            case RetrosheetDefaults::CAREER_TOTAL:
                $default_data =
                    elvis($player_career[$player_id][$date][RetrosheetSplits::TOTAL]);
                break;
            case RetrosheetDefaults::JOE_AVERAGE_ACTUAL:
                $default_data = elvis($joeAverage[$joe_average_type][$split]);
                $is_joe_average = true;
                break;
            // Note: RetrosheetDefaults::JOE_AVERAGE_TOTAL doesn't exist here
            // since the JOE_AVERAGE_ACTUAL is populated with TOTAL if null.
        }
        $pas = idx($default_data, RetrosheetDefaults::PLATE_APPEARANCES, 0);
        return
            ($is_joe_average
            || $pas >= RetrosheetDefaults::MIN_PLATE_APPEARANCE)
            ? $default_data : null;
    }

    // Returns first two and last two dates in season plus one
    // additional random game mid-seeason.
    public static function getValidatorDates($season) {
        $sql =
            "SELECT min(game_date) as min, max(game_date) as max
            FROM sim_input
            WHERE season = $season
            and stats_year = 'previous'";
        $data = reset(exe_sql(DATABASE, $sql));
        $min = $data['min'];
        $max = $data['max'];
        $rand = rand(60,120);
        $test_days = array(
            $min,
            ds_modify($min, '+1 day'),
            ds_modify($min, "+$rand day"),
            ds_modify($max, '-1 day'),
            $max
        );
        $final_days = array();
        foreach ($test_days as $days) {
            $final_days[] = "'$days'";
        }
        return $final_days;
    }

    public static function updateMissingSplits(
        $player_season,
        $joeAverage,
        $playerType,
        $player_previous = null,
        $player_career = null,
        $pitcher_type = null
    ) {
        if (!isset($player_season)) {
            return null;
        }
        if ($playerType == RetrosheetConstants::BATTING) {
            $joe_average_type = RetrosheetJoeAverage::BATTER_STATS;
        } else {
            if (!$pitcher_type) {
                throw new Exception(
                    'Must specify starter or reliever for a non-batter average'
                );
            }
            $joe_average_type =
                $pitcher_type === RetrosheetConstants::RELIEVER
                ? RetrosheetJoeAverage::RELIEVER_STATS
                : RetrosheetJoeAverage::STARTER_STATS;
        }
        $splits = RetrosheetSplits::getSplits();
        foreach ($player_season as $player_id => $dates) {
            foreach ($dates as $date => $split_data) {
                foreach ($splits as $split) {
                    $default_step = 0;
                    $is_filled =
                        isset($player_season[$player_id][$date][$split]);
                    while (!$is_filled) {
                        $player_season[$player_id][$date][$split] =
                            self::addDefaultData(
                                $default_step,
                                $player_id,
                                $date,
                                $split,
                                $player_season,
                                $player_previous,
                                $player_career,
                                $joeAverage,
                                $joe_average_type
                            );
                        $is_filled =
                            isset($player_season[$player_id][$date][$split]);
                        $default_step += 1;
                        $player_season[$player_id][$date][$split]['plate_appearances'] = 0;
                    }
                }
            }
        }
        return $player_season;
    }

    public static function getEventsByBatterQuery(
        $player_where,
        $where,
        $season_where
    ) {

        return
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
            WHERE $player_where
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
    }

    public static function getSeasonStartEnd($season) {
        $season_sql = sprintf(
            "SELECT min(substr(game_id,8,4)) as start,
                max(substr(game_id,8,4)) as end,
                season
            FROM events
            WHERE season = %d
            GROUP BY season",
            $season
        );
        $season_dates = reset(exe_sql(DATABASE, $season_sql));
        $season_start = self::convertRetroDateToDs(
            $season,
            $season_dates['start']
        );
        $season_end = self::convertRetroDateToDs(
            $season,
            $season_dates['end']
        );
        return array($season_start, $season_end);
    }

    public static function getSeasonWhere($stats_year, $season, $ds = 1231) {
        switch ($stats_year) {
            case RetrosheetStatsYear::SEASON:
                return "(season = $season AND substr(game_id,8,4) < $ds)";
                break;
            case RetrosheetStatsYear::CAREER:
                return "((season = $season AND substr(game_id,8,4) < $ds)
                    OR season < $season)";
                break;
            case RetrosheetStatsYear::PREVIOUS:
                $prev_season = $season - 1;
                return "season = $prev_season";
                break;
        }
    }

    public static function getWhereBySplit(
        $split,
        $bat_home_id = RetrosheetHomeAway::HOME,
        $opp_hand = 'PIT_HAND_CD',
        $pitcher_type = null
    ) {
        switch ($split) {
            case RetrosheetSplits::TOTAL:
                $where = 'TRUE';
                break;
            case RetrosheetSplits::HOME:
                $where = "BAT_HOME_ID = $bat_home_id";
                break;
            case RetrosheetSplits::AWAY:
                $away_id = 1 - $bat_home_id;
                $where = "BAT_HOME_ID = $away_id";
                break;
            case RetrosheetSplits::VSLEFT:
                $where = "$opp_hand = 'L'";
                break;
            case RetrosheetSplits::VSRIGHT:
                $where = "$opp_hand = 'R'";
                break;
            case RetrosheetSplits::NONEON:
                $where = "START_BASES_CD = " . RetrosheetBases::BASES_EMPTY;
                break;
            case RetrosheetSplits::RUNNERSON:
                $where = "START_BASES_CD = " . RetrosheetBases::FIRST;
                break;
            case RetrosheetSplits::SCORINGPOS:
                $where = "START_BASES_CD >= " . RetrosheetBases::SECOND .
                    " AND START_BASES_CD != " . RetrosheetBases::BASES_LOADED .
                    " AND OUTS_CT < 2";
                break;
            case RetrosheetSplits::SCORINGPOS2OUT:
                $where = "START_BASES_CD >= " . RetrosheetBases::SECOND .
                " AND OUTS_CT = 2";
                break;
            case RetrosheetSplits::BASESLOADED:
                $where = "START_BASES_CD = " . RetrosheetBases::BASES_LOADED .
                    " AND OUTS_CT < 2";
                break;
        }
        // If starter/reliever is passed in append to WHERE.
        if ($pitcher_type === 'S' || $pitcher_type === 'R') {
            $where .= " AND PITCHER_TYPE = '$pitcher_type'";
        }
        return $where;
    }

    private static function getMaxRetrosheetTableDate($table) {
        $sql = sprintf(
            'SELECT max(ds) as ds
            FROM %s
            WHERE season >= 2013',
            $table
        );
        $data = exe_sql(DATABASE, $sql);
        $data = reset($data);
        $ds = idx($data, 'ds');
        return array($ds, substr($ds, 0, 4));
    }

    public static function getHistoricalBattingStats($test_player = null) {
        list($ds, $season) = self::getMaxRetrosheetTableDate(
            RetrosheetTables::RETROSHEET_HISTORICAL_BATTING_CAREER
        );
        $sql = sprintf(
            "SELECT *
            FROM %s
            WHERE season = %d
            AND ds = '%s'",
            RetrosheetTables::RETROSHEET_HISTORICAL_BATTING_CAREER,
            $season,
            $ds
        );
        if ($test_player !== null) {
            $sql .= sprintf(
                " AND player_id = '%s'",
                $test_player
            );
        }
        $data = exe_sql(DATABASE, $sql);
        return safe_index_by($data, 'player_id', 'split');
    }
}

?>
