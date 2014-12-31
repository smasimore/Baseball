<?php
//Copyright 2014, Saber Tooth Ventures, LLC

class RetrosheetBatting {

    const GENERIC_OUT = 2;
    const STRIKEOUT =  3;
    const WALK = 14;
    const INTENTIONAL_WALK = 15;
    const HIT_BY_PITCH = 16;
    const FIELDERS_CHOICE = 19;
    const SINGLE = 20;
    const DOUBLE = 21;
    const TRIPLE = 22;
    const HOME_RUN = 23;

    public static function getAllEvents() {
        return array(
            'GENERIC_OUT' => self::GENERIC_OUT,
            'STRIKEOUT' => self::STRIKEOUT,
            'WALK' => self::WALK,
            'INTENTIONAL_WALK' => self::INTENTIONAL_WALK,
            'HIT_BY_PITCH' => self::HIT_BY_PITCH,
            'FIELDERS_CHOICE' => self::FIELDERS_CHOICE,
            'SINGLE' => self::SINGLE,
            'DOUBLE' => self::DOUBLE,
            'TRIPLE' => self::TRIPLE,
            'HOME_RUN' => self::HOME_RUN
        );
    }

    public static function getWalkEvents() {
        return array(
            'WALK' => self::WALK,
            'INTENTIONAL_WALK' => self::INTENTIONAL_WALK,
            'HIT_BY_PITCH' => self::HIT_BY_PITCH
        );
    }
}

class RetrosheetBases {

    const BASES_EMPTY = 0;
    const FIRST = 1;
    const SECOND = 2;
    const FIRST_SECOND = 3;
    const THIRD = 4;
    const FIRST_THIRD = 5;
    const SECOND_THIRD = 6;
    const BASES_LOADED = 7;
}

class RetrosheetHomeAway {

    const HOME = 1;
    const AWAY = 0;
}

?>
