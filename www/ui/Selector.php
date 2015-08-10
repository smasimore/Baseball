<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'UIElement.php';
include_once __DIR__ . '/../../Models/Traits/ui/TUIElementWithInput.php';

class Selector extends UIElement {

    use TUIElementWithInput;

    private $options;

    protected function setHTML() {
        if (!$this->options) {
            return;
        }

        $this->value = (string)$this->value;
        $html =
            "<select
                class='selector_input'
                name=$this->name
                value=$this->value>";

        foreach ($this->options as $option) {
            $selected = $option === $this->value ? 'selected' : '';
            $html .= "<option $selected>$option</option>";
        }

        $html .=  "</select>";
        $this->html = $html;
    }

    public function setOptions(array $options) {
        $this->options = $options;
        return $this;
    }
}

?>
