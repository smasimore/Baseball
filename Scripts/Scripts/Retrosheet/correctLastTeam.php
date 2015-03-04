<?php
//Copyright 2014, Saber Tooth Ventures, LLC

include(HOME_PATH.'Scripts/Include/RetrosheetInclude.php');

function pullPitcherStats($season, $table) {
    $sql =
        "SELECT DISTINCT
            b.player_id,
            b.last_team,
            a.last_team as correct_last_team,
            b.season,
            b.ds
        FROM
        (SELECT DISTINCT
            player_id,
            last_team,
            last_game,
            season,
            ds
        FROM $table
        WHERE season = $season
        AND split = 'Total'
        AND pitcher_type = 'R') a
        RIGHT OUTER JOIN
        (SELECT DISTINCT
            player_id,
            last_team,
            last_game,
            season,
            ds
        FROM $table
        WHERE season = $season
        AND pitcher_type = 'R') b
        ON a.player_id = b.player_id
        AND a.ds = b.ds
        AND a.season = b.season
        WHERE a.last_team <> b.last_team";
    return exe_sql(DATABASE, $sql);
}

$test = false;
$season_vars = array(
    'start_script' => 1950,
    'end_script' => 2014,
);
$table = RetrosheetTables::RETROSHEET_HISTORICAL_PITCHING;

for ($season = $season_vars['start_script'];
    $season < $season_vars['end_script'];
    $season++) {

    echo $season."\n";
    $daily_pitchers = pullPitcherStats($season, $table);
    if (!$daily_pitchers) {
        continue;
    }
    foreach ($daily_pitchers as $pitcher) {
        $player_id = $pitcher['player_id'];
        $last_team = $pitcher['correct_last_team'];
        $ds = $pitcher['ds'];
        $update_pitcher_data = array(
            'last_team' => $last_team
        );
        if ($test) {
            print_r($daily_pitchers);
            exit();
        }
        update(
            DATABASE,
            $table,
            $update_pitcher_data,
            'player_id',
            $player_id,
            'string',
            'season',
            $season,
            'ds',
            $ds
        );
    }
}

?>
