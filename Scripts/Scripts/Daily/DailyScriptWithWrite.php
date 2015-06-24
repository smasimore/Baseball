<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

abstract class DailyScriptWithWrite {

    private $startDate = null;
    private $endDate = null;
    private $test = false;
    private $backfill = false;

    abstract protected function gen($ds);

    public function runDaily() {
        $this->startDate = $this->startDate ?: date('Y-m-d');
        $this->endDate = $this->endDate ?: $this->startDate;
        for ($ds = $this->startDate;
            $ds <= $this->endDate;
            $ds = DateTimeUtils::addDay($ds)
        ) {
            $this->gen($ds);
        }
    }

/* IGNORE TILL NEXT DIFF
    // If testing, end script before a write occurs and print the specified
    // array to console.
    public function getShouldWrite($table) {
        if ($this->test) {
            // Shorten table for larger arrays.
            $sample = array_slice($table, 0, 5);
            print_r($sample);
            exit('Completed Test Run');
        } else if (!$table) {
            return false;
        }
        return true;
    }
*/

    public function setStartDate($ds) {
        $this->startDate = $ds;
    }

    public function setEndDate($ds) {
        $this->endDate = $ds;
    }

    public function setBackfill() {
        $this->backfill = true;
    }

    public function setTest() {
        $this->test = true;
    }
}
