<?php
include_once 'Page.php';
include_once 'Table.php';

class PlayerPage extends Page{

    private $splits = array(
        'Total', 
        'Home', 
        'Away', 
        'VsLeft', 
        'VsRight', 
        'NoneOn', 
        'RunnersOn', 
        'ScoringPos', 
        'ScoringPos2Out',
        'BasesLoaded',
        '25', 
        '50', 
        '75', 
        '100',
    );
    private $player;
    private $battingData;
    private $pitchingData;

    public function __construct($player) {
        parent::__construct();
        $this->player = $player;
        $this->fetchData();
        $this->display();
    }

    private function fetchData() {
        $db = 'baseball';

        // TD: only fetch individual player's data
        $batting_data = get_data($db, 'batting_final_nomagic_2014', $date);
        $batting_data = index_by($batting_data, 'player_name');
        $batting_data = json_decode($batting_data[$this->player]['stats'], true);
        $pitching_data = get_data($db, 'era_map_2014', $date);
        $pitching_data = index_by($pitching_data, 'name');

        // Format batting data
        foreach ($batting_data as $year => $data) {
            foreach ($data as $split => $stats) {
                $formatted_stats = array();
                foreach ($stats as $key => $stat) {
                    if (is_numeric($stat)) {
                        $stat = round($stat, 3);
                    }
                    $formatted_stats[$key] = $stat;
                }
                $formatted_stats['split'] = $split;
                $formatted_stats['player_name'] = $this->player;
                $this->battingData[$year][$split] = $formatted_stats;
            }
        }

        $this->pitchingData = $pitching_data[$this->player];
    }

    public function getPlayer() {
        return $this->player;
    }

    public function display() {
        if ($this->pitchingData) {
            $pit_table = new Table(array($this->pitchingData));
            $pit_table
                ->setExpanded(false)
                ->display();
        }
        $bat_table = new Table($this->battingData['2013']);
        $bat_table
            ->setExpanded(false)
            ->display();
    }

}

?>
