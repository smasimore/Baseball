<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

class OddsUtils {

    public static function convertPctToOdds($pct) {
        if ($pct >= 1 || $pct <= 0) {
            throw new Exception(sprintf(
                '%f is not between 0 and 1',
                $pct
            ));
        }
        $odds = $pct > .5
            ? (100 * $pct) / ($pct - 1)
            : (100 * (1 - $pct)) / $pct;
        return number_format($odds, 0);
    }

    public static function convertOddsToPct($odds, $decimals = 4) {
        $pct = $odds < 0
            ? $pct = (-1) * $odds / ((-1) * $odds + 100)
            : $pct = 100 / (100 + $odds);
        return number_format($pct, $decimals);
    }

    public static function calculatePayout($bet, $odds, $decimals = 2) {
        $payout = $odds < 0
            ? 100 / (-1 * $odds) * $bet
            : $bet * $odds / 100;
        return number_format($payout, $decimals);
    }
}

?>
