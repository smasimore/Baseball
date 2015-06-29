<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

abstract class ScriptWithWrite {

    private $startDate = null;
    private $endDate = null;
    private $writeTable = null;
    private $writeData = null;
    protected $test = false;
    protected $backfill = false;

    abstract protected function gen($ds);

    public function run() {
        $this->startDate = $this->startDate ?: date('Y-m-d');
        $this->endDate = $this->endDate ?: $this->startDate;
        for ($ds = $this->startDate;
            $ds <= $this->endDate;
            $ds = DateTimeUtils::addDay($ds)
        ) {
            $this->gen($ds);
            $this->write();
            $this->genPostWriteOperations();
        }
    }

    private function write() {
        $this->validateShouldWrite();
        multi_insert(
            DATABASE,
            $this->writeTable,
            $this->writeData
        );
        // Unset vars in case script is backfilling.
        unset($this->writeTable, $this->writeData);
    }

    // If testing, end script before a write occurs and print the specified
    // array to console.
    private function validateShouldWrite() {
        if (!$this->writeTable) {
            throw new Exception('Must set writeTable');
        } else if (!$this->writeData) {
            throw new Exception(sprintf(
                'No Data Provided For Insert Into %s',
                $this->writeTable
            ));
        } else if ($this->test) {
            // Shorten data for larger arrays.
            $sample = array_slice($this->writeData, 0, 5);
            print_r($sample);
            exit('Completed Test Run');
        }
    }

    protected function genPostWriteOperations() {
        return null;
    }

    protected function setWriteTable($table) {
        $this->writeTable = $table;
    }

    protected function setWriteData($data) {
        $this->writeData = $data;
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
