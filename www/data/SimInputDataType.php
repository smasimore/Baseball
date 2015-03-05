<?php

include_once 'DataType.php';
include_once __DIR__ . '/../includes/SimConstants.php';

final class SimInputDataType extends DataType {

    private $gameDate;

    protected function getTable() {
        return 'sim_input';
    }

    protected function getColumns() {
        return array(
            'gameid',
            'pitching_h',
            'pitching_a',
            'batting_h',
            'batting_a'
        );
    }

    protected function getParams() {
        if ($this->gameDate === null) {
            throw new Exception('Game date must be set.');
        }

        $date = DateTime::createFromFormat('Y-m-d', $this->gameDate);
        $season = $date->format('Y');

        return array(
            'game_date' => $this->gameDate,
            'season' => $season,
            'stats_year' => StatsYear::CAREER,
            'stats_type' => StatsType::BASIC
        );
    }

    public function setGameDate($game_date) {
        $this->gameDate = $game_date;
        return $this;
    }

    public function getData() {
        return $this->data;
    }
}
