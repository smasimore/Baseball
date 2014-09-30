<?php
//Copyright 2014, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('mysqli.reconnect', '1');
ini_set('mysqli.connect_timeout', '-1');
ini_set('default_socket_timeout', '-1');
ini_set('max_execution_time', '-1');
include('/Users/constants.php'); 
include(HOME_PATH.'Include/sweetfunctions.php');

CONST BET_AMOUNT = 100;
CONST KELLY_DIVIDE = 10;
CONST PCT_THRESHOLD = 50;
$sim_table = 'sim_output_nomagic_50total_50pitcher_histrunning_2014';

function calculateKellyBet($sim, $odds, $kelly_divide, $bankroll) {
    if (!$odds) {
        return 0;
    }
    if ($odds < 100) {
        $odds = 100 / (-1 * $odds);
    } else {
        $odds = $odds / 100;
    }
    $fraction_bet = ((($sim / 100) * ($odds + 1)) - 1) / $odds;
    $kelly_max = $bankroll * .025;
    $kelly_min = $bankroll * .0025;
    $total_bet = $fraction_bet * $bankroll / $kelly_divide;
    $capped_bet = max(min($total_bet, $kelly_max), $kelly_min);
    return $capped_bet;
}

function calculateAdvantage($vegas, $sim) {
    switch (true) {
    case ($vegas > 0 && $sim > 0):
        $advantage = $vegas - $sim;
        break;
    case ($vegas > 0 && $sim < 0):
        $advantage = $vegas - 100 + (-1 * $sim) - 100;
        break;
    case ($vegas < 0 && $sim < 0):
        $advantage = $vegas - $sim;
        break;
    default:
        $advantage = 0;
        break;
    }
    return $advantage;
}

function calculatePayout($bet, $odds) {
    if ($odds < 0) {
        $payout = 100 / (-1 * $odds) * $bet;
    } else {
        $payout = $bet * $odds / 100;
    }
    $total = $payout + $bet;
    return number_format($payout);
}

function formatPlus($odds) {
    if ($odds > 0) {
        $odds = "+".$odds;
    }
    return $odds;
}

function isAdvantage($vegas_odds, $sim_odds, $sim_pct, $pct_threshold) {
    if (($vegas_odds > $sim_odds) && $sim_pct >= $pct_threshold) {
        return true;
    } else {
        return false;
    }
}

// If certain fields are null the script will log incorrectly
// Function to stop script if/when odds are temporarily null
function checkNullExit($check) {
    if (!$check) {
        exit();
    }
}

// Pull Data //
date_default_timezone_set('America/Los_Angeles');
$ds = date('Y-m-d');
if ($argv[1]) {
    $ds = $argv[1];
    $date = $argv[1];
}

// Pull Sim Scores & Pitchers
$sim_sql = 
    "SELECT home_i, away_i, time, home_sim_win, gamenumber, pitcher_h_2013_default, 
        pitcher_h_2014_default, pitcher_a_2013_default, pitcher_a_2014_default 
       FROM $sim_table
       WHERE ds = '$ds'";
$rows = exe_sql($database, $sim_sql);
$keys = array_keys($rows);

// Pull odds and index by game hour for double headers
$odds_sql = 
    'SELECT time, date, home, away, home_odds, away_odds, ts
    FROM live_odds_2014
    WHERE ds = "'.$ds.'"
    AND home_odds is not null
    AND away_odds is not null
    AND date = "'.$ds.'"';
$odds = exe_sql($database, $odds_sql);
$current_odds = array();
foreach ($odds as $i => $odd) {
    checkNullExit($odd['time']);
    $home = $odd['home'];
    $game_time = $odd['time'];
    $game_hour = substr($game_time, 0, 2);
    $current_odds[$home.$game_hour] = $odd;
}

// Pull scores and index by game hour for double headers
$scores_sql = 
    'SELECT home, away, home_score, away_score, status, game_date, game_time, ts
    FROM live_scores_2014
    WHERE ds = "'.$ds.'"
    AND game_date = "'.$ds.'" 
    AND status != "Postponed"';
$scores = exe_sql($database, $scores_sql);
$current_scores = array();
foreach ($scores as $score) {
    checkNullExit($score['game_time']);
    $home = $score['home'];
    $game_time = $score['game_time'];
    $game_hour = substr($game_time, 0, 2);
    $current_scores[$home.$game_hour] = $score;
}

