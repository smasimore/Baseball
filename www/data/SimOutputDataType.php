<?php

include_once 'DataType.php';
include_once __DIR__ . '/../includes/SimConstants.php';

final class SimOutputDataType extends DataType {

    private $gameDate;

    protected function getTable() {
        return 'sim_output';
    }

    protected function getColumns() {
        return array('gameid', 'home', 'away', 'home_win_pct');
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
            'weights' => 'b_total_100',
            'stats_year' => StatsYear::CAREER,
            'stats_type' => StatsType::BASIC,
            'weights_mutator' => null, // will this work?
            'analysis_runs' => 5000,
            'use_reliever' => false
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
