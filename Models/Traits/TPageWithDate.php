<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

trait TPageWithDate {

    protected $date;

    public function setDate($date) {
        $this->date = $date;
        return $this;
    }
}
