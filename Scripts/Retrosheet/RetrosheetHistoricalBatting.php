<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ .'/../../Models/Include/RetrosheetInclude.php';
include_once __DIR__ .'/../../Models/Traits/TScriptWithInsert.php';
include_once __DIR__ .'/../Daily/ScriptWithWrite.php';

class RetrosheetHistoricalBatting extends ScriptWithWrite {

    use TScriptWithInsert;

    private $data;

    public function gen($ds) {
        // Reset $this->data each new season. Start/end dates should correspond
        // with seasons. Check to make sure this is not improperly used.
        $start_season = DateTimeUtils::getSeasonFromDate(
            $this->getStartDate()
        );
        $end_season = DateTimeUtils::getSeasonFromDate(
            $this->getEndDate()
        );
        if ($start_season !== $end_season) {
            throw new Exception(
                'Start Date And End Date Should Correspond With ' .
                'season start/end. Make sure you are using this script ' .
                'correctly'
            );
        }
        if ($ds === $this->getStartDate()) {
            $this->data = null;
        }
        $daily_stats = $this->genBattingStatsFromRetrosheet($ds);
        $this->addDailyStatsToSeasonBatting($daily_stats);
        $this->prepareInsertData($ds);
    }

    protected function getWriteData() {
        return $this->data;
    }

    protected function getWriteTable() {
        return Tables::RETROSHEET_BATTING;
    }

    private function prepareInsertData($ds) {
        // Set LDOS flag if the data represents the last day of season.
        $is_ldos = $ds === $this->getEndDate();

        // To mirror live scraping, use the day after the games occured
        // as the insert date.
        $insert_ds = DateTimeUtils::addDay($ds);

        foreach ($this->data as $key => $player) {
            $this->data[$key]['ds'] = $insert_ds;
            $this->data[$key]['is_ldos'] = (int)$is_ldos;
        }
    }

    private function addDailyStatsToSeasonBatting($daily_stats) {
        foreach ($daily_stats as $stats) {
            $player_key = $this->createPlayerArrayKey($stats);
            $num_events = $stats['num_events'];
            $event_name = $stats['event_name'];
            if ($event_name === 'other') {
                continue;
            }
            if (idx($this->data, $player_key) === null) {
                $this->data[$player_key] = array_merge(
                    $stats,
                    array(
                        Batting::SINGLES => 0,
                        Batting::DOUBLES => 0,
                        Batting::TRIPLES => 0,
                        Batting::HOME_RUNS => 0,
                        Batting::WALKS => 0,
                        Batting::STRIKEOUTS => 0,
                        Batting::FLY_OUTS => 0,
                        Batting::GROUND_OUTS => 0,
                        Batting::PLATE_APPEARANCES => 0
                    )
                );
                // Since we array_merge with raw data remove columns we don't
                // need in final output.
                unset(
                    $this->data[$player_key]['num_events'],
                    $this->data[$player_key]['event_name']
                );
            }

            $this->data[$player_key][sprintf('%ss', $event_name)]
                += $num_events;
            $this->data[$player_key]['plate_appearances'] += $num_events;
        }
    }

    private function createPlayerArrayKey($stats) {
        return idx($stats, 'player_id', '_') .
            idx($stats, 'bat_hand_cd', '_') .
            idx($stats, 'home_away', '_') .
            idx($stats, 'outs', '_') .
            idx($stats, 'situation', '_') .
            idx($stats, 'winning', '_') .
            idx($stats, 'pit_id', '_') .
            idx($stats, 'vs_hand', '_');
    }

    // Function to process daily data from Retrosheet:events.
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
                FROM %s
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
            Tables::RETROSHEET_EVENTS,
            $season,
            $retro_ds
        );
        return MySQL::execute($sql);
    }
}

?>
