<?php
//Copyright 2014, Saber Tooth Ventures, LLC

if (!defined('HOME_PATH')) {
    include('/Users/constants.php');
}

class Odds {

    const HISTORICAL_ODDS_TABLE = 'historical_odds';

    public static function convertPctToOdds($pct) {
        return $pct > .5
            ? (100 * $pct) / ($pct - 1)
            : $odds = (100 * (1-$pct)) / $pct;
    }

    public static function convertOddsToPct($odds) {
        $pct = $odds < 0
            ? (-1) * $odds / ((-1) * $odds + 100)
            : 100 / (100 + $odds);
        return number_format($pct * 100, 2);
    }
}

?>
