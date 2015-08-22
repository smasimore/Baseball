<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'UIElement.php';

class UOList extends UIElement {

    private $items;
    private $itemClass = 'list_ui';

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

        $class = $this->class ?: 'list_ul';
        $html = "<ul class='$class list_ul'>";
        foreach ($this->items as $item) {
            $html .= "<li class='$this->itemClass'>$item</li>";
        }
        $html .= "</ul>";
        $this->html = $html;
    }
}

?>
