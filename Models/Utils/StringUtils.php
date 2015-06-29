<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

class StringUtils {

    public static function formatName($string) {
        return ucwords(str_replace('_', ' ', $string));
    }
}

?>
