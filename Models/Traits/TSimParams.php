<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ . '/../Constants/StatsYears.php';
include_once __DIR__ . '/../Constants/StatsTypes.php';
include_once __DIR__ . '/../Constants/StatsCategories.php';
include_once __DIR__ . '/../Utils/DateTimeUtils.php';

trait TSimParams {

    private $gameDate;
    private $weights = 'b_home_away_100';
    private $statsYear = StatsYears::CAREER;
    private $statsType = StatsTypes::BASIC;
    private $weightsMutator = null;
    private $analysisRuns = 5000;
    private $useReliever = false;

    // Range params.
    private $startSeason;
    private $endSeason;
    private $startRandBucket = 0;
    private $endRandBucket = 29;

    // TODO(cert): THIS IS A TEMPORARY HACK SINCE BETS TABLE DOES NOT HAVE
    // RAND_BUCKET COLUMN (IT SHOULD).
    protected function getSkipRandBucket() {
        return false;
    }

    /**
     * Return params keyed by SQLWhereParam type.
     */
    final public function getSimParams() {
        if ($this->gameDate === null && $this->startSeason === null) {
            throw new Exception('Game date or season must be set.');
        } else if ($this->gameDate !== null && $this->startSeason !== null) {
            throw new Exception('Cannot set both game date & season.');
        }

        if ($this->startSeason !== null) {
            return $this->getSimParamsRange();
        }

        return array(
            SQLWhereParams::EQUAL => array(
                'game_date' => $this->gameDate,
                'season' => DateTimeUtils::getSeasonFromDate($this->gameDate),
                'weights' => $this->weights,
                'stats_year' => $this->statsYear,
                'stats_type' => $this->statsType,
                'weights_mutator' => $this->weightsMutator,
                'analysis_runs' => $this->analysisRuns,
                'use_reliever' => $this->useReliever
            )
        );
    }

    private function getSimParamsRange() {
        $params = array(
            SQLWhereParams::EQUAL => array(
                'weights' => $this->weights,
                'stats_year' => $this->statsYear,
                'stats_type' => $this->statsType,
                'weights_mutator' => $this->weightsMutator,
                'analysis_runs' => $this->analysisRuns,
                'use_reliever' => $this->useReliever
            ),
            SQLWhereParams::GREATER_OR_EQUAL => array(
                'season' => $this->startSeason,
            ),
            SQLWhereParams::LESS_OR_EQUAL => array(
                'season' => $this->endSeason,
            )
        );

        // TODO(cert): THIS IS A TEMPORARY HACK SINCE BETS TABLE DOES NOT HAVE
        // RAND_BUCKET COLUMN (IT SHOULD). 
        if (!$this->getSkipRandBucket()) {
            $params[SQLWhereParams::GREATER_OR_EQUAL]['rand_bucket'] = 
                $this->startRandBucket;
            $params[SQLWhereParams::LESS_OR_EQUAL]['rand_bucket'] =
                $this->endRandBucket;
        }

        return $params;
    }

    final public function getUseReliever() {
        return $this->useReliever;
    }

    final public function setGameDate($game_date) {
        if (!DateTime::createFromFormat('Y-m-d', $game_date)) {
            throw new Exception('Date must be in Y-m-d format.');
        }
        $this->gameDate = $game_date;
        return $this;
    }

    final public function setWeights($weights) {
        // Weights can be passed in as an array or string.
        $this->weights = is_array($weights) 
            ? StatsCategories::getReadableWeights($weights)
            : $weights;
        return $this;
    }

    final public function setStatsYear($stats_year) {
        $this->statsYear = $stats_year;
        return $this;
    }

    final public function setStatsType($stats_type) {
        $this->statsType = $stats_type;
        return $this;
    }

    final public function setWeightsMutator($weights_mutator) {
        $this->weightsMutator = $weights_mutator;
        return $this;
    }

    final public function setAnalysisRuns($analysis_runs) {
        $this->analysisRuns = $analysis_runs;
        return $this;
    }

    final public function setUseReliever($use_reliever) {
        $this->useReliever = $use_reliever;
        return $this;
    }

    final public function setSeasonRange($start_season, $end_season = null) {
        $this->startSeason = $start_season;
        $this->endSeason = $end_season ?: $start_season;
        return $this;
    }

    final public function setRandBucketRange($start_bucket, $end_bucket) {
        $this->startRandBucket = $start_bucket;
        $this->endRandBucket = $end_bucket;
        return $this;
    }
}
