<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

/*
* The purpose of this file is to allow us to call one file and have
* access to all classes for Daily scripts. Make sure to add new
* class names to thie file when you add them.
 */

if (!defined('HOME_PATH')) {
    include_once('/Users/constants.php');
}

include_once(HOME_PATH.'Models/DataTypes/BetsDataType.php');
include_once(HOME_PATH.'Models/DataTypes/SimOutputDataType.php');
include_once(HOME_PATH.'Models/DataTypes/LiveOddsDataType.php');
include_once(HOME_PATH.'Models/DataTypes/LiveScoresDataType.php');
include_once(HOME_PATH.'Scripts/Scripts/Daily/DailyScriptWithWrite.php');
include_once(HOME_PATH.'Scripts/Include/Enum.php');
include_once(HOME_PATH.'Scripts/Include/sweetfunctions.php');
include_once(HOME_PATH.'Models/Constants/Tables.php');
include_once(HOME_PATH.'Models/Constants/GameStatus.php');
include_once(HOME_PATH.'Scripts/Include/Teams.php');
include_once(HOME_PATH.'Scripts/Include/DateTimeUtils.php');
include_once(HOME_PATH.'Models/Utils/ExceptionUtils.php');
include_once(HOME_PATH.'Models/Utils/OddsUtils.php');

?>
