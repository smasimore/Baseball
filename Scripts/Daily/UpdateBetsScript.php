<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ .'/../../Models/Include/DailyInclude.php';

class UpdateBetsScript extends ScriptWithWrite {

    use TScriptWithUpdate;

    private $scoresInsert;

    protected function gen($ds) {
        try {
            $scores_data = (new LiveScoresDataType())
                ->setGameDate($ds)
                ->gen()
                ->getData();
        } catch (Exception $e) {
            exit('No Scores Yet');
        }

        try {
            $bets = (new BetsDataType())
                ->setGameDate($ds)
                ->gen()
                ->getData();
        } catch (Exception $e) {
            exit('No Bets Yet');
        }

        foreach ($scores_data as $gameid => $game) {
            if (!array_key_exists($gameid, $bets)) {
                continue;
            }
            $this->scoresInsert[$gameid] = array(
                'status' => $game['status'],
                'home_score' => $game['home_score'],
                'away_score' => $game['away_score']
            );
            if ($game['status_code'] === GameStatus::FINISHED) {
                $winner = $game['winner'];
                $bet_team = $bets[$gameid]['bet_team'];
                $bet_amount = $bets[$gameid]['bet'];
                switch (true) {
                    case $bet_amount === 0:
                        $payout = 0;
                        break;
                    case $bet_team !== $winner:
                        $payout = ($bet_amount * -1);
                        break;
                    case $bet_team === $game['home']:
                        $payout = number_format(
                            OddsUtils::calculatePayout(
                                $bet_amount,
                                $bets[$gameid]['home_vegas_odds']
                            ),
                            2
                        );
                        break;
                    case $bet_team === $game['away']:
                        $payout = number_format(
                            OddsUtils::calculatePayout(
                                $bet_amount,
                                $bets[$gameid]['away_vegas_odds']
                            ),
                            2
                        );
                        break;
                }
                $this->scoresInsert[$gameid]['winner'] = $winner;
                $this->scoresInsert[$gameid]['payout'] = $payout;
            }
        }
    }

    protected function getWriteTable() {
        return Tables::BETS;
    }

    // Note: This isn't used yet but will be when I update mysql.
    protected function getWriteData() {
        return $this->scoresInsert;
    }

    protected function genPostWriteOperations() {
        foreach ($this->scoresInsert as $gameid => $game) {
            update(
                DATABASE,
                Tables::BETS,
                $game,
                'gameid',
                $gameid
            );
        }
    }
}
