<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ . '/../includes/functions.php';
include_once __DIR__ . '/UOList.php';

abstract class UIElement {

    protected $html;
    protected $class;
    protected $innerHTML;

    public function __construct($inner_html = null) {
        $this->innerHTML = $inner_html;
    }

    protected abstract function setHTML();

    public function getHTML() {
        $this->setHTML();
        return $this->html;
    }

    public function display() {
        $this->setHTML();
        echo $this->html;
    }

    public function setClass($class) {
        $this->class = $class;
        return $this;
    }
}
