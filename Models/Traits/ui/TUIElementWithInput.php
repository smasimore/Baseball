<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

// TODO(smas): Update other input UI Elements to use this.
trait TUIElementWithInput {

    private $name;
    private $value;

    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    public function setValue($value) {
        $this->value = $value;
        return $this;
    }
}
