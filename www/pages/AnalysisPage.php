<?php
include_once 'Page.php';

class AnalysisPage extends Page{

    private 
        // Sim params
        $dateSeasonSwitch = '2014-05-15',
        $simPerfectSeason = true,
        $pythagWinPct = true,
        $startDate = '2014-04-01',
        $endDate = '2014-10-31',
        $model = Models::NOMAGIC_PT,
        $casino = Casinos::SPORTSBOOK,

        // Bet params
        $betStrategy = BetTeamStrategies::NORMAL,
        $startingPot = 0,
        $betAmount = 1000,
        $odds = Odds::LAST,
        $home = true,
        $away = true,
        $defaultedPitcher = true,
        $betAmountStrategy = BetAmountStrategies::FLAT,
        $simAdvantage = 0,
        $percSimWin = 50,
        $minOdds = -300,
        $allowedBattingDefaults = 3,

        // Bet adv mult params
        $adv_0_less = 1,
        $adv_0_5 = 1,
        $adv_5_10 = 1,
        $adv_10_15 = 1,
        $adv_15_20 = 1,
        $adv_20_25 = 1,
        $adv_25_more = 1,

        // Bet win perc mult params
        $win_25_less = 1,
        $win_25_30 = 1,
        $win_30_35 = 1,
        $win_35_40 = 1,
        $win_40_45 = 1,
        $win_45_50 = 1,
        $win_50_55 = 1,
        $win_55_60 = 1,
        $win_60_65 = 1,
        $win_65_70 = 1,
        $win_70_75 = 1,
        $win_75_more = 1,


        /*$winThreshold = 0,
        $deltaThreshold = 0,
        $winPlusDeltaThreshold = 0, // e.g. 52.5
        $oddsThreshold = null, // e.g. -300*/

        // Class vars
        $gamesData,
        $resultData,
        $overallData = array(
            'games_bet' => 0, 
            'games_won' => 0,
            'amount_bet' => 0, 
            'amount_won' => 0
        );


    public function __construct($params) {
        parent::__construct();

        // Set checkbox defaults.
        if (!$params) {
            $params[BetParams::HOME] = true;
            $params[BetParams::AWAY] = true;
            $params[BetParams::DEFAULTED_PITCHER] = true;
        }

        if ($params[BetParams::START_DATE]) {
            $this->startDate = $params[BetParams::START_DATE];
        }
        if ($params[BetParams::END_DATE]) {
            $this->endDate = $params[BetParams::END_DATE];
        }
        if (!isset($params[BetParams::PYTHAG_WIN_PCT])) {
            $this->pythagWinPct = false;
        }
        if (!isset($params[BetParams::SIM_PERFECT_SEASON])) {
            $this->simPerfectSeason = false;
        } 
        if (isset($params[BetParams::CASINO])) {
            $this->casino = $params[BetParams::CASINO];
        }
        if (isset($params[BetParams::MODEL])) {
            $this->model = $params[BetParams::MODEL];
        }
        $this->betStrategy = $params[BetParams::BET_STRATEGY]
            ?: $this->betStrategy;
        if (isset($params[BetParams::STARTING_POT])) {
            $this->startingPot = $params[BetParams::STARTING_POT];
        }
        if (isset($params[BetParams::BET_AMOUNT])) {
            $this->betAmount = $params[BetParams::BET_AMOUNT];
        }
        if (isset($params[BetParams::BET_AMOUNT_STRATEGY])) {
            $this->betAmountStrategy = $params[BetParams::BET_AMOUNT_STRATEGY];
        }
        if ($params[BetParams::DATE_SEASON_SWITCH]) {
            $this->dateSeasonSwitch = $params[BetParams::DATE_SEASON_SWITCH];
        }
        // Needs to be isset or else value = 0 not supported.
        if (isset($params[BetParams::SIM_ADV])) {
            $this->simAdvantage = $params[BetParams::SIM_ADV];
        }
        if (isset($params[BetParams::PERC_SIM_WIN])) {
            $this->percSimWin = $params[BetParams::PERC_SIM_WIN];
        }
        if (isset($params[BetParams::MIN_ODDS])) {
            $this->minOdds = $params[BetParams::MIN_ODDS];
        }
        if (isset($params[BetParams::ALLOWED_BATTER_DEFAULTS])) {
            $this->allowedBattingDefaults = $params[BetParams::ALLOWED_BATTER_DEFAULTS];
        }
        if (isset($params[BetParams::ODDS])) {
            $this->odds = $params[BetParams::ODDS];
        }
        if (!isset($params[BetParams::HOME])) {
            $this->home = false;
        }
        if (!isset($params[BetParams::AWAY])) {
            $this->away = false;
        }
        if (!isset($params[BetParams::DEFAULTED_PITCHER])) {
            $this->defaultedPitcher = false;
        }

        // Bet adv mult params
        if (isset($params[BetParams::ADV_0_LESS])) {
            $this->adv_0_less = $params[BetParams::ADV_0_LESS];
        }
        if (isset($params[BetParams::ADV_0_5])) {
            $this->adv_0_5 = $params[BetParams::ADV_0_5];
        } 
        if (isset($params[BetParams::ADV_5_10])) {
            $this->adv_5_10 = $params[BetParams::ADV_5_10];
        }
        if (isset($params[BetParams::ADV_10_15])) {
            $this->adv_10_15 = $params[BetParams::ADV_10_15];
        }
        if (isset($params[BetParams::ADV_15_20])) {
            $this->adv_15_20 = $params[BetParams::ADV_15_20];
        }
        if (isset($params[BetParams::ADV_20_25])) {
            $this->adv_20_25 = $params[BetParams::ADV_20_25];
        }
        if (isset($params[BetParams::ADV_25_MORE])) {
            $this->adv_25_more = $params[BetParams::ADV_25_MORE];
        }

        // Bet win perc mult params
        if (isset($params[BetParams::WIN_25_LESS])) {
            $this->win_25_less = $params[BetParams::WIN_25_LESS];
        }
        if (isset($params[BetParams::WIN_25_30])) {
            $this->win_25_30 = $params[BetParams::WIN_25_30];
        }
        if (isset($params[BetParams::WIN_30_35])) {
            $this->win_30_35 = $params[BetParams::WIN_30_35];
        }
        if (isset($params[BetParams::WIN_35_40])) {
            $this->win_35_40 = $params[BetParams::WIN_35_40];
        }
        if (isset($params[BetParams::WIN_40_45])) {
            $this->win_40_45 = $params[BetParams::WIN_40_45];
        }
        if (isset($params[BetParams::WIN_45_50])) {
            $this->win_45_50 = $params[BetParams::WIN_45_50];
        }
        if (isset($params[BetParams::WIN_50_55])) {
            $this->win_50_55 = $params[BetParams::WIN_50_55];
        }
        if (isset($params[BetParams::WIN_55_60])) {
            $this->win_55_60 = $params[BetParams::WIN_55_60];
        }
        if (isset($params[BetParams::WIN_60_65])) {
            $this->win_60_65 = $params[BetParams::WIN_60_65];
        }
        if (isset($params[BetParams::WIN_65_70])) {
            $this->win_65_70 = $params[BetParams::WIN_65_70];
        }
        if (isset($params[BetParams::WIN_70_75])) {
            $this->win_70_75 = $params[BetParams::WIN_70_75];
        }
        if (isset($params[BetParams::WIN_75_MORE])) {
            $this->win_75_more = $params[BetParams::WIN_75_MORE];
        }

        /*if (isset($params['winThreshold'])) {
            $this->winThreshold = $params['win_threshold'];
        }
        if (isset($params['oddsThreshold'])) {
            $this->oddsThreshold = $params['odds_threshold'];
        }
        if (isset($params['winPlusDeltaThreshold'])) {
            $this->winPlusDeltaThreshold = $params['win_plus_delta_threshold'];
        }*/

        $this->fetchData();
        $this->runSeason();
    }

