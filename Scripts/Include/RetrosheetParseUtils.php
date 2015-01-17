<?php
//Copyright 2014, Saber Tooth Ventures, LLC

include('/Users/constants.php');
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
}

?>
