<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

class SQLWhereParams {

    const EQUAL = 0;
    const NOT_EQUAL = 1;
    const GREATER_OR_EQUAL = 2;
    const LESS_OR_EQUAL = 3;

    public static function getOperator($param_type) {
        switch ($param_type) {
            case self::EQUAL:
                return '=';
            case self::NOT_EQUAL:
                return '<>';
            case self::GREATER_OR_EQUAL:
                return '>=';
            case self::LESS_OR_EQUAL:
                return '<=';
        }
    }

    public static function isGreaterThanLessThan($param_type) {
        if ($param_type === self::GREATER_OR_EQUAL ||
            $param_type === self::LESS_OR_EQUAL
        ) {
            return true;
        }
        return false;
    }
}

?>
