<?php

include_once 'UIElement.php';

class Selector extends UIElement {

    private $title;
    private $name;
    private $value;
    private $options;

    public function __construct(
        $title,
        $name,
        $value,
        $options
    ) {
        $this->title = $title;
        $this->name = $name;
        $this->value = $value;
        $this->options = $options;
        $this->setHTML();
        return $this;
    }

    protected function setHTML() {
        $html =
            "<div>
                <table style='table-layout:auto;width:300px'><tr>
                    <td>
                        <font size='2' color='blue'>
                            $this->title
                        </font>
                    </td>
                    <td>
                        <select
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
