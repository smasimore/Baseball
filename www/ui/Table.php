<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'UIElement.php';

class Table extends UIElement {

    private $data;
    private $id;
    private $columns;
    private $rowClass = 'table_tr';
    private $cellClass = 'table_td';

    public function setData(array $data) {
        $this->data = $data;
        return $this;
    }

    public function setID($id) {
        $this->id = $id;
        return $this;
    }

    public function setColumns($cols) {
        $this->columns = $cols;
        return $this;
    }

    public function setRowClass($class) {
        $this->rowClass = $class;
        return $this;
    }

    public function setCellClass($class) {
        $this->cellClass = $class;
        return $this;
    }

    protected function setHTML() {
        if (!$this->data) {
            return;
        }

        $table_class = $this->class ?: 'table';

        $cell_counter = 0;
        $html = "<table class='$table_class'><tr class='$this->rowClass'>";
        foreach ($this->data as $cell) {
            // Create a new row. Don't do this the first time.
            if ($cell_counter !== 0 && $cell_counter % $this->columns === 0) {
                $html .= "</tr><tr>";
            }

            $html .= "<td class='$this->cellClass'>$cell</td>";
            $cell_counter++;
        }
        $html .= '</tr></table>';

        $this->html = $html;
    }
}

?>
