<?php
include_once 'pages/SimDebugPage.php';

sec_session_start();
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Sim Debug</title>
        <link rel="shortcut icon" href="http://icons.iconarchive.com/icons/custom-icon-design/pretty-office-6/256/baseball-icon.png">
        <link rel="stylesheet" href="css/index.css" />
        <script type="text/JavaScript" src="js/sim_debug.js"></script>
    </head>
    <body class="page">
        <?php
            $page = (new SimDebugPage(login_check($mysqli)))->render();
            $game_data = $page->getGameData();
        ?>
        <script type="text/JavaScript">
            drawSimDebugPage(
                <?php echo json_encode($game_data) ?>
            )
        </script>
    </body>
</html>