    private function fetchData() {
        $sim_table = "sim_output_$this->model"."_2013";
        $sim_data_2013 = exe_sql('baseball',
            "SELECT home_i as home,
            away_i as away,
            ds as game_date,
            time as game_time,
            home_sim_win*100 as home_sim_win,
            (1-home_sim_win)*100 as away_sim_win,
            CASE
                WHEN pitcher_h_2013_default > 0 OR
                    pitcher_a_2013_default > 0
                THEN 1
                ELSE 0
            END as pitcher_default_2013,
            CASE
                WHEN pitcher_h_2014_default > 0 OR
                    pitcher_a_2014_default > 0
                THEN 1
                ELSE 0
            END as pitcher_default_2014,
            away_avg_runs,
            home_avg_runs
            FROM $sim_table
            WHERE ds >= '$this->startDate' AND
                ds <= '$this->endDate'"
        );
        $sim_data['2013'] = $this->indexData($sim_data_2013);

        $sim_table = "sim_output_$this->model"."_2014";
        $sim_data_2014 = exe_sql('baseball',
            "SELECT home_i as home,
            away_i as away,
            ds as game_date,
            time as game_time,
            home_sim_win*100 as home_sim_win,
            (1-home_sim_win)*100 as away_sim_win,
            CASE
                WHEN pitcher_h_2013_default > 0 OR
                    pitcher_a_2013_default > 0
                THEN 1
                ELSE 0
            END as pitcher_default_2014,
            CASE
                WHEN pitcher_h_2014_default > 0 OR
                    pitcher_a_2014_default > 0
                THEN 1
                ELSE 0
            END as pitcher_default_2014,
            away_avg_runs,
            home_avg_runs
            FROM $sim_table
            WHERE ds >= '$this->startDate' AND
                ds <= '$this->endDate'"
        );
        $sim_data['2014'] = $this->indexData($sim_data_2014);

        // Sets real endDate for rendering
        $this->endDate = max(array_column($sim_data['2014'], 'game_date'));

        $odds = exe_sql('baseball',
            "SELECT *
            FROM odds_2014
            WHERE casino = '$this->casino' AND
            ds >= '$this->startDate'"
        );
        $odds_data = $this->indexAndFormatOddsData($odds);

        $scores = exe_sql('baseball',
            "SELECT *
            FROM live_scores_2014
            WHERE status like '%F%'"
        );
        $scores_data = $this->indexData($scores);
        $this->gamesData = array();
        $num_games = count($sim_data['2013']);
        $counter = 0;

        // FIXME: @dcertner
        // Batter defaults (this will eventually be in the sim_output
        // table but putting here for now without sending it all
        // the way through to see if it's useful
        /*$magic = split_string($this->model, "_", BEFORE, EXCL);
        $batter_defaults = exe_sql('baseball',
            "SELECT home_i as home, time as game_time,
                ds as game_date, lineup_a_2013_total_defaults,
                lineup_a_2014_total_defaults, lineup_h_2013_total_defaults,
                lineup_h_2014_total_defaults, lineup_a_2013_defaults,
                lineup_a_2014_defaults, lineup_h_2013_defaults, lineup_h_2014_defaults
            FROM sim_".$magic."_2014 
            WHERE ds >= '$this->startDate' AND
                ds <= '$this->endDate'"
        );
        $batter_defaults_data = $this->indexData($batter_defaults);*/

        // DAN EDIT: Changed 2013 to 2014 for now since it had more data in 
        // foreach($sim_data['2013']...
        foreach ($sim_data['2014'] as $key => $data) {
            $year = ($data['game_date'] >= $this->dateSeasonSwitch)
                ? '2014' : '2013';
            $counter++;
            $data = $sim_data[$year][$key];
            $home_score = $scores_data[$key]['home_score'];
            $away_score = $scores_data[$key]['away_score'];

            // Hack: First 2 weeks of April don't have game times.
            if (!$home_score && !$away_score) {
                $home_score = $scores_data[substr_replace($key, '1', -2)]['home_score'];
                $away_score = $scores_data[substr_replace($key, '1', -2)]['away_score'];
            }

            $this->gamesData[$key] = array(
                'home' => $data['home'],
                'away' => $data['away'],
                'date_year' => $year,
                'pitcher_default' => $data["pitcher_default_$year"],

                // FIXME: @dcertner
                //'batter_default' => ($batter_defaults_data[$key]["lineup_h_".$year."_total_defaults"] +
                //  $batter_defaults_data[$key]["lineup_a_".$year."_total_defaults"]),

                'game_date' => $data['game_date'],
                'home_sim_win' => $data['home_sim_win'],
                'away_sim_win' => $data['away_sim_win'],
                'home_score' => $home_score,
                'away_score' => $away_score,
                'winner' => $home_score > $away_score ? 'home' : 'away', 
                'game_status' => $scores_data[$key]['status'],
                'odds' => $odds_data[$key]
            );

            // Simulates the season given the model's
            // predicted win rates (in hope to see upper and lower ROI bounds
            // that occur with luck..who knows if this will be useful :)
            if ($this->simPerfectSeason) {
                $rand = rand(0,100);
                $this->gamesData[$key]['winner'] = $rand < $data['home_sim_win'] ? 'home' : 'away';
            }
            if ($this->pythagWinPct) {
                $rs = $data['home_avg_runs'] * 5000;
                $ra = $data['away_avg_runs'] * 5000;
                $home_pythag =  (string) pow($rs, 1.83) / (pow($rs, 1.83) + pow($ra, 1.83)) * 100;
                $this->gamesData[$key]['home_sim_win'] = $home_pythag;
                $this->gamesData[$key]['away_sim_win'] = (100 - $home_pythag);
            }
        }
    }

