<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ . '/Constants/BetsRequiredFields.php';
include_once __DIR__ . '/Constants/TeamTypes.php';
include_once __DIR__ . '/Utils/GlobalUtils.php';
include_once __DIR__ . '/Utils/OddsUtils.php';

class Bets {

    // Return param fields.
    const BET_TEAM = 'bet_team';
    const BET_AMOUNT = 'bet_amount';
    const BET_ODDS = 'bet_odds';
    const BET_VEGAS_PCT = 'bet_vegas_pct';
    const BET_SIM_PCT = 'bet_sim_pct';
    const BET_TEAM_WINNER = 'bet_team_winner';
    const BET_NET_PAYOUT = 'bet_net_payout';
    const BET_PCT_DIFF = 'bet_pct_diff';

    // Bet param that can be overridden by setters.
    private $allowHomeBet = true;
    private $allowAwayBet = true;
    private $simVegasPctDiff = 5;
    private $baseBetAmount = 100;

    // Class vars.
    private $gameData;
    private $requiredGameDataKeys = array(
        BetsRequiredFields::VEGAS_HOME_ODDS,
        BetsRequiredFields::VEGAS_AWAY_ODDS,
        BetsRequiredFields::VEGAS_HOME_PCT,
        BetsRequiredFields::VEGAS_AWAY_PCT,
        BetsRequiredFields::SIM_HOME_PCT,
        BetsRequiredFields::SIM_AWAY_PCT,
    );

    /*
     * @param array(
     *            <DATE> => array(
     *                <GAMEID> => array(
     *                    BetsRequiredFields::VEGAS_HOME_ODDS => ..,
     *                    BetsRequiredFields::VEGAS_AWAY_ODDS => ..,
     *                    BetsRequiredFields::VEGAS_HOME_PCT => ..,
     *                    BetsRequiredFields::VEGAS_AWAY_PCT => ..,
     *                    BetsRequiredFields::SIM_HOME_PCT => ..,
     *                    BetsRequiredFields::SIM_AWAY_PCT => ..,
     *                    BetsRequiredFields::HOME_TEAM_WINNER => .., <optional>
     *                )
     *            )
     *        )
     */
    public function __construct(array $game_data) {
        $this->validateGameData($game_data);
        $this->gameData = $game_data;
    }

    public function getBetData() {
        $bet_data = array();
        foreach ($this->gameData as $date => $games) {
            $bet_data[$date] = array();
            foreach ($games as $gameid => $game) {
                $bet_amount = null;
                $bet_odds = null;
                $bet_vegas_pct = null;
                $bet_sim_pct = null;
                $bet_team_winner = null;
                $net_payout = null;
                $bet_pct_diff = null;

                $bet_team = $this->getBetTeam($game);
                if ($bet_team !== null) {
                    $bet_amount = $this->getBetAmount();

                    $bet_odds = $bet_team === TeamTypes::HOME
                        ? $game[BetsRequiredFields::VEGAS_HOME_ODDS]
                        : $game[BetsRequiredFields::VEGAS_AWAY_ODDS];
                    $bet_vegas_pct = $bet_team === TeamTypes::HOME
                        ? $game[BetsRequiredFields::VEGAS_HOME_PCT]
                        : $game[BetsRequiredFields::VEGAS_AWAY_PCT];
                    $bet_sim_pct = $bet_team === TeamTypes::HOME
                        ? $game[BetsRequiredFields::SIM_HOME_PCT]
                        : $game[BetsRequiredFields::SIM_AWAY_PCT];

                    $bet_team_winner = $this->getBetTeamWinner(
                        $bet_team,
                        idx($game, BetsRequiredFields::HOME_TEAM_WINNER)
                    );

                    $net_payout = $this->getNetPayout(
                        $bet_team_winner,
                        $bet_amount,
                        $bet_odds
                    );

                    $bet_pct_diff = $bet_sim_pct - $bet_vegas_pct;
                }

                $bet_data[$date][$gameid] = array(
                    self::BET_TEAM => $bet_team,
                    self::BET_AMOUNT => $bet_amount,
                    self::BET_ODDS => $bet_odds,
                    self::BET_VEGAS_PCT => $bet_vegas_pct,
                    self::BET_SIM_PCT => $bet_sim_pct,
                    self::BET_TEAM_WINNER => $bet_team_winner,
                    self::BET_NET_PAYOUT => $net_payout,
                    self::BET_PCT_DIFF => $bet_pct_diff,
                );
            }
        }

        return $bet_data;
    }

