<?php

include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/Teams.php');

$team = 'red soxx';
$team = Teams::getTeamCityFromName($team);
echo "team is $team \n";


?>