    private function runSeason() {
        $pot = $this->startingPot;
        foreach ($this->gamesData as $key => $game) {
            $game_result = array(
                'home' => $game['home'],
                'away' => $game['away'],
                'pitcher_default' => $game['pitcher_default'],
                'batter_default' => $game['batter_default'],
                'game_date' => $game['game_date']
            );

            // get odds
            // TD: double headers are missing odds. When that's fixed, remove 
            // this.
            if (!$game['odds']) {
                continue;
            }
            list($odds, $bet_team) = $this->getOddsAndBetTeam($game);

            // Get bet amount. Can't use rounded amaounts.
            $bet_amount = $this->getBetAmount(
                $pot,
                $game["$bet_team"."_sim_win"] - $odds["$bet_team"."_vegas_win"],
                $game["$bet_team"."_sim_win"],
                $odds["$bet_team"."_vegas_win"]
            );

            $bet_odds = $odds["$bet_team"."_odds"];
            $game_result['bet_team'] = $game[$bet_team];
            $game_result['bet_odds'] = $bet_odds;
            $game_result['bet_vegas_pct'] = $odds["$bet_team"."_vegas_win"]
                ? round($odds["$bet_team"."_vegas_win"], 1) : null;
            $game_result['bet_sim_pct'] = $game["$bet_team"."_sim_win"]
                ? round($game["$bet_team"."_sim_win"], 1) : null;
            $game_result['advantage'] = $game_result['bet_sim_pct']
                ? round($game_result['bet_sim_pct'] - $game_result['bet_vegas_pct'], 1)
                : null;

            if (!$bet_team || !$bet_amount) {
                $game_result['bet_team'] = null;
                $game_result['bet_odds'] = null;
                $game_result['bet_vegas_pct'] = null;
                $game_result['bet_sim_pct'] = null;
                $game_result['advantage'] = null;
                $game_result['bet_amount'] = 0;
                $game_result['winner'] = $game[$game['winner']];
                $game_result['pot_change'] = 0;
                $game_result['pot'] = round($pot);
                $this->resultData[$key] = $game_result;
                continue;
            }    

            $game_result['bet_amount'] = $bet_amount;

            // get win/loss
            $won = $bet_team == $game['winner'] ? true : false;
            $pot_change = $this->getPotChange(
                $bet_amount, 
                $bet_odds, 
                $won
            );
            $game_result['winner'] = $game[$game['winner']];
            $game_result['pot_change'] = $pot_change;

            // update pot
            $pot = $pot + $pot_change;
            $game_result['pot'] = round($pot);

            // log
            $this->resultData[$key] = $game_result;

            // Set _color key for shading row in table
            if ($pot_change > 0) {
                $color = Colors::GREEN_FADED;
            } else if ($pot_change < 0) {
                $color = Colors::RED_FADED;
            }
            $this->resultData[$key]['_color'] = $color;

            if ($bet_team && $bet_amount) {
                $this->overallData['games_bet']++;
                $this->overallData['amount_bet'] += $game_result['bet_amount'];
                $this->overallData['amount_won'] += $game_result['pot_change'];
                if ($pot_change > 0) {
                    $this->overallData['games_won']++;
                }
            }

        }
    }

    private function getPotChange($bet_amount, $bet_odds, $won) {
        if ($won) {
            return round(calculatePayout($bet_amount, $bet_odds));
        }
        return (-1)*$bet_amount;
    }
        

    private function getBetAmount($pot, $adv, $sim_pct, $vegas_pct) {
        switch ($this->betAmountStrategy) {
            case BetAmountStrategies::FLAT:
                return $this->betAmount;
            case BetAmountStrategies::DYNAMIC:
                return $this->getDynamicBetAmount($pot, $adv, $sim_pct, $vegas_pct);
            case BetAmountStrategies::KELLY:
                return $this->getKellyBetAmount($pot, $adv, $sim_pct, $vegas_pct);
            case BetAmountStrategies::MASSERT:
                return $this->getMassertBetAmount($pot, $adv, $sim_pct, $vegas_pct);
        }
    }

