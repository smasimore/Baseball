<?php

include_once 'UIElement.php';

// TODO(smas): Update Slider and Selector elements callsites to use this.
class ParamInput extends UIElement {

    private $title;

    protected function setHTML() {
        $this->html =
            "<div class=$this->class>
                <table><tr><td class='leftcell'>
                    <font class='input_title' color='#2B96E8'>
                        $this->title
                    </font>
                </td><td class='rightcell'>
                    $this->innerHTML
                </td></tr></table>
            </div>";
    }

    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }
}

?>
