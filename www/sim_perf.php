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

            $perf_data = $page->getPerfData();
            $perf_data_by_year = $page->getPerfDataByYear();
            $label = $page->getPerfScoreLabel($perf_data, 'Overall');
            $labels_by_year = $page->getPerfScoreLabelsByYear(
                $perf_data_by_year
            );
        ?>
        <script type="text/JavaScript">
            drawChart(
                <?php echo json_encode('overall'); ?>,
                <?php echo json_encode($label); ?>,
                <?php echo json_encode($perf_data); ?>
            );
            drawCharts(
                <?php echo json_encode($perf_data_by_year); ?>,
                <?php echo json_encode($labels_by_year); ?>
            );
        </script>
    </body>
</html>
