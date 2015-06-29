<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'DataType.php';
include_once __DIR__ . '/../Constants/SQLWhereParams.php';

final class LiveScoresDataType extends DataType {

    private $gameDate;

    final protected function getTable() {
        return Tables::LIVE_SCORES;
    }

    final protected function getColumns() {
        return array(
            'gameid',
            'status',
            'home_score',
            'away_score',
            'home',
            'away'
        );
    }

    final protected function getParams() {
        if ($this->gameDate === null) {
            throw new Exception('Game date must be set.');
        }
        return array(
            SQLWhereParams::EQUAL => array(
                'game_date' => $this->gameDate
            ),
            SQLWhereParams::NOT_EQUAL => array(
                'status' => 'Postponed'
            )
        );
    }

    final protected function formatData() {
        $parsed_data = index_by($this->data, 'gameid');
        $parsed_data = array();
        foreach ($indexed_data as $gameid => $game) {
            $status = $game['status'];
            switch (true) {
                case strpos($status, 'AM') !== false:
                case strpos($status, 'PM') !== false:
                case $status === 'Not Started':
                    $game['status_code'] = GameStatus::NOT_STARTED;
                    break;
                case $status === 'Final':
                case strpos($status, 'F/') !== false:
                case strpos($status, 'Final/') !== false:
                    $game['status_code'] = GameStatus::FINISHED;
                    $game['winner'] = $this->getGameWinner($game);
                    break;
                default:
                    $game['status_code'] = GameStatus::STARTED;
                    break;
            }
            $parsed_data[$gameid] = $game;
        }
        $this->data = $parsed_data;
    }


    final public function setGameDate($ds) {
        $this->gameDate = $ds;
        return $this;
    }

    private function getGameWinner($game) {
        return $game['home_score'] > $game['away_score']
            ? $game['home'] : $game['away'];
    }
}
