<?php

class UOList {

    private $elements;
    private $html;
    private $class;
    private $itemClass;

    public function __construct($elements, $class = null, $item_class = null) {
        $this->elements = $elements;
        $this->class = $class;
        $this->itemClass = $item_class;
        $this->setHTML();
    }

    public function setHTML() {
        $html = $this->class ? "<ul class='$this->class'>" : "<ul>";
        foreach ($this->elements as $element) {
            $html .= "<li class='$this->itemClass'>$element</li>";
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
