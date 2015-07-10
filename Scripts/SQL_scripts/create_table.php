<?php

include_once('/Users/constants.php');

// USER PARAMS //
$table = 'sim_output';
$columns = array(
    'home_win_pct' => 'FLOAT',
    'game_details' => 'MEDIUMTEXT',
    'gameid' => 'VARCHAR(25)',
    'game_date' => 'DATE',
    'date_ran_sim' => 'DATE',
    'home' => 'VARCHAR(5)',
    'away' => 'VARCHAR(5)',
    'season' => 'SMALLINT(4)',
    'stats_year' => 'VARCHAR(8)',
    'stats_type' => 'VARCHAR(5)',
    'weights_i' => 'SMALLINT(5)',
    'weights' => 'MEDIUMTEXT',
    'weights_mutator' => 'VARCHAR(25)',
    'analysis_runs' => 'SMALLINT(7)',
    'sim_game_date' => 'VARCHAR(10)',
    'timestamp' => 'TIMESTAMP'
);
$partitions = array(
    'season' => array(
        'type' => 'int',
        'min' => '1950',
    ),
    'weights_i' => array(
        'type' => 'int',
        'min' => 1
    )
);

// SQL PARAMS //
$pwd = DB_PASSWORD;
$db = DATABASE;

$sql_cols = implode(
    ', ',
    array_map(
        function($col, $type) {
            return "$col $type";
        },
        array_keys($columns),
        $columns
    )
);

if (count($partitions) === 1) {
    $col = key($partitions);
    $partition_params = current($partitions);
    $min = $partition_params['min'];
    if ($partition_params['type'] === 'date') {
        $p = str_replace("-", "", $min);;
        $partition = "'$min'";
    }

    $sql_partitions =
        "PARTITION BY LIST($col) (
        PARTITION p$p VALUES IN ($partition))";
} else {
    $cols = array();
    $p = array();
    $partition = array();
    foreach ($partitions as $col => $params) {
        $min = $params['min'];
        $cols[$col] = $col;
        $p[$col] = str_replace("-", "", $min);
        $partition[$col] = $params['type'] === 'int' ?
            "$min" : "'$min'";
    }

    $cols = implode(', ', $cols);
    $p = implode('', $p);
    $partition = implode(', ', $partition);

    $sql_partitions =
        "PARTITION BY LIST COLUMNS($cols) (
        PARTITION p$p VALUES IN (($partition)))
        ";
}

$sql = "CREATE TABLE $table ($sql_cols) $sql_partitions;";

$cmd = "mysql -u root -p$pwd -D $db < auto_create_table.sql";

file_put_contents('auto_create_table.sql', $sql);
shell_exec($cmd);
