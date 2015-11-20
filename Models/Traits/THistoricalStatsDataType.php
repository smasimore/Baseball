<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ .'/../DataTypes/DataType.php';
include_once __DIR__ .'/../Constants/StatsYears.php';
include_once __DIR__ .'/../Constants/StatsTypes.php';
include_once __DIR__ .'/../Constants/PitcherTypes.php';

trait THistoricalStatsDataType {

    private $gameDate;

    final protected function getColumns() {
        return null;
    }

    final protected function getParams() {
        if ($this->gameDate === null) {
            throw new Exception('Game date must be set.');
        }
        return array(
            SQLWhereParams::EQUAL => array(
                'season' => DateTimeUtils::getSeasonFromDate($this->gameDate),
                'ds' => $this->gameDate
            )
        );
    }

    final protected function formatData() {
        $this->data = index_by($this->data, 'player_id');
    }

    final public function setGameDate($ds) {
        $this->gameDate = $ds;
        return $this;
    }
}
