<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'ArrayUtils.php';
include_once 'ExceptionUtils.php';

function idx($array, $key, $default = null) {
    if (isset($array[$key])) {
        return $array[$key];
    }
    return $default;
}

/*
 * funxtion index_by(
 *   array<array> $data_arr,
 *   mixed $index_arr, // array of indices or a string
 *   bool $strict = true, // should throw error on dupe index
 *   bool $non_unique = false, // whether index can have more than 1 value
 * )
 */
function index_by($data_arr, $index_arr, $strict = true, $non_unique = false) {
    if (!$data_arr) {
        return array();
    } else if (!ArrayUtils::isArrayOfArrays($data_arr)) {
        throw new Exception('Can Only Index An Array Of Arrays');
    } else if ($strict && $non_unique) {
        throw new Exception(
            'Cannot have strict and non-unique params both set as true'
        );
    }
    // If a string is passed in as an index convert into an array.
    if (!is_array($index_arr)) {
        $index_arr = array($index_arr);
    }

    $indexed_table = array();
    foreach ($data_arr as $data) {
        $final_index = "";
        foreach ($index_arr as $index) {
            $index_data = idx($data, $index);
            $final_index .= $index_data;
        }
        if ($strict && idx($indexed_table, $final_index) !== null) {
            throw new Exception(sprintf(
                'Cannot Index Uniquely On Key %s',
                $final_index
            ));
        }
        if ($non_unique) {
            $indexed_table[$final_index][] = $data;
        } else {
            $indexed_table[$final_index] = $data;
        }
    }
    return $indexed_table;
}

?>
