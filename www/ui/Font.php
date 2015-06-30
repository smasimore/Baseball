<?php

include_once 'UIElement.php';

class Font extends UIElement {

    private $color = Colors::BLACK;

    protected function setHTML() {
        $this->html =
            "<font color='$this->color' class='$this->class'>
                $this->innerHTML
            </font>";
    }

    public function setColor($color) {
        Colors::assertIsValidValue($color);
        $this->color = $color;
        return $this;
    }
}

?>
