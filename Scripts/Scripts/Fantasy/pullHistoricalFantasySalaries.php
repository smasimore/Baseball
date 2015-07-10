<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once('/Users/constants.php');
include_once(HOME_PATH.'Scripts/Include/ScrapingInclude.php');

const VS_HAND = 0;
const DRAFT_STREET = 1;
const FAN_DUEL = 2;
const STAR_STREET = 3;
const FAN_THROWDOWN = 4;
const DRAFT_DAY = 5;
const DRAFT_KINGS = 6;
const SALARY_TABLE = 'f_historical_fantasy_salaries';

$colheads = array(
    'player_name',
    'team',
    'site',
    'position',
    'salary',
    'points_scored',
    'ds'
);

$test = false;

$target = "http://rotoguru1.com/cgi-bin/player.cgi?7702x";
$source_code = scrape($target);
$source_code = split_string($source_code, 'Pitchers (A-K)', AFTER, EXCL);
$players = parse_array_clean($source_code, "<option value=", ">");

foreach ($players as $player_id) {
    $target = "http://rotoguru1.com/cgi-bin/player.cgi?$player_id"."x";
    $source_code = scrape($target);
    $player_name =
        return_between($source_code, "<HTML><HEAD><TITLE>", "</TITLE>", EXCL);
    $team = trim(return_between($source_code, "Team: <b>", "</b><br>", EXCL));
	// Get last word of team name unless it's Sox (cause there are 2) since
	// they are formatted as 'Baltimore Orioles' etc.
	if (!strpos($team, 'Sox')) {
		$team = explode(" ", $team);
		$team = array_pop($team);
	}
	$dk_position =
		return_between($source_code, 'DraftKings position: <B>', '</b>', EXCL);
	$fd_position =
		return_between($source_code, 'FanDuel position: <B>', '</b>', EXCL);

	// Remove unncessesary headers/footers from source_code.
	$source_code = split_string($source_code, 'Pitchers (A-K)', BEFORE, EXCL);
	$source_code =
		split_string($source_code, 'Select another player', AFTER, EXCL);

	$last_name = trim(split_string($player_name, ",", BEFORE, EXCL));
	$first_name = trim(split_string($player_name, ",", AFTER, EXCL));
	$name = format_for_mysql($first_name."_$last_name");

	$salary_start = 'align=right>';
	$salary_end = '</td>';
	$salary = parse_array_clean($source_code, $salary_start, $salary_end);

	$points_start = '">';
	$points_end = '</a>';
	$points = parse_array_clean($source_code, $points_start, $points_end);

	$date_start = '<tr ><td bgcolor=FFCC99 align=center>';
	$date_end = '</td>';
	$dates = parse_array_clean($source_code, $date_start, $date_end);

	$ds_index = 0;
	$salary_index = 0;
	$insert_table = array();

	foreach ($points as $i => $point) {
		switch ($i % 7) {
			case VS_HAND:
				$salary_index += 2;
				// idx() is used for last few rows where there are no more dates.
				// TODO(cert): Fix this so script ends after last date.
				$month_day = idx($dates, $ds_index);
				$ds = "2014-$month_day";
				$ds_index += 1;
				break;
			case DRAFT_STREET:
				break;
			case FAN_DUEL:
				// Only insert players if they have a salary for that day.
				if (idx($salary, $salary_index) !== " ") {
					$insert_salary =
						str_replace(',', '', trim($salary[$salary_index], '$'));
					$insert_table[] = array(
						'player_name' => $name,
						'team' => Teams::getTeamAbbreviationFromName($team),
						'site' => 'fanduel',
						'position' => $fd_position,
						'salary' => $insert_salary,
						'points_scored' => (float)trim($point),
						'ds' => $ds
					);
				}
				$salary_index += 4;
				break;
			case STAR_STREET:
				break;
			case FAN_THROWDOWN:
				break;
			case DRAFT_DAY:
				break;
			case DRAFT_KINGS:
				if (idx($salary, $salary_index) !== " ") {
					$insert_salary =
						str_replace(',', '', trim($salary[$salary_index], '$'));
					$insert_table[] = array(
						'player_name' => $name,
						'team' => Teams::getTeamAbbreviationFromName($team),
						'site' => 'draftkings',
						'position' => $dk_position,
						'salary' => $insert_salary,
						'points_scored' => (float)trim($point),
						'ds' => $ds
					);
				}
				$salary_index += 1;
				break;
		}
	}

	if ($test) {
		print_r($insert_table);
		exit();
	}
	if (isset($insert_table[0])) {
		multi_insert(
			DATABASE,
			SALARY_TABLE,
			$insert_table,
			$colheads
		);
	}
}

?>
