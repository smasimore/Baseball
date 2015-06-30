<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

class ROIUtils {

    public static function calculateROI($bet_data) {
        if (!$bet_data) {
            return null;
        }
        $roi_arr = array('bet' => 0, 'payout' => 0);
        foreach ($bet_data as $game) {
            $payout = $game['payout'];
            if (!$payout) {
                continue;
            }
            $roi_arr['bet'] += $game['bet'];
            $roi_arr['payout'] += $game['payout'];
        }
        return $roi_arr['bet'] 
            ? $roi_arr['payout'] / $roi_arr['bet']
            : null;
    }

    public static function calculateRecord($bet_data) {
        if (!$bet_data) {
            return null;
        }

        $wins = 0;
        $losses = 0;
        foreach ($bet_data as $game) {
            if ((int)$game['payout'] > 0) {
                $wins++;
            } else if ((int)$game['payout'] < 0) {
                $losses++;
            }
        }

        return array($wins, $losses);
    }
}

?>
