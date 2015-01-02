<?php
include_once 'psl-config.php';
include_once __DIR__ . '/../../Scripts/Include/mysql.php';
 
function sec_session_start() {
    $session_name = 'sec_session_id';   // Set a custom session name
    $secure = SECURE;
    // This stops JavaScript being able to access the session id.
    $httponly = true;
    // Forces sessions to only use cookies.
    if (ini_set('session.use_only_cookies', 1) === FALSE) {
        header("Location: ../error.php?err=Could not initiate a safe session (ini_set)");
        exit();
    }
    // Gets current cookies params.
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params($cookieParams["lifetime"],
        $cookieParams["path"], 
        $cookieParams["domain"], 
        $secure,
        $httponly);
    // Sets the session name to the one set above.
    session_name($session_name);
    session_start();            // Start the PHP session 
    session_regenerate_id();    // regenerated the session, delete the old one. 
}

function login($username, $password, $mysqli) {
    // Using prepared statements means that SQL injection is not possible. 
    if ($stmt = $mysqli->prepare("SELECT id, username, password, salt 
        FROM members
       WHERE username = ?
        LIMIT 1")) {
        $stmt->bind_param('s', $username);  // Bind "$username" to parameter.
        $stmt->execute();    // Execute the prepared query.
        $stmt->store_result();

        // get variables from result.
        $stmt->bind_result($user_id, $username, $db_password, $salt);
        $stmt->fetch();

        // hash the password with the unique salt
        $password = hash('sha512', $password . $salt);
        if ($stmt->num_rows == 1) {
            // If the user exists we check if the account is locked
            // from too many login attempts 
 
            if (checkbrute($user_id, $mysqli) == true) {
                // Account is locked 
                // Send an email to user saying their account is locked
                return false;
            } else {
                // Check if the password in the database matches
                // the password the user submitted.
                if ($db_password == $password) {
                    // Password is correct!
                    // Get the user-agent string of the user.
                    $user_browser = $_SERVER['HTTP_USER_AGENT'];
                    // XSS protection as we might print this value
                    $user_id = preg_replace("/[^0-9]+/", "", $user_id);
                    $_SESSION['user_id'] = $user_id;
                    // XSS protection as we might print this value
                    $username = preg_replace("/[^a-zA-Z0-9_\-]+/", 
                                                                "", 
                                                                $username);
                    $_SESSION['username'] = $username;
                    $_SESSION['login_string'] = hash('sha512', 
                        $password . $user_browser);
                    $_SESSION['timestamp'] = time();
                    // Login successful.
                    return true;
                } else {
                    // Password is not correct
                    // We record this attempt in the database
                    $now = time();
                    $mysqli->query("INSERT INTO login_attempts(user_id, time)
                                    VALUES ('$user_id', '$now')");
                    return false;
                }
            }
        } else {
            // No user exists.
            return false;
        }
    }
}

function checkbrute($user_id, $mysqli) {
    // Get timestamp of current time 
    $now = time();
 
    // All login attempts are counted from the past 2 hours. 
    $valid_attempts = $now - (2 * 60 * 60);
 
    if ($stmt = $mysqli->prepare("SELECT time 
                             FROM login_attempts <code><pre>
                             WHERE user_id = ? 
                            AND time > '$valid_attempts'")) {
        $stmt->bind_param('i', $user_id);
 
        // Execute the prepared query. 
        $stmt->execute();
        $stmt->store_result();
 
        // If there have been more than 5 failed logins 
        if ($stmt->num_rows > 5) {
            return true;
        } else {
            return false;
        }
    }
}

function login_check($mysqli) {
    // Check if all session variables are set 
    if (isset($_SESSION['user_id'], 
                        $_SESSION['username'], 
                        $_SESSION['login_string'],
                        $_SESSION['timestamp'])) {
        $user_id = $_SESSION['user_id'];
        $login_string = $_SESSION['login_string'];
        $username = $_SESSION['username'];
        $time_logged_in = $_SESSION['timestamp'];

        // If logged in for more than 24 hours log out
        if (time() - $time_logged_in > 84600) {
            logout();
            return false;
        }
 
        // Get the user-agent string of the user.
        $user_browser = $_SERVER['HTTP_USER_AGENT'];
 
        if ($stmt = $mysqli->prepare("SELECT password 
                                      FROM members 
                                      WHERE id = ? LIMIT 1")) {
            // Bind "$user_id" to parameter. 
            $stmt->bind_param('i', $user_id);
            $stmt->execute();   // Execute the prepared query.
            $stmt->store_result();
 
            if ($stmt->num_rows == 1) {
                // If the user exists get variables from result.
                $stmt->bind_result($password);
                $stmt->fetch();
                $login_check = hash('sha512', $password . $user_browser);
 
                if ($login_check == $login_string) {
                    // Logged In!!!! 
                    return true;
                } else {
                    // Not logged in 
                    return false;
                }
            } else {
                // Not logged in 
                return false;
            }
        } else {
            // Not logged in 
            return false;
        }
    } else {
        // Not logged in 
        return false;
    }
}

function esc_url($url) {
 
    if ('' == $url) {
        return $url;
    }
 
    $url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $url);
 
    $strip = array('%0d', '%0a', '%0D', '%0A');
    $url = (string) $url;
 
    $count = 1;
    while ($count) {
        $url = str_replace($strip, '', $url, $count);
    }
 
    $url = str_replace(';//', '://', $url);
 
    $url = htmlentities($url);
 
    $url = str_replace('&amp;', '&#038;', $url);
    $url = str_replace("'", '&#039;', $url);
 
    if ($url[0] !== '/') {
        // We're only interested in relative links from $_SERVER['PHP_SELF']
        return '';
    } else {
        return $url;
    }
}

function s_log($data) {
    $type = "\n" . gettype($data);
    file_put_contents(
        __DIR__ . '/../sarah_errors.txt',
        "$type - ", 
        FILE_APPEND
    );

    // if class print name of class
    if (is_object($data)) {
        $data = get_class($data);
    }
    if (!is_array($data)) {
        file_put_contents(
            __DIR__ . '/../sarah_errors.txt',
            "$data", 
            FILE_APPEND
        );
        return;
    }
    $data = json_encode($data);
    file_put_contents(
        __DIR__ . '/../sarah_errors.txt',
        "$data", 
        FILE_APPEND
    );
}

function smart_format($num, $digits = 2) {
    if (gettype($num) == 'double') {
        $num = number_format($num, $digits);
    }
    return $num;
}

function d_log($data) {
    $type = "\n" . gettype($data);
    file_put_contents(
        '../dan_errors.txt',
        "$type - ",
        FILE_APPEND
    );

    // if class print name of class
    if (is_object($data)) {
        $data = get_class($data);
    }
    if (!is_array($data)) {
        file_put_contents(
            "../dan_errors.txt",
            "$data",
            FILE_APPEND
        );
        return;
    }
    $data = json_encode($data);
    file_put_contents(
        "../dan_errors.txt",
        "$data",
        FILE_APPEND
    );
}

function logout() {
    // Unset all session values
    $_SESSION = array();

    // Destroy session
    session_destroy();
}

function index_by($data, $index, $index_2 = null, $index_3 = null) {
    if (!$data) {
        return array();
    }

    $indexed_table = array();
    foreach ($data as $row) {
        $i1 = $row[$index];
        if ($index_3) {
            $i3 = $row[$index_3];
            $i2 = $row[$index_2];
            $indexed_table[$i1.$i2.$i3] = $row;
        } else if ($index_2) {
            $i2 = $row[$index_2];
            $indexed_table[$i1.$i2] = $row;
        } else {
            $indexed_table[$i1] = $row;
        }
    }
    return $indexed_table;
}

function format_render($string) {
    return ucwords(str_replace('_', ' ', $string));
}

function team_batting_by_player($lineup_stats, $split = 'Total') {
    if (!count($lineup_stats)) {
        return array();
    }
    $player_avg = array();
    foreach ($lineup_stats as $i => $player) {
        $total = $player[$split];
        $player_avg[$i]['avg'] =
            $total['pct_single'] +
            $total['pct_double'] +
            $total['pct_triple'] +
            $total['pct_home_run'];
        $player_avg[$i]['player_name'] = $total['player_name'];
        $player_avg[$i]['default'] = $total['default'];
    }   
    return $player_avg;
}

function team_batting_avg($lineup_stats, $split = 'Total') {
    if (!count($lineup_stats)) {
        return 0;
    }
    $player_avg = array();
    foreach ($lineup_stats as $player) {
        $total = $player[$split];
        $player_avg[] =
            $total['pct_single'] +
            $total['pct_double'] +
            $total['pct_triple'] +
            $total['pct_home_run'];
    }
    return round(array_sum($player_avg) / count($player_avg), 3);
}

function calculate_daily_record($odds_data) {
    if (!$odds_data) {
        return null;
    }
    $record = array();
    foreach ($odds_data as $odds) {
        $status = $odds['winner'];
        switch (true) {
            case ($status === '1'):
                $record['W'] += 1;
                break;
            case ($status === '0'):
                $record['L'] += 1;
                break;
            default:
                $record['N'] += 1;
        }
    }
    return $record;
}   

function format_odds_table($odds_data) {
    if (!$odds_data) {
        return null;
    }
    $final_odds = array();
    foreach ($odds_data as $odds) {
        $home = $odds['home'];
        $away = $odds['away'];
        $home_score = $odds['home_score'];
        $away_score = $odds['away_score'];
        $vegas_away = convertOddsToPct($odds['away_odds']);
        $vegas_home = convertOddsToPct($odds['home_odds']);
        $sim_away = $odds['away_sim'];
        $sim_home = $odds['home_sim'];
        $away_delta = ($sim_away * 100) - $vegas_away;
        $home_delta = ($sim_home * 100) - $vegas_home;
        $bet_team = $odds['bet_team'];
        $bet_team_type = null;
        if ($bet_team == $home) {
            $bet_team_type = 'home';
        } else if ($bet_team == $away) {
            $bet_team_type = 'away';
        }
        $confidence = null;
        if ($bet_team_type) {
            $index = $bet_team_type."_sim";
            $confidence = $odds[$index];
        }
        $pending = 0;
        if (!$odds['status'] || strpos($odds['status'], 'PM') ||
            strpos($odds['status'], 'AM')) {
            $pending = 1;
        }
        // Skip game if it's postponed
        if ($odds['status'] == 'Postponed') {
            continue;
        }
        if ($bet_team == $home) {
            $bet_odds = $odds['home_odds'];
            $odds_delta = number_format($home_delta, 2)."%";
        } else if ($bet_team == $away) {
            $bet_odds = $odds['away_odds'];
            $odds_delta = number_format($away_delta, 2)."%";
        } else {
            $bet_odds = "-";
            $odds_delta = "-";
        }
        if ($pending) {
            $matchup = "$away vs. $home";
        } else {
            $matchup = "$away ($away_score) vs. $home ($home_score)";
        }
        if ($bet_odds > 0) {
            $bet_odds = "+".$bet_odds;
        }
        $result = null;
        $winner = $odds['winner'];
        switch (true) {
            case ($winner === '1'):
                $result = 'W';
                break;
            case ($winner === '0'):
                $result = 'L';
                break;
            case (!$pending && $bet_team !== 'No Bet' && $home_score == $away_score):
                $result = 'Tied';
                break;
            case (($bet_team == $home && $home_score > $away_score) ||
                ($bet_team == $away && $away_score > $home_score)):
                $margin = max(($home_score - $away_score),($away_score - $home_score));
                $result = "Winning By $margin";
                break;
            case (($bet_team == $away && $home_score > $away_score) ||
                ($bet_team == $home && $away_score > $home_score)):
                $margin = max(($home_score - $away_score),($away_score - $home_score));
                $result = "Losing By $margin";
                break;
            default:
                $result = '-';
                break;
        }
        $time = substr($odds['game_time'], 0, 5);
        $hour = split_string($time, ":", BEFORE, EXCL);
        $minute = split_string($time, ":", AFTER, EXCL);
        if ($hour > 12) {
            $hour -= 12;
            $ampm = 'PM';
        } else if ($hour == 12) {
            $ampm = 'PM';
        } else {
            $ampm = 'AM';
        }
        $time = "$hour:$minute $ampm";
        if ($pending) {
            $status = 'Not Started';
        } else {
            $status = $odds['status'];
        }
        $final_odds[$home.$game_time]['game_time'] = $time;
        $final_odds[$home.$game_time]['matchup'] = $matchup;
        $final_odds[$home.$game_time]['bet_team (% Win) : Odds'] = $bet_team;
        if ($confidence) {
            $final_odds[$home.$game_time]['bet_team (% Win) : Odds'] = $bet_team." (".number_format($confidence * 100, 0)."%) : $bet_odds";
        }
        $final_odds[$home.$game_time]['advantage'] = $odds_delta;
        $final_odds[$home.$game_time]['bet amount'] = "$".number_format($odds['bet'] / 10, 2);
        // Add some color to the result field
        switch (true) {
            case (strpos($result, 'inning') || $result == 'W'):
                $final_odds[$home.$game_time]['result'] = "<font color='green'>$result</font>";
                break;
            case (strpos($result, 'osing') || $result == 'L'):
                $final_odds[$home.$game_time]['result'] = "<font color='red'>$result</font>";
                break;
            default:
                $final_odds[$home.$game_time]['result'] = $result;
                break;
        }
        $final_odds[$home.$game_time]['status'] = $status;
        if (strpos($result, 'inning')) {
            $final_odds[$home.$game_time]['result'] = "<font color='green'>$result</font>";
        }
    }
    return $final_odds;
}

function calculate_day_roi($investment_data, $date) {
    foreach ($investment_data as $game) {
        if ($game['ds'] == $date) {
            $total_bet += $game['bet_amount'];
            $total_return += $game['bet_return'];
        }
    }
    if ($total_bet) {
        return number_format((($total_return / $total_bet) * 100), 2);
    } else {
        return 0;
    }
}

function calculate_season_roi($investment_data) {
    $result = array();
    foreach ($investment_data as $game) {
        if (!$game['bet_amount']) {
            continue;
        }
        $total_bet += $game['bet_amount'];
        $total_return += $game['bet_return'];
        if ($game['bet_return'] > 0) {
            $result['W'] += 1;
        } else {
            $result['L'] += 1;
        }
    }
    if ($total_bet) {
        $result['bet'] = $total_bet;
        $result['return'] = $total_return;
        $result['roi'] = number_format((($total_return / $total_bet) * 100), 2);
        return $result;
    } else {
        return 0;
    }
}

function get_sim_group($bet_sim) {
    switch (true) {
        case ($bet_sim < 40):
            $sim_group = 'Sim 0% - 40%';
            break;
        case ($bet_sim >= 40 && $bet_sim < 45):
            $sim_group = 'Sim 40% - 45%';
            break;
        case ($bet_sim >= 45 && $bet_sim < 50):
            $sim_group = 'Sim 45% - 50%';
            break;
        case ($bet_sim >= 50 && $bet_sim < 55):
            $sim_group = 'Sim 50% - 55%';
            break;
        case ($bet_sim >= 55 && $bet_sim < 60):
            $sim_group = 'Sim 55% - 60%';
            break;
        case ($bet_sim >= 60 && $bet_sim < 65):
            $sim_group = 'Sim 60% - 65%';
            break;
        case ($bet_sim >= 65 && $bet_sim < 70):
            $sim_group = 'Sim 65% - 70%';
            break;
        case ($bet_sim >= 70 && $bet_sim < 75):
            $sim_group = 'Sim 70% - 75%';
            break;
        case ($bet_sim >= 75):
            $sim_group = 'Sim > 75%';
            break;
    }
    return $sim_group;
}

function calculate_sim_performance($betting_data) {
    $results = array();
    foreach ($betting_data as $game) {
        if ($game['pitcher_detault'] > 0) {
            continue;
        }
        $home_sim = $game['home_sim_win'];
        $away_sim = $game['away_sim_win'];
        $winner = $game['winner'];
        $home_group = get_sim_group($home_sim);
        $away_group = get_sim_group($away_sim);
        if ($winner == 'away') {
            $results[$away_group]['wins'] += 1;
            $results[$away_group]['total'] += 1;
            $results[$home_group]['total'] += 1;
        } else {
            $results[$home_group]['wins'] += 1;
            $results[$home_group]['total'] += 1;
            $results[$away_group]['total'] += 1;
        }
    }
    $formatted_results = array();
    foreach ($results as $name => $data) {
        $win_pct = $data['wins'] / $data['total'];
        $formatted_results[$name]['sim_group'] = $name;
        $formatted_results[$name]['win_pct'] = $win_pct;
        $formatted_results[$name]['wins'] = $data['wins'];
        $formatted_results[$name]['total_games'] = $data['total'];
    }
    ksort($formatted_results);
    return $formatted_results;
}

function calculate_situation_roi($betting_data, $situation, $team_map = null, $sim = null) {
    foreach ($betting_data as $game) {
        $bet_team = $game['bet_team'];
        if (!$game['bet_amount']) {
            continue;
        }
        $home = $game['home'];
        $away = $game['away'];
        $bet_amount = $game['bet_amount'];
        $bet_return = $game['bet_return'];
        // Pull some information differently if running from sim page
        if ($sim) {
            if ($bet_team == 'Home') {
                $bet_team = $home;
            } else if ($bet_team == 'Away') {
                $bet_team = $away;
            }
            $sim_home = $game['home_sim_win'];
            $sim_away = $game['away_sim_win'];
            $vegas_home = $game['home_vegas_win'];
            $vegas_away = $game['away_vegas_win'];
        } else {
            $sim_home = $game['sim_home'];
            $sim_away = $game['sim_away'];
            $vegas_home = $game['vegas_home'];
            $vegas_away = $game['vegas_away'];
        }
        $bet_sim = null;
        $bet_vegas = null;
        $sim_group = null;
        $vegas_group = null;
        if ($bet_team == $home) {
            $bet_sim = $sim_home;
            $bet_vegas = $vegas_home;
            $groups['Home'] += 1;
            $total_bet['Home'] += $bet_amount;
            $total_return['Home'] += $bet_return;
            if ($bet_return > 0) {
                $wins['Home'] += 1;
            } else {
                $losses['Home'] += 1;
            }
        } else if ($bet_team == $away) {
            $bet_sim = $sim_away;
            $bet_vegas = $vegas_away;
            $groups['Away'] += 1;
            $total_bet['Away'] += $bet_amount;
            $total_return['Away'] += $bet_return;
            if ($bet_return > 0) {
                $wins['Away'] += 1;
            } else {
                $losses['Away'] += 1;
            }
        } 

        switch (true) {
            case ($bet_sim < 40):
                $sim_group = 'Sim < 40%';
                break;
            case ($bet_sim >= 40 && $bet_sim < 45):
                $sim_group = 'Sim 40% - 45%';
                break;
            case ($bet_sim >= 45 && $bet_sim < 50):
                $sim_group = 'Sim 45% - 50%';
                break;
            case ($bet_sim >= 50 && $bet_sim < 55):
                $sim_group = 'Sim 50% - 55%';
                break;
            case ($bet_sim >= 55 && $bet_sim < 60):
                $sim_group = 'Sim 55% - 60%';
                break;
            case ($bet_sim >= 60 && $bet_sim < 65):
                $sim_group = 'Sim 60% - 65%';
                break;
            case ($bet_sim >= 65 && $bet_sim < 70):
                $sim_group = 'Sim 65% - 70%';
                break;
            case ($bet_sim >= 70 && $bet_sim < 75):
                $sim_group = 'Sim 70% - 75%';
                break;
            case ($bet_sim >= 75):
                $sim_group = 'Sim > 75%';
                break;
        }

        $groups[$sim_group] += 1;
        $total_bet[$sim_group] += $bet_amount;
        $total_return[$sim_group] += $bet_return;
        if ($bet_return > 0) {
            $wins[$sim_group] += 1;
        } else {
            $losses[$sim_group] += 1;
        }

        switch (true) {
            case ($bet_vegas < 40):
                $vegas_group = 'Vegas < 40%';
                break;
            case ($bet_vegas >= 40 && $bet_vegas < 45):
                $vegas_group = 'Vegas 40% - 45%';
                break;
            case ($bet_vegas >= 45 && $bet_vegas < 50):
                $vegas_group = 'Vegas 45% - 50%';
                break;
            case ($bet_vegas >= 50 && $bet_vegas < 55):
                $vegas_group = 'Vegas 50% - 55%';
                break;
            case ($bet_vegas >= 55 && $bet_vegas < 60):
                $vegas_group = 'Vegas 55% - 60%';
                break;
            case ($bet_vegas >= 60 && $bet_vegas < 65):
                $vegas_group = 'Vegas 60% - 65%';
                break;
            case ($bet_vegas >= 65 && $bet_vegas < 70):
                $vegas_group = 'Vegas 65% - 70%';
                break;
            case ($bet_vegas >= 70 && $bet_vegas < 75):
                $vegas_group = 'Vegas 70% - 75%';
                break;
            case ($bet_vegas >= 75):
                $vegas_group = 'Vegas > 75%';
                break;
        }
        $groups[$vegas_group] += 1;
        $total_bet[$vegas_group] += $bet_amount;
        $total_return[$vegas_group] += $bet_return;
        if ($bet_return > 0) {
                $wins[$vegas_group] += 1;
            } else {
                $losses[$vegas_group] += 1;
            }
        if ($team_map) {
            foreach ($team_map as $team) {
                if ($bet_team == $team) {
                    $total_bet[$team] += $bet_amount;
                    $total_return[$team] += $bet_return;
                    $groups[$team] += 1;
                    if ($bet_return > 0) {
                        $wins[$team] += 1;
                    } else {
                        $losses[$team] += 1;
                    }
                }
            }
        }
        $any_home = "ANY ".$home;
        $any_away = "ANY ".$away;
        $total_bet[$any_home] += $bet_amount;
        $total_bet[$any_away] += $bet_amount;
        $total_return[$any_home] += $bet_return;
        $total_return[$any_away] += $bet_return;
        $groups[$any_home] += 1;
        $groups[$any_away] += 1;
        if ($bet_return > 0) {
            $wins[$any_home] += 1;
            $wins[$any_away] += 1;
        } else {
            $losses[$any_home] += 1;
            $losses[$any_away] += 1;
        }
    }

    switch ($situation) {
        case 'home_away':
            $result['Home']['Bet Home/Away'] = 'Home';
            $result['Away']['Bet Home/Away'] = 'Away';
            $include = array('Home','Away');
            break;
        case 'sim_score':
            $result['Sim < 40%']['Bet Sim Score'] = 'Sim < 40%';
            $result['Sim 40% - 45%']['Bet Sim Score'] = 'Sim 40% - 45%';
            $result['Sim 45% - 50%']['Bet Sim Score'] = 'Sim 45% - 50%';
            $result['Sim 50% - 55%']['Bet Sim Score'] = 'Sim 50% - 55%';
            $result['Sim 55% - 60%']['Bet Sim Score'] = 'Sim 55% - 60%';
            $result['Sim 60% - 65%']['Bet Sim Score'] = 'Sim 60% - 65%';
            $result['Sim 65% - 70%']['Bet Sim Score'] = 'Sim 65% - 70%';
            $result['Sim 70% - 75%']['Bet Sim Score'] = 'Sim 70% - 75%';
            $result['Sim > 75%']['Bet Sim Score'] = 'Sim > 75%';
            $include = array('Sim < 40%','Sim 40% - 45%','Sim 45% - 50%',
                'Sim 50% - 55%','Sim 55% - 60%','Sim 60% - 65%',
                'Sim 65% - 70%','Sim 70% - 75%','Sim > 75%');
            break;
        case 'vegas_score':
            $result['Vegas < 40%']['Bet Vegas Score'] = 'Vegas < 40%';
            $result['Vegas 40% - 45%']['Bet Vegas Score'] = 'Vegas 40% - 45%';
            $result['Vegas 45% - 50%']['Bet Vegas Score'] = 'Vegas 45% - 50%';
            $result['Vegas 50% - 55%']['Bet Vegas Score'] = 'Vegas 50% - 55%';
            $result['Vegas 55% - 60%']['Bet Vegas Score'] = 'Vegas 55% - 60%';
            $result['Vegas 60% - 65%']['Bet Vegas Score'] = 'Vegas 60% - 65%';
            $result['Vegas 65% - 70%']['Bet Vegas Score'] = 'Vegas 65% - 70%';
            $result['Vegas 70% - 75%']['Bet Vegas Score'] = 'Vegas 70% - 75%';
            $result['Vegas > 75%']['Bet Vegas Score'] = 'Vegas > 75%';
            $include = array('Vegas < 40%','Vegas 40% - 45%','Vegas 45% - 50%',
                'Vegas 50% - 55%','Vegas 55% - 60%','Vegas 60% - 65%',
                'Vegas 65% - 70%','Vegas 70% - 75%','Vegas > 75%');
            break;
        case 'bet_team':
            $include = array();
            arsort($groups);
            foreach ($groups as $name => $size) {
                if (in_array($name, $team_map)) {
                    $result[$name]['Bet Team'] = $name;
                    array_push($include, $name);
                }
            }
            break;
        case 'any_team':
            $include = array();
            foreach ($team_map as $team) {
                $new_name = 'ANY '.$team;
                $result[$new_name]['Bet Includes Team'] = $team;
                array_push($include, $new_name);
            }
            break;
    }
    foreach ($groups as $name => $size) {
        if (!in_array($name, $include)) {
            continue;
        }
        if ($total_bet[$name]) {
            $result[$name]['ROI'] = number_format($total_return[$name] / $total_bet[$name] * 100, 2).'%';
            $result[$name]['ROI'] = number_format($total_return[$name] / $total_bet[$name] * 100, 2).'%';
            $result[$name]['Win Pct'] = number_format($wins[$name] / ($losses[$name] + $wins[$name]) * 100, 2).'%';
            $result[$name]['Wins'] = $wins[$name];
            $result[$name]['Losses'] = $losses[$name];
            $result[$name]['Sample Size'] = $size; 
        }
    }
    // If we are giving team stats sort by ROI (perhaps split out 
    // winners/losers at some point)
    if ($situation == 'bet_team' || $situation == 'any_team') {
        //
    }
    return $result;
}

function get_data($db, $table, $date = null) {
    if ($date === null) {
        $data = exe_sql($db,
            "SELECT * 
            FROM $table"
        );
    } else {
        $date = preg_replace('/[^\d-]+/', '', $date);
        $data = exe_sql($db,
            "SELECT *
            FROM $table 
            WHERE ds = '$date'"
        );
    }
    if (!$data) {
        return null;
    }
    $keys = array_keys($data);
    if (!is_numeric($keys[0])) {
        $data = array($data);
    }
    return $data;
}

function calculatePayout($bet_amount, $odds) {
    if (!$odds) {
        return null;
    }   

    if ($odds > 0) {
        $payout = $bet_amount * $odds / 100;
    } else {
        $payout = (-1)*$bet_amount / $odds * 100;
    }   
    
    return $payout;
}

function listModels() {
    $models_list = array();
    $models = exe_sql('information_schema',
        "select table_name from tables where table_schema = 'baseball'
        and table_name like '%output%'
        and table_name != 'sim_output_deprecated'");
    foreach ($models as $model) {
        $name = substr($model['table_name'], 11, -5);
        $models_list[$name] = $name;
    }
    return $models_list;
}

function ds_modify($date, $day_change) {
    $dateOneDayAdded = strtotime(date($date, strtotime($todayDate)) . $day_change);
    $new_date = date('Y-m-d', $dateOneDayAdded);
    return $new_date;
}

function array_column($array, $key) {
    $ret_array = array();
    foreach ($array as $row) {
        $ret_array[] = $row[$key];
    }
    return $ret_array;
}
