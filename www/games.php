<?php
include_once 'includes/db_connect.php';
include_once 'includes/functions.php';
include_once 'includes/ui_elements.php';
include_once 'pages/GamesPage.php';

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
            if ($_GET['date'] != 'today' && isset($_GET['date'])) {
                $date = preg_replace('/[^\d-]+/', '', $_GET['date']);
            }
            $page = new GamesPage($date);
        } else { 
            ui_error_logged_out();
        } ?>
    </body>
</html>
