<?php

include_once 'UIElement.php';

class Div extends UIElement {

    protected function setHTML() {
        $this->html =
            "<div class='$this->class'>
                $this->innerHTML
            </div>";
    }
}

?>
