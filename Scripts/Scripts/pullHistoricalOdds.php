<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('default_socket_timeout', -1);
ini_set('max_execution_time', -1);
ini_set('mysqli.connect_timeout', -1);
ini_set('mysqli.reconnect', '1');
include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/RetrosheetInclude.php');
include(HOME_PATH.'Scripts/Include/Odds.php');

const GAMEDATE = 0;
const OPPONENT = 1;
const SCORE = 2;
const MONEYLINE = 5;
const HOME = 'home';
const AWAY = 'away';
const WIN = 'W';
const ERROR =
    'The file you requested has moved or does not exist on our system.';

$startYear = 1999;
$colheads = array(
	'gameid',
	'home',
	'away',
	'home_odds',
	'away_odds',
	'home_pct_win',
	'away_pct_win',
	'home_team_winner',
	'season',
	'game_date'
);

$test = false;

for ($season = $startYear; $season < 2015; $season++) {
	echo "$season \n";

	// Teams are represented on Covers.com from numbers 2955-2985.
	$insert_table = array();
	for ($i = 2955; $i < 2985; $i++) {

		$target =
			"http://www.covers.com/pageLoader/pageLoader.aspx?page=/data/mlb/teams/pastresults/$season/team$i.html";
		$source_code = scrape($target);
		if (strpos($source_code, ERROR)) {
			continue;
		}

		$team = return_between(
			$source_code,
			"<h1 class=\"teams\">",
			"<div class=\"teamlogo\">",
			EXCL
		);
		$team = trim(split_string($team, "<br>", AFTER, EXCL));
		$team = Teams::getStandardTeamName($team);
		$team_abbr = Teams::getTeamAbbreviationFromName($team);

		$data_pre = "<td class=\"datacell\">";
		$data_post = "</td>";
		$page_elements = parse_array_clean($source_code, $data_pre, $data_post);

		// Since page is parsed from top to bottom if there is a double header
		// Game 1 is seen before Game 0. For this reason we start with $game_num = 1
		// and then go down to 0 on line 80.
		$game_num = 1;
		$team_odds = array();
		$game_date = null;
		$home_away = null;
		foreach ($page_elements as $n => $data) {
			$data = trim($data);
			// Reset game_num every 7th page element since each game has 6
			// pieces of data.
			$index = $n % 7;
			if ($index == 0) {
				$game_num = 1;
			}
			switch ($index) {
				case GAMEDATE:
					$month = split_string($data, "/", BEFORE, EXCL);
					$day = return_between($data, "/", "/", EXCL);
					$game_date = "$season-$month-$day";
					if ($team_odds[$game_date][$game_num]) {
						$game_num = 0;
					}
					$team_odds[$game_date][$game_num]['game_date'] = $game_date;
					$team_odds[$game_date][$game_num]['season'] = $season;
					break;
				case OPPONENT:
					$home_away =
						trim(split_string($data, "<a href", BEFORE, EXCL));
					$opponent = return_between($data, "html\" >", "</a>", EXCL);
					$opponent = Teams::getStandardTeamAbbr($opponent);
					$home_team = $home_away == '@' ? trim($opponent) : $team_abbr;
					$away_team = $home_away == '@' ? $team_abbr : trim($opponent);
					$home_away = $home_away == '@' ? AWAY : HOME;
					$team_odds[$game_date][$game_num][HOME] = $home_team;
					$team_odds[$game_date][$game_num][AWAY] = $away_team;
					break;
				case SCORE:
					$win_loss = substr($data, 0, 1);
					$team_odds[$game_date][$game_num]['home_team_winner'] =
						$home_away === HOME
						? $win_loss === WIN
						: $win_loss !== WIN;
					break;
				case MONEYLINE:
					$line = trim($data, 'W');
					$line = trim($line, 'L');
					$line = (int)trim($line);
					$ml_index = $home_away."_odds";
					$pct_index = $home_away."_pct_win";
					$team_odds[$game_date][$game_num][$ml_index] = $line;
					$team_odds[$game_date][$game_num][$pct_index] =
						Odds::convertOddsToPct($line);
					break;
			}
		}

		foreach ($team_odds as $date) {
			$num_games = count($date);
			if ($num_games > 1) {
				foreach ($date as $num => $game) {
					$gameid = RetrosheetParseUtils::getGameID(
						$game['game_date'],
						$game[HOME],
						$game[AWAY],
						$num == 1 ? RetrosheetGameTypes::DOUBLE_HEADER_SECOND
						: RetrosheetGameTypes::DOUBLE_HEADER_FIRST
					);
					$game['gameid'] = $gameid;
					$insert_table[$gameid] =
						isset($insert_table[$gameid])
						? array_merge($insert_table[$gameid], $game) : $game;
				}
			} else {
				$gameid = RetrosheetParseUtils::getGameID(
					$date[1]['game_date'],
					$date[1][HOME],
					$date[1][AWAY]
				);
				$date[1]['gameid'] = $gameid;
				$insert_table[$gameid] =
					isset($insert_table[$gameid])
					? array_merge($insert_table[$gameid], $date[1]) : $date[1];
			}
		}
	}

	foreach ($insert_table as $game) {
		if (!array_key_exists('away_odds', $game)) {
			print_r($game);
			throw new Exception("Missing Away Odds");
		}
		if (!array_key_exists('home_odds', $game)) {
			print_r($game);
			throw new Exception("Missing Home Odds");
		}
	}

	if (!$test && isset($insert_table)) {
		multi_insert(
			DATABASE,
			Odds::HISTORICAL_ODDS_TABLE,
			$insert_table,
			$colheads
		);
	} else if ($test && isset($insert_table)) {
		print_r($insert_table);
		exit();
	}
}

?>
