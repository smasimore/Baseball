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
        <script type="text/JavaScript" src="js/sim_debug.js"></script>
    </head>
    <body class="page">
        <?php
            $page = new SimPerformancePage(login_check($mysqli), $_GET);
            //$data = $page->getJSData();
        ?>
        <script type="text/JavaScript">
            //drawSimAnalysisPage(
             //   <?php echo json_encode($data) ?>
            //)
        </script>
    </body>
</html>
