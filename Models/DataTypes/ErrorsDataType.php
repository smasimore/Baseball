<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'DataType.php';

final class ErrorsDataType extends DataType {

    final protected function getTable() {
        return Tables::ERRORS;
    }

    final protected function getParams() {
        return null;
    }
}
