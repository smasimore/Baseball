<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ . '/UIElement.php';

class PageHeader extends UIElement {

    private $title;
    private $subtitleArr;
    private $loggedIn;

    public function setLoggedIn($logged_in) {
        $this->loggedIn = $logged_in;
        return $this;
    }

    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    public function setSubtitleArr($subtitle_arr) {
        $this->subtitleArr = $subtitle_arr;
        return $this;
    }

    protected function setHTML() {
        $html_title = $this->title !== null
            ? "<p class='title'> $this->title </p>"
            : "<p class='hidden'> Hidden Title </p>";
        $html_subtitle = $this->getSubtitleHTMLArr();
        $header_text = new UOList(
            array_merge(array($html_title), $html_subtitle),
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

    private function getSubtitleHTMLArr() {
        if ($this->subtitleArr === null) {
            return array("<p class='hidden'> Hidden Subtitle </p>");
        }
        $html_arr = array();
        foreach ($this->subtitleArr as $subtitle) {
            $html_arr[] =
                "<p class='subtitle'>
                    $subtitle
                </p>";
        }
        return $html_arr;
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
