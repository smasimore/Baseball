<?php
include_once 'classes/AnalysisPage.php';

sec_session_start();
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Analysis</title>
        <link 
            rel="shortcut icon" 
            href="http://icons.iconarchive.com/icons/custom-icon-design/pretty-office-6/256/baseball-icon.png">
        <link rel="stylesheet" href="css/index.css" />
        <script type="text/JavaScript" src="js/tables.js"></script>
         <script type="text/JavaScript" src="js/slider.js"></script>
        <script type="text/JavaScript" src="js/ChartNew/ChartNew.js"></script>
        <script type="text/JavaScript" src="js/graph.js"></script>
    </head>
    <body class="page">
        <?php if (login_check($mysqli) == true) {
            $page = new AnalysisPage($_GET);
            $page->display();
            list($graph_x, $graph_y) = $page->getGraphData();
            list($graph_home, $graph_away) = $page->getHomeAwayGraphData();
        ?>
            <script>
                displayLineChart('season', [<?php echo $graph_x; ?>], [<?php echo $graph_y; ?>]);
                displayLineChart(
                    'homeaway', 
                    [<?php echo $graph_x; ?>], 
                    [<?php echo $graph_home; ?>], 
                    [<?php echo $graph_away; ?>]
                );
            </script>
        <?php
        } else { 
            ui_error_logged_out();
        } ?>
    </body>
</html>
