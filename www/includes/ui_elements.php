<?php
include_once 'sweetfunctions.php';

const LOG_LINES = 10;

function ui_page_header($title, $subtitle = null) {
    $html_title = 
        "<font 
            face='verdana' 
            color='white' 
            class='title'
            size='4'
        >
            $title
        </font>";
    $html_subtitle = 
        "<font 
            face='verdana' 
            color='white' 
            size='2' 
            class='title'
        >
            $subtitle
        </font>";
    $list = ui_ul(array($html_title, $html_subtitle));
    echo 
        "<div class='page_header'>
            $list
        </div>";
}

function ui_page_header_odds($odds_data = null, $date = null) {

    if (!$date || $date == date('Y-m-d')) {
        $date = date('Y-m-d');
        $page_title = "Today's Games";
    } else {
        $insert_date = 1;
        $page_title = "$date Games";
    }
    $m = date('m');
    $d = date('d');
    $past_days = ($m - 4) * 30;
    $season_progress = number_format((($d + $past_days) / 180 * 100), 0);
    $db = 'baseball';
    if (!$odds_data) {
        $odds_data = get_data($db, 'locked_odds_2014', $date);
    }
    $investment_data = get_data($db, 'bets_2014');
    if (!$odds_data && !$insert_date) {
        $date = ds_modify($date, "-1 day");
        $odds_data = get_data($db, 'locked_odds_2014', $date);
        $page_title = "Yesterday's Games";
    } else if (!$odds_data && $insert_date) {
        ui_error('Incomplete data.');
        return;
    }

    $day_roi = calculate_day_roi($investment_data, $date);
    $season_betting = calculate_season_roi($investment_data);
    $season_roi = $season_betting['roi'];
    $season_return = number_format($season_betting['return'] * 10);
    $record = calculate_daily_record($odds_data);
    $win = $record['W'] ?: 0;
    $loss = $record['L'] ?: 0;
    $season_wins = $season_betting['W'];
    $season_losses = $season_betting['L'];
    $odds_table_formatted = format_odds_table($odds_data);
    $odds_data = index_by($odds_data, 'home');
    $game_data = index_by($game_data, 'home_i');
    ui_page_header("$page_title ($win - $loss) | Daily ROI = $day_roi%",
        "Season ROI = $season_roi% ($season_wins - $season_losses) | $$season_return Return ($1k Bets) | $season_progress% Season Complete");
 
}

function ui_ul($elements) {
    $html = "<ul>";
    foreach ($elements as $element) {
        $html .= "<li>$element</li>";
    }
    $html .= "</ul>";
    return $html;
}

function ui_table($data, $id = 'table', $expandable = true) {
    if (!$data) {
        return;
    }

    $header = array_keys(reset($data));
    $html = "<table id=$id>";

    // header
    $html .= "<tr id='header' onclick='expand($id);'>";
    foreach ($header as $label) {
        $formatted_label = format_render($label);
        $html .= "<th>$formatted_label</th>";
    }
    $html .= '</tr>';

    $display = ($expandable == true) ? "none" : null;
    foreach ($data as $row) {
        $html .= "<tr style='display:$display;'>";
        foreach ($row as $cell) {
            $cell = smart_format($cell);
            $html .= "<td>$cell</td>";
        }
        $html .= '</tr>';
    }
    $html .= '</table>';

    echo $html;
}

function ui_error($text, $class = "error") {
    echo "<p class=$class>$text</p>";
}

function ui_error_logged_out() {
    echo 
        "<p>
            <span class='error'>
                You are not authorized to access this page.
            </span>
            Please
            <a href='index.php'>
                login
            </a>
            .
        </p>";
}

function ui_games_page($odds_data, $game_data) {
    if (!$odds_data || !$game_data) {
        return;
    }

    $game_elements = array();

    foreach ($game_data as $game) {
        $home = $game['home_i'];
        $game_elements[] = ui_game_section($odds_data[$home], $game);
    }

    $html = "<div class='games_container'>";
    $html .= ui_ul($game_elements);
    $html .= "</div>";
    echo $html;
}

