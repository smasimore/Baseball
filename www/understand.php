<?php
include_once 'includes/db_connect.php';
include_once 'includes/functions.php';
include_once 'includes/sweetfunctions.php';
include_once 'includes/ui_elements.php';

sec_session_start();
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Understand</title>
        <link 
            rel="shortcut icon" 
            href="http://icons.iconarchive.com/icons/custom-icon-design/
                pretty-office-6/256/baseball-icon.png">
        <link rel="stylesheet" href="css/index.css" />
        <script type="text/JavaScript" src="js/tables.js"></script>
    </head>
    <body class="page">
        <?php if (login_check($mysqli) == true) {
            $db = 'baseball';
            $team_map = $team_mapping;
            $betting_data = get_data($db, 'bets_2014');
            $home_away_results = calculate_situation_roi($betting_data, 'home_away');
            $sim_score_results = calculate_situation_roi($betting_data, 'sim_score');
            $vegas_score_results = calculate_situation_roi($betting_data, 'vegas_score');
            $bet_team_results = calculate_situation_roi($betting_data, 'bet_team', $team_map);
            $any_team_results = calculate_situation_roi($betting_data, 'any_team', $team_map);
            ui_page_header_odds();
            ui_table($home_away_results, 'home_away', true);
            ui_table($sim_score_results, 'sim_score', true);
            ui_table($vegas_score_results, 'vegas_score', true);
            ui_table($bet_team_results, 'bet_team', true);
            ui_table($any_team_results, 'any_team', true);
        } else { 
            ui_error_logged_out();
        } ?>
    </body>
</html>
