<?php
//Copyright 2014, Saber Tooth Ventures, LLC

if (!defined('HOME_PATH')) {
    include('/Users/constants.php');
}

// General class for generic retrosheet constants.
class RetrosheetConstants {

    // Player and stats types.
    const BATTER = 'batter';
    const BATTING = 'batting';
    const PITCHER = 'pitcher';
    const PITCHING = 'pitching';

    // Stats precision.
    const NUM_DECIMALS = 3;
}

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

class RetrosheetStatsYear {

    const SEASON = 'season';
    const PREVIOUS = 'previous';
    const CAREER = 'career';
}

class RetrosheetStatsType {

    const BASIC = 'basic';
    const MAGIC = 'magic';
}

class RetrosheetDefaults extends Enum {

    const MIN_PLATE_APPEARANCE = 18;
    const PLATE_APPEARANCES = 'plate_appearances';
    // The order of these vars representing our defaulting order. i.e. If
    // a players split isn't available we use their Total, then previous, etc.
    const SEASON_TOTAL = 0;
    const PREVIOUS_ACTUAL = 1;
    const PREVIOUS_TOTAL = 2;
    const CAREER_ACTUAL = 3;
    const CAREER_TOTAL = 4;
    const JOE_AVERAGE_ACTUAL = 5;
    const JOE_AVERAGE_TOTAL = 6;
}

class RetrosheetTables {

    const HISTORICAL_SEASON_BATTING = 'historical_season_batting';
    const HISTORICAL_SEASON_PITCHING = 'historical_season_pitching';
    const HISTORICAL_PREVIOUS_BATTING = 'historical_previous_batting';
    const HISTORICAL_PREVIOUS_PITCHING = 'historical_previous_pitching';
    const HISTORICAL_CAREER_BATTING = 'historical_career_batting';
    const HISTORICAL_CAREER_PITCHING = 'historical_career_pitching';

    const SIM_INPUT = 'sim_input';
}

class RetrosheetGameTypes extends Enum {

    const SINGLE_GAME = 0;
    const DOUBLE_HEADER_FIRST = 1;
    const DOUBLE_HEADER_SECOND = 2;
}

class RetrosheetJoeAverage {

    const JOE_AVERAGE = 'joe_average';
    const BATTER_STATS = 'batter_stats';
    const PITCHER_STATS = 'pitcher_stats';
}

class RetrosheetEventColumns {

    const BAT_HAND_CD = 'BAT_HAND_CD';
    const PIT_HAND_CD = 'PIT_HAND_CD';
}

?>