function ui_game_section($odds_row, $game) {
    $html = "<div class='game_section'>";
    $home = $game['home_i'];
    $away = $game['away_i'];
    $time = date("g:i a", strtotime($game['time']));

    $score = null;
    // Time is in EST
    if (time() + 10800 > strtotime($game['time'])) {
        $home_score = $odds_row['home_score'];
        $away_score = $odds_row['away_score'];
        $status = $odds_row['status'];
        $score = "$away_score - $home_score";
        $score = ($status[0] === 'F') ? 
            "<font color='yellow'>$score</font>" : $score;
    }

    $header = ui_game_section_header($home, $away, $time, $score);
    $team = ui_game_section_team($game);
    $lineup = ui_game_section_batter($game);
    $sim = ui_game_section_sim($game);

    $html .= ui_ul(array($header, $team, $lineup, $sim));
    $html .= "</div>";
    return $html;
}

function ui_game_section_sim($game) {
    $h = $game['home_i'];
    $a = $game['away_i'];
    $home_sim = $game['home_sim_win'];
    $away_sim = 1 - $home_sim;
    $home_runs = round($game['home_avg_runs'], 2);
    $away_runs = round($game['away_avg_runs'], 2);

    $table_id = "sim_$h";
    $html = "<table id=$table_id style='margin-top:5px'>";
    $html .=
        "<tr id='header' onclick='expand($table_id);'>
            <th colspan='3'>Simulation</th>
        </th>";
    $html .=
        "<tr style='display:none;'>
            <td><b>Stat</b></td>
            <td><b>$a</b></td>
            <td><b>$h</b></td>
        </tr>";
    $html .=
        "<tr style='display:none;'>
            <td>Win %</td>
            <td>$away_sim</td>
            <td>$home_sim</td>
        </tr>";
    $html .=
        "<tr style='display:none;'>
            <td>Avg Runs</td>
            <td>$away_runs</td>
            <td>$home_runs</td>
        </tr>";

    $html .= "</table>";
    return $html;
}

function ui_game_section_batter($game) {
    $h = $game['home_i'];
    $a = $game['away_i'];
    $split = 'Total';
    $h_batting = json_decode($game['lineup_h_stats'], true);
    $h_batting_2013 = $h_batting['2013'];
    $h_batting_2014 = $h_batting['2014'];
    $h_total_2013 = team_batting_by_player($h_batting_2013, $split);
    $h_total_2014 = team_batting_by_player($h_batting_2014, $split);

    $a_batting = json_decode($game['lineup_a_stats'], true);
    $a_batting_2013 = $a_batting['2013'];
    $a_batting_2014 = $a_batting['2014'];
    $a_total_2013 = team_batting_by_player($a_batting_2013, $split);
    $a_total_2014 = team_batting_by_player($a_batting_2014, $split);

    $table_id = "lineup_$h";
    $html = "<table id=$table_id style='margin-top:5px'>";
    $html .=
        "<tr id='header' onclick='expand($table_id);'>
            <th colspan='6'>Lineups</th>
        </th>";
    $html .=
        "<tr style='display:none;'>
            <td><b>Player</b></td>
            <td><b>2013</b></td>
            <td><b>2014</b></td>
            <td><b>Player</b></td>
            <td><b>2013</b></td>
            <td><b>2014</b></td>
        </tr>";

    foreach ($h_total_2013 as $i => $player_info) {
        $h_player_raw = $h_total_2013[$i]['player_name'];
        $a_player_raw = $a_total_2013[$i]['player_name'];
        $h_player_name = format_render($h_player_raw);
        $a_player_name = format_render($a_player_raw);

        $h_player_avg_2013 = round($h_total_2013[$i]['avg'], 3);
        $a_player_avg_2013 = round($a_total_2013[$i]['avg'], 3);
        $h_player_avg_2014 = round($h_total_2014[$i]['avg'], 3);
        $a_player_avg_2014 = round($a_total_2014[$i]['avg'], 3);

        switch ($h_total_2013[$i]['default']) {
            case (1):
                $h_player_avg_2013 = "<font color='red'>$h_player_avg_2013</font>";
                break;
            case (2);
                $h_player_avg_2013 = "<font color='orange'>$h_player_avg_2013</font>";
                break;
        }
        switch ($a_total_2013[$i]['default']) {
            case (1):
                $a_player_avg_2013 = "<font color='red'>$a_player_avg_2013</font>";
                break;
            case (2):
                $a_player_avg_2013 = "<font color='orange'>$a_player_avg_2013</font>";
                break;
        }
        switch ($h_total_2014[$i]['default']) {
            case (1):
                $h_player_avg_2014 = "<font color='red'>$h_player_avg_2014</font>";
                break;
            case (2);
                $h_player_avg_2014 = "<font color='orange'>$h_player_avg_2014</font>";
                break;
        }
        switch ($a_total_2014[$i]['default']) {
            case (1):
                $a_player_avg_2014 = "<font color='red'>$a_player_avg_2014</font>";
                break;
            case (2):
                $a_player_avg_2014 = "<font color='orange'>$a_player_avg_2014</font>";
                break;
        }

        $html .=
            "<tr style='display:none;'>
                <td>
                    <a href='player.php?player=$a_player_raw'>
                        $a_player_name
                    </a>
                </td>
                <td>$a_player_avg_2013</td>
                <td>$a_player_avg_2014</td>
                <td>
                    <a href='player.php?player=$h_player_raw'>
                        $h_player_name
                    </a>
                </td>
                <td>$h_player_avg_2013</td>
                <td>$h_player_avg_2014</td>
            </tr>";
    }
    
    
    $html .= "</table>";
    return $html;
}

