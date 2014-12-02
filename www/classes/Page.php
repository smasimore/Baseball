<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once '/Users/baseball/Git/smas/Baseball/www/includes/db_connect.php';
include_once '/Users/baseball/Git/smas/Baseball/www/includes/functions.php';
include_once '/Users/baseball/Git/smas/Baseball/www/includes/ui_elements.php';
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

    protected function __construct($logged_in, $custom_header) {
        $this->loggedIn = $logged_in;
        if (!$custom_header) {
            $this->displayHeader();
        }
    }

    protected function setHeader($header, $sub_header) {
        $this->header = new PageHeader($this->loggedIn, $header, $sub_header);
        $this->displayHeader();
    }

    private function displayHeader() {
        if (!$this->header) {
            $this->header = new PageHeader($this->loggedIn);
        }
        $this->header->display();

        // Needed to prevent overlap of elements below header.
        echo "<div style='clear:both;'></div>";
    }
}

?>
