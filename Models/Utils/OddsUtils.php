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
        return $pct > .5
            ? (100 * $pct) / ($pct - 1)
            : (100 * (1 - $pct)) / $pct;
    }

    public static function convertOddsToPct($odds) {
        return $odds < 0
            ? $pct = (-1) * $odds / ((-1) * $odds + 100)
            : $pct = 100 / (100 + $odds);
    }

    public static function calculatePayout($bet, $odds) {
        return $odds < 0
            ? 100 / (-1 * $odds) * $bet
            : $bet * $odds / 100;
    }
}

?>
