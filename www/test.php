<?php
include_once 'data/SimDebugDataType.php';

$dt = new SimDebugDataType();
$dt->gen();
print_r($dt->getGameID());
