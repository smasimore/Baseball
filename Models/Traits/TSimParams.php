<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ . '/../Constants/StatsYears.php';
include_once __DIR__ . '/../Constants/StatsTypes.php';
include_once __DIR__ . '/../Constants/StatsCategories.php';

trait TSimParams {

    private $gameDate;
    private $weights = 'b_home_away_100';
    private $statsYear = StatsYears::CAREER;
    private $statsType = StatsTypes::BASIC;
    private $weightsMutator = null;
    private $analysisRuns = 5000;
    private $useReliever = false;

    final public function getSimParams() {
        if ($this->gameDate === null) {
            throw new Exception('Game date must be set.');
        }

        return array(
            'game_date' => $this->gameDate,
            'season' => $this->getSeason(),
            'weights' => $this->weights,
            'stats_year' => $this->statsYear,
            'stats_type' => $this->statsType,
            'weights_mutator' => $this->weightsMutator,
            'analysis_runs' => $this->analysisRuns,
            'use_reliever' => $this->useReliever
        );
    }

    private function getSeason() {
        $date = DateTime::createFromFormat('Y-m-d', $this->gameDate);
        return (int)$date->format('Y');
    }

    final public function getStatsYear() {
        return $this->statsYear;
    }

    final public function getStatsType() {
        return $this->statsType;
    }

    final public function getWeights() {
        return $this->weights;
    }

    final public function getWeightsMutator() {
        return $this->weightsMutator;
    }

    final public function getUseReliever() {
        return $this->useReliever;
    }

    final public function getAnalysisRuns() {
        return $this->analysisRuns;
    }

    final public function getGameDate() {
        return $this->gameDate();
    }

    final public function setGameDate($game_date) {
        $this->gameDate = $game_date;
        return $this;
    }

    final public function setWeights($weights) {
        $this->weights = StatsCategories::getReadableWeights($weights);
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
}
