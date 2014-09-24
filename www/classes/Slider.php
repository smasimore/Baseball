<?php

include_once 'UIElement.php';

class Slider extends UIElement {

    private $name;
    private $value;
    private $min;
    private $max;
    private $increment;
    private $title;

    public function __construct(
        $title,
        $name,
        $value,
        $min,
        $max,
        $tick_increment
    ) {
        $this->title = $title;
        $this->name = $name;
        $this->value = $value;
        $this->min = $min;
        $this->max = $max;
        $this->increment = $tick_increment;
        $this->setHTML();
        return $this;
    }

    protected function setHTML() {
        $html =
            "<div>
                <table style='table-layout:auto;width:300px'><tr><td>
                    <font size='2' color='blue'>
                        $this->title
                    </font>
                </td><td>
                    <input
                        type='range'
                        name=$this->name
                        id=$this->name
                        min=$this->min
                        max=$this->max
                        list=".$this->name."_ticks
                        value=$this->value
                        onchange='updateInput(".$this->name."_display, this.value);'
                    />
                </td><td>
                    <input
                        type='text'
                        id=$this->name"."_display
                        value=$this->value
                        style='width:20px'
                        onchange='
                            if (this.value < $this->min) {
                                this.value = $this->min;
                            }
                            if (this.value > $this->max) {
                                this.value = $this->max;
                            } 
                            updateInput($this->name, this.value)';'
                    />
                </td></tr></table>";

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
}

?>
