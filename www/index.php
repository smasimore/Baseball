<?php
include_once 'pages/LoginPage.php';
include_once 'pages/GamesPage.php';

sec_session_start();
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Log In</title>
        <link rel="shortcut icon" href="http://icons.iconarchive.com/icons/custom-icon-design/pretty-office-6/256/baseball-icon.png">
        <link rel="stylesheet" href="css/index.css" />
        <script type="text/JavaScript" src="js/sha512.js"></script> 
        <script type="text/JavaScript" src="js/forms.js"></script> 
    </head>
    <body class="page">
        <?php
            $logged_in = login_check($mysqli);
            if (!$logged_in) {
                (new LoginPage($logged_in))
                    ->setLoginError(isset($_GET['error']))
                    ->render();
            } else {
                header('Location: /games.php', true);
                die();
            }
        ?> 
    </body>
</html>
