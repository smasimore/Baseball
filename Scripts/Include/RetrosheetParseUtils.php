<?php
//Copyright 2014, Saber Tooth Ventures, LLC

if (!defined('HOME_PATH')) {
    include('/Users/constants.php');
}
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');
include(HOME_PATH.'Scripts/Include/RetrosheetConstants.php');

class RetrosheetParseUtils {

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
            "SELECT stats
            FROM historical_joe_average
            WHERE season = $season";
        return reset(exe_sql(DATABASE, $sql));
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

    public static function addDefaultData(
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
                $default_data =
                    elvis($player_career[$player_id][$date][$split]);
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

    public static function updateMissingSplits(
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
                                $average_season,
                                $player_prev_season,
                                $player_career,
                                $average_prev_season,
                                $average_career
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

    public static function getSeasonWhere($stats_year, $season, $ds = 1231) {
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

    public static function getWhereBySplit(
        $split,
        $bat_home_id = RetrosheetHomeAway::HOME,
        $opp_hand = 'PIT_HAND_CD'
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
        return $where;
    }
}

?>
