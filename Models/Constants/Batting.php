<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'Enum.php';

class Batting extends Enum {

    const SINGLES = 'singles';
    const DOUBLES = 'doubles';
    const TRIPLES = 'triples';
    const HOME_RUNS = 'home_runs';
    const WALKS = 'walks';
    const STRIKEOUTS = 'strikeouts';
    const FLY_OUTS = 'fly_outs';
    const GROUND_OUTS = 'ground_outs';
    const PLATE_APPEARANCES = 'plate_appearances';

    const PCT_SINGLE = 'pct_single';
    const PCT_DOUBLE = 'pct_double';
    const PCT_TRIPLE = 'pct_triple';
    const PCT_HOME_RUN = 'pct_home_run';
    const PCT_WALK = 'pct_walk';
    const PCT_STRIKEOUT = 'pct_strikeout';
    const PCT_GROUND_OUT = 'pct_ground_out';
    const PCT_FLY_OUT = 'pct_fly_out';

    const HIT = 'hit';
    const WALK = 'walk';
    const OUT = 'out';
    const STRIKEOUT = 'strikeout';
}

?>
