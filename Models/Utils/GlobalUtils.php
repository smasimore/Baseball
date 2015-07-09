<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

function idx($array, $key, $default = null) {
    if (isset($array[$key])) {
        return $array[$key];
    }
    return $default;
}

?>
