<?php
include_once 'includes/db_connect.php';
include_once 'includes/functions.php';
include_once 'includes/sweetfunctions.php';
include_once 'includes/ui_elements.php';

$roi = exe_sql('baseball',
'SELECT sum(bet_amount) as bet_amount,
sum(bet_return) as bet_return, ds
FROM bets_2014
GROUP BY ds');
$total_bet = 0;
$total_return = 0;
$graph_x = null;
$graph_y = null;
$hacky_zero = null;
$graph_x_zoom = null;
$graph_y_zoom = null;
$seven_days_back = ds_modify($date, '-1 week');
$zoom_start = 0;
foreach($roi as $day) {
    $ds = $day['ds'];
    $total_bet += $day['bet_amount'];
    $total_return += $day['bet_return'];
    $daily_roi = number_format(($total_return / $total_bet * 100), 2);
    $graph_x .= ",'".$ds."'";
    $graph_y .= ",".$daily_roi;
    $graph_return .= ",".($total_return * 10);
    $hacky_zero .= ", 0";
    if ($ds == $seven_days_back) {
        $zoom_start = 1;
    }
    if ($zoom_start) {
        $graph_x_zoom .= ",'".$ds."'";
        $graph_y_zoom .= ",".$daily_roi;
        $hacky_zero_zoom .= ", 0";
    }
}
$graph_x = substr($graph_x, 1);
$graph_y = substr($graph_y, 1);
$graph_return = substr($graph_return, 1);
$hacky_zero = substr($hacky_zero, 1);
$hacky_zero_zoom = substr($hacky_zero, 1);
$graph_x_zoom = substr($graph_x_zoom, 1);
$graph_y_zoom = substr($graph_y_zoom, 1);

sec_session_start();
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Season ROI</title>
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
            $secure = 1;
        } else {
            ui_error_logged_out();
        }
    ?>
    <canvas id="season_zoom" height="450" width="800"></canvas>
    <canvas id="season" height="450" width="800"></canvas>
    <canvas id="season_return" height="450" width="800"></canvas>

    <script>

    var secure = [<?php echo $secure; ?>]
    var graph_x = [<?php echo $graph_x; ?>]
    var graph_y = [<?php echo $graph_y; ?>]
    var graph_return = [<?php echo $graph_return; ?>]
    var hacky_zero = [<?php echo $hacky_zero; ?>]
    var hacky_zero_zoom = [<?php echo $hacky_zero_zoom; ?>]
    var graph_x_zoom = [<?php echo $graph_x_zoom; ?>]
    var graph_y_zoom = [<?php echo $graph_y_zoom; ?>]


    var season = {
      labels : graph_x,
      datasets : [
        {
            fillColor : "rgba(220,220,220,0.5)",
            strokeColor : "rgba(220,220,220,1)",
            pointColor : "rgba(220,220,220,1)",
            pointStrokeColor : "#fff",
            data : hacky_zero
        },
        {
          fillColor : "rgba(151,187,205,0.5)",
          strokeColor : "rgba(151,187,205,1)",
          pointColor : "rgba(151,187,205,1)",
          pointStrokeColor : "#fff",
          data : graph_y 
        }
      ]

    }

    var season_zoom = {
      labels : graph_x_zoom,
      datasets : [
        {
            fillColor : "rgba(220,220,220,0.5)",
            strokeColor : "rgba(220,220,220,1)",
            pointColor : "rgba(220,220,220,1)",
            pointStrokeColor : "#fff",
            data : hacky_zero_zoom
        },
        {
          fillColor : "rgba(151,187,205,0.5)",
          strokeColor : "rgba(151,187,205,1)",
          pointColor : "rgba(151,187,205,1)",
          pointStrokeColor : "#fff",
          data : graph_y_zoom
        }
      ]

    }

    var season_return = {
      labels : graph_x,
      datasets : [
        {
            fillColor : "rgba(220,220,220,0.5)",
            strokeColor : "rgba(220,220,220,1)",
            pointColor : "rgba(220,220,220,1)",
            pointStrokeColor : "#fff",
            data : hacky_zero
        },
        {
          fillColor : "rgba(151,187,205,0.5)",
          strokeColor : "rgba(151,187,205,1)",
          pointColor : "rgba(151,187,205,1)",
          pointStrokeColor : "#fff",
          data : graph_return
        }
      ]

    }

    if (secure) {
        var myLine = new Chart(document.getElementById("season").getContext("2d")).Line(season);
        var myLineZoom = new Chart(document.getElementById("season_zoom").getContext("2d")).Line(season_zoom);
        var myLineReturn = new Chart(document.getElementById("season_return").getContext("2d")).Line(season_return);
    }
  
    </script> 
    </body>
</html>
