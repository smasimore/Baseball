<?php
//Copyright 2014, Saber Tooth Ventures, LLC

class Bases {

    // Note: These do not match RetrosheetBases.
    const NONE_ON = 0;
    const FIRST = 1;
    const SECOND = 2;
    const THIRD = 3;
    const FIRST_SECOND = 4;
    const FIRST_THIRD = 5;
    const SECOND_THIRD = 6;
    const LOADED = 7;

    public function basesToString($bases) {
        switch ($bases) {
            case self::NONE_ON:
                return 'None On';
            case self::FIRST:
                return 'First';
            case self::SECOND:
                return 'Second';
            case self::THIRD:
                return 'Third';
            case self::FIRST_SECOND:
                return 'First Second';
            case self::FIRST_THIRD:
                return 'First Third';
            case self::SECOND_THIRD:
                return 'Second Third';
            case self::LOADED:
                return 'Loaded';
        }
    }
}
?>
