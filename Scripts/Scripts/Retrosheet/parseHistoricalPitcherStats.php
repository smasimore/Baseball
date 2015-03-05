<?php
//Copyright 2014, Saber Tooth Ventures, LLC

include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/RetrosheetInclude.php');

$test = false;
$runType = RetrosheetConstants::PITCHING;

// Function specific to relievers that gets stats based on a pitcher's
// team in the current season. $ds and $season refer to the date for which
// are pulling the stats while $pitcher_ds/season is the date on which the
// game is played and we know what team the pitcher is on.
function getRelieverPitchingData(
    $season,
    $ds,
    $table,
    $starter_reliever,
    $pitcher_ds
) {
    $pitcher_season = substr($pitcher_ds, 0, 4);
    $query =
        "SELECT b.last_team as player_id,
            sum(a.total_outs) as total_outs,
            sum(a.total_games) as total_games,
            sum(a.singles) as singles,
            sum(a.doubles) as doubles,
            sum(a.triples) as triples,
            sum(a.home_runs) as home_runs,
            sum(a.walks) as walks,
            sum(a.strikeouts) as strikeouts,
            sum(a.ground_outs) as ground_outs,
            sum(a.fly_outs) as fly_outs,
            sum(a.plate_appearances) as plate_appearances,
            a.split,
            a.season,
            a.ds
        FROM $table a
        RIGHT OUTER JOIN retrosheet_historical_pitching b
        ON a.player_id = b.player_id
        AND b.season = $pitcher_season
        AND b.ds = '$pitcher_ds'
        AND b.split = 'Total'
        AND b.pitcher_type = 'R'
        WHERE a.season = $season
        AND a.pitcher_type = 'R'
        AND a.ds = '$ds'
        AND b.last_team is not null
        GROUP BY b.last_team, a.split, b.season, b.ds";
    $season_data = exe_sql(DATABASE, $query);
    return index_by($season_data, 'player_id', 'split');
}

// Function can be used for all starter pulls and the season pull of relievers.
function getPitchingData(
    $season,
    $ds,
    $table,
    $starter_reliever
) {
    switch ($starter_reliever) {
        case RetrosheetConstants::RELIEVER:
            $query =
                "SELECT last_team as player_id,
                    sum(total_outs) as total_outs,
                    sum(total_games) as total_games,
                    sum(singles) as singles,
                    sum(doubles) as doubles,
                    sum(triples) as triples,
                    sum(home_runs) as home_runs,
                    sum(walks) as walks,
                    sum(strikeouts) as strikeouts,
                    sum(ground_outs) as ground_outs,
                    sum(fly_outs) as fly_outs,
                    sum(plate_appearances) as plate_appearances,
                    split,
                    season,
                    ds
                FROM $table
                WHERE season = $season
                AND pitcher_type = 'R'
                AND ds = '$ds'
                GROUP BY last_team, split, season, ds";
            break;
        case RetrosheetConstants::STARTER:
            $query =
                "SELECT *
                FROM $table
                WHERE season = $season
                AND pitcher_type = 'S'
                AND ds = '$ds'";
            break;
    }
    $season_data = exe_sql(DATABASE, $query);
    return index_by($season_data, 'player_id', 'split');
}

function updatePitchingArray($batting_instance, $player_stats) {
    $pct_stats = RetrosheetPercentStats::getPctStats();
    $player_id = $batting_instance['player_id'];
    $ds = $batting_instance['ds'];
    $split = $batting_instance['split'];
    $plate_appearances = $batting_instance['plate_appearances'];
    $total_outs = $batting_instance['total_outs'];
    $total_games = $batting_instance['total_games'];
    $avg_innings = $total_games
        ? number_format(
            $total_outs / 3 / $total_games,
            RetrosheetConstants::NUM_DECIMALS
            ) : 0;

    if ($plate_appearances < RetrosheetDefaults::MIN_PLATE_APPEARANCE) {
        return $player_stats;
    }
    $player_stats[$player_id][$ds][$split]['plate_appearances'] =
        $plate_appearances;
    if ($split === 'Home' || $split === 'Away' || $split === 'Total') {
        $player_stats[$player_id][$ds][$split]['avg_innings'] = $avg_innings;
    }
    foreach ($batting_instance as $stat_name => $stat) {
        if (in_array($stat_name, $pct_stats)) {
            $stat_pct_name = array_search($stat_name, $pct_stats);
            $stat_pct = number_format(
                $stat / $plate_appearances,
                RetrosheetConstants::NUM_DECIMALS
            );
            $player_stats[$player_id][$ds][$split][$stat_pct_name] = $stat_pct;
        }
    }
    return $player_stats;
}

