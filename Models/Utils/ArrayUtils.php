<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

class ArrayUtils {

    public static function head($array) {
        if (!is_array($array)) {
            throw new Exception(
                'Cannot Use ArrayUtils::head on non-arrays'
            );
        }
        foreach ($array as $array_element) {
            return $array_element;
        }
    }

    public static function isArrayOfArrays($array) {
        foreach ($array as $sub_array) {
            return is_array($sub_array);
        }
    }

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

    public static function sortAssociativeArray(
        $array,
        $key,
        $keep_keys = true
    ) {
        $func = function($a, $b) use ($key) {
            return $a[$key] > $b[$key];
        };
        if ($keep_keys) {
            uasort($array, $func);
        } else {
            usort($array, $func);
        }

        return $array;
    }
}

?>
