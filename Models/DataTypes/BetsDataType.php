<?php

include_once 'DataType.php';
include_once __DIR__ . '/../Traits/TSimParams.php';

final class BetsDataType extends DataType {

    use TSimParams;

    protected function getTable() {
        return 'bets';
    }

    protected function getColumns() {
        return array(
            'gameid',
            'home',
            'away',
            'home_sim',
            'away_sim',
            'home_vegas_odds',
            'away_vegas_odds',
            'bet_team',
            'home_sim',
            'away_sim',
            'home_score',
            'away_score',
            'status',
            'bet',
            'payout'
        );
    }

    protected function getParams() {
        return $this->getSimParams();
    }
}
