<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

trait TScriptWithInsert {

    protected function write() {
        multi_insert(
            DATABASE,
            $this->writeTable,
            $this->writeData
        );
    }
}
