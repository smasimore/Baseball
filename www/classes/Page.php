<?php

include_once '../includes/db_connect.php';
include_once '../includes/functions.php';
include_once '../includes/ui_elements.php';
include_once 'PageHeader.php';
include_once 'Table.php';
include_once 'Slider.php';
include_once 'UOList.php';
include_once 'Selector.php';
include_once 'Enum.php';
include_once 'Colors.php';

class Page {

    private $loggedIn;

    public function __construct($logged_in = true) {
        $this->loggedIn = $logged_in;
        $this->displayHeader();
    }

    private function displayHeader() {
        $header = new PageHeader($this->loggedIn);
        $header->display();
    }
}

?>
