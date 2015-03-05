<?php
include_once 'Page.php';
include_once __DIR__ .'/../includes/SimConstants.php';
include_once __DIR__ .'/../data/SimOutputDataType.php';

class GamesPage2 extends Page {

    private $date = array();

    public function __construct($logged_in, $date) {
        parent::__construct($logged_in, true);
        $this->date = $date;
        $this->fetchData();
    }

    private function fetchData() {
        // TODO(smas): remove this. Overriding for testing.
        $this->date = '1955-04-13';

        $dt = new SimOutputDataType();
        $dt->setGameDate($this->date)->gen();

        s_log($dt->getData());
    }
}
?>
