<?php
include_once 'Page.php';
include_once __DIR__ . '/../ui/UOList.php';
include_once __DIR__ .'/../includes/Bases.php';
include_once __DIR__ .'/../data/SimDebugDataType.php';

class SimDebugPage extends Page {

    private $loggedIn;
    private $events = array();
    private $eventsHTML = array();
    private $season;
    private $statsYear;
    private $statsType;
    private $weights;
    private $analysisRuns;
    private $simGameDate;
    private $weightsMutator;


    public function __construct($logged_in) {
        parent::__construct($logged_in, true);
        $this->loggedIn = $logged_in;
        $this->fetchData();
        $this->display();
    }

    private function fetchData() {
        $dt = new SimDebugDataType();
        $dt->gen();

        $events = $dt->getEvents();

        $this->gameID = $dt->getGameID();
        $this->season = $dt->getSeason();
        $this->statsYear = $dt->getStatsYear();
        $this->statsType = $dt->getStatsType();
        $this->weights = $dt->getWeights();
        $this->analysisRuns = $dt->getAnalysisRuns();
        $this->simGameDate = $dt->getSimGameDate();
        $this->weightsMutator = $dt->getWeightsMutator();

        foreach ($events as $i => $event) {
            $score = json_decode($event['score'], true);
            $id = "game$i";
            $inning = $event['team'] === 'Away' ? 'Top' : 'Bottom';

            $this->events[] = array(
                'id' => $id,
                'score' => $score['Away'] . ' - ' . $score['Home'],
                'inning' => "$inning " . $event['inning'],
                'outs' => 'Outs: ' . $event['outs'],
                'bases' => $event['bases'],
                'team_at_bat' => $event['team'],
                'batter' => 'Batter: P' . $event['batter'],
                'hit_type' => 'Event: ' .
                    ucwords(str_replace('_', ' ', $event['hit_type'])),
            );

            $footer = $this->getEventFooter(
                json_decode($event['batter_stats'], true),
                $this->formatImpactStats($event['stacked_hit_stats'])
            );

            $this->eventsHTML[] = $this->getEventHTML($id, $footer);
        }
    }

    private function getEventFooter($batter_stats, $impact_stats) {
        arsort($batter_stats);
        $stats_html = array('<div>-----Batter Stats-----</div>');
        foreach ($batter_stats as $hit_type => $stat) {
            $hit_type_formatted = ucwords(
                str_replace('_', '', str_replace('pct_', '', $hit_type))
            );
            $stats_html[] = "<div>$hit_type_formatted: $stat</div>";
        }

        $stats_html[] = '<div>-----Stacked Impact Stats-----</div>';

        arsort($impact_stats);
        foreach ($impact_stats as $impact_type => $stat) {
            $stat = round($stat, 5);
            $impact_type_formatted = ucwords(
                str_replace('_', ' ', str_replace('__', ', ', $impact_type))
            );
            $stats_html[] = "<div>$impact_type_formatted: $stat</div>";
        }

        $list = new UOList($stats_html);

        return $list->getHTML();
    }

    private function formatImpactStats($stats) {
        $stats = json_decode($stats, true);
        $formatted_stats = array();
        foreach ($stats as $i => $stat) {
            $impact = explode('_', $i);
            $end_outs = $impact[0] . '_Outs';
            $end_bases = Bases::basesToString($impact[1]);
            $runs_added = $impact[2] . '_Runs_Added';

            $readable = $end_outs . '__' . $end_bases . '__' . $runs_added;
            $formatted_stats[$readable] = $stat;
        }

        return $formatted_stats;
    }


    public function display() {
        $list = new UOList($this->eventsHTML, null, 'bottom_border');
        $list_html = $list->getHTML();

        $date_label = $this->simGameDate ? 'SIM GAME DATE ' : null;
        $mutator_label = $this->weightsMutator ? 'WEIGHTS MUTATOR ' : null;

        $this->setHeader(
            'Sabertooth Ventures',
            "GAMEID $this->gameID
            SEASON $this->season
            STATS YEAR $this->statsYear
            STATS TYPE $this->statsType
            WEIGHTS $this->weights
            ANALYSIS RUNS $this->analysisRuns
            $date_label $this->simGameDate
            $mutator_label $this->weightsMutator"
        );

        echo
            "<div style='text-align:center;'>
                $list_html
            </div>";

    }

    private function getEventHTML($id, $footer) {
        return "<canvas id='$id' width='300' height='300'></canvas>$footer";
    }

    public function getGameData() {
        return $this->events;
    }
}
?>
