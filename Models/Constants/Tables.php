<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'Enum.php';

class Tables extends Enum {

    const SIM_OUTPUT = 'sim_output';
    const LIVE_SCORES = 'live_scores';
    const BETS = 'bets';
    const ERRORS = 'errors';

    // Odds Tables
    const HISTORICAL_ODDS = 'historical_odds';
    const LIVE_ODDS = 'live_odds';
    const ODDS = 'odds';

    // Retrosheet
    const RETROSHEET_EVENTS = 'events';
    const RETROSHEET_BATTING = 'retrosheet_batting';
}

?>
