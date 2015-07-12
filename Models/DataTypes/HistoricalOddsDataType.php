<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'DataType.php';
include_once __DIR__ . '/../Constants/SQLWhereParams.php';

final class HistoricalOddsDataType extends DataType {

    private $seasonStart;
    private $seasonEnd;

    final protected function getTable() {
        return Tables::HISTORICAL_ODDS;
    }

    final protected function getColumns() {
        return array(
            'gameid',
            'home_odds',
            'away_odds',
            'home_pct_win',
            'away_pct_win',
            'home_team_winner',
            'season',
            'game_date'
        );
    }

    final protected function getParams() {
        if ($this->startSeason === null) {
            throw new Exception('Season must be set.');
        }

        if ($this->startSeason === $this->endSeason) {
            return array(
                SQLWhereParams::EQUAL => array('season' => $this->startSeason)
            );
        }

        return array(
            SQLWhereParams::GREATER_OR_EQUAL => array(
                'season' => $this->startSeason
            ),
            SQLWhereParams::LESS_OR_EQUAL => array(
                'season' => $this->endSeason
            )
        );
    }

    final public function setSeason($start_season, $end_season = null) {
        $this->startSeason = $start_season;
        $this->endSeason = $end_season ?: $start_season;
        return $this;
    }
}
