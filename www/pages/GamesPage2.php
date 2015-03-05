<?php
include_once 'Page.php';
include_once __DIR__ .'/../includes/SimConstants.php';
include_once __DIR__ .'/../data/SimOutputDataType.php';
include_once __DIR__ .'/../data/SimInputDataType.php';

class GamesPage2 extends Page {

    private $date = array();
    private $simData;

    public function __construct($logged_in, $date) {
        parent::__construct($logged_in, true);
        $this->date = $date;
        $this->fetchData();
        $this->setHeader($this->date);
        $this->display();
    }

    private function fetchData() {
        // TODO(smas): remove this. Overriding for testing.
        $this->date = '1955-04-13';
        //$this->date = '1990-04-09'; // For sim_input data.

        $sim_output_dt = new SimOutputDataType();
        $sim_output_dt->setGameDate($this->date)->gen();
        $this->simData = $sim_output_dt->getData();
    }

    private function display() {
        $sim_output_table = new Table($this->simData, 'sim_data');
        $sim_output_table->display();
    }
}
?>