$script_vars = array(
    'start_script' => 1950,
    'end_script' => 2014,
    'season_start' => null,
    'season_end' => null,
    'previous_season_end' => null
);
$colheads = array(
    'player_id',
    'stats',
    'season',
    'ds'
);

$starter_reliever =
    RetrosheetConstants::RELIEVER;
    //RetrosheetConstants::STARTER;
$season_insert_table = "historical_season_$starter_reliever"."_$runType";
$previous_insert_table = "historical_previous_$starter_reliever"."_$runType";
$career_insert_table = "historical_career_$starter_reliever"."_$runType";
$season_table = "retrosheet_historical_$runType";
$career_table = "retrosheet_historical_$runType"."_career";

for ($season = $script_vars['start_script'];
    $season < $script_vars['end_script'];
    $season++) {

    $script_vars = RetrosheetParseUtils::updateSeasonVars(
        $season,
        $script_vars,
        $career_table
    );
    // First season is left in just for the above function to register
    // previous_season_end.
    if ($season === $script_vars['start_script']) {
        continue;
    }
    $joe_average = RetrosheetParseUtils::getJoeAverageStats($season);
    $previous = $season - 1;
    // Since previous relief data depends on current season pitching, only
    // pull starting pitcher data before the ds loop.
    if ($starter_reliever === RetrosheetConstants::STARTER) {
        $previous_data = getPitchingData(
            $previous,
            $script_vars['previous_season_end'],
            $season_table,
            $starter_reliever
        );
    }
    for ($ds = $script_vars['season_start'];
        $ds <= $script_vars['season_end'];
        $ds = ds_modify($ds, '+1 day')) {
        echo $ds."\n";
        $player_season = null;
        $player_previous = null;
        $player_career = null;
        $season_data =
            getPitchingData($season, $ds, $season_table, $starter_reliever);
        // Since previous/career data requires current season info for relievers
        // we treat that seperately. No need to re-pull previous for starters
        // though since we pulled it earlier and it doesn't change throughout
        // season.
        if ($starter_reliever === RetrosheetConstants::RELIEVER) {
            $career_data = getRelieverPitchingData(
                $season,
                $ds,
                $career_table,
                $starter_reliever,
                $ds
            );
            $previous_data = getRelieverPitchingData(
                $previous,
                $script_vars['previous_season_end'],
                $season_table,
                $starter_reliever,
                $ds
            );
        } else {
            $career_data =
                getPitchingData($season, $ds, $career_table, $starter_reliever);
        }
        if (!$career_data) {
            echo "No Data For $ds \n";
            continue;
        }
        foreach ($career_data as $index => $career_split) {
            $player_career = updatePitchingArray(
                $career_split,
                $player_career
            );
            $previous_split =
                $previous_data ? idx($previous_data, $index): null;
            if ($previous_split) {
                $player_previous =
                    updatePitchingArray($previous_split, $player_previous);
            }
            $season_split = $season_data ? idx($season_data, $index) : null;
            if ($season_split) {
                $player_season =
                    updatePitchingArray($season_split, $player_season);
            }
        }

        $player_career = RetrosheetParseUtils::updateMissingSplits(
            $player_career,
            $joe_average,
            $runType,
            /* player_previous */ null,
            /* player_career */ null,
            $starter_reliever
        );
        $player_previous = RetrosheetParseUtils::updateMissingSplits(
            $player_previous,
            $joe_average,
            $runType,
            /* player_previous */ null,
            $player_career,
            $starter_reliever
        );
        $player_season = RetrosheetParseUtils::updateMissingSplits(
            $player_season,
            $joe_average,
            $runType,
            $player_previous,
            $player_career,
            $starter_reliever
        );

        $player_season = RetrosheetParseUtils::prepareStatsMultiInsert(
            $player_season,
            $season,
            $ds
        );
        $player_previous = RetrosheetParseUtils::prepareStatsMultiInsert(
            $player_previous,
            $season,
            $ds
        );
        $player_career = RetrosheetParseUtils::prepareStatsMultiInsert(
            $player_career,
            $season,
            $ds
        );

        if ($test) {
            if (isset($player_season)) {
                print_r($player_season);
                exit();
            }
        } else {
            if (isset($player_season)) {
                multi_insert(
                    DATABASE,
                    $season_insert_table,
                    $player_season,
                    $colheads
                );
            }
            if (isset($player_previous)) {
                multi_insert(
                    DATABASE,
                    $previous_insert_table,
                    $player_previous,
                    $colheads
                );
            }
            if (isset($player_career)) {
                multi_insert(
                    DATABASE,
                    $career_insert_table,
                    $player_career,
                    $colheads
                );
            }
        }
    }
}

?>
