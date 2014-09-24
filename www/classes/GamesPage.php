<?php
include_once 'Page.php';
include_once 'Table.php';
include_once '../includes/ui_elements.php';

class GamesPage extends Page{
    private $date;
    private $gamesData;
    private $oddsData;

    public function __construct($date) {
        parent::__construct();
        $this->date = $date;
        $this->fetchData();
        $this->display();
    }

    private function fetchData() {
        $db = 'baseball';
        $odds_data = get_data($db, 'locked_odds_2014', $this->date);
        $game_data = get_data($db, 'sim_output_nomagic_50total_50pitcher_histrunning_2014', $this->date);

        $attempts = 0;
        while (!$odds_data || !$game_data) {
            $this->date = ds_modify($this->date, "-1 day");
            $odds_data = get_data($db, 'locked_odds_2014', $this->date);
            $game_data = get_data($db, 'sim_output_nomagic_50total_50pitcher_2014', $this->date);

            if ($attempts = 10) {
                break;
            } else {
                $attempts++;
            }
        }
        
        $odds_table_formatted = format_odds_table($odds_data);
        $this->oddsData = index_by($odds_data, 'home');//, 'game_time');
        $this->gamesData = index_by($game_data, 'home_i');//, 'time');
    }

    public function display() {
        $odds_table_formatted = format_odds_table($this->oddsData);
        $odds_table = new Table($odds_table_formatted, 'odds');
        $odds_table->display();
        //ui_games_page($this->oddsData, $this->gamesData);
        echo $this->getGames();
    }


    private function getGames() {
        if (!$this->oddsData || !$this->gamesData) {
            return;
        }

        $game_elements = array();

        foreach ($this->gamesData as $game) {
            $home = $game['home_i'];
            $game_elements[] = $this->getGameSection($this->oddsData[$home], $game);
        }

        $html = "<div class='games_container'>";
        $html .= ui_ul($game_elements);
        $html .= "</div>";
        return $html;
    }

    private function getGameSection($odds_row, $game) {
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

        $header = $this->getGameSectionHeader($home, $away, $time, $score);
        $team = $this->getGameSectionTeam($game);
        $lineup = $this->getGameSectionBatter($game);
        $sim = $this->getGameSectionSim($game);

        $html .= ui_ul(array($header, $team, $lineup, $sim));
        $html .= "</div>";
        return $html;
    }

    private function getGameSectionHeader($home, $away, $time, $score) {
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


    private function getGameSectionTeam($game) {
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
 
    private function getGameSectionBatter($game) {
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

    private function getGameSectionSim($game) {
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
}

?>
