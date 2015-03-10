<?php

include_once 'UIElement.php';

class Selector extends UIElement {

    private $title;
    private $name;
    private $value;
    private $options;
    private $class;

    public function __construct(
        $title,
        $name,
        $value,
        $options,
        $class = null
    ) {
        $this->title = $title;
        $this->name = $name;
        $this->value = (string)$value;
        $this->options = $options;
        $this->class = $class;
        $this->setHTML();
        return $this;
    }

    protected function setHTML() {
        $html =
            "<div class='$this->class selector'>
                <table class='table'><tr>
                    <td class='leftcell'>
                        <font class='input_title' color='#2B96E8'>
                            $this->title
                        </font>
                    </td>
                    <td class='rightcell'>
                        <select
                            class='selector_input'
                            name=$this->name
                            value=$this->value>";
        
        foreach ($this->options as $option) {
            $selected = $option === $this->value ? 'selected' : '';
            $html .= "<option $selected>$option</option>";
        }

        $html .=  "</td></tr></table></div>";
        $this->html = $html;
    }
}

?>
