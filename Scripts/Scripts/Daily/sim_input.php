<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once('/Users/constants.php');
include_once(HOME_PATH.'Scripts/Scripts/Daily/SimInput.php');

$simInput = new SimInput;
$simInput->getLineups();
$simInput->write();

?>
