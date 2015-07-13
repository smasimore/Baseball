<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

/*
* The purpose of this file is to allow us to call one file and have
* access to all classes for Daily scripts. Make sure to add new
* class names to thie file when you add them.
 */

include_once __DIR__ .'/../Utils/DateTimeUtils.php';
include_once __DIR__ .'/../Utils/ExceptionUtils.php';
include_once __DIR__ .'/../Utils/OddsUtils.php';
include_once __DIR__ .'/../Utils/ArrayUtils.php';
include_once __DIR__ .'/../Utils/GlobalUtils.php';
include_once __DIR__ .'/../Utils/sweetfunctions.php';

include_once __DIR__ .'/../DataTypes/BetsDataType.php';
include_once __DIR__ .'/../DataTypes/SimOutputDataType.php';
include_once __DIR__ .'/../DataTypes/LiveOddsDataType.php';
include_once __DIR__ .'/../DataTypes/LiveScoresDataType.php';

include_once __DIR__ .'/../Constants/Tables.php';
include_once __DIR__ .'/../Constants/GameStatus.php';
include_once __DIR__ .'/../Constants/Teams.php';

include_once __DIR__ .'/../Traits/TScriptWithInsert.php';
include_once __DIR__ .'/../Traits/TScriptWithUpdate.php';

include_once __DIR__ .'/../../Scripts/Daily/ScriptWithWrite.php';

?>
