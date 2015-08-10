<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'Page.php';
include_once __DIR__ .'/../../Models/Traits/TPageWithDate.php';
include_once __DIR__ .'/../../Models/DataTypes/SimInputDataType.php';
include_once __DIR__ .'/../../Models/DataTypes/BetsDataType.php';
include_once __DIR__ .'/../../Models/DataTypes/LiveOddsDataType.php';

class GamesPage extends Page {

    use TPageWithDate;

    const THREE_HRS_IN_SECS = 10800;
    const NOT_STARTED = 'Not Started';

    private $gamesData;

    private $simInputData;
    private $seasonBetsData;
    private $betsData;
    private $liveOddsDT;

    final protected function gen() {
        // Gen BetsDT before simInputDT since when there are no games set we
        // still want the ROI header to render with data.
        $weights = array(StatsCategories::B_HOME_AWAY => 1.0);
        $bets_dt = (new BetsDataType())
            ->setWeights($weights)
            ->setSeasonRange(DateTimeUtils::getSeasonFromDate($this->date))
            ->gen();
        $this->seasonBetsData = $bets_dt->getData();

        // Get current day's bet data by filtering the cumulative bets DT.
        $filter = array('game_date' => $this->date);
        $this->betsData = $bets_dt->getFilteredData($filter, true);
        $this->betsData = ArrayUtils::sortAssociativeArray(
            $this->betsData,
            'game_time'
        );

        $this->simInputData = (new SimInputDataType())
            ->setGameDate($this->date)
            ->gen()
            ->getData();

        // Fetch odds to get starting and most recent odds.
        $this->liveOddsDT = (new LiveOddsDataType())
            ->setGameDate($this->date)
            ->gen();

        $this->setupGameData();
    }

    final protected function getHeaderParams() {
        return array(
            $this->date,
            array(
                $this->getROIHeader($this->betsData, "Today's"),
                $this->getROIHeader($this->seasonBetsData, 'Season')
            )
        );
    }

    final protected function renderPage() {
        $summary_data = $this->getSummaryTableData();
        (new Table())
            ->setData($summary_data)
            ->setID('summary_table')
            ->render();

        // Games section.
        $this->getGamesSection()->render();
    }

    private function setupGameData() {
        foreach ($this->simInputData as $gameid => $game) {
            $team_stats = $this->getTeamStats($game);
            $this->gamesData[$gameid] = array(
                'team_stats' => $team_stats
            );
        }
    }

    private function getROIHeader($bets_data, $roi_type) {
        $roi = ROIUtils::calculateROI($bets_data);
        list($wins, $losses) = ROIUtils::calculateRecord($bets_data);
        return $roi === null
            ? 'No Games Completed Yet'
            : sprintf(
                "%s ROI: %s Record: (%d - %d) Net %s: $%d",
                $roi_type,
                number_format(($roi * 100), 2) . '%',
                $wins,
                $losses,
                $roi > 0 ? 'Gain' : 'Loss',
                array_sum(array_column($bets_data, 'payout'))
            );
    }

    private function getTeamStats($game_data) {
        return array(
            array(
                'Pitcher',
                StringUtils::formatName($game_data['pitching_h']['name']),
                StringUtils::formatName($game_data['pitching_a']['name'])
            )
        );
    }

    private function getSummaryTableData() {
        $summary_data = array();
        foreach ($this->betsData as $game) {
            // Convert to PST and format.
            $game_time = date(
                'g:i a',
                $game['game_time'] - self::THREE_HRS_IN_SECS
            );

            $away_team = $game['away'];
            $home_team = $game['home'];
            $bet_team = $game['bet_team'];
            $bet_away_team = $bet_team === $away_team;
            $away_score = $game['away_score'];
            $home_score = $game['home_score'];

            // Format scores.
            $away_score_formatted = $game['status'] === self::NOT_STARTED
                ? ''
                : sprintf(' (%d)', $away_score);
            $home_score_formatted = $game['status'] === self::NOT_STARTED
                ? ''
                : sprintf(' (%d)', $home_score);

            // Format game matchup column.
            $matchup = sprintf(
                '%s%s @ %s%s%s',
                $bet_team && $bet_away_team ? "*$away_team*" : $away_team,
                $away_score_formatted,
                $bet_team && !$bet_away_team ? "*$home_team*" : $home_team,
                $home_score_formatted,
                $game['status'] ? ' - ' . $game['status'] : ''
            );
            if (($away_score > $home_score && $bet_team && $bet_away_team) ||
                ($home_score > $away_score && $bet_team && !$bet_away_team)) {
                $matchup = (new Font($matchup))
                    ->setColor(Colors::GREEN)
                    ->getHTML();
            } else if (
                ($away_score < $home_score && $bet_team && $bet_away_team) ||
                ($home_score < $away_score && $bet_team && !$bet_away_team)) {
                $matchup = (new Font($matchup))
                    ->setColor(Colors::RED)
                    ->getHTML();
            }

            // Bet columns - default to home odds if no bet team.
            $bet_team_pct_win = $bet_away_team
                ? $game['away_sim']
                : $game['home_sim'];
            $bet_team_odds = $bet_away_team
                ? $game['away_vegas_odds']
                : $game['home_vegas_odds'];
            $bet_advantage = $bet_team_pct_win -
                OddsUtils::convertOddsToPct($bet_team_odds);

            $most_recent_odds = $this->liveOddsDT->getMostRecentOdds(
                $game['gameid'],
                $bet_away_team
            );
            $bet_odds_movement = sprintf(
                '%d  --->  %d (%d%%)',
                $this->liveOddsDT->getStartingOdds(
                    $game['gameid'],
                    $bet_away_team
                ),
                $most_recent_odds,
                round(OddsUtils::convertOddsToPct($most_recent_odds) * 100)
            );

            $odds = $bet_team === null
                ? null
                : sprintf(
                    '%d (+%d%%)',
                    $bet_team_odds,
                    round($bet_advantage * 100, 1)
                );

            $predicted = $bet_team_pct_win  === null
                ? null
                : sprintf(
                    '%d (%d%%)',
                    OddsUtils::convertPctToOdds($bet_team_pct_win),
                    round($bet_team_pct_win * 100)
                );

            $bet_and_payout = sprintf(
                '$%d / $%s',
                $game['bet'],
                $game['payout'] !== null ? $game['payout'] : ' --'
            );

            $summary_data[] = array(
                'Game' => $matchup,
                'Start Time (PST)' => $game_time,
                'Odds (Advantage)' => $odds,
                'Odds Movement' => $bet_odds_movement,
                'Predicted Odds' => $predicted,
                'Bet / Payout' => $bet_and_payout
            );
        }

        return $summary_data;
    }

    private function getGamesSection() {
        $games = array();
        foreach ($this->betsData as $gameid => $game) {
            $games_section = $this->getGameSection(
                $gameid,
                $this->gamesData[$gameid]
            );
            $games[] = (new Div($games_section))
                ->setClass('game_section')
                ->getHTML();
        }

        return (new UOList())
            ->setItems($games);
    }

    private function getGameSection($gameid, $data) {
        return (new UOList())
            ->setItems(array(
                $this->getGameHeader($gameid),
                $this->getTeamSection($gameid, $data)
            ))
            ->getHTML();
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
        return (new Table())
            ->setData($data['team_stats'])
            ->setID(sprintf('team_data_%s', $gameid))
            ->setHeader(array('', 'Home', 'Away'))
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
