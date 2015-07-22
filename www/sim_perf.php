<?php
include_once 'pages/SimPerformancePage.php';

sec_session_start();
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Sim Performance</title>
        <link rel="shortcut icon" href="http://icons.iconarchive.com/icons/custom-icon-design/pretty-office-6/256/baseball-icon.png">
        <link rel="stylesheet" href="css/index.css" />
        <script type="text/JavaScript" src="js/slider.js"></script>
        <script type="text/JavaScript" src="js/sim_perf.js"></script>
        <script src="js/highcharts/standalone-framework.js"></script>
        <script src="js/highcharts/highcharts.js"></script>
        <script src="js/highcharts/exporting.js"></script>
    </head>
    <body class="page">
        <?php
            $page = (new SimPerformancePage(login_check($mysqli)))
                ->setParams($_GET)
                ->render();
        ?>
        <script type="text/JavaScript">
            drawChart(
                <?php echo json_encode('overall'); ?>,
                <?php echo json_encode($page->getPerfData()); ?>
            );
            drawCharts(<?php echo json_encode($page->getPerfDataByYear()); ?>);
        </script>
    </body>
</html>
