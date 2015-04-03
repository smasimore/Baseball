<?php

include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/RetrosheetPlayerMapping.php');

$test = array(
    array(
        'last' => 'trout',
        'first' => 'mike'
    ),
    array(
        'last' => 'carpenter',
        'first' => 'david',
        'team' => 'ATL'
    ),
    array(
        'last' => 'posey',
        'first' => 'buster'
    )
);

$id_map = RetrosheetPlayerMapping::getPlayerIDMap($test, 2014);
print_r($id_map);

?>
