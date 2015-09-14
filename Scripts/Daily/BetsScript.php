<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ .'/../../Models/Include/DailyInclude.php';

class BetsScript extends ScriptWithWrite {

    use TScriptWithInsert;

    private $pctWinThreshold = 0;
    private $pctAdvantageThreshold = .05;
    private $baseBet = 100;
    private $newBetsInsert;

    protected function gen($ds) {
        $sim_output_dt = (new SimOutputDataType())
            ->setGameDate($ds)
            ->gen();
        $sim_output_data = $sim_output_dt->getData();

        $odds_data = (new LiveOddsDataType())
            ->setGameDate($ds)
            ->gen()
            ->getData();
        // Note: Indexing by gameid will give us the most current odds of the
        // day given its foreach loop structure, this is intended behavior.
        $odds_data = index_by($odds_data, 'gameid', false);

        // For morning games sometimes ESPN doesn't have scores listed yet.
        $scores_data = null;
        try {
            $scores_data = (new LiveScoresDataType())
                ->setGameDate($ds)
                ->gen()
                ->getData();
        } catch (Exception $e) {
            // No scores yet.
        }

        $bets = null;
        try {
            $bets = (new BetsDataType())
                ->setGameDate($ds)
                ->gen()
                ->getData();
        } catch (Exception $e) {
            // No bets.
        }

        // Reset in the event we are backfilling and running multiple dates.
        $this->newBetsInsert = array();

        foreach ($sim_output_data as $gameid => $game) {
            // Skip PPD games.
            if ($scores_data !== null &&
                $scores_data[$gameid]['status_code'] === GameStatus::POSTPONED
            ) {
                continue;
            }
            $home = $game['home'];
            $away = $game['away'];
            $home_sim_pct = $game['home_win_pct'];
            $away_sim_pct = 1 - $home_sim_pct;
            $home_vegas_odds = $odds_data[$gameid]['home_odds'];
            $away_vegas_odds = $odds_data[$gameid]['away_odds'];
            $home_vegas_pct = number_format(
                OddsUtils::convertOddsToPct($home_vegas_odds),
                4
            );
            $away_vegas_pct = number_format(
                OddsUtils::convertOddsToPct($away_vegas_odds),
                4
            );


            // TODO(cert) Currently if the cron job doesn't run, etc.
            // and a game starts before we add it to the table this will
            // skip it. Not hi-pri right now since this shouldn't really
            // happen.

            // Add odds to $newOddsInsert if the game hasn't already started.
            // If we are backfilling we can include started games.
            if ($this->backfill === false &&
                $this->test === false &&
                $scores_data !== null &&
                $scores_data[$gameid]['status_code'] !==
                    GameStatus::NOT_STARTED &&
                $scores_data[$gameid]['status_code'] !==
                    GameStatus::POSTPONED
            ) {
                continue;
            }
            // If a game is in locked bets but there is no bet team we'll
            // want to see if we should bet if the game hasn't started.
            if ($this->test === false && $bets &&
                array_key_exists($gameid, $bets)
            ) {
                if (idx($bets[$gameid], 'bet_team') !== null ||
                    $scores_data[$gameid]['status_code'] ===
                        GameStatus::POSTPONED
                ) {
                    continue;
                }
            }
            $this->newBetsInsert[$gameid] = array_merge(
                idx($sim_output_dt->getSimParams(), SQLWhereParams::EQUAL),
                array(
                    'gameid' => $gameid,
                    'home' => $home,
                    'away' => $away,
                    'home_sim' => $home_sim_pct,
                    'away_sim' => $away_sim_pct,
                    'home_vegas' => $home_vegas_pct,
                    'away_vegas' => $away_vegas_pct,
                    'home_vegas_odds' => $home_vegas_odds,
                    'away_vegas_odds' => $away_vegas_odds,
                    'game_time' => $odds_data[$gameid]['game_time'],
                    'odds_time' => $odds_data[$gameid]['ts'],
                    'status' => $scores_data[$gameid]['status'],
                    'ds' => $ds,
                    // Re-add use_reliever and cast as int so we can insert
                    // into mysql.
                    'use_reliever' => (int)$sim_output_dt->getUseReliever()
                )
            );

            $bet = $this->calculateBet();
            // Don't bet on a PPD game.
            if ($scores_data[$gameid]['status_code'] ===
                GameStatus::POSTPONED
            ) {
                $this->newBetsInsert[$gameid]['bet_team'] = null;
                $this->newBetsInsert[$gameid]['bet'] = 0;
            } else if ($this->getShouldBet($home_vegas_pct, $home_sim_pct)) {
                $this->newBetsInsert[$gameid]['bet_team'] = $home;
                $this->newBetsInsert[$gameid]['bet'] = $bet;
            } else if ($this->getShouldBet($away_vegas_pct, $away_sim_pct)) {
                $this->newBetsInsert[$gameid]['bet_team'] = $away;
                $this->newBetsInsert[$gameid]['bet'] = $bet;
            } else {
                $this->newBetsInsert[$gameid]['bet_team'] = null;
                $this->newBetsInsert[$gameid]['bet'] = 0;
            }
        }
        $this->deleteNoBets();
    }

