<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . '/../includes/db_connect.php';
include_once __DIR__ . '/../includes/functions.php';
include_once __DIR__ . '/../includes/ui_elements.php';
include_once 'PageHeader.php';
include_once 'Table.php';
include_once 'Slider.php';
include_once 'UOList.php';
include_once 'Selector.php';
include_once 'Enum.php';
include_once 'Colors.php';

class Page {

    private $loggedIn;
    private $header = null;

    protected function __construct($logged_in, $custom_header = false) {
        $this->loggedIn = $logged_in;

        if (!$this->loggedIn && $_SERVER['REQUEST_URI'] != '/' &&
            strpos($_SERVER['REQUEST_URI'], 'index') === false) {
            header('Location: /index.php', true);
            die();
        }

        if (!$custom_header) {
            $this->display();
        }
    }

    protected function setHeader($header, $sub_header = null) {
        $this->header = new PageHeader($this->loggedIn, $header, $sub_header);
        $this->display();
    }

    private function display() {
        if (!$this->header) {
            $this->header = new PageHeader($this->loggedIn);
        }
        $this->header->display();

        // Needed to prevent overlap of elements below header.
        echo "<div style='clear:both;'></div>";
    }
}

?>
