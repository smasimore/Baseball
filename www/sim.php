<?php
include_once 'includes/db_connect.php';
include_once 'includes/functions.php';
include_once 'includes/sweetfunctions.php';
include_once 'includes/ui_elements.php';

////////////////////////////////////
//// Basic Parameters
//////////////////////////////////////
$magic = 'magic';
$start_date = '2014-04-01';
$cross_date = '2014-05-15';
$win_threshold = 50;
$win_plus_delta_threshold = 50;
$odds_threshold = -300;
$bet_home_games = true;
$bet_away_games = true;
$casino = 'sportsbook.ag';
$odds_metric = 'live';
$odds_cutoff = '_hour';

////////////////////////////////////
// Display & Multiplier Metrics
////////////////////////////////////
$cash = true;                       // False will show ROI (instead of $)
$default_bet_amount = 1000;          // Not used with Kelly Multiplier
$mult_on = false;                   // Adds betting multipler (will be overridden by Kelly)
$kelly_mult = false;                 // Turn on Kelly Multiplier
$massert = false;                    // Experimental Multiplier (overrides Kelly)
$bankroll = 100000;                 // Starting bankroll only used with Kelly turned on
$kelly_cap = 2500;                  // Currently overridden in function
$kelly_min = 250;                   // Currently overridden in function
$kelly_divide = 10;

//////////////////////////////////////
// SPECIAL OVERRIDES
//////////////////////////////////////
$bet_only_home_away = false;        // Options: 'Away', 'Home'
$bet_sim_winner = false;            // Disregards whether it's advantageous to bet
$bet_opposite = false;
$coin_flip = false;

function calculateBetMultiplier($mult_on, $base_bet, $sim, $vegas,
    $kelly_mult = null, $massert = null, $kelly_cap = null, $kelly_divide = null, $kelly_min = null, $bankroll = null) {
    if (!$vegas) {
        return 0;
    } else if ($kelly_mult) {
        $odds = convertPctToOdds($vegas / 100);
        if ($odds < 100) {
            $odds = 100 / (-1 * $odds); 
        } else {
            $odds = $odds / 100;
        }
        $fraction_bet = ((($sim / 100) * ($odds + 1)) - 1) / $odds;
        $total_bet = $fraction_bet * $bankroll;
        if ($kelly_divide) {
            $total_bet = $total_bet / $kelly_divide;
        }
        if ($massert) {
            // Tweak end number to get desired multiplier
            $massert = $sim * $odds * ($sim - $vegas) / 100000 * 1.5;
            $total_bet = $massert * $bankroll;
        }
        // EXPERIMENT - Make CAP .025 of bankroll
        $kelly_cap = $bankroll * .05;
        $kelly_min = $bankroll * .005;
        $capped_bet = min($total_bet, $kelly_cap);
        $capped_bet = max($capped_bet, $kelly_min);
        return $capped_bet;
    } else if (!$mult_on) {
        return $base_bet;
    }
    $advantage = $sim - $vegas;
    switch (true) {
        case ($advantage > 20):
            $mult = 9;
            break;
        case ($advantage > 17.5):
            $mult = 8;
            break;
        case ($advantage > 15):
            $mult = 7;
            break;
        case ($advantage > 12.5):
            $mult = 6;
            break;
        case ($advantage > 10):
            $mult = 5;
            break;
        case ($advantage > 7.5):
            $mult = 4;
            break;
        case ($advantage > 5):
            $mult = 3;
            break;
        case ($advantage > 2.5):
            $mult = 2;
            break;
        default:
            $mult = 1;
            break;
    }
    switch (true) {
        case ($sim > 75):
            $mult *= 7;
            break;
        case ($sim > 70):
            $mult *= 6;
            break;
        case ($sim > 65):
            $mult *= 5;
            break;
        case ($sim > 60):
            $mult *= 4;
            break;
        case ($sim > 55):
            $mult *= 3;
            break;
        case ($sim > 52.5):
            $mult *= 2;
            break;
        default:
            $mult *= 1;
            break;
    }
    $final_bet = $base_bet * $mult;
    return $final_bet;
}

