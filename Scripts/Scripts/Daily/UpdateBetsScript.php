<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once('/Users/constants.php');
include_once(HOME_PATH.'Scripts/Include/DailyInclude.php');

class UpdateBetsScript extends ScriptWithWrite {

    use TScriptWithUpdate;

    private $scoresInsert;

    protected function gen($ds) {
        $scores_data = (new LiveScoresDataType())
            ->setGameDate($ds)
            ->gen()
            ->getData();
        $bets = (new BetsDataType())
            ->setGameDate($ds)
            ->gen()
            ->getData();

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
                        $payout = OddsUtils::calculatePayout(
                            $bet_amount,
                            $bets[$gameid]['home_vegas_odds']
                        );
                        break;
                    case $bet_team === $game['away']:
                        $payout = OddsUtils::calculatePayout(
                            $bet_amount,
                            $bets[$gameid]['away_vegas_odds']
                        );
                        break;
                }
                $this->scoresInsert[$gameid]['winner'] = $winner;
                $this->scoresInsert[$gameid]['payout'] = $payout;

                // TODO(danielc) Not used right now but will be
                // once I build out the update function.
                $this->setWriteTable(Tables::BETS);
                $this->setWriteData($this->scoresInsert);
            }
        }
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
