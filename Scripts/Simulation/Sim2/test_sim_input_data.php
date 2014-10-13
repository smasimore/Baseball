<?php 
include('/Users/constants.php'); 
include(HOME_PATH.'Scripts/Include/sweetfunctions.php');

$data = array(
    'gameid' => '2014033107smas',
    'game_date' => '2014-03-31',
    'home' => 'smas',
    'away' => 'cert',
    'stats_type' => 'basic',
    'stats_year' => 'current',
    'season' => 2014,
    'pitching_h' => json_encode(array(
        'name' => 'Sarah Masimore',
        'handedness' => 'L',
        'innings' => 7,
        'era' => 1.2,
        'bucket' => 'ERA25',
        'pitcher_vs_batter' => array(
            'player_name' => 'smas pitcher',
            'pct_single' => .1,
            'pct_double' => .1,
            'pct_triple' => .1,
            'pct_home_run' => .1,
            'pct_walk' => .1,
            'pct_strikeout' => .3,
            'pct_ground_out' => .1,
            'pct_fly_out' => .1
        ),
        'reliever_era' => 3.3,
        'reliever_bucket' => 'ERA75',
        'reliever_vs_batter' => array(
            'player_name' => "smas reliever",
            'pct_single' => .1,
            'pct_double' => .1,
            'pct_triple' => .1,
            'pct_home_run' => .1,
            'pct_walk' => .1,
            'pct_strikeout' => .3,
            'pct_ground_out' => .1,
            'pct_fly_out' => .1
        )
    )),
    'pitching_a' => json_encode(array(
        'name' => 'Dan Certner',
        'handedness' => 'R',
        'innings' => 5,
        'era' => 3.5,
        'bucket' => 'ERA75',
        'pitcher_vs_batter' => array(
            'player_name' => 'cert pitcher',
            'pct_single' => .3,
            'pct_double' => .1,
            'pct_triple' => .1,
            'pct_home_run' => .1,
            'pct_walk' => .1,
            'pct_strikeout' => .1,
            'pct_ground_out' => .1,
            'pct_fly_out' => .1
        ),
        'reliever_era' => 5.5,
        'reliever_bucket' => 'ERA100',
        'reliever_vs_batter' => array(
            'player_name' => 'cert pitcher',
            'pct_single' => .3,
            'pct_double' => .1,
            'pct_triple' => .1,
            'pct_home_run' => .1,
            'pct_walk' => .1,
            'pct_strikeout' => .1,
            'pct_ground_out' => .1,
            'pct_fly_out' => .1
        )
    )),
    'batting_h' => '',
    'batting_a' => '',
    'error_rate_h' => .02,
    'error_rate_a' => .05
);

function createBattingJson($team, $splits) {
    $team_batting = array();
    for ($player = 1; $player <= 9; $player++) {
        foreach ($splits as $split) {
            $team_batting[$player][$split] = array(
                'player_name' => "$team$player",
                'pct_single' => .3,
                'pct_double' => .1,
                'pct_triple' => .1,
                'pct_home_run' => .1,
                'pct_walk' => .1,
                'pct_strikeout' => .1, 
                'pct_ground_out' => .1,
                'pct_fly_out' => .1
            );
        }
    }

    return json_encode($team_batting);
}

$data['batting_h'] = createBattingJson(
    'smas', 
    array(
        'Total', 
        'Home', 
        'VsRight', 
        'VsLeft',
        'NoneOn', 
        'RunnersOn', 
        'ScoringPos', 
        'ScoringPos2Out',
        'BasesLoaded', 
        'ERA75', 
        'ERA100',
        'Stadium'
    )
);

$data['batting_a'] = createBattingJson(
    'cert',
    array(
        'Total',
        'Away',
        'VsLeft',
        'VsRight',
        'NoneOn',
        'RunnersOn',
        'ScoringPos',
        'ScoringPos2Out',
        'BasesLoaded',
        'ERA25',
        'ERA75',
        'Stadium'
    )
);

insert('baseball', 'sim_input_test', $data);

?>
