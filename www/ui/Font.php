<?php

include_once 'UIElement.php';

class Font extends UIElement {

    protected function setHTML() {
        $this->html =
            "<font class='$this->class'>
                $this->innerHTML
            </font>";
    }
}

?>