// Pull bankroll information for Kelly Betting
$bankroll_ds = ds_modify($date, '-1 Day');
$bankroll_sql = 
    "SELECT bankroll 
    FROM bankroll_2014
    WHERE ds = '$bankroll_ds'";
$bankroll = exe_sql($database, $bankroll_sql);
$bankroll = $bankroll['bankroll'];
// Kelly betting depends on bankroll, so if for some reason
// bankroll hasn't logged use the previous days and send an email
if (!$bankroll) {
    $bankroll_ds = ds_modify($date, '-2 Day');
    $bankroll_sql =
        "SELECT bankroll
        FROM bankroll_2014
        WHERE ds = '$bankroll_ds'";
    $bankroll = exe_sql($database, $bankroll_sql);
    $bankroll = $bankroll['bankroll'];
    //send_email("Bankroll Issue","");
}

// If there is only one row have to change formatting for script to work
if (!is_numeric($keys[0])) {
    $rows = array($rows);
}
// Don't run script if there is no data yet
if (!$rows[0]) {
    exit('No Data Yet');
}

$data = array();
$update_sql = array();
$subject = null;
$pending = null;
$new_info = null;
$winner = null;
$wins = 0;
$losses = 0;
$no_bets = 0;
$total_bet = 0;
$total_return = 0;
$daily_roi = 'NA';
$total_games = count($scores);