    private function getOddsAndBetTeam($game) {
        $odds_data = $game['odds'];
        $home_sim = $game['home_sim_win'];
        $away_sim = $game['away_sim_win'];
        $pitcher_default = $game['pitcher_default'];
        $batter_default = $game['batter_default'];

        // If not betting when pitcher defaulted, return null.
        // Do the same if batter defaults are under threshold
        if ($pitcher_default && !$this->defaultedPitcher) {
            return null;
        } else if ($batter_default > $this->allowedBattingDefaults) {
            return null;
        }

        $odds = null;
        $bet_team = null;
        switch ($this->odds) {
            case Odds::LAST:
                $odds = $odds_data[max(array_keys($odds_data))];
                break;
            // For dynamic, need to calculate bet team and odds at the same time.
            case Odds::DYNAMIC_3_HRS:
                foreach ($odds_data as $time => $data) {
                    if (
                        $data['home_vegas_win'] + $this->simAdvantage < $home_sim &&
                        $game['home_sim_win'] > $this->percSimWin &&
                        $data['home_odds'] >= $this->minOdds &&
                        $this->home
                    ) {
                        $odds = $data;
                        $bet_team = 'home';
                        break;
                    } else if (
                        $data['away_vegas_win'] + $this->simAdvantage < $away_sim &&
                        $game['away_sim_win'] > $this->percSimWin &&
                        $data['away_odds'] >= $this->minOdds &&
                        $this->away
                    ) {
                        $odds = $data;
                        $bet_team = 'away';
                        break;
                    } 
                }
                break;
        }

        switch ($this->betStrategy) {
            case BetTeamStrategies::NORMAL:
                // Get bet team if not dynamic odds (already calculated).
                if ($this->odds !== Odds::DYNAMIC_3_HRS && $odds) {
                    if (
                        $odds['home_vegas_win'] + $this->simAdvantage < $home_sim &&
                        $game['home_sim_win'] > $this->percSimWin &&
                        $odds['home_odds'] >= $this->minOdds &&
                        $this->home
                    ) {
                        $bet_team = 'home';
                    } else if (
                        $odds['away_vegas_win'] + $this->simAdvantage < $away_sim &&
                        $game['away_sim_win'] > $this->percSimWin &&
                        $odds['away_odds'] >= $this->minOdds &&
                        $this->away
                    ) {
                        $bet_team = 'away';
                    }
                }
                break;
            case BetTeamStrategies::COIN_FLIP:
                $bet_team = rand(0, 1) === 0 ? 'home' : 'away';
                break;
            case BetTeamStrategies::BET_SIM_FAVORED:
                $bet_team = $home_sim > $away_sim ? 'home' : 'away';
                break;
            case BetTeamStrategies::BET_SIM_UNDERDOG:
                $bet_team = $home_sim > $away_sim ? 'away' : 'home';
                break;
            case BetTeamStrategies::BET_VEGAS_FAVORED:
                $bet_team = $odds['home_vegas_win'] > $odds['away_vegas_win'] 
                    ? 'home' : 'away';
                break;
            case BetTeamStrategies::BET_VEGAS_UNDERDOG:
                $bet_team = $odds['home_vegas_win'] > $odds['away_vegas_win']
                    ? 'away' : 'home';
                break;
        }

        return array($odds, $bet_team);
    }

    private function indexAndFormatOddsData($odds) {
        $odds_formatted = array();
        foreach ($odds as $row) {
            // Only keep odds from day of game.
            if ($row['game_date'] != $row['odds_date']) {
                continue;
            }   

            $game_hour = substr($row['game_time'], 0, 2);
            $key = $row['home'].$row['game_date'].$game_hour;
            $row['game_hour'] = $game_hour;

            // Only keep odds within 3 hours of game
            $game_time = explode(":", $row['game_time']);
            $game_minutes = intval($game_time[0])*60 + intval($game_time[1]);
            $odds_time = explode(":", $row['odds_time']);
            $odds_minutes = intval($odds_time[0])*60 + intval($odds_time[1]);
            $time_diff = $game_minutes - $odds_minutes;
            if ($time_diff > 180) {
                continue;
            }
            $odds_formatted[$key][$time_diff] = array(
                'home_odds' => $row['home_odds'],
                'away_odds' => $row['away_odds'],
                'home_vegas_win' => $row['home_pct_win'],
                'away_vegas_win' => $row['away_pct_win'],
            );
        }
        
        return $odds_formatted;
    }

    private function indexData($data) {
        foreach ($data as $key => $row) {

            $data[$key]['game_hour'] = substr($row['game_time'], 0, 2);
        }
        return index_by($data, 'home', 'game_date', 'game_hour');
    }

    public function getGraphData($type = 'cash') {
        if (!$this->resultData) {
            return null;
        }

        $graph_x = '';
        $graph_y = '';
        $counter = 0;
        $x_axis_scale = round(.05*count($this->resultData));
        foreach ($this->resultData as $game) {
            // Only showing every other datapoint.
            if ($counter % 2 !== 0) {
                $counter++;
                continue;
            }
            // Only show date every 30 games.
            if ($counter % $x_axis_scale == 0) {
                $graph_x .= ",'".$game['game_date']."'";
            } else {
                $graph_x .= ",''";
            }
            if ($type == 'cash') {
                $graph_y .= ','.$game['pot'];
            } else {
                // TD: get daily roi
            }
            $counter++;
        }

        // Remove first comma.
        $graph_x = substr($graph_x, 1); 
        $graph_y = substr($graph_y, 1); 

        return array($graph_x, $graph_y);
    }

