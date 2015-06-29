<?php
include_once 'Page.php';
include_once __DIR__ .'/../../Models/DataTypes/SimInputDataType.php';
include_once __DIR__ .'/../../Models/DataTypes/BetsDataType.php';
include_once __DIR__ .'/../../Models/Utils/ROIUtils.php';

class GamesPage2 extends Page {

    private $date;
    private $gamesData;

    private $simInputDT;
    private $betsData;

    public function __construct($logged_in, $date) {
        parent::__construct($logged_in, true);
        $this->date = $date;

        try {
            $this->fetchData();
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            $this->displayErrors();
            return;
        }

        $this->setHeader($this->date, $this->createROIHeader());
        $this->setupGameData();
        $this->display();
    }

    private function fetchData() {
        $this->simInputData = (new SimInputDataType())
            ->setGameDate($this->date)
            ->gen()
            ->getData();
        $weights = array(StatsCategories::B_HOME_AWAY => 1.0);
        $this->betsData = (new BetsDataType())
            ->setColumns($this->getBetsColumns())
            ->setWeights($weights)
            ->setGameDate($this->date)
            ->gen()
            ->getData();
    }

    private function setupGameData() {
        foreach ($this->simInputData as $gameid => $game) {
            $team_stats = $this->getTeamStats($game);
            $this->gamesData[$gameid] = array(
                'team_stats' => $team_stats
            );
        }
    }

    private function createROIHeader() {
        $roi = ROIUtils::calculateROI($this->betsData);
        return $roi === null
            ? 'No Games Completed Yet'
            : sprintf(
                'Daily ROI is %s',
                number_format(($roi * 100), 2) . '%'
            );
    }

    private function getTeamStats($game_data) {
        return array(
            array(
                'Pitcher',
                StringUtils::formatName($game_data['pitching_h']['name']),
                StringUtils::formatName($game_data['pitching_a']['name'])
            ),
            array(
                'Pitcher ERA',
                $game_data['pitching_h']['era'],
                $game_data['pitching_a']['era']
            )
        );
    }

    private function display() {
        // Summary table.
        (new Table($this->betsData, 'bets_data'))->display();

        // Games section.
        $this->getGamesSection()->display();

    }

    private function getGamesSection() {
        $games = array();
        foreach ($this->gamesData as $gameid => $data) {
            // Game not in bets table yet.
            if (idx($this->betsData, $gameid) === null) {
                continue;
            }

            $games[] = (new Div($this->getGameSection($gameid, $data)))
                ->setClass('game_section')
                ->getHTML();
        }

        return new UOList($games);
    }

    private function getGameSection($gameid, $data) {
        return (new UOList(array(
            $this->getGameHeader($gameid),
            $this->getTeamSection($gameid, $data)
        )))->getHTML();
    }

    private function getGameHeader($gameid) {
        $bets_game = $this->betsData[$gameid];

        $teams_header = sprintf(
            "%s @ %s",
            $bets_game['away'],
            $bets_game['home']
        );
        $time_header = date("g:i a", strtotime($bets_game['game_time']));

        $score_header = sprintf(
            "%d - %d",
            $bets_game['away_score'],
            $bets_game['home_score']
        );
        $score_class = strpos($bets_game['status'], 'Final') !== false
            ? 'game_header_final'
            : 'game_header';

        return
            (new Font($teams_header))->setClass('game_header')->getHTML() .
            (new Font($time_header))->setClass('game_header')->getHTML() .
            (new Font($score_header))->setClass($score_class)->getHTML();
    }

    private function getTeamSection($gameid, $data) {
        return (new Table($data['team_stats'], "team_data_$gameid"))
            ->setCustomHeader(array('', 'Home', 'Away'))
            ->getHTML();
    }

    private function getBetsColumns() {
        return array(
            'gameid',
            'game_time',
            'home',
            'away',
            'home_sim',
            'away_sim',
            'home_vegas_odds',
            'away_vegas_odds',
            'bet_team',
            'home_score',
            'away_score',
            'status',
            'bet',
            'payout'
        );
    }
}
?>
