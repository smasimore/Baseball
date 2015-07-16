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

// TODO(cert) - Add ability for non-unique and "safe" indexing.
/*
 * funxtion index_by(
 *   array<array> $data_arr,
 *   mixed $index_arr, // array of indices or a string
 * )
 */
function index_by($data_arr, $index_arr) {
    if (!$data_arr) {
        throw new Exception('No Data To Index By');
    } else if (!ArrayUtils::isArrayOfArrays($data_arr)) {
        throw new Exception('Can Only Index An Array Of Arrays');
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
        $indexed_table[$final_index] = $data;
    }
    return $indexed_table;
}

?>
