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
        <script type="text/JavaScript" src="js/histogram.js"></script>
        <script src="js/highcharts/standalone-framework.js"></script>
        <script src="js/highcharts/highcharts.js"></script>
        <script src="js/highcharts/exporting.js"></script>
    </head>
    <body class="page">
        <?php
            $page = new SimPerformancePage(login_check($mysqli), $_GET);
            list($hist_actual, $hist_games) = $page->getHistData();
        ?>
        <script type="text/JavaScript">
            type = <?php echo json_encode('overall'); ?>;
            hist =
                <?php
                    echo json_encode($hist_actual['overall']);
                    unset($hist_actual['overall']);
                ?>;
            samples =
                <?php
                    echo json_encode($hist_games['overall']);
                    unset($hist_games['overall']);
                ?>;
            drawHistogram();
            drawHistograms(
                <?php
                    echo json_encode(array(
                        'hist' => $hist_actual,
                        'sample' => $hist_games
                    ));
                ?>
            );
        </script>
    </body>
</html>
