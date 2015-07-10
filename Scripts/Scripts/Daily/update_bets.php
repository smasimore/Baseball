<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once('/Users/constants.php');
include_once(HOME_PATH.'Scripts/Scripts/Daily/UpdateBetsScript.php');

$b = new UpdateBetsScript;
$b->run();

?>
