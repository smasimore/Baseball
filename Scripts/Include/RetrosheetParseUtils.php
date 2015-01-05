<?php
//Copyright 2014, Saber Tooth Ventures, LLC

class RetrosheetParseUtils {

    public static function updateSeasonVars($season, $season_vars, $table) {
        if ($season > $season_vars['start_script']) {
            $season_vars['previous_end'] =
                ds_modify($season_vars['season_end'], '+1 day');
            $season_vars['previous'] = $season - 1;
        }
        $season_sql =
            "SELECT min(ds) as start,
                max(ds) as end,
                season
            FROM $table
            WHERE season = '$season'
            GROUP BY season";
        $season_dates = reset(exe_sql(DATABASE, $season_sql));
        $season_vars['season_start'] = $season_dates['start'];
        $season_vars['season_end'] = $season_dates['end'];
        return $season_vars;
    }

}

?>
