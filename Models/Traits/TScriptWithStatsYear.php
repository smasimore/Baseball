<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ .'/../Constants/StatsYears.php';

trait TScriptWithStatsYear {

    private $statsYear;

    public function setStatsYear($stats_year) {
        StatsYears::assertIsValidValue($stats_year);
        $this->statsYear = $stats_year;
        return $this;
    }

    public function getStatsYear() {
        if ($this->statsYear === null) {
            throw new Exception('Stats year must be set');
        }
        return $this->statsYear;
    }
}
