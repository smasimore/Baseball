<?php

class UOList {

    private $elements;
    private $html;
    private $class;

    public function __construct($elements, $class = null) {
        $this->elements = $elements;
        $this->class = $class;
        $this->setHTML();
    }

    public function setHTML() {
        $html = $this->class ? "<ul class='$this->class'>" : "<ul>";
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
