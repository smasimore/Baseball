<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

abstract class ScriptWithWrite {

    private $startDate = null;
    private $endDate = null;
    protected $test = false;
    protected $backfill = false;

    abstract protected function gen($ds);
    abstract protected function write();
    abstract protected function getWriteData();
    abstract protected function getWriteTable();

    public function run() {
        $this->startDate = $this->startDate ?: date('Y-m-d');
        $this->endDate = $this->endDate ?: $this->startDate;
        for ($ds = $this->startDate;
            $ds <= $this->endDate;
            $ds = DateTimeUtils::addDay($ds)
        ) {
            $this->writeData = array();
            $this->gen($ds);
            $this->validateShouldWrite();
            $this->write();
            $this->genPostWriteOperations();
        }
    }

    // If testing, end script before a write occurs and print the specified
    // array to console.
    private function validateShouldWrite() {
        $write_table = $this->getWriteTable();
        $write_data = $this->getWriteData();
        if (!$write_table) {
            throw new Exception('Must set writeTable');
        } else if (!$write_data) {
            throw new Exception(sprintf(
                'No Data Provided For Insert Into %s',
                $write_table
            ));
        } else if ($this->test) {
            // Shorten data for larger arrays.
            $sample = array_slice($write_data, 0, 5);
            print_r($sample);
            exit('Completed Test Run');
        }
    }

    protected function genPostWriteOperations() {
        return null;
    }

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