    public function getHomeAwayGraphData($type = 'cash') {
        if (!$this->resultData) {
            return null;
        }

        $graph_home = '';
        $graph_away = '';
        $home_pot = 0;
        $away_pot = 0;
        $counter = 0; 
        $x_axis_scale = round(.05*count($this->resultData));
        foreach ($this->resultData as $game) {
            if ($counter % 2 !== 0) { 
                $counter++;
                continue;
            }   
            if ($type == 'cash') {
                if ($game['home'] === $game['bet_team']) {
                    $home_pot += $game['pot_change'];
                } else if ($game['away'] === $game['bet_team']) {
                    $away_pot += $game['pot_change'];
                }
                $graph_home .= ','.$home_pot;
                $graph_away .= ','.$away_pot;
            } else {
                // TD: get daily roi
            }
            $counter++;
        }
        
        // Remove first comma.
        $graph_home = substr($graph_home, 1);
        $graph_away = substr($graph_away, 1);
        
        return array($graph_home, $graph_away); 

    }

    public function display() {
        $sim_list = $this->getSimInputList();
        $bet_list = $this->getBetInputList(); 
        $bet_adv_mult_list = $this->getBetAdvMultInputList();
        $bet_win_perc_mult_list = $this->getBetWinPercMultInputList();

        $submit_button = "<input type='submit' value='Submit'>";

        $form = 
            "<form action='analysis.php' style='float:left; width:100%;'>
                <table class='analysis_page_table'><tr>
                    <td class='analysis_page_cell' style='width:500px;'>
                        <table class='analysis_page_table'><tr><td class='analysis_page_cell'>
                            <div style='background-color: #6495ed; width:310px;'>
                                $sim_list
                            </div></td><td class='analysis_page_cell'>
                            <div style='background-color: #6495ed; width:310px;'>
                                $bet_list
                            </div></td></tr><tr><td class='analysis_page_cell'>
                            <div style='background-color: #6495ed; width:310px;'>
                                $bet_adv_mult_list
                            </div></td><td class='analysis_page_cell'>
                            <div style='background-color: #6495ed; width:310px;'>
                                $bet_win_perc_mult_list
                            </div></td></tr>
                        </table>
                        <div style='margin:0 auto;'>$submit_button</div>
                    </td>
                    <td class='analysis_page_cell'>
                        <table class='analysis_page_table'><tr><td class='analysis_page_cell'>
                            <div style='width=100%; text-align=center;'>
                                <canvas 
                                    id='season' 
                                    height='500' 
                                    width='900' 
                                    style='display:block; margin: 0 auto;'
                                />
                            </div></td></tr><tr><td class='analysis_page_cell'>
                            <div style='width=100%; text-align=center;'>
                                <canvas 
                                    id='homeaway' 
                                    height='500' 
                                    width='900' 
                                    style='display:block; margin: 0 auto;'
                                />
                            </div></td></tr>
                        </table>
                    </td>
                </tr></table>
            </form>";

        $result_table = new Table($this->resultData, 'results', 'Results');
        $result_table = $result_table->setExpanded(false)->getHTML();
        $raw_table = new Table($this->gamesData, 'raw', 'Raw Data');
        $raw_table = $raw_table->setExpanded(false)->getHTML();
        $sim_performance_table = calculate_sim_performance($this->gamesData);
        $sim_performance_table = new Table($sim_performance_table, 'performance', 'Model Performance');
        $sim_performance_table = $sim_performance_table->setExpanded(true)->getHTML();
        
        echo $this->getSummaryElement();
        $page = new UOList(array(
            $form,
            $sim_performance_table,
            $result_table,
            $raw_table
        ));
        $page->display();

        //$sim_adv_data = $this->calculateHistogram('advantage', 5);
    }

    private function getBetInputList() {
        $strategy_selector = new Selector(
            'Bet Strategy (overrides sim adv and home/away)',
            BetParams::BET_STRATEGY,
            $this->betStrategy,
            BetTeamStrategies::getConstants()
        );
        $strategy_selector = $strategy_selector->getHTML();
        $starting_pot_input = $this->renderInputTableRow(
            'Starting Pot',
            "<input 
                type='number' 
                name=".BetParams::STARTING_POT."
                value=$this->startingPot
            />"
        );
        $bet_amount_input = $this->renderInputTableRow(
            'Bet Amount',
            "<input 
                type='number' 
                name=".BetParams::BET_AMOUNT."
                value=$this->betAmount
            />"
        );
        $bet_amount_strategy_selector = new Selector(
            'Bet Amount Strategy',
            BetParams::BET_AMOUNT_STRATEGY,
            $this->betAmountStrategy,
            BetAmountStrategies::getConstants()
        );
        $bet_amount_strategy_selector = $bet_amount_strategy_selector->getHTML();
        $odds_selector = new Selector(
            'Odds',
            BetParams::ODDS,
            $this->odds,
            Odds::getConstants()
        );
        $odds_selector = $odds_selector->getHTML();
        $sim_advantage_slider = new Slider(
            '% Sim Advantage',
            BetParams::SIM_ADV,
            $this->simAdvantage,
            -50, 50, 25
        );
        $sim_advantage_slider = $sim_advantage_slider->getHTML();
        $perc_win_slider = new Slider(
            '% Win',
            BetParams::PERC_SIM_WIN,
            $this->percSimWin,
            0, 100, 25
        );
        $perc_win_slider = $perc_win_slider->getHTML();
        $min_odds_slider = new Slider(
            'Min Odds',
            BetParams::MIN_ODDS,
            $this->minOdds,
            -300, 300, 25
        );
        $min_odds_slider = $min_odds_slider->getHTML();
        $batting_defaults_slider = new Slider (
            'Max Batting Defaults',
            BetParams::ALLOWED_BATTER_DEFAULTS,
            $this->allowedBattingDefaults,
            0, 18, 1
        );
        $batting_defaults_slider = $batting_defaults_slider->getHTML();
        $home_checked = $this->home ? 'checked' : '';
        $home_checkbox = $this->renderInputTableRow(
            'Bet Home',
            "<input type='checkbox' $home_checked name = " . BetParams::HOME . " />"
        );
        $away_checked = $this->away ? 'checked' : '';
        $away_checkbox = $this->renderInputTableRow(
            'Bet Away',
            "<input type='checkbox' $away_checked name = " . BetParams::AWAY . " />"
        );
        $defaulted_pitcher_checked = $this->defaultedPitcher ? 'checked' : '';
        $defaulted_pitcher_checkbox = $this->renderInputTableRow(
            'Bet If Pitcher Defaulted',
            "<input type='checkbox' $defaulted_pitcher_checked name = " . BetParams::DEFAULTED_PITCHER . " />"
        );

        $bet_list = new UOList(array(
            "<font color='white' style='padding:10px;'>
                Bet Params
            </font>",
            $strategy_selector,
            $starting_pot_input,
            $bet_amount_input,
            $bet_amount_strategy_selector,
            $odds_selector,
            $sim_advantage_slider,
            $perc_win_slider,
            $min_odds_slider,
            $batting_defaults_slider,
            $home_checkbox,
            $away_checkbox,
            $defaulted_pitcher_checkbox
        ));

        return $bet_list->getHTML();
    }