function isAdvantage($sim, $vegas, $odds_threshold, $win_plus_delta_threshold, $win_threshold) {
    $delta = $sim - $vegas;
    $odds = convertPctToOdds($vegas/100);
    $win_plus_delta = $delta + $sim;
    if (($win_plus_delta >= $win_plus_delta_threshold) &&
        $delta > 0 && $odds >= $odds_threshold &&
        $sim >= $win_threshold) {
        return true;
    } else {
        return false;
    }
}

// Pull Data
$sim_table = "sim_output_$magic"."_2014";
$sim_table_last = "sim_output_$magic"."_2013";
$sims_sql = 
    "SELECT a.home_i as home, 
    a.away_i as away, 
    a.ds as game_date,
    a.time as game_time,
    a.home_sim_win,
    1-a.home_sim_win as away_sim_win,
    b.home_sim_win as home_sim_win_2013
    FROM $sim_table a 
    LEFT OUTER JOIN $sim_table_last b
    ON a.ds >= '$start_date'
    AND a.ds = b.ds
    AND a.time = b.time
    AND a.home_i = b.home_i
    AND b.ds < '$cross_date'
    WHERE a.ds < '$date'";
$sims = exe_sql('baseball', $sims_sql);
$sims = index_by($sims, array('home', 'game_date', 'game_time'));
foreach ($sims as $sim) {
    $home = $sim['home'];
    $game_date = $sim['game_date'];
    $game_time = $sim['game_time'];
    $game_hour = substr($game_time, 0, 2);
    $sims[$home.$game_date.$game_time]['game_hour'] = $game_hour;
}
$sims = index_by($sims, array('home', 'game_date', 'game_hour'));

$odds = exe_sql('baseball',
    "SELECT *
    FROM odds_aggregate_2014
    WHERE casino = '$casino'"
);
$odds = index_by($odds, array('home', 'game_date', 'game_time'));
foreach ($odds as $odd) {
    $home = $odd['home'];
    $game_date = $odd['game_date'];
    $game_time = $odd['game_time'];
    $game_hour = substr($game_time, 0, 2);
    $odds[$home.$game_date.$game_time]['home_odds_hour'] = json_decode($odds[$home.$game_date.$game_time]['home_odds_hour'], true);
    $odds[$home.$game_date.$game_time]['away_odds_hour'] = json_decode($odds[$home.$game_date.$game_time]['away_odds_hour'], true);
    $odds[$home.$game_date.$game_time]['game_hour'] = $game_hour;
}
$odds = index_by($odds, array('home', 'game_date', 'game_hour'));

$home_odds_metric = "$odds_metric"."_home_pct_win$odds_cutoff";
$away_odds_metric = "$odds_metric"."_away_pct_win$odds_cutoff";
$home_odds_payout = "$odds_metric"."_home_odds$odds_cutoff";
$away_odds_payout = "$odds_metric"."_away_odds$odds_cutoff";
if ($odds_metric == 'last') {
    $home_odds_metric = "last_home_pct_win";
    $away_odds_metric = "last_away_pct_win";
    $home_odds_payout = "last_home_odds";
    $away_odds_payout = "last_away_odds";
}

$scores =exe_sql('baseball',
    "SELECT *
    FROM live_scores_2014
    WHERE status like '%F%'"
);
$scores = index_by($scores, array('home', 'game_date', 'game_time'));
foreach ($scores as $score) {
    $home = $score['home'];
    $game_date = $score['game_date'];
    $game_time = $score['game_time'];
    $game_hour = substr($game_time, 0, 2);
    $scores[$home.$game_date.$game_time]['game_hour'] = $game_hour;
}
$scores = index_by($scores, array('home', 'game_date', 'game_hour'));

