<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

trait THistoricalStarterPitchingDataType {

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

    final public function setGameDate($ds) {
        $this->gameDate = $ds;
        return $this;
    }
}
