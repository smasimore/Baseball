<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'ExceptionUtils.php';

function idx($array, $key, $default = null) {
    if (isset($array[$key])) {
        return $array[$key];
    }
    return $default;
}

// TODO(cert) - Clean this function up and add ability for non-unique and "safe".
// Also add tests :)
function index_by($data, $index, $index_2 = null, $index_3 = null) {
    if (!$data) {
        return array();
    }

    $indexed_table = array();
    foreach ($data as $row) {
        $i1 = $row[$index];
        if ($index_3) {
            $i3 = $row[$index_3];
            $i2 = $row[$index_2];
            $indexed_table[$i1.$i2.$i3] = $row;
        } else if ($index_2) {
            $i2 = $row[$index_2];
            $indexed_table[$i1.$i2] = $row;
        } else {
            $indexed_table[$i1] = $row;
        }
    }
    return $indexed_table;
}

?>
