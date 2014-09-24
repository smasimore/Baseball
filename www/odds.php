<?php
include_once 'includes/db_connect.php';
include_once 'includes/functions.php';
include_once 'includes/sweetfunctions.php';
include_once 'includes/ui_elements.php';

$team = 'SF';
if (isset($_GET['team'])) {
    $team = preg_replace('/[^a-zA-Z0-9]+/', '', $_GET['team']);
}
$roi = exe_sql('baseball',
"SELECT *
FROM odds_2014 
WHERE casino = 'sportsbook.com' 
AND game_date = '2014-04-03'
AND (home = '$team' OR away = '$team')");
$graph_x = null;
$graph_y_away = null;
$graph_y_home = null;
$home_team = $roi[0]['home'];
$away_team = $roi[0]['away'];
$game_time = $roi[0]['game_time'];
$game_date = $roi[0]['game_date'];
foreach($roi as $bet) {
    $ds = $bet['odds_date']."_".$bet['odds_time'];
    $home_odds = $bet['home_pct_win'];
    $away_odds = $bet['away_pct_win'];
    $graph_x .= ",'".$ds."'";
    $graph_y_away .= ",".$away_odds;
    $graph_y_home .= ",".$home_odds;
}
$graph_x = substr($graph_x, 1);
$graph_y_away = substr($graph_y_away, 1);
$graph_y_home = substr($graph_y_home, 1);

sec_session_start();
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Game Odds</title>
        <link 
            rel="shortcut icon" 
            href="http://icons.iconarchive.com/icons/custom-icon-design/
                pretty-office-6/256/baseball-icon.png">
        <link rel="stylesheet" href="css/index.css" />
        <script type="text/JavaScript" src="js/tables.js"></script>
        <script type="text/JavaScript" src="js/Chart.js/Chart.js"></script>
        <meta name = "viewport" content = "initial-scale = 1, user-scalable = yes">
        <style>
            canvas{
            }
        </style>
    </head>
    <body>
    <?php if (login_check($mysqli) == true) {
            ui_page_header_odds();
            ui_page_header("Blue = $home_team   |   Grey = $away_team","$game_date @ $game_time");
        } else {
            ui_error_logged_out();
        }
    ?>
    <canvas id="season" height="450" width="1300"></canvas>

    <script>

    var graph_x = [<?php echo $graph_x; ?>]
    var graph_y_away = [<?php echo $graph_y_away; ?>]
    var graph_y_home = [<?php echo $graph_y_home; ?>]


    var season = {
      labels : graph_x,
      datasets : [
        {
            fillColor : "rgba(220,220,220,0.5)",
            strokeColor : "rgba(220,220,220,1)",
            pointColor : "rgba(220,220,220,1)",
            pointStrokeColor : "#fff",
            data : graph_y_away
        },
        {
          fillColor : "rgba(151,187,205,0.5)",
          strokeColor : "rgba(151,187,205,1)",
          pointColor : "rgba(151,187,205,1)",
          pointStrokeColor : "#fff",
          data : graph_y_home 
        }
      ]
    }

  var myLine = new Chart(document.getElementById("season").getContext("2d")).Line(season);
  
    </script> 
    </body>
</html>
