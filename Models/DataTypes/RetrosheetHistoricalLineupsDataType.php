<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'DataType.php';

final class RetrosheetHistoricalLineupsDataType extends DataType {

    private $gameDate;

    final protected function getTable() {
        return Tables::RETROSHEET_HISTORICAL_LINEUPS;
    }

    final protected function getColumns() {
        return null;
    }

    final protected function getParams() {
        if ($this->gameDate === null) {
            throw new Exception('Game date must be set.');
        }
        return array(
            SQLWhereParams::EQUAL => array(
                'game_date' => $this->gameDate,
                'season' => DateTimeUtils::getSeasonFromDate($this->gameDate)
            )
        );
    }

    final public function setGameDate($ds) {
        $this->gameDate = $ds;
        return $this;
    }
}
