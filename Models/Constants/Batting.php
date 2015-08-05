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
}

?>
