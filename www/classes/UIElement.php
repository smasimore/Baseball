<?php

include_once __DIR__ . '/../includes/functions.php';

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
