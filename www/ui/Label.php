<?php

include_once 'UIElement.php';

class Label extends UIElement {

    private $label;

    protected function setHTML() {
        $this->html =
            "<div class='$this->class label'>
                <table class='table'><tr><td class='leftcell'>
                    <font class='input_title' color='#2B96E8'>
                        $this->label
                    </font>
                </td><td class='rightcell'>
                    $this->innerHTML
                </td></tr></table>
            </div>";
    }

    public function setLabel($label) {
        $this->label = $label;
        return $this;
    }
}

?>
