<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'DataType.php';
include_once __DIR__ . '/../Constants/StatsYears.php';
include_once __DIR__ . '/../Constants/StatsTypes.php';
include_once __DIR__ . '/../Constants/SQLWhereParams.php';
include_once __DIR__ . '/../Traits/TSimParams.php';

final class SimInputDataType extends DataType {

    use TSimParams;

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

        return array(
            SQLWhereParams::EQUAL => array(
                'game_date' => $this->gameDate,
                'season' => $this->getSeason(),
                'stats_year' => $this->statsYear,
                'stats_type' => $this->statsType
            )
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