function ui_game_section_team($game) {
    $h = $game['home_i'];
    $a = $game['away_i'];
    $h_pitcher = format_render($game['pitcher_h_i']);
    $a_pitcher = format_render($game['pitcher_a_i']);
    $h_pitcher_handedness = $game['pitcher_h_handedness_i'] == 'R' 
        ? 'VsRight' : 'VsLeft';
    $a_pitcher_handedness = $game['pitcher_a_handedness_i'] == 'R'
        ? 'VsRight' : 'VsLeft';;
    $h_era_2013 = $game['pitcher_h_era_2013'];
    $a_era_2013 = $game['pitcher_a_era_2013'];
    $h_era_2014 = $game['pitcher_h_era_2014'];
    $a_era_2014 = $game['pitcher_a_era_2014'];
    $h_bucket_2013 = $game['pitcher_h_2013_era_bucket_i'];
    $a_bucket_2013 = $game['pitcher_a_2013_era_bucket_i'];
    $h_bucket_2014 = $game['pitcher_h_2014_era_bucket_i'];
    $a_bucket_2014 = $game['pitcher_a_2014_era_bucket_i'];

    $h_era_2013 = $game['pitcher_h_2013_default'] ?
        "<font color='red'>$h_era_2013</font>"
        : $h_era_2013;
    $a_era_2013 = $game['pitcher_a_2013_default'] ?
        "<font color='red'>$a_era_2013</font>"
        : $a_era_2013;
    $h_era_2014 = $game['pitcher_h_2014_default'] ?
        "<font color='red'>$h_era_2014</font>"
        : $h_era_2014;
    $a_era_2014 = $game['pitcher_a_2014_default'] ?
        "<font color='red'>$a_era_2014</font>"
        : $a_era_2014;

    $h_batting = json_decode($game['lineup_h_stats'], true);
    $h_batting_2013 = $h_batting['2013'];
    $h_batting_2014 = $h_batting['2014'];
    
    $h_total_avg_2013 = team_batting_avg($h_batting_2013, 'Total');
    $h_total_avg_2014 = team_batting_avg($h_batting_2014, 'Total');
    $h_home_avg_2013 = team_batting_avg($h_batting_2013, 'Home');
    $h_home_avg_2014 = team_batting_avg($h_batting_2014, 'Home');
    $h_hand_avg_2013 = team_batting_avg($h_batting_2013, $a_pitcher_handedness);
    $h_hand_avg_2014 = team_batting_avg($h_batting_2014, $a_pitcher_handedness);
    $h_bucket_avg_2013 = team_batting_avg($h_batting_2013, $a_bucket_2013);
    $h_bucket_avg_2014 = team_batting_avg($h_batting_2014, $a_bucket_2014);
    $h_total_avg_2013 = team_batting_avg($h_batting_2013, 'Total');
    $h_total_avg_2014 = team_batting_avg($h_batting_2014, 'Total');
    $h_noneon_avg_2013 = team_batting_avg($h_batting_2013, 'NoneOn');
    $h_noneon_avg_2014 = team_batting_avg($h_batting_2014, 'NoneOn');
    $h_runnerson_avg_2013 = team_batting_avg($h_batting_2013, 'RunnersOn');
    $h_runnerson_avg_2014 = team_batting_avg($h_batting_2014, 'RunnersOn');
    $h_scoringpos_avg_2013 = team_batting_avg($h_batting_2013, 'ScoringPos');
    $h_scoringpos_avg_2014 = team_batting_avg($h_batting_2014, 'ScoringPos');
    $h_scoringpos2o_avg_2013 = team_batting_avg($h_batting_2013, 'ScoringPos2Out');
    $h_scoringpos2o_avg_2014 = team_batting_avg($h_batting_2014, 'ScoringPos2Out');
    $h_basesloaded_avg_2013 = team_batting_avg($h_batting_2013, 'BasesLoaded');
    $h_basesloaded_avg_2014 = team_batting_avg($h_batting_2014, 'BasesLoaded');

    $a_batting = json_decode($game['lineup_a_stats'], true);
    $a_batting_2013 = $a_batting['2013'];
    $a_batting_2014 = $a_batting['2014'];
    $a_total_avg_2013 = team_batting_avg($a_batting_2013, 'Total');
    $a_total_avg_2014 = team_batting_avg($a_batting_2014, 'Total'); 
    $a_away_avg_2013 = team_batting_avg($a_batting_2013, 'Away');
    $a_away_avg_2014 = team_batting_avg($a_batting_2014, 'Away');
    $a_hand_avg_2013 = team_batting_avg($a_batting_2013, $h_pitcher_handedness);
    $a_hand_avg_2014 = team_batting_avg($a_batting_2014, $h_pitcher_handedness);
    $a_bucket_avg_2013 = team_batting_avg($a_batting_2013, $h_bucket_2013);
    $a_bucket_avg_2014 = team_batting_avg($a_batting_2014, $h_bucket_2014);
    $a_total_avg_2013 = team_batting_avg($a_batting_2013, 'Total');
    $a_total_avg_2014 = team_batting_avg($a_batting_2014, 'Total');
    $a_noneon_avg_2013 = team_batting_avg($a_batting_2013, 'NoneOn');
    $a_noneon_avg_2014 = team_batting_avg($a_batting_2014, 'NoneOn');
    $a_runnerson_avg_2013 = team_batting_avg($a_batting_2013, 'RunnersOn');
    $a_runnerson_avg_2014 = team_batting_avg($a_batting_2014, 'RunnersOn');
    $a_scoringpos_avg_2013 = team_batting_avg($a_batting_2013, 'ScoringPos');
    $a_scoringpos_avg_2014 = team_batting_avg($a_batting_2014, 'ScoringPos');
    $a_scoringpos2o_avg_2013 = team_batting_avg($a_batting_2013, 'ScoringPos2Out');
    $a_scoringpos2o_avg_2014 = team_batting_avg($a_batting_2014, 'ScoringPos2Out');
    $a_basesloaded_avg_2013 = team_batting_avg($a_batting_2013, 'BasesLoaded');
    $a_basesloaded_avg_2014 = team_batting_avg($a_batting_2014, 'BasesLoaded');

    $h_bucket_2013 = $game['pitcher_h_2013_default'] ?
        "<font color='red'>$h_bucket_2013</font>"
        : $h_bucket_2013;
    $a_bucket_2013 = $game['pitcher_a_2013_default'] ?
        "<font color='red'>$a_bucket_2013</font>"
        : $a_bucket_2013;
    $h_bucket_2014 = $game['pitcher_h_2014_default'] ?
        "<font color='red'>$h_bucket_2014</font>"
        : $h_bucket_2014;
    $a_bucket_2014 = $game['pitcher_a_2014_default'] ?
        "<font color='red'>$a_bucket_2014</font>"
        : $a_bucket_2014;

    $h_pitcher_raw = $game['pitcher_h_i'];
    $a_pitcher_raw = $game['pitcher_a_i'];
    $table_id = "team_$h";
    $html = "<table id=$table_id style='margin-top:5px'>";
    $html .= 
        "<tr id='header' onclick='expand($table_id);'>
            <th colspan='3'>$a - $a_pitcher</th>
            <th colspan='3'>$h - $h_pitcher</th>
        </th>";

    $html .=
        "<tr style='display:none;'>
            <td><a href='player.php?player=$a_pitcher_raw'>$a_pitcher</a></td>
            <td>2013</td>
            <td>2014</td>
            <td><a href='player.php?player=$h_pitcher_raw'>$h_pitcher</a></td>
            <td>2013</td>
            <td>2014</td>
        </tr>"; 
    $html .=
        "<tr style='display:none;'>
            <td>Pitcher ERA</td>
            <td>$a_era_2013</td>
            <td>$a_era_2014</td>
            <td>Pitcher ERA</td>
            <td>$h_era_2013</td>
            <td>$h_era_2014</td>
        </tr>";
    $html .=
        "<tr style='display:none;'>
            <td>Pitcher Bucket</td>
            <td>$a_bucket_2013</td>
            <td>$a_bucket_2014</td>
            <td>Pitcher Bucket</td>
            <td>$h_bucket_2013</td>
            <td>$h_bucket_2014</td>
        </tr>";
    $html .=
        "<tr style='display:none;'>
            <td>BA - Total</td>
            <td>$a_total_avg_2013</td>
            <td>$a_total_avg_2014</td>
            <td>BA - Total</td>
            <td>$h_total_avg_2013</td>
            <td>$h_total_avg_2014</td>
        </tr>";

    $html .=
        "<tr style='display:none;'>
            <td>BA - Away</td>
            <td>$a_away_avg_2013</td>
            <td>$a_away_avg_2014</td>
            <td>BA - Home</td>
            <td>$h_home_avg_2013</td>
            <td>$h_home_avg_2014</td>
        </tr>";

    $html .=
        "<tr style='display:none;'>
            <td>BA - Bucket</td>
            <td>$a_bucket_avg_2013</td>
            <td>$a_bucket_avg_2014</td>
            <td>BA - Bucket</td>
            <td>$h_bucket_avg_2013</td>
            <td>$h_bucket_avg_2014</td>
        </tr>";

    $html .=
        "<tr style='display:none;'>
            <td>BA - Handedness</td>
            <td>$a_hand_avg_2013</td>
            <td>$a_hand_avg_2014</td>
            <td>BA - Handedness</td>
            <td>$h_hand_avg_2013</td>
            <td>$h_hand_avg_2014</td>
        </tr>";

    $html .=
        "<tr style='display:none;'>
            <td>BA - None On</td>
            <td>$a_noneon_avg_2013</td>
            <td>$a_noneon_avg_2014</td>
            <td>BA - None On</td>
            <td>$h_noneon_avg_2013</td>
            <td>$h_noneon_avg_2014</td>
        </tr>";

    $html .=
        "<tr style='display:none;'>
            <td>BA - Runners On</td>
            <td>$a_runnerson_avg_2013</td>
            <td>$a_runnerson_avg_2014</td>
            <td>BA - Runners On</td>
            <td>$h_runnerson_avg_2013</td>
            <td>$h_runnerson_avg_2014</td>
        </tr>";

    $html .=
        "<tr style='display:none;'>
            <td>BA - Scoring Pos</td>
            <td>$a_scoringpos_avg_2013</td>
            <td>$a_scoringpos_avg_2014</td>
            <td>BA - Scoring Pos</td>
            <td>$h_scoringpos_avg_2013</td>
            <td>$h_scoringpos_avg_2014</td>
        </tr>";

    $html .=
        "<tr style='display:none;'>
            <td>BA - Scoring Pos 2o</td>
            <td>$a_scoringpos2o_avg_2013</td>
            <td>$a_scoringpos2o_avg_2014</td>
            <td>BA - Scoring Pos 2o</td>
            <td>$h_scoringpos2o_avg_2013</td>
            <td>$h_scoringpos2o_avg_2014</td>
        </tr>";

    $html .=
        "<tr style='display:none;'>
            <td>BA - Bases Loaded</td>
            <td>$a_basesloaded_avg_2013</td>
            <td>$a_basesloaded_avg_2014</td>
            <td>BA - Bases Loaded</td>
            <td>$h_basesloaded_avg_2013</td>
            <td>$h_basesloaded_avg_2014</td>
        </tr>";

    $html .= "</table>"; 
    return $html;
}

