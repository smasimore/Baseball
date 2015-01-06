<?php
//Copyright 2014, Saber Tooth Ventures, LLC

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

        $season_vars = array(
            'season_start' => $season_dates[$season]['start'],
            'season_end' => $season_dates[$season]['end'],
            'previous_season_start' => $season_dates[$prev_season]['start'],
            'previous_season_end' => $season_dates[$prev_season]['end']
        );
        return $season_vars;
    }
}

?>
