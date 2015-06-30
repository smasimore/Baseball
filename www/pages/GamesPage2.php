<?php
include_once 'Page.php';
include_once __DIR__ .'/../../Models/DataTypes/SimInputDataType.php';
include_once __DIR__ .'/../../Models/DataTypes/BetsDataType.php';
include_once __DIR__ .'/../../Models/Utils/ROIUtils.php';
include_once __DIR__ .'/../../Models/Utils/OddsUtils.php';

class GamesPage2 extends Page {

    const THREE_HRS_IN_SECS = 10800;

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

        $this->setHeader($this->date, $this->getROIHeader());
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

    private function getROIHeader() {
        $roi = ROIUtils::calculateROI($this->betsData);
        list($wins, $losses) = ROIUtils::calculateRecord($this->betsData);
        return $roi === null
            ? 'No Games Completed Yet'
            : sprintf(
                'Daily ROI is %s (%d - %d)',
                number_format(($roi * 100), 2) . '%',
                $wins,
                $losses
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
        $summary_data = $this->getSummaryTableData();
        (new Table($summary_data, 'summary_table'))->display();

        // Games section.
        $this->getGamesSection()->display();

    }

    private function getSummaryTableData() {
        $summary_data = array();
        foreach ($this->betsData as $game) {
            // Convert to PST and format.
            $game_time = date(
                'g:i a',
                strtotime($game['game_time']) - self::THREE_HRS_IN_SECS
            );

            $away_team = $game['away'];
            $home_team = $game['home'];
            $away_score = $game['away_score'];
            $home_score = $game['home_score'];
            $bet_team = $game['bet_team'];

            $bet_team_pct_win = null;
            $bet_team_odds = null;
            $bet_advantage = null;
            if ($bet_team) {
                $bet_team_pct_win = $bet_team === $away_team
                    ? $game['away_sim']
                    : $game['home_sim'];
                $bet_team_odds = $bet_team === $away_team
                    ? $game['away_vegas_odds']
                    : $game['home_vegas_odds'];
                $bet_advantage = $bet_team_pct_win -
                    OddsUtils::convertOddsToPct($bet_team_odds);
            }

            // Format color of score.
            $score = "$away_score - $home_score";
            if (($away_score > $home_score && $bet_team === $away_team) ||
                 ($home_score > $away_score && $bet_team === $home_team)) {
                $score = (new Font($score))->setColor(Colors::GREEN)->getHTML();
            } else if (($away_score < $home_score && $bet_team === $away_team)
                        || ($home_score < $away_score &&
                            $bet_team === $home_team)) {
                $score = (new Font($score))->setColor(Colors::RED)->getHTML();
            }

            $summary_data[] = array(
                'ID' => $game['gameid'],
                'Time (PST)' => $game_time,
                'Teams' => "$away_team @ $home_team",
                'Bet Team' => $bet_team,
                'Bet Pct Win' => round($bet_team_pct_win * 100) . '%',
                'Bet Advantage' => round($bet_advantage * 100, 1) . '%',
                'Bet' => $game['bet'],
                'Bet Odds' => $bet_team_odds,
                'Score' => $score,
                'Status' => $game['status'],
                'Payout' => $game['payout']
            );
        }

        return $summary_data;
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
