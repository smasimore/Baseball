<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'UIElement.php';

class UOList extends UIElement {

    private $items;
    private $itemClass;

    public function setItems(array $items) {
        $this->items = $items;
        return $this;
    }

    public function setItemClass($item_class) {
        $this->itemClass = $item_class;
        return $this;
    }

    protected function setHTML() {
        if (!$this->items) {
            return;
        }

        $html = $this->class ? "<ul class='$this->class'>" : "<ul>";
        foreach ($this->items as $item) {
            $html .= "<li class='$this->itemClass'>$item</li>";
        }
        $html .= "</ul>";
        $this->html = $html;
    }
}

?>