    private function getSimInputList() {
        $start_date_input = $this->renderInputTableRow(
            'Start Date',
            "<input type='date' 
                name=".BetParams::START_DATE." 
                value=$this->startDate 
            />"
        );
        $end_date_input = $this->renderInputTableRow(
            'End Date',
            "<input type='date' 
                name=".BetParams::END_DATE." 
                value=$this->endDate 
            />"
        );
        $date_season_switch_input = $this->renderInputTableRow(
            'Date to Switch to Current Data',
            "<input type='date'
                name=".BetParams::DATE_SEASON_SWITCH."
                value=$this->dateSeasonSwitch
            />"
        );
        $pythag_win_pct_checked = $this->pythagWinPct ? 'checked' : '';
        $pythag_win_pct_checkbox = $this->renderInputTableRow(
            'Use Pythag Win Percentage',
            "<input type='checkbox' $pythag_win_pct_checked name = " . BetParams::PYTHAG_WIN_PCT . " />"
        );
        $sim_perfect_season_checked = $this->simPerfectSeason ? 'checked' : '';
        $sim_perfect_season_checkbox = $this->renderInputTableRow(
            'Sim "Perfect" Season',
            "<input type='checkbox' $sim_perfect_season_checked name = " . BetParams::SIM_PERFECT_SEASON . " />"
        );
        $model_selector = new Selector(
            'Model',
            BetParams::MODEL,
            $this->model,
            listModels()
        );
        $model_selector = $model_selector->getHTML();
        $casino_selector = new Selector(
            'Casino',
            BetParams::CASINO,
            $this->casino,
            Casinos::getConstants()
        );
        $casino_selector = $casino_selector->getHTML();

        $sim_list = new UOList(array(
            "<font color='white' style='padding:10px;'>
                Sim Params
            </font>",
            $start_date_input,
            $end_date_input,
            $date_season_switch_input,
            $pythag_win_pct_checkbox,
            $sim_perfect_season_checkbox,
            $model_selector,
            $casino_selector
        ));
        
        return $sim_list->getHTML();
    }

    private function getSummaryElement() {
        $games_bet = $this->overallData['games_bet'];
        $win_perc = $games_bet 
            ? round(100 * $this->overallData['games_won'] / $games_bet, 1)
            : 0;
        $total_bet = number_format($this->overallData['amount_bet']);
        $amount_won = number_format($this->overallData['amount_won']);
        $roi = 0;
        if ($this->overallData['amount_bet']) {
            $roi = round(
                ($this->overallData['amount_won'] /
                $this->overallData['amount_bet']) * 100,
                2
            );
        }
        return 
            "<div style='background-color:#6495ed; padding:10px; margin-bottom:10px;'>
                <font color='white' style='padding:10px;'>
                    ROI: $roi%
                </font>
                <font color='white' style='padding:10px;'>
                    Total Bet: $$total_bet
                </font>
                <font color='white' style='padding:10px;'>
                    Total Won: $$amount_won
                </font>
                <font color='white' style='padding:10px;'>
                    Games Bet: $games_bet
                </font>
                <font color='white' style='padding:10px;'>
                    Percent Games Won: $win_perc%
                </font>
            </div>";
    }

    private function renderInputTableRow($label, $input, $fixed = false) {
        $layout = $fixed ? 'fixed' : 'auto';
        $row = 
            "<table style='table-layout:$layout;'><tr>
                <td><font size='2' color='blue'>
                    $label
                </font></td>
                <td>$input</td>
            </tr></table>";
        return $row;
    }

    // TD: working on this
    private function calculateHistogram($field, $increment) {
        $min = min(array_column($this->resultData, 'advantage'));
        $max = max(array_column($this->resultData, 'advantage'));
        $num_groups = round(($max - $min) / $increment);

        /*$histogram = array();
        for ($i = 0; $i < $num_groups; $i++) {
            $histogram[]['group'] = "$
        foreach ($this->resultData as $row) {
            //s_log($row[$field]);
        }*/
    }

    private function getKellyBetAmount($pot, $adv, $sim_pct, $vegas_pct) {
      if ($adv <= 0) {
        return 0;
      }
      $odds = convertPctToOdds($vegas_pct / 100);
      if ($odds < 100) {
        $odds = 100 / (-1 * $odds);
      } else {
        $odds = $odds / 100;
      }
      $kelly_divide = 10;
      $fraction_bet = ((($sim_pct / 100) * ($odds + 1)) - 1) / $odds;
      $total_bet = $fraction_bet * $pot / $kelly_divide;
      //$total_bet = max($total_bet, ($pot * .005));
      //$total_bet = min($total_bet, ($pot * .05));
      return $total_bet;
    }

