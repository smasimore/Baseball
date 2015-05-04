<?php
include_once __DIR__ . '/../Models/DataTypes/SimDebugDataType.php';

$dt = new SimDebugDataType();
$dt->gen();
print_r($dt->getGameID());
