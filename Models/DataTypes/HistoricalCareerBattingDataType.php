<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ .'/../Traits/THistoricalStatsDataType.php';

final class HistoricalCareerBattingDataType extends DataType {

    use THistoricalStatsDataType;

    final protected function getTable() {
        return Tables::HISTORICAL_CAREER_BATTING;
    }
}
