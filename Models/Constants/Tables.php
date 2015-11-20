<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'Enum.php';
include_once 'StatsYears.php';
include_once 'PitcherTypes.php';

class Tables extends Enum {

    const SIM_INPUT = 'sim_input';
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
    const RETROSHEET_HISTORICAL_LINEUPS = 'retrosheet_historical_lineups';
    const RETROSHEET_BATTING = 'retrosheet_batting';
    const HISTORICAL_CAREER_BATTING = 'historical_career_batting';
    const HISTORICAL_PREVIOUS_BATTING = 'historical_previous_batting';
    const HISTORICAL_SEASON_BATTING = 'historical_season_batting';
    const HISTORICAL_CAREER_STARTER_PITCHING = 'historical_career_starter_pitching';
    const HISTORICAL_PREVIOUS_STARTER_PITCHING = 'historical_previous_starter_pitching';
    const HISTORICAL_SEASON_STARTER_PITCHING = 'historical_season_starter_pitching';
    const HISTORICAL_CAREER_RELIEVER_PITCHING = 'historical_career_reliever_pitching';
    const HISTORICAL_PREVIOUS_RELIEVER_PITCHING = 'historical_previous_reliever_pitching';
    const HISTORICAL_SEASON_RELIEVER_PITCHING = 'historical_season_reliever_pitching';
    const HISTORICAL_CAREER_STARTER_PITCHING_ADJUSTMENTS = 'historical_career_starter_pitching_adjustments';
    const HISTORICAL_PREVIOUS_STARTER_PITCHING_ADJUSTMENTS = 'historical_previous_starter_pitching_adjustments';
    const HISTORICAL_SEASON_STARTER_PITCHING_ADJUSTMENTS = 'historical_season_starter_pitching_adjustments';

}

?>