    private function getMassertBetAmount($pot, $adv, $sim_pct, $vegas_pct) {
      if ($adv <= 0) {
        return 0;
      }
      $odds = convertPctToOdds($vegas_pct / 100);
      if ($odds < 100) {
        $odds = 100 / (-1 * $odds);
      } else {
        $odds = $odds / 100;
      }
      $fraction_bet = $sim_pct * $odds * $adv / 50000;
      $total_bet = $fraction_bet * $pot;
      //$total_bet = max($total_bet, ($pot * .005));
      //$total_bet = min($total_bet, ($pot * .05));
      return $total_bet;
    }
       
    private function getDynamicBetAmount($pot, $adv, $sim_pct, $vegas_pct) {
        $adv_ceiling = (ceil($adv) % 5 === 0) ? ceil($adv) : round(($adv + 5/2)/5)*5;
        $adv_mult = $this->adv_0_less;
        if ($adv_ceiling >= 5) {
            switch ($adv_ceiling) {
                case 5:
                    $adv_mult = $this->adv_0_5;
                    break;
                case 10:
                    $adv_mult = $this->adv_5_10;
                    break;
                case 15:
                    $adv_mult = $this->adv_10_15;
                    break;
                case 20:
                    $adv_mult = $this->adv_15_20;
                    break;
                case 25:
                    $adv_mult = $this->adv_20_25;
                    break;
                default:
                    $adv_mult = $this->adv_25_more; 
                    break;
            }
        }

        $sim_pct_ceiling = (ceil($sim_pct) % 5 === 0) ? ceil($sim_pct) : round(($sim_pct + 5/2)/5)*5;
        $sim_pct_mult = $this->win_25_less;
        if ($sim_pct_ceiling >= 5) {
            switch ($sim_pct_ceiling) {
                case 30:
                    $sim_pct_mult = $this->win_25_30;
                    break;
                case 35:
                    $sim_pct_mult = $this->win_30_35;
                    break;
                case 40:
                    $sim_pct_mult = $this->win_35_40;
                    break;
                case 45:
                    $sim_pct_mult = $this->win_40_45;
                    break;
                case 50:
                    $sim_pct_mult = $this->win_45_50;
                    break;
                case 55:
                    $sim_pct_mult = $this->win_50_55;
                    break;
                case 60:
                    $sim_pct_mult = $this->win_55_60;
                    break;
                case 65:
                    $sim_pct_mult = $this->win_60_65;
                    break;
                case 70:
                    $sim_pct_mult = $this->win_65_70;
                    break;
                case 75:
                    $sim_pct_mult = $this->win_70_75;
                    break;
                default:
                    $sim_pct_mult = $this->win_75_more;
                    break;
            }
        }

        return $this->betAmount * $adv_mult * $sim_pct_mult;
    }
 
    private function getBetWinPercMultInputList() {
        $win_25_less_input = $this->renderInputTableRow(
            '< 25%',
            "<input
                min=0
                step='any'
                type='number'
                name=".BetParams::WIN_25_LESS."
                value=$this->win_25_less
            />",
            true
        );
        $win_25_30_input = $this->renderInputTableRow(
            '25-30%',
            "<input
                min=0
                step='any'
                type='number'
                name=".BetParams::WIN_25_30."
                value=$this->win_25_30
            />",
            true
        );
        $win_30_35_input = $this->renderInputTableRow(
            '30-35%',
            "<input
                min=0
                step='any'
                type='number'
                name=".BetParams::WIN_30_35."
                value=$this->win_30_35
            />",
            true
        );
        $win_35_40_input = $this->renderInputTableRow(
            '35-40%',
            "<input
                min=0
                step='any'
                type='number'
                name=".BetParams::WIN_35_40."
                value=$this->win_35_40
            />",
            true
        );
        $win_40_45_input = $this->renderInputTableRow(
            '40-45%',
            "<input
                min=0
                step='any'
                type='number'
                name=".BetParams::WIN_40_45."
                value=$this->win_40_45
            />",
            true
        );
        $win_45_50_input = $this->renderInputTableRow(
            '45-50%',
            "<input
                min=0
                step='any'
                type='number'
                name=".BetParams::WIN_45_50."
                value=$this->win_45_50
            />",
            true
        );
        $win_50_55_input = $this->renderInputTableRow(
            '50-55%',
            "<input
                min=0
                step='any'
                type='number'
                name=".BetParams::WIN_50_55."
                value=$this->win_50_55
            />",
            true
        );
        $win_55_60_input = $this->renderInputTableRow(
            '55-60%',
            "<input
                min=0
                step='any'
                type='number'
                name=".BetParams::WIN_55_60."
                value=$this->win_55_60
            />",
            true
        );
        $win_60_65_input = $this->renderInputTableRow(
            '60-65%',
            "<input
                min=0
                step='any'
                type='number'
                name=".BetParams::WIN_60_65."
                value=$this->win_60_65
            />",
            true
        );
        $win_65_70_input = $this->renderInputTableRow(
            '50-55%',
            "<input
                min=0
                step='any'
                type='number'
                name=".BetParams::WIN_65_70."
                value=$this->win_65_70
            />",
            true
        );
        $win_70_75_input = $this->renderInputTableRow(
            '55-60%',
            "<input
                min=0
                step='any'
                type='number'
                name=".BetParams::WIN_70_75."
                value=$this->win_70_75
            />",
            true
        );
        $win_75_more_input = $this->renderInputTableRow(
            '> 75%',
            "<input
                min=0
                step='any'
                type='number'
                name=".BetParams::WIN_75_MORE."
                value=$this->win_75_more
            />",
            true
        );

        $bet_adv_mult_list = new UOList(array(
            "<font color='white' style='padding:10px;'>
                Bet Win Percent Multipliers
            </font>",
            $win_25_less_input,
            $win_25_30_input,
            $win_30_35_input,
            $win_35_40_input,
            $win_40_45_input,
            $win_45_50_input,
            $win_50_55_input,
            $win_55_60_input,
            $win_60_65_input,
            $win_65_70_input,
            $win_70_75_input,
            $win_75_more_input,
        ));

        return $bet_adv_mult_list->getHTML();
    }

