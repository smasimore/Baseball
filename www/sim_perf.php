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
            $perf_label = $page->getPerfScoreLabel(
                $perf_data,
                'Sim Performance'
            );
            $perf_labels_by_year = $page->getPerfScoreLabelsByYear();

            $bet_data_by_date = $page->getBetCumulativeData();
            $bet_label = $page->getBetCumulativeDataLabel(
                $bet_data_by_date,
                'Bet Performance'
            );
            $bet_data_by_date_by_year =
                $page->getBetCumulativeDataByYear();
            $bet_labels_by_year =
                $page->getBetCumulativeDataLabelByYear();
        ?>

        <script type="text/JavaScript">
            drawSimPerfChart(
                <?php echo json_encode('overall_perf'); ?>,
                <?php echo json_encode($perf_label); ?>,
                <?php echo json_encode($perf_data); ?>
            );
            drawSimBetChart(
                <?php echo json_encode('overall_bets'); ?>,
                <?php echo json_encode($bet_label); ?>,
                <?php echo json_encode($bet_data_by_date); ?>
            );
            drawSimPerfCharts(
                <?php echo json_encode($perf_data_by_year); ?>,
                <?php echo json_encode($perf_labels_by_year); ?>
            );
            drawSimBetCharts(
                <?php echo json_encode($bet_data_by_date_by_year); ?>,
                <?php echo json_encode($bet_labels_by_year); ?>
            );
        </script>
    </body>
</html>