    protected function genPostWriteOperations() {
        $this->sendBetEmails();
    }

    protected function getWriteTable() {
        return Tables::BETS;
    }

    protected function getWriteData() {
        return $this->newBetsInsert;
    }

    private function sendBetEmails() {
        foreach ($this->newBetsInsert as $gameid => $bet) {
            $bet_team = $bet['bet_team'];
            if ($bet_team === null) {
                continue;
            }
            $bet_team_home_away = $bet_team === $bet['home'] ? 'home' : 'away';
            $sim_pct = $bet[sprintf('%s_sim', $bet_team_home_away)];
            $vegas_pct = $bet[sprintf('%s_vegas', $bet_team_home_away)];
            $advantage = $sim_pct - $vegas_pct;
            $vegas_odds = $bet[sprintf('%s_vegas_odds', $bet_team_home_away)];
            $bet_suggestion = sprintf(
                'Bet on %s (%.2f) %.2f Advantage - Vegas Odds: %d',
                $bet_team,
                number_format(($sim_pct * 100), 2),
                number_format(($advantage * 100), 2),
                $vegas_odds
            );
            echo "$bet_suggestion \n";
            // TODO(cert) Turn back on e-mails when we have model back in
            // order.
            //send_email($bet_suggestion, "");
        }
    }

    private function deleteNoBets() {
        // Delete rows with previous no bets.
        // TODO(cert) Move to parent and write better delete function.
        $no_bets = array_keys($this->newBetsInsert);
        $no_bets = implode("','", $no_bets);
        $sql = sprintf(
            "DELETE
            FROM %s
            WHERE gameid in('%s')",
            Tables::BETS,
            $no_bets
        );
        exe_sql(
            DATABASE,
            $sql,
            'delete'
        );
    }

    private function getShouldBet($vegas_pct, $sim_pct) {
        $is_advantage = $sim_pct > $vegas_pct;
        $is_above_win_threshold = $sim_pct >= $this->pctWinThreshold;
        $is_above_advantage_threshold = $sim_pct - $vegas_pct >=
            $this->pctAdvantageThreshold;

        return
            $is_advantage &&
            $is_above_win_threshold &&
            $is_above_advantage_threshold;
    }

    //TODO(cert) Can make this robust later.
    private function calculateBet() {
        return $this->baseBet;
    }

    private function validateThreshold($thresh) {
        if ($thresh > 1) {
            throw new Exception(
                'Win Threshold Must Be < 1 - Use Decimals'
            );
        }
    }

    public function setPctWinThreshold($thresh) {
        $this->validateThreshold($thresh);
        $this->pctWinThreshold = $thresh;
        return $this;
    }

    public function setPctAdvantageThreshold($thresh) {
        $this->validateThreshold($thresh);
        $this->pctAdvantageThreshold = $thresh;
        return $this;
    }

    public function setBaseBet($bet) {
        $this->baseBet = $bet;
        return $this;
    }
}