$final_array = array();
$total_bet = 0;
$total_return = 0;
$daily_roi = 0;
$bets = 0;
$wins = 0;
$max = 0;
$max_date = null;
$min = 0;
$original_bankroll = $bankroll;
$days = array();
$graph_x = null;
$graph_y = null;
$hacky_zero = null;
$graph_2013 = null;
$x_axis = 1;
foreach ($sims as $sim) {
    $home = $sim['home'];
    $away = $sim['away'];
    $game_date = $sim['game_date'];
    $game_time = $sim['game_time'];
    $game_hour = $sim['game_hour'];
    if ($sim['home_sim_win_2013']) {
        $home_sim_win = $sim['home_sim_win_2013'] * 100;
        $bet_year = '2013';
        $graph_2013 .= ",0";
    } else {
        $home_sim_win = $sim['home_sim_win'] * 100;
        $bet_year = '2014';
    }
    $away_sim_win = 100 - $home_sim_win;

    // Find Odds
    if ($odds_metric == 'live') {
        $search = 1;
        $i = 0;
        $odds_count = count($odds[$home.$game_date.$game_hour]['home_odds_hour']);
        while ($search && $i < $odds_count) {
            $home_odds = $odds[$home.$game_date.$game_hour]['home_odds_hour'][$i];
            $away_odds = $odds[$home.$game_date.$game_hour]['away_odds_hour'][$i];
            $home_vegas_win = convertOddsToPct($home_odds);
            $away_vegas_win = convertOddsToPct($away_odds);
            if ((isAdvantage($home_sim_win, $home_vegas_win, $odds_threshold, $win_plus_delta_threshold, $win_threshold) && $bet_home_games) ||
                (isAdvantage($away_sim_win, $away_vegas_win, $odds_threshold, $win_plus_delta_threshold, $win_threshold) && $bet_away_games)) {
                $search = 0;
            }
            $i++;
        }
    } else {
        $home_vegas_win = $odds[$home.$game_date.$game_hour][$home_odds_metric];
        $away_vegas_win = $odds[$home.$game_date.$game_hour][$away_odds_metric];
        $home_odds = $odds[$home.$game_date.$game_hour][$home_odds_payout];
        $away_odds = $odds[$home.$game_date.$game_hour][$away_odds_payout];
    }
    $home_delta = $home_sim_win - $home_vegas_win;
    $away_delta = $away_sim_win - $away_vegas_win;
    $home_win_plus_delta = $home_delta + $home_sim_win;
    $away_win_plus_delta = $away_delta + $away_sim_win;

    $home_score = $scores[$home.$game_date.$game_hour]['home_score'];
    $away_score = $scores[$home.$game_date.$game_hour]['away_score'];
    // TODO: FIX DOUBLE HEADERS HERE AFTER
    if (!$home_score && !$away_score) {
        $home_score = $scores[$home.$game_date.'1']['home_score'];
        $away_score = $scores[$home.$game_date.'1']['away_score'];
    }
    if (!$home_score && !$away_score) {
        continue;
    }

    // Find betting advantage
    $bet_team = null;
    $bet_sim = null;
    $bet_vegas = null;
    if (isAdvantage($home_sim_win, $home_vegas_win, $odds_threshold, $win_plus_delta_threshold, $win_threshold) && $bet_home_games) {
        $bet_team = 'Home';
        $bet_sim = $home_sim_win;
        $bet_vegas = $home_vegas_win;
    } else if (isAdvantage($away_sim_win, $away_vegas_win, $odds_threshold, $win_plus_delta_threshold, $win_threshold) && $bet_away_games) {
        $bet_team = 'Away';
        $bet_sim = $away_sim_win;
        $bet_vegas = $away_vegas_win;
    } else {
        $bet_team = 'No Bet';
    }
    // Do situational bets (overrides at top)
    // For now doing this turns off the multiplier
    switch (true) {
        case $bet_only_home_away:
            $disclaimer = "Bet All $bet_only_home_away";
            $mult_on = 0;
            $bet_team = $bet_only_home_away;
            break;
        case $bet_sim_winner:
            $disclaimer = "Bet Sim Winner";
            $mult_on = 0;
            if ($home_sim_win > $away_sim_win) {
                $bet_team = 'Home';
            } else {
                $bet_team = 'Away';
            }
            break;
        case $bet_opposite:
            $disclaimer = "Bet Opposite";
            $mult_on = 0;
            if ($home_sim_win > $away_sim_win) {
                $bet_team = 'Away';
            } else {
                $bet_team = 'Home';
            }
            break;
        case $coin_flip:
            $rand = rand(0,1);
            $disclaimer = "Coin Flip";
            $mult_on = 0;
            if ($rand == 1) {
                $bet_team = 'Away';
            } else {
                $bet_team = 'Home';
            }
            break;
    }
    // Find game winner
    $winner = null;
    if ($home_score > $away_score) {
        $winner = 'Home';
    } else if ($away_score > $home_score) {
        $winner = 'Away';
    }
    // Find whether you bet on the winner
    $bet_amount = calculateBetMultiplier($mult_on, $default_bet_amount, $bet_sim, $bet_vegas, $kelly_mult, $massert, $kelly_cap, $kelly_divide, $kelly_min, $bankroll);
    $bet_return = null;
    $bet_winner = null;
    switch (true) {
        case ($bet_team == 'No Bet'):
            $bet_winner = 0;
            $bet_amount = 0;
            $winner_display = 'No Bet';
            break;
        case ($bet_team == $winner):
            $bet_winner = 1;
            $winner_display = 'W';
            break;
        case ($bet_team !== $winner):
            $bet_winner = -1;
            $winner_display = "L";
            break;
    }
    // Find out how much you made/lost on the bet
    switch ($bet_team) {
        case 'Home':
            $bet_return = $bet_amount * $bet_winner;
            if ($bet_return > 0) {
                $bet_return = calculatePayout($bet_amount, $home_odds);
            }
            break;
        case 'Away':
            $bet_return = $bet_amount * $bet_winner;
            if ($bet_return > 0) {
                $bet_return = calculatePayout($bet_amount, $away_odds);
            } 
            break;
        default:
            $bet_return = 0;
            break;
    }

    if (!$home_vegas_win) {
        continue;
    }

    $final_array[$home.$game_date.$game_hour]['game_date'] = $game_date;
    $final_array[$home.$game_date.$game_hour]['away'] = $away;
    $final_array[$home.$game_date.$game_hour]['home'] = $home;
    $final_array[$home.$game_date.$game_hour]['away_score'] = $away_score;
    $final_array[$home.$game_date.$game_hour]['home_score'] = $home_score;
    $final_array[$home.$game_date.$game_hour]['away_sim_win'] = $away_sim_win;
    $final_array[$home.$game_date.$game_hour]['home_sim_win'] = $home_sim_win;
    $final_array[$home.$game_date.$game_hour]['away_vegas_win'] = $away_vegas_win;
    $final_array[$home.$game_date.$game_hour]['home_vegas_win'] = $home_vegas_win;
    $final_array[$home.$game_date.$game_hour]['away_vegas_odds'] = $away_odds;
    $final_array[$home.$game_date.$game_hour]['home_vegas_odds'] = $home_odds;
    $final_array[$home.$game_date.$game_hour]['away_delta'] = $away_delta;
    $final_array[$home.$game_date.$game_hour]['home_delta'] = $home_delta;
    $final_array[$home.$game_date.$game_hour]['bet_team'] = $bet_team;
    //$final_array[$home.$game_date.$game_hour]['data year'] = $bet_year;
    $final_array[$home.$game_date.$game_hour]['W/L'] = $winner_display;
    $final_array[$home.$game_date.$game_hour]['bet_amount'] = $bet_amount;
    $final_array[$home.$game_date.$game_hour]['bet_return'] = $bet_return;

    $total_bet += $bet_amount;
    $total_return += $bet_return;
    $bankroll += $bet_return;
    if ($total_return > $max) {
        $max = $total_return;
        $max_date = $game_date;
    } else if ($total_return < $min) {
        $min = $total_return;
    }
    if ($total_bet) {
        $daily_roi = $total_return / $total_bet * 100;
    }
    if ($bet_amount > 0) {
        $bets += 1;
    }
    if ($bet_return > 0) {
        $wins += 1;
    }
    // Count number of days for average bet/day
    $days[$game_date] = 1;

    //$final_array[$home.$game_date.$game_hour]['total_bet'] = $total_bet;
    if ($kelly_mult) {
        //$final_array[$home.$game_date.$game_hour]['bankroll'] = $bankroll;
    }
    $final_array[$home.$game_date.$game_hour]['total_return'] = $total_return;
    $final_array[$home.$game_date.$game_hour]['roi'] = number_format($daily_roi, 2)."%";

    // Make Graph
    $graph_x .= ",'".$x_axis."'";
    $x_axis++;
    if ($cash) {
        $graph_y .= ",".$total_return;
    } else {
        $graph_y .= ",".$daily_roi;
    }
    $hacky_zero .= ", 0";

}