foreach($rows as $i => $game) {
    $away = $game['away_i'];
    $home = $game['home_i'];
    $game_time = $game['time'];
    $game_hour = substr($game_time, 0, 2);
    $hometime = $home.$game_hour;

    // Skip postponed games by chceking scores table (which excludes PPD games)
    if (!$current_scores[$hometime]) {
        continue;
    }
    $home_sim_win = $game['home_sim_win'];
    $away_sim_win = 1 - $home_sim_win;
    $sim_away_odds = number_format(convertPctToOdds($away_sim_win), 0);
    $sim_home_odds = number_format(convertPctToOdds($home_sim_win), 0);
    $vegas_away_odds = $current_odds[$hometime]['away_odds'];
    $vegas_home_odds = $current_odds[$hometime]['home_odds'];
    $sim_away_pct = number_format(($away_sim_win*100), 1);
    $sim_home_pct = number_format(($home_sim_win*100), 1);
    $vegas_away_pct = number_format(convertOddsToPct($vegas_away_odds), 1);
    $vegas_home_pct = number_format(convertOddsToPct($vegas_home_odds), 1);

    $home_pitcher_default_2013 = $game['pitcher_h_2013_default'];
    $away_pitcher_default_2013 = $game['pitcher_a_2013_default'];

    $game_date = $current_odds[$hometime]['date'];
    $current_away_odds_delta = null;
    $current_home_odds_delta = null;
    $new_odds_insert = null;
    $new_result_insert = null;
    $previous_no_bet = null;
    $started = null;
    $finished = null;
    $advantage = null;
    $bet = 0;
    $payout = 0;

    // If there has been good odds earlier in the day we will have already bet 
    // so want to keep those odds "locked" in this script
    $locked_odds_sql =
        'SELECT *
        FROM locked_odds_2014
        WHERE ds = "'.$ds.'"
        AND home = "'.$home.'"
        AND game_date = "'.$game_date.'"
        AND game_time like "'.$game_hour.'%"';
    $locked_odds = exe_sql($database, $locked_odds_sql);
    // Special use case for no bets - log it and then reset in case we want to 
    // actually place a bet
    if ($locked_odds['bet_team'] == 'No Bet') {
        $previous_no_bet = 1;
        $locked_odds = null;
    }
    if (!$locked_odds) {
        // If there are no locked odds prepare data to be written to table
        $new_odds_insert = 1;
        $locked_odds['home'] = $home;
        $locked_odds['away'] = $away;
        $locked_odds['home_sim'] = $home_sim_win;
        $locked_odds['away_sim'] = $away_sim_win;
        $locked_odds['home_odds'] = $vegas_home_odds;
        $locked_odds['away_odds'] = $vegas_away_odds;
        $locked_odds['game_time'] = $current_odds[$hometime]['time'];
        $locked_odds['game_date'] = $current_odds[$hometime]['date'];
        $locked_odds['odds_time'] = $current_odds[$hometime]['ts'];
        $locked_odds['ds'] = $ds;
    } else {
    // If values exist for the game in the locked odds table use them instead 
    // of the current values
        $home_sim_win = $locked_odds['home_sim'];
        $away_sim_win = $locked_odds['away_sim'];
        $sim_away_pct = number_format(($away_sim_win*100), 1);
        $sim_home_pct = number_format(($home_sim_win*100), 1);
        $sim_away_odds = number_format(convertPctToOdds($away_sim_win), 0);
        $sim_home_odds = number_format(convertPctToOdds($home_sim_win), 0);
        $current_vegas_home_odds = $vegas_home_odds;
        $current_vegas_away_odds = $vegas_away_odds;
        $vegas_home_odds = $locked_odds['home_odds'];
        $vegas_away_odds = $locked_odds['away_odds'];
        $vegas_away_pct = number_format(convertOddsToPct($vegas_away_odds), 1);
        $vegas_home_pct = number_format(convertOddsToPct($vegas_home_odds), 1);
        $current_away_odds_delta = $current_vegas_away_odds - $vegas_away_odds;
        $current_home_odds_delta = $current_vegas_home_odds - $vegas_home_odds;
    }

    // Check if the Away bet is adventageous and set bets to that if so
    if (isAdvantage($vegas_away_odds, $sim_away_odds, $sim_away_pct, PCT_THRESHOLD)) {
        $bet_odds = $vegas_away_odds;
        $bet_sim = $sim_away_pct;
        $display_odds = formatPlus($vegas_away_odds);
        $bet_suggestion_display = $away." ".$display_odds." (".$sim_away_pct."%)";
        $bet_suggestion = $away;
        $advantage = 1;
        $advantage_display = number_format($sim_away_pct - $vegas_away_pct, 1)."%";
    // If no Away advantage check for a home one
    // TEST: SHUT OFF HOME BETTING FOR NOW
    } else if (isAdvantage($vegas_home_odds, $sim_home_odds, $sim_home_pct, PCT_THRESHOLD)) {
        $bet_odds = $vegas_home_odds;
        $bet_sim = $sim_home_pct;
        $display_odds = formatPlus($vegas_home_odds);
        $bet_suggestion_display = $home." ".$display_odds." (".$sim_home_pct."%)";
        $bet_suggestion = $home;
        $advantage = 1;
        $advantage_display = number_format($sim_home_pct - $vegas_home_pct, 1)."%";
    } else {
        $bet_suggestion_display = $away." @ ".$home." No Bet";
        $bet_suggestion = 'NA';
        $advantage = 0;
        $advantage_display = "NA";
    }

    $started = 1;
    if (strpos($current_scores[$hometime]['status'], "PM") || strpos($current_scores[$hometime]['status'], "AM")) {
        $started = 0;
        $pending = 1;
    }
    $finished = 0;
    // Determine if game has ended, and if so, find the winner
    if ($current_scores[$hometime]['status'] == "F" || (strpos($current_scores[$hometime]['status'], "F/") === 0)) {
        $finished = 1;
        if ($current_scores[$hometime]['away_score'] > $current_scores[$hometime]['home_score']) {
            $winning_team = $away;
        } else {
            $winning_team = $home;
        }
    // Determine if you have bet on the winner (1 = yes, 0 = no, 2 = no bet)
        switch (true) {
            case ($winning_team == $bet_suggestion):
                $winner = 1;
                break;
            case ($bet_suggestion == 'NA'):
                $winner = 2;
                break;
            default:
                $winner = 0;
                break;
        }
        // Make it so it doesn't add winning/losing odds if they change 
        // after the game has started
        if ($started && $new_odds_insert) {
            $winner = 2;
        }
    }

/////// THE FOLLOWING SECTION IS FOR DISPLAY ONLY //////////
    $data[$i]['Matchup'] = $away." @ ".$home;
    if ($started) {
        $data[$i]['Matchup'] = $away." (".$current_scores[$hometime]['away_score'].") @ ".
            $home." (".$current_scores[$hometime]['home_score'].") - ".$current_scores[$hometime]['status'];
    }
    if ($finished) {
        $data[$i]['Matchup'] = $away." (".$current_scores[$hometime]['away_score'].") @ ".
            $home." (".$current_scores[$hometime]['home_score'].") - FINAL";
    }
    $data[$i]['Date'] = $current_odds[$hometime]['date'];
    $data[$i]['Time'] = $current_odds[$hometime]['time']."\n";
    $data[$i]["Bet Suggestion"] = $bet_suggestion_display;
    $data[$i]["Bet Advantage"] = $advantage_display."\n";
///////////////////////////////////////////////////////////////

    $kelly_bet_amount = calculateKellyBet($bet_sim, $bet_odds, KELLY_DIVIDE, $bankroll);

    // Override kelly bet
    $kelly_bet_amount = null;

    if ($kelly_bet_amount) {
        $bet = $kelly_bet_amount;
    } else {
        $bet = BET_AMOUNT;
    }
    // If the game has finished, determine how much you won/lost if you bet 
    // using the win codes (0,1,2) from above
    if ($finished) {
        if ($winner == 1) {
            $data[$i]['Results'] = "";
            $data[$i]['Win/Loss'] = "WIN";
            $payout = calculatePayout($bet, $bet_odds);
            $total_bet += $bet; 
            $total_payout += $payout;
            $wins += 1;
            $game_roi = number_format((($payout / $bet) * 100), 2);
            $data[$i]['Game ROI'] = $game_roi."%"."\n";
        } else if ($winner == 2) {
            $data[$i]['Results'] = "";
            $data[$i]['Win/Loss'] = "DID NOT BET";
            $bet = 0;
            $payout = 0;
            $no_bets += 1;
            $data[$i]['Game ROI'] = "NA"."\n";;
        } else {
            $data[$i]['Results'] = "";
            $data[$i]['Win/Loss'] = "LOSS";
            $payout = -$bet;
            $total_bet += $bet;
            $total_payout += $payout;
            $losses += 1;
            $game_roi = number_format((($payout / $bet) * 100), 2);
            $data[$i]['Game ROI'] = $game_roi."%"."\n";
        }
    }

///////////// ANOTHER SECTION THAT JUST DISPLAYS STUFF //////////////////
    
    if (!$finished) {
        $data[$i]['Sim Odds'] = "";
        $data[$i]["Away Sim ".$away." (".$sim_away_pct."%)"] = $sim_away_odds;
        $data[$i]["Home Sim ".$home." (".$sim_home_pct."%)"] = $sim_home_odds;
        $data[$i]["Away Pitcher 2013 Default"] = $away_pitcher_default_2013;
        $data[$i]["Home Pitcher 2013 Default"] = $home_pitcher_default_2013."\n";
        $data[$i]["Vegas Odds"] = "";
        $data[$i]["Away Vegas ".$away." (".$vegas_away_pct."%)"] = $vegas_away_odds;
        $data[$i]["Home Vegas ".$home." (".$vegas_home_pct."%)"] = $vegas_home_odds;
    }

    // This is an ugly formatting function...doesn't concern you Sarah :)
    foreach ($data[$i] as $n => $odd) {
        if (strpos($odd, '/') || strpos($odd, ':') || strpos($odd, "$") 
            || strpos($odd, "%") || strpos($odd, '014')) {
            continue;
        } else if ($odd > 0) {
            $odd = "+".$odd;
        } else if ($odd == 100) {
            $odd = 'EVEN';
        }
        $data[$i][$n] = $odd;
    }
//////////////////////////////////////////////////////////////////////////////////

    // Only write to the lock table when there is an advantage for us
    if ($advantage && $new_odds_insert && !$started) {
        $locked_odds['bet_team'] = $bet_suggestion;
        $locked_odds['bet'] = $bet;
        $new_info = 1;
        // Delete all previous no bets from the locked odds table
        if ($previous_no_bet) {
            exe_sql('baseball',
                "DELETE FROM locked_odds_2014
                WHERE ds = '$date' 
                AND home = '$home'", 'delete'
            );
        }
        insert($database, 'locked_odds_2014', $locked_odds);
        // Divide the bet by 10 for our smaller scale test, then format and 
        // send an e-mail so we know how much/who to bet on
        $bet_2 = $bet / 10;
        $formatted_bet = number_format($bet_2, 2);
        //send_email("BET $formatted_bet ON ".$bet_suggestion, $bet_suggestion_display."\n".
            "Time = $game_time"."\n".
            "Home Pitcher Default = $home_pitcher_default_2013"."\n".
            "Away Pitcher Default = $away_pitcher_default_2013";
    // Add the "No Bets" to locked odds so they show up on the website
    } else if (!$advantage && $new_odds_insert && !$started) {
        $nobet = array();
        $nobet['home'] = $home;
        $nobet['away'] = $away;
        $nobet['bet_team'] = 'No Bet';
        $nobet['home_odds'] = $vegas_home_odds;
        $nobet['away_odds'] = $vegas_away_odds;
        $nobet['home_sim'] = $home_sim_win;
        $nobet['away_sim'] = $away_sim_win;
        $nobet['game_time'] = $current_odds[$hometime]['time'];
        $nobet['game_date'] = $current_odds[$hometime]['date'];
        $nobet['odds_time'] = $current_odds[$hometime]['ts'];
        $nobet['ds'] = $ds;
        if ($previous_no_bet) {
            update($database, 'locked_odds_2014', $nobet, 'home', $home, 'ds', $date, 'bet_team', 'No Bet');
        } else {
            insert($database, 'locked_odds_2014', $nobet);
        }
    }

    // If game is finished, add the results to bet table
    $results_sql = 'SELECT * 
        FROM bets_2014
        WHERE ds = "'.$ds.'" 
        AND home = "'.$home.'"
        AND game_time = "'.$current_odds[$hometime]['time'].'"
        AND game_date = "'.$current_odds[$hometime]['date'].'"';
    $results = exe_sql($database, $results_sql);
    // Check to see if the results already wrote so we're not duplicating 
    // entries in the table
    if (!$results) {
        $new_result_insert = 1;
    }
    if ($finished && $new_result_insert) {
        if ($bet !== 0) {
            $new_info = 1;
        }
        $locked_bets['home'] = $home;
        $locked_bets['away'] = $away;
        $locked_bets['game_time'] = $current_odds[$hometime]['time'];
        $locked_bets['game_date'] = $current_odds[$hometime]['date'];
        $locked_bets['vegas_home'] = $vegas_home_pct;
        $locked_bets['vegas_away'] = $vegas_away_pct;
        $locked_bets['sim_home'] = $sim_home_pct;
        $locked_bets['sim_away'] = $sim_away_pct;
        $locked_bets['bet_team'] = $bet_suggestion;
        $locked_bets['bet_amount'] = number_format($bet, 2);
        $locked_bets['bet_return'] = number_format($payout, 2);
        $locked_bets['bankroll'] = $bankroll;
        $locked_bets['ds'] = $ds;
        insert($database, 'bets_2014', $locked_bets);
    }

    // The following updates the status/scores as the games are played
    $update_sql = array();
    if (!$finished || ($finished && $new_result_insert)) {
        $update_sql['home_score'] = $current_scores[$hometime]['home_score'];
        $update_sql['away_score'] = $current_scores[$hometime]['away_score'];
        $update_sql['status'] = $current_scores[$hometime]['status'];
        if ($finished && $winner !== 2) {
            $update_sql['winner'] = $winner;
            $update_sql['payout'] = $payout;
        }
        $game_time = $current_odds[$hometime]['time'];
        update($database, 'locked_odds_2014', $update_sql, 'home', $home, 'ds', $date, 'game_time', $game_time);
    }

    $season_roi_sql = 
        'SELECT sum(bet_return) as bet_return, sum(bet_amount) as bet_amount FROM bets_2014';
    $season_roi = exe_sql($database, $season_roi_sql);
    $final_season_roi = number_format(($season_roi['bet_return'] / $season_roi['bet_amount'] * 100), 2);
    print_r($data[$i]);
}

if ($total_bet) {
    $daily_roi = number_format(((($total_payout) / $total_bet) * 100), 2)."%";
    $remaining = $total_games - $wins - $losses - $no_bets;
    echo "\n";
    echo "Daily ROI = ".$daily_roi."\n";
    echo 'Season ROI = '.$final_season_roi."%"."\n";
}

// Check to see if bankroll has already been logged to prevent
// duplicate entries in the bankroll table
$log_bankroll = exe_sql($database,
    "SELECT *
    FROM bankroll_2014
    WHERE ds = '$date'");
if (!$log_bankroll) {
    $new_bankroll = 1;
}

if (!$remaining && !$pending && $new_bankroll) {
    $updated_bankroll = $bankroll + $total_payout;
    $final_bankroll['bankroll'] = $updated_bankroll;
    $final_bankroll['ds'] = $date;
    insert($database, 'bankroll_2014', $final_bankroll);
}

?>
