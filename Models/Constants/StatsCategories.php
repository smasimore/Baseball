<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

class StatsCategories {
    const B_TOTAL = 'b_total';
    const B_HOME_AWAY = 'b_home_away';
    const B_PITCHER_HANDEDNESS = 'b_pitcher_handedness';
    const B_SITUATION = 'b_situation';
    const B_STADIUM = 'b_stadium';

    const P_TOTAL = 'p_total';
    const P_HOME_AWAY = 'p_home_away';
    const P_BATTER_HANDEDNESS = 'p_batter_handedness';
    const P_SITUATION = 'p_situation';
    const P_STADIUM = 'p_stadium';

    /**
     * weights      Array of weight names to weight.
     *
     * returns      String in readable weight format.
     */
    public static function getReadableWeights($weights) {
        ksort($weights);
        if (array_sum($weights) !== 1.0) {
            throw new Exception(sprintf(
                'Weights do not add up to 1.' .
                    'They add up to %s',
                array_sum($weights)
            ));
        }

        $readable_weights_arr = array();
        foreach ($weights as $name => $weight) {
            $readable_weights_arr[] = $name . '_' . (int)($weight*100);
        }

        return implode('__', $readable_weights_arr);
    }
}