    public function setAllowHomeBet($allow_home_bet) {
        if (!is_bool($allow_home_bet)) {
            throw new Exception('Allow home bet must be true or false.');
        }

        $this->allowHomeBet = $allow_home_bet;
        return $this;
    }

    public function setAllowAwayBet($allow_away_bet) {
        if (!is_bool($allow_away_bet)) {
            throw new Exception('Allow away bet must be true or false.');
        }

        $this->allowAwayBet = $allow_away_bet;
        return $this;
    }

    public function setSimVegasPctDiff($sim_veg_pct_diff) {
        $this->validatePctInput($sim_veg_pct_diff);
        $this->simVegasPctDiff = $sim_veg_pct_diff;
        return $this;
    }

    public function setBaseBetAmount($base_bet_amount) {
        $this->baseBetAmount = $base_bet_amount;
        return $this;
    }

    private function validateGameData(array $game_data) {
        // Keyed on date.
        if (!strtotime(key($game_data))) {
            throw new Exception(
                'Game data passed to Bets must be keyed by date.'
            );
        }

        // Contains all required fields.
        $games_by_date = reset($game_data);
        $sample_game = reset($games_by_date);
        foreach ($this->requiredGameDataKeys as $key) {
            if (!idx($sample_game, $key)) {
                throw new Exception(
                    sprintf('Game data must contain %s field.', $key)
                );
            }
        }
    }

    private function validatePctInput($input) {
        // Don't accept floats.
        if (!is_int($input)) {
            throw new Exception(
                sprintf('%g must be an int in Bets pct input.', $input)
            );
        }

        if ($input < 0 || $input > 100) {
            throw new Exception(
                sprintf('%d must be between 0 and 100.', $input)
            );
        }
    }

    private function getBetTeam(array $game) {
        if ($this->shouldBetHome($game) === true) {
            return TeamTypes::HOME;
        } else if ($this->shouldBetAway($game) === true) {
            return TeamTypes::AWAY;
        }

        return null;
    }

    private function shouldBetHome(array $game) {
        if (!$this->allowHomeBet) {
            return false;
        }

        $sim_pct = $game[BetsRequiredFields::SIM_HOME_PCT];
        $veg_pct = $game[BetsRequiredFields::VEGAS_HOME_PCT];

        return $this->shouldBetTeam($sim_pct, $veg_pct);
    }

    private function shouldBetAway(array $game) {
        if (!$this->allowAwayBet) {
            return false;
        }

        $sim_pct = $game[BetsRequiredFields::SIM_AWAY_PCT];
        $veg_pct = $game[BetsRequiredFields::VEGAS_AWAY_PCT];

        return $this->shouldBetTeam($sim_pct, $veg_pct);
    }

    private function shouldBetTeam($sim_pct, $veg_pct) {
        $is_sim_veg_pct_diff_greater = $this->isSimVegasPctDiffGreater(
            $sim_pct,
            $veg_pct
        );
        if (!$is_sim_veg_pct_diff_greater) {
            return false;
        }

        return true;
    }

    private function isSimVegasPctDiffGreater($team_pct, $vegas_pct) {
        return $team_pct - $vegas_pct >= $this->simVegasPctDiff;
    }

    private function getBetAmount() {
        return $this->baseBetAmount;
    }

    private function getBetTeamWinner($bet_team, $home_team_winner) {
        // Home team winner field not passed in to construct, assume game in
        // future and return null.
        if ($home_team_winner === null) {
            return null;
        }

        // Passed in as string.
        $home_team_winner = (int)$home_team_winner;

        if ($bet_team === TeamTypes::HOME && $home_team_winner === 1) {
            return true;
        }

        if ($bet_team === TeamTypes::AWAY && $home_team_winner === 0) {
            return true;
        }

        return false;
    }

    private function getNetPayout($bet_team_winner, $bet_amount, $bet_odds) {
        // Game not complete yet.
        if ($bet_team_winner === null) {
            return null;
        }

        if ($bet_team_winner) {
            return OddsUtils::calculatePayout($bet_amount, $bet_odds);
        }

        // Lost game.
        return $bet_amount * -1;
    }
}
