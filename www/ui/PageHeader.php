<?php

include_once __DIR__ . '/UIElement.php';

class PageHeader extends UIElement {

    private $loggedIn;
    private $title;
    private $subtitle;

    public function __construct($logged_in, $title = null, $subtitle = null) {
        $this->loggedIn = $logged_in;
        $this->title = $title;
        $this->subtitle = $subtitle;

        if ($logged_in && !$title) {
            $this->fetchData();
        }

        return $this;
    }

    // Sets up loggedIn header
    public function fetchData() {
        $date = null;
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
        $odds_data = null;
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

    public function setHTML() {
        $html_title =
            "<p class='title'>
                $this->title
            </p>";
        $html_subtitle =
            "<p class='subtitle'>
                $this->subtitle
            </p>";
        $header_text = new UOList(
            array($html_title, $html_subtitle),
            'alignleft'
        );
        $header_text = $header_text->getHTML();

        $logout = $this->loggedIn ?
            "<a class='logout alignright' href='includes/logout.php'>
                Logout
            </a>" :
            null;
        $nav = $this->getNav();

        $this->html =
            "<div class='page_nav'>
                <div class='page_header header_device'>
                    $header_text
                    $logout
                </div>
                <div style='clear:both;'>
                    $nav
                </div>
            </div>";
    }

    private function getNav() {
        if (!$this->loggedIn) {
            return null;
        }

        $nav = new UOList(
            array(
                "<a class='nav_item' href='games.php'>Games</a>",
                "<a class='nav_item' href='sim_perf.php'>Sim Perf</a>",
                "<a class='nav_item' href='sim_debug.php'>Sim Debug</a>",
                "<a class='nav_item' href='log.php'>Log</a>"
            ),
            'nav_list'
        );
        return $nav->getHTML();
    }
}

?>
