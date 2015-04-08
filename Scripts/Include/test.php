<?php

include('/Users/constants.php');
include(HOME_PATH.'Scripts/Include/mysql.php');

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

$data = exe_sql('baseball', "SELECT * FROM live_scores  WHERE ds = '2015-04-07' LIMIT 10");
print_r($data);

?>
