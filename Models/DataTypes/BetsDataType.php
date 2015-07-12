<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'DataType.php';
include_once __DIR__ . '/../Traits/TSimParams.php';

final class BetsDataType extends DataType {

    use TSimParams;

    final protected function getTable() {
        return Tables::BETS;
    }

    final protected function getParams() {
        return $this->getSimParams();
    }

    final protected function getSkipRandBucket() {
        return true;
    }

    final protected function formatData() {
        // Use unix time.
        $this->data = array_map(
            function($row) {
                $row['game_time'] = strtotime($row['game_time']);
                return $row;
            },
            $this->data
        );

        $this->data = index_by($this->data, 'gameid');
    }
}
