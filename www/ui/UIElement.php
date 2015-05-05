<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ . '/../includes/functions.php';
include_once __DIR__ . '/UOList.php';

abstract class UIElement {

    protected $html;
    protected $loggedIn;

    public function __construct($logged_in = false) {
        $this->loggedIn = $logged_in;
    }

    protected abstract function setHTML();

    public function getHTML() {
        return $this->html;
    }

    public function display() {
        $this->setHTML();
        echo $this->html;
    }
}
