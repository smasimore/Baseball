<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'ExceptionUtils.php';

function idx($array, $key, $default = null) {
    if (isset($array[$key])) {
        return $array[$key];
    }
    return $default;
}

?>
