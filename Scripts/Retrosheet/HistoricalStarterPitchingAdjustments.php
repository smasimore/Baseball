<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ .'/../../Models/Include/RetrosheetInclude.php';
include_once __DIR__ .'/../../Models/Traits/TScriptWithInsert.php';
include_once __DIR__ .'/../../Models/Traits/TScriptWithStatsYear.php';
include_once __DIR__ .'/../../Models/DataTypes/HistoricalCareerStarterPitchingDataType.php';
include_once __DIR__ .'/../Daily/ScriptWithWrite.php';

class HistoricalStarterPitchingAdjustments extends ScriptWithWrite {

    use TScriptWithInsert;
    use TScriptWithStatsYear;

    private $data;
    private $statNameToBucket = array(
        Batting::PCT_SINGLE => Batting::HIT,
        Batting::PCT_DOUBLE => Batting::HIT,
        Batting::PCT_TRIPLE => Batting::HIT,
        Batting::PCT_HOME_RUN => Batting::HIT,
        Batting::PCT_WALK => Batting::WALK,
        Batting::PCT_STRIKEOUT => Batting::STRIKEOUT,
        Batting::PCT_GROUND_OUT => Batting::OUT,
        Batting::PCT_FLY_OUT => Batting::OUT
    );

    public function gen($ds) {
        echo "$ds \n";
        $season = DateTimeUtils::getSeasonFromDate($ds);
        $pitcher_data = $this->getPitcherStats($ds);
        $avg_pitcher_data = $this->getAvgPitcherStats($season);

        $this->data = array();
        foreach ($pitcher_data as $pitcher_stats) {
            $player_id = $pitcher_stats['player_id'];
            $stats = json_decode($pitcher_stats['stats'], true);

            $pitcher_buckets = array();
            foreach ($stats as $split_name => $split) {
                $pitcher_buckets[$split_name] = array_merge(
                    array(
                        'player_id' => $player_id,
                        'split' => $split_name,
                        'plate_appearances' => $split['plate_appearances'],
                        'avg_innings' => idx($split, 'avg_innings')
                    ),
                    $this->getPitcherAdjustments(
                        $split,
                        $avg_pitcher_data,
                        $split_name
                    )
                );
            }

            $this->data[] = array(
                'player_id' => $player_id,
                'season' => $season,
                'ds' => $ds,
                'stats' => json_encode($pitcher_buckets)
            );
        }
    }

    protected function getWriteData() {
        return $this->data;
    }

    protected function getWriteTable() {
        switch ($this->getStatsYear()) {
            case StatsYears::CAREER:
                return Tables::HISTORICAL_CAREER_STARTER_PITCHING_ADJUSTMENTS;
            default:
                throw new Exception(sprintf(
                    '%s not supported yet in pitcher adjustments',
                    $this->getStatsYear()
                ));
        }
    }

    private function getPitcherStats($ds) {
        return (new HistoricalCareerStarterPitchingDataType())
            ->setGameDate($ds)
            ->gen()
            ->getData();
    }

    private function getAvgPitcherStats($season) {
        $data = RetrosheetParseUtils::getJoeAverageStats($season - 1);
        $data = json_decode($data['starter_stats'], true);
        $avg_stats = array();
        foreach ($data as $split_name => $split) {
            $avg_stats[$split_name] = $this->getAveragePitcherBucket($split);
        }

        return $avg_stats;
    }

    private function getAveragePitcherBucket($split) {
        $bucket_stats = array(
            Batting::HIT => 0,
            Batting:: WALK => 0,
            Batting::STRIKEOUT => 0,
            Batting::OUT => 0
        );
        foreach ($split as $stat_name => $stat) {
            $bucket = Batting::getStatBucket($stat_name);
            if ($bucket === null) {
                continue;
            }
            $bucket_stats[$bucket] += $stat;
        }
        $this->verifyAverageBucket($bucket_stats);
        $this->verifyBucketSumEqualsExpected($bucket_stats, 1);

        return $bucket_stats;
    }

    private function getPitcherAdjustments(
        array $split,
        array $average_stats,
        $split_name
    ) {
        $bucket_stats = array(
            Batting::HIT => $average_stats[$split_name][Batting::HIT] * -1,
            Batting::WALK => $average_stats[$split_name][Batting::WALK] * -1,
            Batting::STRIKEOUT =>
                $average_stats[$split_name][Batting::STRIKEOUT]* -1,
            Batting::OUT => $average_stats[$split_name][Batting::OUT] * -1,
        );
        foreach ($split as $stat_name => $stat) {
            $bucket = idx($this->statNameToBucket, $stat_name);
            if ($bucket === null) {
                continue;
            }
            $bucket_stats[$bucket] += $stat;
        }
        $this->verifyBucketSumEqualsExpected($bucket_stats, 0);

        return $bucket_stats;
    }

    private function verifyAverageBucket($stats) {
        foreach ($stats as $stat) {
            if ($stat < 0 || $stat > 1) {
                throw new Exception(sprintf(
                    '%u is not a valid stat bucket value',
                    $stat
                ));
            }
        }
    }

    // Use expected = 1 when calculating average buckets and 0 when creating
    // pitcher adjustments.
    private function verifyBucketSumEqualsExpected($stats, $expected) {
        $stat_check = 0;
        foreach ($stats as $stat) {
            $stat_check += $stat;
        }
        if (round($stat_check, 2) !== round($expected, 2)) {
            throw new Exception(sprintf(
                'Pitcher bucket %u not equal to %u',
                $stat_check,
                $expected
            ));
        }
    }
}

?>
