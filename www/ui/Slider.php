<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ . '/UIElement.php';
include_once __DIR__ . '/../../Models/Traits/ui/TUIElementWithInput.php';
include_once __DIR__ . '/Table.php';

class Slider extends UIElement {

    use TUIElementWithInput;

    private $min;
    private $max;
    private $increment;

    protected function setHTML() {
        $table = (new Table())
            ->setData(array(
                "<input
                    class='slider $this->class'
                    type='range'
                    name=$this->name
                    id=$this->name
                    min=$this->min
                    max=$this->max
                    list=".$this->name."_ticks
                    value=$this->value
                    onchange='updateInput(".$this->name."_display, this.value);'
                />",
                "<input
                    type='text'
                    id=$this->name"."_display
                    value=$this->value
                    class='slider_box'
                    onchange='
                        if (this.value < $this->min) {
                            this.value = $this->min;
                        }
                        if (this.value > $this->max) {
                            this.value = $this->max;
                        }
                        updateInput($this->name, this.value)';'
                />"
            ))
            ->setColumns(2)
            ->setClass('slider_table')
            ->setRowClass('noborder nopadding')
            ->setCellClass('noborder nopadding')
            ->getHTML();

        $html = "<div>$table";

        if ($this->increment) {
            $html .= "<datalist id=".$this->name."_ticks>";
            $ticks = ($this->max - $this->min + 1) / $this->increment;
            for ($i = 0; $i < $ticks; $i++) {
                $num = $this->min + $i * $this->increment;
                $html .= "<option>$num</option>";
            }
            $html .= "</datalist>";
        }

        $html .= "</div>";

        $this->html = $html;
    }

    public function setMinAndMax($min, $max) {
        $this->min = $min;
        $this->max = $max;
        return $this;
    }

    public function setTickIncrement($increment) {
        $this->increment = $increment;
        return $this;
    }
}

?>
