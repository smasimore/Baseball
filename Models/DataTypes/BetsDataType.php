<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'DataType.php';
include_once __DIR__ . '/../Traits/TSimParams.php';
include_once __DIR__ . '/../Constants/SQLWhereParams.php';

final class BetsDataType extends DataType {

    use TSimParams;

    final protected function getTable() {
        return Tables::BETS;
    }

    final protected function getParams() {
        return array(
            SQLWhereParams::EQUAL => $this->getSimParams()
        );
    }

    protected function formatData() {
        $this->data = index_by($this->data, 'gameid');
    }
}
