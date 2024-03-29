<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ .'/../../Models/Include/RetrosheetInclude.php';
include_once __DIR__ .'/../../Models/Utils/ESPNParseUtils.php';
include_once __DIR__ .'/../../Models/Utils/RetrosheetPlayerMappingUtils.php';

class SimInput {

    const LINEUPS = 'lineups';
    const BATTING_CAREER = 'batting_career';
    const SIM_INPUT = 'sim_input';

    private $startDate = null;
    private $endDate = null;
    private $statsType = 'basic';
    private $statsYear = 'career';
    private $testPlayer = null;
    private $simInputData = array();
    private static $colheads = array(
        'rand_bucket' => '?',
        'gameid' => '!',
        'home' => '!',
        'away' => '!',
        'pitching_h' => '?',
        'pitching_a' => '?',
        'batting_h' => '!',
        'batting_a' => '!',
        'error_rate_h' => '?',
        'error_rate_a' => '?',
        'stats_type' => '!',
        'stats_year' => '!',
        'season' => '!',
        'game_date' => '!'
    );

    public function getLineups() {
        $this->startDate = $this->startDate ?: date('Y-m-d');
        $this->endDate = $this->endDate ?: $this->startDate;
        for ($ds = $this->startDate;
            $ds <= $this->endDate;
            $ds = DateTimeUtils::addDay($ds)
        ) {
            $logged_games = array();
            if ($ds === date('Y-m-d')) {
                $sql = sprintf(
                    "SELECT gameid
                    FROM %s
                    WHERE season = %d
                    AND stats_type = '%s'
                    AND stats_year = '%s'
                    AND game_date = '%s'",
                    self::SIM_INPUT,
                    date('Y'),
                    $this->statsType,
                    $this->statsYear,
                    $ds
                );
                $data = exe_sql(DATABASE, $sql);
                $logged_games = safe_array_column($data, 'gameid');
            }
            $season = substr($ds, 0, 4);
            // TODO(cert): Create ESPN Joe Averages
            $joe_average = RetrosheetParseUtils::getJoeAverageStats(2013);
            $lineups = $this->getDailyLineup($ds);
            if ($lineups == null) {
                throw new Exception(sprintf(
                    'No lineup for %s',
                    $ds
                ));
            }
            $batting_stats = $this->getBattingStats($ds);
            //TODO(cert): Figure out pitchers once we update model.
            $pitching_stats = null;
            foreach ($lineups as $lineup) {
                $game_hour = $lineup['time_est'] !== 'Postponed'
                    ? substr($lineup['time_est'], 0, 2)
                    : '00';
                $gameid = sprintf(
                    '%s%s%s',
                    $lineup['home'],
                    str_replace('-', '', $ds),
                    $game_hour
                );
                $rand_bucket = rand(0, 29);
                // For daily logging, skip any games already written to
                // sim_input (this won't work for backfills at the moment).
                if (
                    in_array($gameid, $logged_games) &&
                    $this->testPlayer === null
                ) {
                    continue;
                }
                $this->simInputData[$gameid] = array(
                    'gameid' => $gameid,
                    'rand_bucket' => $rand_bucket,
                    'home' => $lineup['home'],
                    'away' => $lineup['away'],
                    'season' => $lineup['season'],
                    'game_date' => $lineup['ds'],
                    'stats_year' => $this->statsYear,
                    'stats_type' => $this->statsType,
                    'pitching_h' =>
                        json_encode(
                            $this->getPitcherInfo($lineup, 'home')
                        ),
                    'pitching_a' =>
                        json_encode(
                            $this->getPitcherInfo($lineup, 'away')
                        ),
                    'batting_h' =>
                        json_encode(
                            $this->fillLineups(
                                $lineup['home_lineup'],
                                $batting_stats,
                                $joe_average
                            )
                        ),
                    'batting_a' =>
                        json_encode(
                            $this->fillLineups(
                                $lineup['away_lineup'],
                                $batting_stats,
                                $joe_average
                            )
                        )
                );
            }
        }
    }

    private function getDailyLineup($ds) {
        $sql = sprintf(
            "SELECT *
            FROM %s
            WHERE ds = '%s'",
            self::LINEUPS,
            $ds
        );
        return exe_sql(DATABASE, $sql);
    }

    private function getBattingStats($ds) {
        $sql = sprintf(
            "SELECT *
            FROM %s
            WHERE ds = '%s'",
            self::BATTING_CAREER,
            $ds
        );
        if ($this->testPlayer !== null) {
            $sql .= " and player_id = '$this->testPlayer'";
        }
        $data = exe_sql(DATABASE, $sql);
        return index_by($data, 'player_id');
    }

    private function getPitcherInfo($lineup, $home_away) {
        $name = idx($lineup, sprintf('%s_pitcher_name', $home_away));
        $id = idx($lineup, sprintf('%s_pitcher_id', $home_away));
        return array(
            'name' => $name,
            'id' => $id,
            'era' => null
        );
    }

    private function fillLineups($lineup, $stats, $joe_average) {
        $batter_joe_average = $joe_average['batter_stats'];
        $lineup = json_decode($lineup, true);
        $filled_lineups = array();
        foreach ($lineup as $lpos => $player) {
            $pos = trim($lpos, "L");
            $player_id = $player['player_id'];
            $player_stats = idx($stats, $player_id);
            $batter_v_pitcher = $player_stats
                ? idx($player_stats, 'stats')
                : $batter_joe_average;
            $batter_v_pitcher = json_decode($batter_v_pitcher, true);
            //TODO(cert) Add hand, etc. when pitcher data is incorporated.
            $filled_lineups[$pos] = $batter_v_pitcher;
        }
        return $filled_lineups;
    }

    public function write() {
        if ($this->testPlayer !== null) {
            print_r($this->simInputData);
            exit();
        } else if ($this->simInputData == null) {
            exit('No New Games');
        }
        multi_insert(
            DATABASE,
            self::SIM_INPUT,
            $this->simInputData,
            self::$colheads
        );
    }

    public function setTest($player = 'poseb001') {
        $this->testPlayer = $player;
    }

    public function setStatsType($type) {
        $this->statsType = $type;
    }

    public function setStatsYear($year) {
        $this->statsYear = $year;
    }

    public function setStartDate($ds) {
        $this->startDate = $ds;
    }

    public function setEndDate($ds) {
        $this->endDate = $ds;
    }
}


?>
