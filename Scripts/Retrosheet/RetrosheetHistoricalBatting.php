<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ .'/../../Models/Include/RetrosheetInclude.php';
include_once __DIR__ .'/../../Models/Traits/TScriptWithInsert.php';
include_once __DIR__ .'/../Daily/ScriptWithWrite.php';

class RetrosheetHistoricalBatting extends ScriptWithWrite {

    use TScriptWithInsert;

    private $ds;
    private $data;

    public function gen($ds) {
        $this->data = $this->genBattingStatsFromRetrosheet($ds);

        // For the purpose of this script we'll use the day after the games
        // occured as the insert date.
        // $this->ds = DateTimeUtils::addDay($ds);
        // Will use later.
    }

    protected function getWriteData() {
        return $this->data;
    }

    protected function getWriteTable() {
        return 'test';
    }

    // Function to initially scrape daily data from Retrosheet:events.
    private function genBattingStatsFromRetrosheet($ds) {
        $season = DateTimeUtils::getSeasonFromDate($ds);
        $retro_ds = RetrosheetParseUtils::convertDsToRetroDate($ds);
        $sql = sprintf(
            "SELECT
                count(1) as num_events,
                a.event_name,
                a.season,
                a.ds,
                a.bat_id AS player_id,
                a.bat_hand_cd,
                a.home_away,
                a.outs,
                a.situation,
                a.winning,
                a.pit_id,
                a.pit_hand_cd AS vs_hand
            FROM
                (SELECT
                    CASE
                        WHEN (event_cd in(2,19) AND battedball_cd = 'G') THEN 'ground_out'
                        WHEN (event_cd in(2,19) AND battedball_cd != 'G') THEN 'fly_out'
                        WHEN event_cd = 3 THEN 'strikeout'
                        WHEN event_cd in(14,15,16) THEN 'walk'
                        WHEN event_cd = 20 THEN 'single'
                        WHEN event_cd = 21 THEN 'double'
                        WHEN event_cd = 22 THEN 'triple'
                        WHEN event_cd = 23 THEN 'home_run'
                        ELSE 'other'
                        END AS event_name,
                    season,
                    concat(substr(game_id,4,4), '-', substr(game_id,8,2), '-', substr(game_id,10,2)) AS ds,
                    bat_id,
                    pit_id,
                    outs_ct as outs,
                    CASE
                        WHEN bat_home_id = 1 THEN 'Home'
                        ELSE 'Away'
                        END AS home_away,
                    CASE
                        WHEN bat_hand_cd = 'L' then 'L'
                        WHEN bat_hand_cd = 'R' then 'R'
                        ELSE 'Unknown'
                        END as bat_hand_cd,
                    CASE
                        WHEN home_score_ct = away_score_ct then 'Tied'
                        WHEN bat_home_id = 1 AND home_score_ct > away_score_ct THEN 'Winning'
                        WHEN bat_home_id = 1 AND home_score_ct < away_score_ct THEN 'Losing'
                        WHEN bat_home_id = 0 AND home_score_ct < away_score_ct THEN 'Winning'
                        WHEN bat_home_id = 0 AND home_score_ct > away_score_ct THEN 'Losing'
                        END as winning,
                    CASE
                        WHEN pit_hand_cd = 'R' then 'VsRight'
                        WHEN pit_hand_cd = 'L' then 'VsLeft'
                        ELSE 'VsUnknown'
                        END as pit_hand_cd,
                    CASE
                        WHEN start_bases_cd = 0 THEN 'NoneOn'
                        WHEN start_bases_cd = 1 THEN 'RunnersOn'
                        WHEN (start_bases_cd = 7 AND outs_ct != 2) THEN 'BasesLoaded'
                        WHEN (start_bases_cd > 1 AND outs_ct = 2) THEN 'ScoringPos2Out'
                        ELSE 'ScoringPos'
                        END AS situation
                FROM events
                WHERE season = %d and substr(game_id,8,4) = '%s') a
            GROUP BY
               a.event_name,
               a.season,
               a.ds,
               a.bat_id,
               a.bat_hand_cd,
               a.home_away,
               a.outs,
               a.situation,
               a.winning,
               a.pit_id,
               a.pit_hand_cd",
            $season,
            $retro_ds
        );
        return exe_sql(
            DATABASE,
            $sql
        );
    }
}

?>
