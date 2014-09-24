<?php

include_once 'UOList.php';

class PageHeader {

    private $title;
    private $subtitle;

    public function __construct($logged_in = true, $title = null, $subtitle = null) {
        $this->title = $title;
        $this->subtitle = $subtitle;

        if ($logged_in) {
            $this->fetchData();
        }

        return $this;
    }

    // Sets up loggedIn header
    public function fetchData() {
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

        $attempts = 0;
        while (!$odds_data && !$insert_date) {
            $date = ds_modify($date, "-1 day");
            $odds_data = get_data($db, 'locked_odds_2014', $date);
            $page_title = "$date Games";
            if ($attempts === 10) {
                break;
            } else {
                $attempts++;
            }
        } 
        if (!$odds_data && $insert_date) {
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
        $this->title = "$page_title ($win - $loss) | Daily ROI = $day_roi%";
        $this->subtitle = "Season ROI = $season_roi% ($season_wins - $season_losses) " .
            "| $$season_return Return ($1k Bets) | $season_progress% Season Complete";
    }

    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    public function setSubtitle($subtitle) {
        $this->subtitle = $subtitle;
        return $this;
    } 


    public function display() {
        $html_title =
            "<font
                face='verdana'
                color='white'
                class='title'
                size='4'>
                $this->title
            </font>";
        $html_subtitle =
            "<font
                face='verdana'
                color='white'
                size='2'
                class='title'>
                $this->subtitle
            </font>";
        $list = new UOList(array($html_title, $html_subtitle));
        $list = $list->getHTML();
        echo
            "<div class='page_header'>
                $list
            </div>";

        echo $html;
    }
}

?>
