<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'DataType.php';
include_once __DIR__ . '/../Traits/TSimParams.php';
include_once __DIR__ . '/../Constants/SQLWhereParams.php';

final class SimOutputDataType extends DataType {

    use TSimParams;

    protected function getTable() {
        return 'sim_output';
    }

    protected function getColumns() {
        return array('gameid', 'home', 'away', 'home_win_pct');
    }

    protected function getParams() {
        return array(
            SQLWhereParams::EQUAL => $this->getSimParams()
        );
    }

    final protected function formatData() {
       $this->data = index_by($this->data, 'gameid');
    }
}
