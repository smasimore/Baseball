<?php
include_once 'includes/db_connect.php';
include_once 'includes/functions.php';
include_once 'includes/ui_elements.php';
include_once 'classes/PageHeader.php';

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
        if (isset($_GET['error'])) {
            echo '<p class="error" align="center">Error Logging In!</p>';
        }
        ?> 
        <?php 
            $header = new PageHeader(false, 'Saber Tooth Ventures', 'Owning Yachts Since 2014');
            $header->display();
            if (login_check($mysqli) == false) : 
        ?> 
            <form 
                id="form" 
                action="includes/process_login.php" 
                method="post" 
                name="login_form">                      
                <div class="fieldset">
                <fieldset>
                    <legend> Log in to continue </legend>
                    <p>
                        Username: <input type="text" name="username" /> 
                    </p>
                    <p>
                        Password: <input type="password" 
                                     name="password" 
                                     id="password"/>
                    </p>
                    <input 
                        type="button" 
                        value="Login" 
                        id="form_submit"
                           onclick="formhash(this.form, this.form.password);" 
                    /> 
                </fieldset>
                </div>
            </form>
        <?php else : ?> 
            <ul>
                <li><a href="games.php?date=today">Games</a></li>
                <li><a href="analysis.php">Analysis</a></li>
                <li><a href="roi.php">ROI</a></li>
                <li><a href="understand.php">Understand</a></li>
                <li><a href="sim.php">Sim</a></li>
                <li><a href="log.php?name=sarah">Sarah's Log</a></li>
                <li><a href="log.php?name=dan">Dan's Log</a></li>
            </ul>
            <p>If you are done, please <a href="includes/logout.php">log out</a>.
        <?php endif; ?>
    </body>
</html>
