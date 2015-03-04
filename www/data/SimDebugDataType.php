<?php

include_once 'DataType.php';

final class SimDebugDataType extends DataType {

    protected function getTable() {
        return 'sim_debug';
    }

    protected function getColumns() {
        return null;
    }

    protected function getParams() {
        return array();
    }

    public function getEvents() {
        return $this->data;
    }

    public function getGameID() {
        $event = reset($this->data);
        return $event['gameid'];
    }

    public function getSeason() {
        $event = reset($this->data);
        return $event['season'];
    }

    public function getStatsYear() {
        $event = reset($this->data);
        return $event['stats_year'];
    }

    public function getStatsType() {
        $event = reset($this->data);
        return $event['stats_type'];
    }

    public function getWeights() {
        $event = reset($this->data);
        return $event['weights'];
    }

    public function getAnalysisRuns() {
        $event = reset($this->data);
        return $event['analysis_runs'];
    }

    public function getSimGameDate() {
        $event = reset($this->data);
        return $event['sim_game_date'];
    }

    public function getWeightsMutator() {
        $event = reset($this->data);
        return $event['weights_mutator'];
    }
}
