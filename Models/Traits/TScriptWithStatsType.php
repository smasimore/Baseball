<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ .'/../Constants/StatsTypes.php';

trait TScriptWithStatsType {

    private $statsType;

    public function setStatsType($stats_type) {
        StatsTypes::assertIsValidValue($stats_type);
        $this->statsType = $stats_type;
        return $this;
    }

    public function getStatsType() {
        if ($this->statsType === null) {
            throw new Exception('Stats type must be set');
        }
        return $this->statsType;
    }
}