$num_days = count($days);
$bankroll_roi = number_format($total_return / $original_bankroll * 100, 2);
$total_bet_day = number_format($total_bet / $num_days);
$total_bet = number_format($total_bet);
$total_return = number_format($total_return);
$roi = number_format($daily_roi, 2);
$win_pct = number_format($wins / $bets * 100);

$header = "ROI = $roi% | Return = $$total_return | Total Bet = $$total_bet | Avg. Daily Bet = $$total_bet_day";
if ($kelly_mult) {
    $header = "ROI = $roi% | Return = $$total_return | Bankroll Return = $bankroll_roi% | Total Bet = $$total_bet | Avg. Daily Bet = $$total_bet_day";
}
if ($disclaimer) {
    $header = "($disclaimer) ROI = $roi% | Return = $$total_return | Total Bet = $$total_bet";
}
$subheader = "Total Bets = $bets | Total Wins = $wins | Win Pct = $win_pct%";
$min_max_header = "Max Gain = $".number_format($max, 0)." | Max Loss = -$".number_format(-$min, 0);
$min_max_subheader = "Record Day: $max_date | 2013 Data (Red Bar) Through $cross_date";

// Calculate data for Sim Understand
$team_map = $team_mapping;
$home_away_results = calculate_situation_roi($final_array, 'home_away', null, true);
$sim_score_results = calculate_situation_roi($final_array, 'sim_score', null, true);
//$delta_results = calculate_situation_roi($final_array, 'delta', null, true);
$vegas_score_results = calculate_situation_roi($final_array, 'vegas_score', null, true);
$bet_team_results = calculate_situation_roi($final_array, 'bet_team', $team_map, true);
$any_team_results = calculate_situation_roi($final_array, 'any_team', $team_map, true);

