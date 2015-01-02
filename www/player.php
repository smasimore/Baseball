<?php
include_once 'pages/PlayerPage.php';

sec_session_start();
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Games</title>
        <link 
            rel="shortcut icon" 
            href="http://icons.iconarchive.com/icons/custom-icon-design/pretty-office-6/256/baseball-icon.png">
        <link rel="stylesheet" href="css/index.css" />
        <script type="text/JavaScript" src="js/tables.js"></script>
    </head>
    <body class="page">
        <?php if (login_check($mysqli) == true) {
            $player_page = new PlayerPage($_GET['player']);
        } else { 
            ui_error_logged_out();
        } ?>
    </body>
</html>
