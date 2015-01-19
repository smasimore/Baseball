<?php
//Copyright 2014, Saber Tooth Ventures, LLC

if (!defined('HOME_PATH')) {
    include('/Users/constants.php');
}
include(HOME_PATH.'Scripts/Include/Enum.php');

class RetrosheetBatting extends Enum {

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

class RetrosheetSplits extends Enum {

    const TOTAL = 'Total';
    const HOME = 'Home';
    const AWAY = 'Away';
    const VSLEFT = 'VsLeft';
    const VSRIGHT = 'VsRight';
    const NONEON = 'NoneOn';
    const RUNNERSON = 'RunnersOn';
    const SCORINGPOS = 'ScoringPos';
    const SCORINGPOS2OUT = 'ScoringPos2Out';
    const BASESLOADED = 'BasesLoaded';

    public static function getSplits() {
        return array(
            self::TOTAL,
            self:: HOME,
            self:: AWAY,
            self:: VSLEFT,
            self:: VSRIGHT,
            self:: NONEON,
            self:: RUNNERSON,
            self:: SCORINGPOS,
            self:: SCORINGPOS2OUT,
            self:: BASESLOADED
        );
    }
}

class RetrosheetBases extends Enum {

    const BASES_EMPTY = 0;
    const FIRST = 1;
    const SECOND = 2;
    const FIRST_SECOND = 3;
    const THIRD = 4;
    const FIRST_THIRD = 5;
    const SECOND_THIRD = 6;
    const BASES_LOADED = 7;
}

class RetrosheetHomeAway extends Enum {

    const HOME = 1;
    const AWAY = 0;
}

class RetrosheetDefaults extends Enum {

    const SEASON_TOTAL = 0;
    const PREV_YEAR_ACTUAL = 1;
    const PREV_YEAR_TOTAL = 2;
    const CAREER_ACTUAL = 3;
    const CAREER_TOTAL = 4;
    const SEASON_JOE_AVERAGE_ACTUAL = 5;
    const SEASON_JOE_AVERAGE_TOTAL = 6;
    const PREV_SEASON_JOE_AVERAGE_ACTUAL = 7;
    const PREV_SEASON_JOE_AVERAGE_TOTAL = 8;
    const CAREER_JOE_AVERAGE_ACTUAL = 9;
    const CAREER_JOE_AVERAGE_TOTAL = 10;
}

?>
