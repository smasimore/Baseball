<?php

class UOList {

    private $elements;
    private $html;

    public function __construct($elements) {
        $this->elements = $elements;
        $this->setHTML();
    }

    public function setHTML() {
        $html = "<ul>";
        foreach ($this->elements as $element) {
            $html .= "<li>$element</li>";
        }
        $html .= "</ul>";
        $this->html = $html;
    }

    public function getHTML() {
        return $this->html;
    }

    public function display() {
        echo $this->html;
    }
}

?>
