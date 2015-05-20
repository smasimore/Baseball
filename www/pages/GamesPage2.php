<?php
include_once 'Page.php';
include_once __DIR__ .'/../../Models/DataTypes/SimInputDataType.php';
include_once __DIR__ .'/../../Models/DataTypes/BetsDataType.php';

class GamesPage2 extends Page {

    private $date;
    private $gamesData;

    private $simInputDT;
    private $betsData;

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
            ->setColumns($this->getBetColumns())
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

    private function getTeamStats($game_data) {
        return array(
            array(
                'pitcher_l' => 'Pitcher',
                'pitcher_a' => 'Away Pitcher',
                'pitcher_h' => 'Home Pitcher'
            ),
            array(
                'pitcher_era_l' => 'Pitcher ERA',
                'pitcher_era_a' => 'Away Pitcher ERA',
                'pitcher_era_h' => 'Home Pitcher ERA'
            )
        );
    }

    private function display() {
        $sim_output_table = new Table($this->betsData, 'bets_data');
        $sim_output_table->display();

        $games_section = $this->getGamesSection();
        $games_section->display();

    }

    private function getGamesSection() {
        $games = array();
        foreach ($this->gamesData as $gameid => $data) {
            $games[] = (new Div($this->getGameSection($gameid)))
                ->setClass('game_section')
                ->getHTML();
        }

        return new UOList($games);
    }

    private function getGameSection($gameid) {
        return (new UOList(array(
            $this->getGameHeader($gameid)
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
        $score_class = $bets_game['status'] === 'Final' ? 'game_header_final' :
            'game_header';

        return
            (new Font($teams_header))->setClass('game_header')->getHTML() .
            (new Font($time_header))->setClass('game_header')->getHTML() .
            (new Font($score_header))->setClass($score_class)->getHTML();
    }

    private function getBetColumns() {
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
