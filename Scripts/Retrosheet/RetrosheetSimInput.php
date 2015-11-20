<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ .'/../../Models/Include/RetrosheetInclude.php';
include_once __DIR__ .'/../Daily/ScriptWithWrite.php';
include_once __DIR__ .'/../../Models/Traits/TScriptWithInsert.php';
include_once __DIR__ .'/../../Models/Traits/TScriptWithStatsYear.php';
include_once __DIR__ .'/../../Models/Traits/TScriptWithStatsType.php';
include_once __DIR__ .'/../../Models/DataTypes/HistoricalCareerBattingDataType.php';
include_once __DIR__ .'/../../Models/DataTypes/RetrosheetHistoricalLineupsDataType.php';
include_once __DIR__ .'/../../Models/DataTypes/HistoricalCareerStarterPitchingAdjustmentsDataType.php';

class RetrosheetSimInput extends ScriptWithWrite {

    use TScriptWithInsert;
    use TScriptWithStatsYear;
    use TScriptWithStatsType;

    private $data = array();
    private $season;
    private $joeAverage;

    public function gen($ds) {
        $this->season = $this->getSeasonFromDate($ds);
        $this->joeAverage = $this->getJoeAverageStats($this->season);
        $lineups = (new RetrosheetHistoricalLineupsDataType())
            ->setGameDate($ds)
            ->gen()
            ->getData();

        // Do nothing if there are no games.
        if (!$lineups) {
            echo sprintf("No Games On %s \n", $ds);
            return;
        }

        // Pull pitcher_adjustments if neccesary.
        if ($this->getStatsType() === StatsTypes::PITCHER_ADJUSTMENT) {
            $pitcher_adjustments[StatsYears::CAREER] =
                (new HistoricalCareerStarterPitchingAdjustmentsDataType())
                    ->setGameDate($ds)
                    ->gen()
                    ->getData();
        }

        // TODO(cert) Add pitching_stats.
        $pitching_stats[StatsYears::CAREER] = array();
        $batting_stats[StatsYears::CAREER] =
            (new HistoricalCareerBattingDataType())
                ->setGameDate($ds)
                ->gen()
                ->getData();

        // TODO(cert) Pull previous/season data when neccesary.

        foreach ($lineups as $lineup) {
            $pitching_h_data = $this->getPitcherStats(
                $lineup['pitcher_h'],
                $pitching_stats
            );
            $pitching_a_data = $this->getPitcherStats(
                $lineup['pitcher_a'],
                $pitching_stats
            );
            if ($this->getStatsType() === StatsTypes::PITCHER_ADJUSTMENT) {
                $pitching_h_adjustment_data = $this->getPitcherAdjustmentStats(
                    $lineup['pitcher_h'],
                    $pitcher_adjustments
                );
                $pitching_a_adjustment_data = $this->getPitcherAdjustmentStats(
                    $lineup['pitcher_a'],
                    $pitcher_adjustments
                );
            }

            $this->data[] = array(
                'rand_bucket' => $lineup['rand_bucket'],
                'gameid' => $lineup['game_id'],
                'home' => $lineup['home'],
                'away' => $lineup['away'],
                'season' => $this->getSeason(),
                'game_date' => $ds,
                'stats_year' => $this->getStatsYear(),
                'stats_type' => $this->getStatsType(),
                'error_rate_h' => null,
                'error_rate_a' => null,
                'pitching_h' => $pitching_h_data,
                'pitching_a' => $pitching_a_data,
                // Adjust the home team batters by the away team pitcher.
                'batting_h' => $this->getBatterStats(
                    $lineup['lineup_h'],
                    $batting_stats,
                    $pitching_a_adjustment_data
                ),
                'batting_a' => $this->getBatterStats(
                    $lineup['lineup_a'],
                    $batting_stats,
                    $pitching_h_adjustment_data
                )
            );
        }
    }

    protected function getWriteData() {
        return $this->data;
    }

    protected function getWriteTable() {
        return Tables::SIM_INPUT;
    }

    protected function getPartitions() {
        return array(
            $this->getSeason() => 'string',
            $this->getStatsYear() => 'string',
            $this->getStatsType() => 'string',
        );
    }

    private function getSeasonFromDate($ds) {
        // Only call actual static function if $this->season isn't set.
        return $this->season ?: DateTimeUtils::getSeasonFromDate($ds);
    }

    private function getJoeAverageStats($season) {
        return $this->joeAverage ?:
            RetrosheetParseUtils::getJoeAverageStats($season);
    }

    private function getBatterStats(
        $lineup_arr,
        $batter_stats,
        $pitcher_adjustment
    ) {
        $lineup_arr = json_decode($lineup_arr, true);
        $final_lineup = array();
        foreach ($lineup_arr as $lpos => $player) {
            $pos = trim($lpos, 'L');
            $player_id = $player['player_id'];
            // TODO(cert) Clean this up...also right now it only pulls whatever
            // stats year is selected with no defaulting...
            $stats = idx(
                $batter_stats[$this->getStatsYear()],
                $player_id
            );
            // Actual stats are stored in 'stats' column within $batter_stats.
            $stats = $stats ? idx($stats, 'stats') : null;
            // TODO(cert) Add waterfall stuff here and don't default to career.
            if ($this->getStatsType() === StatsTypes::PITCHER_ADJUSTMENT) {
                $stats = $stats
                    ? $this->getAdjustedPlayerStats(
                        $stats,
                        $pitcher_adjustment_data
                    ) : null;
            }
            $stats['hand'] = idx($player, 'hand');
            $final_lineup[$pos] = $stats;
        }

        return $final_lineup;
    }

    private function getAdjustedPlayerStats($batter_stats, $pitcher_stats) {
        // TODO(cert) - Factor in average innings at a very future date.
        $batter_arr = json_decode($batter_stats, true);
        $pitcher_arr = json_decode($pitcher_stats, true);
        $adjusted_batting_stats = array();
        if (!$batter_arr) {
            // TODO(cert) - Figure out nullcheck.
        }
        foreach ($batter_arr as $split => $stats) {
            // Start here determining what % things are...
        }
    }

    private function getPitcherStats($pitcher_json, $pitching_data) {
        $pitcher = json_decode($pitcher_json, true);
        $player_id = idx($pitcher, 'id', 'joe_average');
        // TODO(cert) - Actually fill pitcher data and do waterfall, etc.
        // using $pitching_data.
        return array(
            'player_id' => $player_id,
            'player_name' => idx($pitcher, 'name', $player_id),
            'handedness' => idx($pitcher, 'hand'),
            'avg_innings' => null,
            'pitcher_v_batter' => null,
            'reliever_v_batter' => null
        );
    }

    private function getPitcherAdjustmentStats(
        $pitcher_json,
        $adjustment_data
    ) {
        if ($this->getStatsType() !== StatsTypes::PITCHER_ADJUSTMENT) {
            return null;
        }
        $pitcher = json_decode($pitcher_json, true);
        $player_id = idx($pitcher, 'id');
        if ($player_id === null) {
            return null;
        }

        // TODO(cert) Don't just default to career here.
        $player_data = idx($adjustment_data[StatsYears::CAREER], $player_id);

        return $player_data ? idx($player_data, 'stats') : null;
    }

    private function getSeason() {
        if ($this->season === null) {
            throw new Exception(
                'Must set declare $this->season before calling getSeason()'
            );
        }
        return $this->season;
    }
}
?>
