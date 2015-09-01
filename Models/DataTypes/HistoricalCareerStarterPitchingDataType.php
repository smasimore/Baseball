<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'DataType.php';
include_once __DIR__ .'/../Traits/THistoricalStarterPitchingDataType.php';

final class HistoricalCareerStarterPitchingDataType extends DataType {

    use THistoricalStarerPitchingDataType;

    final protected function getTable() {
        return Tables::HISTORICAL_CAREER_STARTER_PITCHING;
    }
}
