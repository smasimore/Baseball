<?php

include_once 'DataType.php';
include_once __DIR__ . '/../includes/SimConstants.php';

final class SimInputDataType extends DataType {

    private $gameDate;

    protected function getTable() {
        return 'sim_input';
    }

    protected function getColumns() {
        return array(
            'gameid',
            'pitching_h',
            'pitching_a',
            'batting_h',
            'batting_a'
        );
    }

    protected function getParams() {
        if ($this->gameDate === null) {
            throw new Exception('Game date must be set.');
        }

        $date = DateTime::createFromFormat('Y-m-d', $this->gameDate);
        $season = $date->format('Y');

        return array(
            'game_date' => $this->gameDate,
            'season' => $season,
            'stats_year' => StatsYear::CAREER,
            'stats_type' => StatsType::BASIC
        );
    }

    protected function formatData() {
        $indexed_data = index_by($this->data, 'gameid');
        $formatted_data = array();
        foreach ($indexed_data as $game => $game_details) {
            foreach ($game_details as $stat_name => $stat) {
                $decoded_stat = json_decode($stat, true);
                $formatted_data[$game][$stat_name] = $decoded_stat ?: $stat;
            }
        }

        $this->data = $formatted_data;
    }

    public function setGameDate($game_date) {
        $this->gameDate = $game_date;
        return $this;
    }

    public function getHomePitcherName($game_id) {
        return $this->formatPlayerName(
            $this->data[$game_id]['pitching_h']['player_name']
        );
    }

    public function getAwayPitcherName($game_id) {
        return $this->formatPlayerName(
            $this->data[$game_id]['pitching_a']['player_name']
        );
    }

    private function formatPlayerName($unformatted_name) {
        return ucwords(str_replace('_', ' ', $unformatted_name));
    }
}
