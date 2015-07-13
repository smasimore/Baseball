<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'SimInput.php';

$simInput = new SimInput;
$simInput->getLineups();
$simInput->write();

?>
