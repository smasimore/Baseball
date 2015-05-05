<?php
include_once 'Page.php';
include_once __DIR__ .'/../../Models/DataTypes/SimInputDataType.php';
include_once __DIR__ .'/../../Models/DataTypes/BetsDataType.php';

class GamesPage2 extends Page {

    private $date = array();
    private $simInputDT;
    private $betsDT;

    public function __construct($logged_in, $date) {
        parent::__construct($logged_in, true);
        $this->date = $date;
        $this->setHeader($this->date);

        try {
            $this->fetchData();
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            $this->displayErrors();
            return;
        }

        $this->display();
    }

    private function fetchData() {
        $this->simInputDT = new SimInputDataType();
        $this->simInputDT->setGameDate($this->date)->gen();
        $weights = array(StatsCategories::B_HOME_AWAY => 1.0);
        $this->betsDT = new BetsDataType();
        $this->betsDT->setWeights($weights)->setGameDate($this->date)->gen();
    }

    private function display() {
        $sim_output_table = new Table($this->betsDT->getData(), 'bets_data');
        $sim_output_table->display();
    }
}
?>
