<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

if (!defined('HOME_PATH')) {
    include('/Users/constants.php');
}

class ESPNParseUtils {

    const ESPN_BATTING = 'espn_batting';
    const MIN_PLATE_APPEARANCE = 50;

    public static function getSeasonEnd($table, $season) {
        $sql = sprintf(
            'SELECT max(ds) as ds
            FROM %s
            WHERE season = %s',
            $table,
            $season
        );
        $data = exe_sql(DATABASE, $sql);
        $data = reset($data);
        return idx($data, 'ds');
    }

    public static function getAllBatters($ds, $test_player = null) {
        $sql = sprintf(
            "SELECT
                player_id,
                player_name,
                espn_id,
                split,
                total_plate_appearances as plate_appearances,
                hits - doubles - triples - home_runs as singles,
                doubles,
                triples,
                home_runs,
                walks + intentional_walks + hit_by_pitch as walks,
                strikeouts,
                total_plate_appearances - hits - walks - hit_by_pitch
                - intentional_walks - strikeouts as other_outs,
                (ground_balls / (ground_balls + fly_balls)) as gbr
            FROM %s
            WHERE ds = '%s'",
            self::ESPN_BATTING,
            $ds
        );
        if ($test_player !== null) {
            $sql .= " AND player_id = '$test_player'";
        }
        $data = exe_sql(DATABASE, $sql);
        return safe_index_by($data, 'player_id', 'split', 'player_name');
    }

    public static function parseOtherOuts($stats) {
        $gbr = idx($stats, 'gbr');
        $other_outs = idx($stats, 'other_outs');
        $stats['ground_outs'] = round($other_outs * $gbr);
        $stats['fly_outs'] = $other_outs - $stats['ground_outs'];
        return $stats;
    }

    public static function parsePctStats($stats, $joe_average) {
        if ($joe_average === null) {
            throw new Exception(
                'Must specifiy averages to create pct stats'
            );
        }
        $joe_average = json_decode($joe_average, true);
        $pct_stats = RetrosheetPercentStats::getPctStats();
        foreach ($stats as $split_stats) {
            $pas = $split_stats['plate_appearances'];
            $split = $split_stats['split'];
            if ($pas === null || $pas < self::MIN_PLATE_APPEARANCE) {
                $final_stats[$split] = self::getJoeAverageWaterfall(
                    $pct_stats,
                    $stats['Total'],
                    $joe_average[$split]
                );
                continue;
            }
            $final_stats[$split] = array(
                'plate_appearances' => $pas
            );
            foreach ($pct_stats as $stat_name => $stat) {
                $final_stats[$split][$stat_name] = format_double(
                    ($split_stats[$stat] / $pas),
                    4
                );
            }
        }
        return json_encode($final_stats);
    }

    private function getJoeAverageWaterfall(
        $pct_stats,
        $player_stats,
        $joe_average
    ) {
        $total_pas = $player_stats['plate_appearances'];
        if ($total_pas >= self::MIN_PLATE_APPEARANCE) {
            $final_stats['plate_appearances'] = 0;
            foreach ($pct_stats as $stat_name => $stat) {
                $final_stats[$stat_name] = format_double(
                    ($player_stats[$stat] / $total_pas),
                    4
                );
            }
            return $final_stats;
        }
        $joe_average['plate_appearances'] = 0;
        return $joe_average;
    }
}

?>
