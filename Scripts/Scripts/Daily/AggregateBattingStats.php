<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/Teams.php');
include(HOME_PATH.'Models/Utils/DateTimeUtils.php');
include(HOME_PATH.'Scripts/Include/ESPNParseUtils.php');
include(HOME_PATH.'Scripts/Include/RetrosheetInclude.php');
include(HOME_PATH.'Scripts/Include/RetrosheetPlayerMapping.php');

class AggregateBattingStats {

    const BATTING_CAREER_LAST_DAY_OF_SEASON = 'batting_career_ldos';
    const BATTING_CAREER = 'batting_career';

    private $backfillRetrosheet = false;
    private $testPlayer = null;
    private $startDate = null;
    private $endDate = null;
    private $insertStats = array();
    private $finalStats = array();
    private $finalPctStats = array();
    private static $ldos_colheads = array(
        'player_id',
        'split',
        'plate_appearances',
        'singles',
        'doubles',
        'triples',
        'home_runs',
        'walks',
        'strikeouts',
        'fly_outs',
        'ground_outs',
        'season',
        'ds'
    );
    private static $colheads = array(
        'player_id',
        'stats',
        'season',
        'ds'
    );

    public function aggregateStats() {
        $this->startDate = $this->startDate ?: date('Y-m-d');
        $this->endDate = $this->endDate ?: $this->startDate;
        $splits = RetrosheetSplits::getSplits();
        // Pull previous year's last ds for career batting (to be added to this
        // season's).
        $previous_season = substr($this->startDate, 0, 4) - 1;
        $previous_data = $this->getPreviousData($previous_season);
        for ($ds = $this->startDate;
            $ds <= $this->endDate;
            $ds = DateTimeUtils::addDay($ds)
        ) {
            $season = substr($ds, 0, 4);
            // For now, just use Retrosheet Joe Average for 2013.
            // TODO(cert): Create ESPN Joe Averages
            $joe_average = RetrosheetParseUtils::getJoeAverageStats(2013);
            $espn_batters = ESPNParseUtils::getAllBatters(
                $ds,
                $this->testPlayer
            );
            foreach ($espn_batters as $player_id => $current_data) {
                foreach ($splits as $split) {
                    if (idx($current_data, $split) === null
                        && idx(idx($previous_data, $player_id), $split) === null) {
                        continue;
                    }
                    $this->finalStats[$player_id][$split] = array_merge(
                        $this->combineStats(
                            $previous_data[$player_id][$split],
                            $current_data[$split]
                        ),
                        array(
                            'season' => $season,
                            'ds' => $ds
                        )
                    );
                    $this->insertStats[] = $this->finalStats[$player_id][$split];
                }
                $this->finalPctStats[] = array(
                    'player_id' => $player_id,
                    'stats' => ESPNParseUtils::parsePctStats(
                        $this->finalStats[$player_id],
                        $joe_average['batter_stats']
                    ),
                    'season' => $season,
                    'ds' => $ds
                );
            }
        }
    }

    public function write() {
        if ($this->testPlayer !== null) {
            print_r($this->finalStats);
            exit('COMPLETED TEST RUN');
        }
        // First, update the last day of season table for this year with the
        // newer aggregate data if this is a live run.
        if ($this->startDate === date('Y-m-d')) {
            $sql = sprintf(
                'DELETE FROM %s
                WHERE season = %d',
                self::BATTING_CAREER_LAST_DAY_OF_SEASON,
                date('Y')
            );
            exe_sql(DATABASE, $sql, 'delete');
            multi_insert(
                DATABASE,
                self::BATTING_CAREER_LAST_DAY_OF_SEASON,
                $this->insertStats,
                self::$ldos_colheads
            );
        }
        // Then, update the aggregated pct stats into the career batting table.
        multi_insert(
            DATABASE,
            self::BATTING_CAREER,
            $this->finalPctStats,
            self::$colheads
        );
        logInsert(self::BATTING_CAREER);
    }

    private function getPreviousData($season) {
        // If an entry exists in ldos_batting then just return that. Otherwise,
        // we'll pull from Retrosheet, etc. and write the results to table.
        if ($this->backfillRetrosheet === false) {
            $sql = sprintf(
                'SELECT *
                FROM %s
                WHERE season = %s',
                self::BATTING_CAREER_LAST_DAY_OF_SEASON,
                $season
            );
            if ($this->testPlayer !== null) {
                $sql .= " AND player_id = '$this->testPlayer'";
            }
            $data = exe_sql(DATABASE, $sql);
            return safe_index_by($data, 'player_id', 'split');
        }
        // The code below should normally not execute unless you've manually
        // opted to backfill Retrosheet data.
        if ($season < 2014) {
            throw new Exception(
                'Do not use BattingStats for dates earlier than 2015'
            );
        }
        // Pull data pre-2014.
        $retrosheet_data =
            RetrosheetParseUtils::getHistoricalBattingStats($this->testPlayer);
        // Pull 2014 Data.
        $end_2014 = ESPNParseUtils::getSeasonEnd(ESPNParseUtils::ESPN_BATTING, 2014);
        $espn_batters = ESPNParseUtils::getAllBatters(
            $end_2014,
            $this->testPlayer
        );
        $splits = RetrosheetSplits::getSplits();
        $final_stats = array();
        foreach ($espn_batters as $player_id => $espn_data) {
            foreach ($splits as $split) {
                if (idx($espn_data, $split) === null
                    && idx(idx($retrosheet_data, $player_id), $split) === null) {
                    continue;
                }
                $final_stats[] = array_merge(
                    $this->combineStats(
                        $retrosheet_data[$player_id][$split],
                        $espn_data[$split]
                    ),
                    array(
                        'season' => 2014,
                        'ds' => '2014-10-31'
                    )
                );
            }
        }
        if ($this->testPlayer !== null) {
            exit('CANNOT BACKFILL ON TEST RUN');
        }
        multi_insert(
            DATABASE,
            self::BATTING_CAREER_LAST_DAY_OF_SEASON,
            $final_stats,
            self::$ldos_colheads
        );
        exit('BACKFILL COMPLETE');
    }

    private function combineStats(
        $previous_stats,
        $current_stats
    ) {
        $current_stats = ESPNParseUtils::parseOtherOuts($current_stats);
        $all_stats = array($previous_stats, $current_stats);
        $pct_stats = RetrosheetPercentStats::getPctStats();
        $pct_stats[] = 'plate_appearances';
        $combined_array = array();
        foreach ($all_stats as $source) {
            if ($source == null) {
                continue;
            }
            foreach ($source as $index => $stat) {
                if (idx($combined_array, $index) !== null && in_array($index, $pct_stats)) {
                    $combined_array[$index] += $stat;
                } else {
                    $combined_array[$index] = $stat;
                }
            }
        }
        return $combined_array;
    }

    public function setTest($player = 'poseb001') {
        $this->testPlayer = $player;
    }

    public function setBackFillRetrosheet() {
        $this->backfillRetrosheet = true;
    }

    public function setStartDate($ds) {
        $this->startDate = $ds;
    }

    public function setEndDate($ds) {
        $this->endDate = $ds;
    }
}

?>
