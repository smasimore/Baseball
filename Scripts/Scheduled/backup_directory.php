<?php
include('/Users/constants.php');
include(HOME_PATH.'WebbotsSpidersScreenScraper_Libraries_REV2_0/sweetfunctions.php');

date_default_timezone_set('America/Los_Angeles');
$date = date('y-m-d');
$cmd = 'cp -r ~/Desktop/Baseball/ /Volumes/Sarah\ Masimore/Baseball/Directory_Backup/' . $date;
$cmd2 = 'cp -r /Library/WebServer/Documents/ /Volumes/Sarah\ Masimore/Baseball/Website_Backup/' . $date;
shell_exec($cmd);
shell_exec($cmd2);
send_email('Directory Backup Confirmation', "Baseball directory and website version $date backed up.");
?>
