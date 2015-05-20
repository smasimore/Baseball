<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

class ArrayUtils {

    public static function removeColumns($array, $col_names) {
        if (!is_array($col_names)) {
            throw new Exception('Second param needs to be an array of arrays.');
        }

        $ret_array = array();
        foreach ($array as $i => $row) {
            if (!is_array($row)) {
                throw new Exception(
                    'Second param needs to be an array of arrays.'
                );
            }

            foreach ($row as $key => $value) {
                if (!in_array($key, $col_names)) {
                    $ret_array[$i][$key] = $value;
                }
            }
        }

        return $ret_array;
    }
}

?>
