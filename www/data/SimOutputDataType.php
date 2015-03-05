<?php

include_once 'DataType.php';
include_once __DIR__ . '/../includes/SimConstants.php';

final class SimOutputDataType extends DataType {

    private $gameDate;

    protected function getTable() {
        return 'sim_output';
    }

    protected function getColumns() {
        return array('gameid', 'home_win_pct', 'home', 'away');
    }

    protected function getParams() {
        if ($this->gameDate === null) {
            throw new Exception("Game date must be set.");
        }

        return array(
            'game_date' => $this->gameDate,
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