function ui_game_section_header($home, $away, $time, $score) {
    $teams_html =
        "<font
            face='verdana'
            color='white'
            class='title'
            style='padding-bottom:10px'>
            $away @ $home
        </font>";
    $time_html =
        "<font
            face='verdana'
            color='white'
            class='title'
            style='padding-left:20px'>
            $time
        </font>";
    $score_html =
        "<font
            face='verdana'
            color='white'
            class='title'
            style='padding-left:20px'>
            $score
        </font>";
    return
        "<div>
            $teams_html
            $time_html
            $score_html
        </div>";
}

function ui_player_page($player, $batting_data, $pitching_data) {
    $batting_data = $batting_data[$player]['stats'];
    $pitching_data = $pitching_data[$player];
    // STOPPED HERE - need to render batting and pitching data
}

function ui_log($name = 'sarah') {
    
    $html = "<div style='height:300px;width:100%;border:3px solid #000000;
        font:16px/26px Georgia, Garamond, Serif;overflow:auto;'>";

    $filename = '../' . $name . '_errors.txt';
    $fp = fopen( $filename, "r+" ) or die("Couldn't open $filename");
    // Empties file
    ftruncate($fp, 0);

    $text = array();
    while (!feof($fp)) {
        $line = fgets($fp);
        $text[] = $line;
    }

    // Remove empty lines
    $text = array_values(array_filter($text, "trim"));
    if (count($text) > LOG_LINES) {
        $text = array_splice($text, count($text) - LOG_LINES);
    }

    $html .= "<ul id='error_list' type='none' style='padding: 0;'>";

    $text = array(); 
    foreach ($text as $i => $row) {
        $id = "row_$i";
        if ($i % 2 == 0) {
            $html .= "<li id=$id class='even_list' style='width=100%;padding:5px;'
                onclick='highlight($id); update_details($id);'>$row</li>";
        } else {
            $html .= "<li id=$id class='odd_list' style='width=100%;padding:5px;'
                onclick='highlight($id); update_details($id)'>$row</li>";
        }
    }
    $html .= "</ul></div>";

    $html_details = 
        "<div 
            id='error_details' 
            style='margin-top:5px;height:500px;width:100%;border:3px 
            dashed #ccc;padding-left:5px;margin-right:5px;
            font:16px/26px Georgia, Garamond, Serif;overflow:auto;
            background:white; display: none;'
        />";

    // when click on something update details - needs to be js FILLED element
    $html .= $html_details;

    echo $html;
}
