<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// TODO(smas): Anything not UI-related should not be in here.
include_once __DIR__ . '/../includes/db_connect.php';
include_once __DIR__ . '/../includes/functions.php';
include_once __DIR__ . '/../includes/ui_elements.php';
include_once __DIR__ .'/../ui/PageHeader.php';
include_once __DIR__ .'/../ui/Table.php';
include_once __DIR__ .'/../ui/Slider.php';
include_once __DIR__ .'/../ui/UOList.php';
include_once __DIR__ .'/../ui/Selector.php';
include_once __DIR__ .'/../ui/Enum.php';
include_once __DIR__ .'/../ui/Colors.php';
include_once __DIR__ .'/../ui/Div.php';
include_once __DIR__ .'/../ui/Font.php';
include_once __DIR__ .'/../../Models/Utils/StringUtils.php';

class Page {

    protected $errors = array();

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
            $this->displayHeader();
        }
    }

    final protected function setHeader($header, $sub_header = null) {
        $this->header = (new PageHeader())
            ->setLoggedIn($this->loggedIn)
            ->setTitle($header)
            ->setSubtitle($sub_header);
        $this->displayHeader();
    }

    private function displayHeader() {
        if (!$this->header) {
            $this->header = (new PageHeader())->setLoggedIn($this->loggedIn);
        }
        $this->header->display();

        // Needed to prevent overlap of elements below header.
        echo "<div style='clear:both;'></div>";
    }

    final protected function displayErrors() {
        $errors_list = new UOList($this->errors, null, 'error_box medium_w');
        $errors_list->display();
    }
}

?>
