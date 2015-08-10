<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'UIElement.php';

class Table extends UIElement {

    private $data;
    private $id;
    private $expanded = true;
    private $header = null;

    public function setExpanded($expanded) {
        $this->expanded = $expanded;
        return $this;
    }

    public function setData(array $data) {
        $this->data = $data;
        return $this;
    }

    public function setID($id) {
        $this->id = $id;
        return $this;
    }

    public function setHeader(array $header) {
        $this->header = $header;
        return $this;
    }

    // To add a color to a row, set a key '_color' for each row and put 
    // color (e.g. Colors::RED_FADED).
    protected function setHTML() {
        if (!$this->data) {
            return;
        }

        $header = $this->header ?: array_keys(reset($this->data));

        $html = "<table id=$this->id>";

        // header
        $html .= "<tr id='header' onclick='expand($this->id);'>";
        foreach ($header as $label) {
            $formatted_label = format_render($label);
            $html .= "<th>$formatted_label</th>";
        }
        $html .= '</tr>';

        $display = ($this->expanded == false) ? "none" : null;
        foreach ($this->data as $row) {
            $color = isset($row['_color']) ? $row['_color'] : '';
            unset($row['_color']);
            $html .= "<tr style='display:$display;'>";
            foreach ($row as $cell) {
                // Format cell if number of array
                if (is_numeric($cell)) {
                    $cell = round($cell, 3);
                }
                if (is_array($cell)) {
                    $cell = json_encode($cell);
                }
                $html .= "<td style='background:$color;'>$cell</td>";
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
        $this->html = $html;
    }
}

?>
