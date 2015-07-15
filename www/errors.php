<?php
include_once 'pages/ErrorLogPage.php';

sec_session_start();
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Errors</title>
        <link 
            rel="shortcut icon" 
            href="http://icons.iconarchive.com/icons/custom-icon-design/pretty-office-6/256/baseball-icon.png">
        <link rel="stylesheet" href="css/index.css" />
        <script type="text/JavaScript" src="js/tables.js"></script>
    </head>
    <body class="page">
        <?php
            $date = idx($_GET, 'date', date('Y-m-d'));
            if ($date) {
                $date = preg_replace('/[^\d-]+/', '', $date);
            }

            (new ErrorLogPage(login_check($mysqli)))
                ->setDate($date)
                ->render();
        ?>
    </body>
</html>