sec_session_start();

$graph_x = substr($graph_x, 1);
$graph_y = substr($graph_y, 1);
$graph_2013 = substr($graph_2013, 1);
$hacky_zero = substr($hacky_zero, 1);

?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>ROI Sim</title>
        <link 
            rel="shortcut icon" 
            href="http://icons.iconarchive.com/icons/custom-icon-design/
                pretty-office-6/256/baseball-icon.png">
        <link rel="stylesheet" href="css/index.css" />
        <script type="text/JavaScript" src="js/tables.js"></script>
        <script type="text/JavaScript" src="js/Chart.js/Chart.js"></script>
        <meta name = "viewport" content = "initial-scale = 1, user-scalable = yes">
        <style>
            canvas{
            }
        </style>
    </head>
    <body>
    <?php if (login_check($mysqli) == true) {
            //ui_page_header_odds();
            ui_page_header($header, $subheader);
            ui_table($final_array);
            ui_table($home_away_results, 'home_away', true);
            ui_table($sim_score_results, 'sim_score', true);
            //ui_table($delta_results, 'delta', true);
            ui_table($vegas_score_results, 'vegas_score', true);
            ui_table($bet_team_results, 'bet_team', true);
            ui_table($any_team_results, 'any_team', true);
            ui_page_header($min_max_header, $min_max_subheader);
            $secure = 1;
        } else {
            ui_error_logged_out();
        }
    ?>
    <canvas id="season" height="450" width="1700"></canvas>

    <script>

    var secure = [<?php echo $secure; ?>]
    var graph_x = [<?php echo $graph_x; ?>]
    var graph_y = [<?php echo $graph_y; ?>]
    var hacky_zero = [<?php echo $hacky_zero; ?>]
    var graph_2013 = [<?php echo $graph_2013; ?>]

    var season = {
      labels : graph_x,
      datasets : [
        {
            fillColor : "rgba(220,220,220,0.5)",
            strokeColor : "rgba(220,220,220,1)",
            pointColor : "rgba(220,220,220,1)",
            pointStrokeColor : "#fff",
            data : hacky_zero
        },
        {
          fillColor : "rgba(151,187,205,0.5)",
          strokeColor : "rgba(151,187,205,1)",
          pointColor : "rgba(151,187,205,1)",
          pointStrokeColor : "#fff",
          data : graph_y 
        },
        {
          fillColor : "rgba(151,0,0,.5)",
          strokeColor : "rgba(151,0,0,1)",
          pointColor : "rgba(151,0,0,1)",
          pointStrokeColor : "#ff",
          data : graph_2013
        }
      ]

    }

    if (secure == 1) {
        var myLine = new Chart(document.getElementById("season").getContext("2d")).Line(season);
    }

    </script> 
    </body>
</html>