    private function getBetAdvMultInputList() {
        $adv_0_less_input = $this->renderInputTableRow(
            '< 0%',
            "<input
                min=0
                step='any'
                type='number'
                name=".BetParams::ADV_0_LESS."
                value=$this->adv_0_less
            />",
            true
        );
        $adv_0_5_input = $this->renderInputTableRow(
            '0-5%',
            "<input
                min=0
                step='any'
                type='number'
                name=".BetParams::ADV_0_5."
                value=$this->adv_0_5
            />",
            true
        );
        $adv_5_10_input = $this->renderInputTableRow(
            '5-10%',
            "<input
                min=0
                step='any'
                type='number'
                name=".BetParams::ADV_5_10."
                value=$this->adv_5_10
            />",
            true
        );
        $adv_10_15_input = $this->renderInputTableRow(
            '10-15%',
            "<input
                min=0
                step='any'
                type='number'
                name=".BetParams::ADV_10_15."
                value=$this->adv_10_15
            />",
            true
        );
        $adv_15_20_input = $this->renderInputTableRow(
            '15-20%',
            "<input
                min=0
                step='any'
                type='number'
                name=".BetParams::ADV_15_20."
                value=$this->adv_15_20
            />",
            true
        );
        $adv_20_25_input = $this->renderInputTableRow(
            '20-25%',
            "<input
                min=0
                step='any'
                type='number'
                name=".BetParams::ADV_20_25."
                value=$this->adv_20_25
            />",
            true
        );
        $adv_25_more_input = $this->renderInputTableRow(
            '> 25%',
            "<input
                min=0
                step='any'
                type='number'
                name=".BetParams::ADV_25_MORE."
                value=$this->adv_25_more
            />",
            true
        );

        $bet_adv_mult_list = new UOList(array(
            "<font color='white' style='padding:10px;'>
                Bet Advantage Multipliers
            </font>",
            $adv_0_less_input,
            $adv_0_5_input,
            $adv_5_10_input,
            $adv_10_15_input,
            $adv_15_20_input,
            $adv_20_25_input,
            $adv_25_more_input,
        ));

        return $bet_adv_mult_list->getHTML();
    }

}

class BetParams extends Enum {
    const START_DATE = 'start_date';
    const END_DATE = 'end_date';
    const CASINO = 'casino';
    const MODEL = 'model';
    const DATE_SEASON_SWITCH = 'date_season_switch';
    const SIM_PERFECT_SEASON = 'sim_perfect_season';
    const PYTHAG_WIN_PCT = 'pythag_win_pct';
    const ALLOWED_BATTER_DEFAULTS = 'allowed_batter_defaults';
    const BET_STRATEGY = 'bet_strategy';
    const BET_AMOUNT_STRATEGY ='bet_amount_strategy';
    const STARTING_POT = 'starting_pot';
    const BET_AMOUNT = 'bet_amount';
    const SIM_ADV = 'sim_advantage';
    const PERC_SIM_WIN = 'perc_sim_win';
    const MIN_ODDS = 'min_odds';
    const ODDS = 'odds';
    const HOME = 'home';
    const AWAY = 'away';
    const DEFAULTED_PITCHER = 'defaulted_pitcher';

    const ADV_0_LESS = 'adv_0_less';
    const ADV_0_5 = 'adv_0_5';
    const ADV_5_10 = 'adv_5_10';
    const ADV_10_15 = 'adv_10_15';
    const ADV_15_20 = 'adv_15_20';
    const ADV_20_25 = 'adv_20_25';
    const ADV_25_MORE = 'adv_25_more';

    const WIN_25_LESS = 'win_25_less';
    const WIN_25_30 = 'win_25_30';
    const WIN_30_35 = 'win_30_35';
    const WIN_35_40 = 'win_35_40';
    const WIN_40_45 = 'win_40_45';
    const WIN_45_50 = 'win_45_50';
    const WIN_50_55 = 'win_50_55';
    const WIN_55_60 = 'win_55_60';
    const WIN_60_65 = 'win_60_65';
    const WIN_65_70 = 'win_65_70';
    const WIN_70_75 = 'win_70_75';
    const WIN_75_MORE = 'win_75_more';
}

class Casinos extends Enum {
    const SPORTSBOOK = 'sportsbook.ag';
    const CAESARS = 'caesars';
    const MGM = 'mgm';
    const WYNN = 'wynn';
    const DIMES = '5dimes';
}

class Models extends Enum {
    const NOMAGIC = 'nomagic';
    const MAGIC = 'magic';
    const NOMAGIC_PT = 'nomagic_50total_50pitcher';
    const MAGIC_PT = 'magic_50total_50pitcher';
}

// Mutually exclusive
class Odds extends Enum {
    const LAST = 'last';
    const DYNAMIC_3_HRS = 'dynamic_3_hrs'; // First odds to qualify 
                                           // in 3 hrs before game
}

class BetAmountStrategies extends Enum {
    const FLAT = 'flat';
    const DYNAMIC = 'dynamic';
    const KELLY = 'kelly';
    const MASSERT = 'massert';
}

class BetTeamStrategies extends Enum {
    const NORMAL = 'normal';
    const COIN_FLIP = 'coin_flip';
    const BET_SIM_FAVORED = 'bet_sim_favored';
    const BET_SIM_UNDERDOG = 'bet_sim_underdog';
    const BET_VEGAS_FAVORED = 'bet_vegas_favored';
    const BET_VEGAS_UNDERDOG = 'bet_vegas_underdog';
}
?>
