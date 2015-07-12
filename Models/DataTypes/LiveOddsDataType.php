<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'DataType.php';

final class LiveOddsDataType extends DataType {

    private $gameDate;

    final protected function getTable() {
        return Tables::LIVE_ODDS;
    }

    final protected function getColumns() {
        return array(
            'gameid',
            'home_odds',
            'away_odds',
            'game_date',
            'game_time',
            'ts'
        );
    }

    final protected function getParams() {
        if ($this->gameDate === null) {
            throw new Exception('Game date must be set.');
        }
        return array(
            SQLWhereParams::EQUAL => array(
                'game_date' => $this->gameDate,
                'ds' => $this->gameDate
            )
        );
    }

    final public function setGameDate($ds) {
        $this->gameDate = $ds;
        return $this;
    }

    final public function getStartingOdds($game_id, $away_team = true) {
        // Odds ordered by time.
        $team = $away_team ? 'away' : 'home';
        foreach ($this->data as $game_odds) {
            if ($game_odds['gameid'] === $game_id) {
                return $game_odds[sprintf('%s_odds', $team)];
            }
        }

        return null;
    }

    final public function getMostRecentOdds($game_id, $away_team = true) {
        // Odds ordered by time.
        $team = $away_team ? 'away' : 'home';
        $odds = null;
        foreach ($this->data as $game_odds) {
            if ($game_odds['gameid'] === $game_id) {
                $odds = $game_odds[sprintf('%s_odds', $team)];
            }
        }

        return $odds;
    }
}
