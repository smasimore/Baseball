<?php

include_once __DIR__ . '/../includes/functions.php';
include_once __DIR__ . '/UOList.php';

abstract class UIElement {

    protected $html;

    public abstract function __construct();

    protected abstract function setHTML();

    public function getHTML() {
        return $this->html;
    }

    public function display() {
        $this->setHTML();
        echo $this->html;
    }
}
