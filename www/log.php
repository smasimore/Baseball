<?php
include_once 'includes/db_connect.php';
include_once 'includes/functions.php';
include_once 'pages/LogPage.php';

sec_session_start();
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Log</title>
        <link 
            rel="shortcut icon" 
            href="http://icons.iconarchive.com/icons/custom-icon-design/pretty-office-6/256/baseball-icon.png">
        <link rel="stylesheet" href="css/index.css" />
        <script type="text/JavaScript" src="js/errors.js"></script>
    </head>
        <body class="page">
        <?php
            $page = new LogPage(login_check($mysqli), $_GET['name']);
        ?>
    </body>
</html>
