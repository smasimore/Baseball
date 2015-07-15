<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

if (!defined('DATABASE')) {
    include_once('/Users/constants.php');
}

include_once 'ExceptionUtils.php';
include_once 'ArrayUtils.php';

class MySQL {

    const MYSQL_REQUESTS = 'mysql_requests';
    const UNIT_TEST_TABLE = 'test_mysql';

    public static function insert($table, $data, $database = DATABASE) {
        self::validateCanWrite(__METHOD__, $table, $data);

        // Connect to mysql unless in unit test.
        if ($table !== self::UNIT_TEST_TABLE) {
            $mysqli_connect = self::connectToDatabase();
            mysqli_select_db($mysqli_connect, $database);
        }

        $colheads = self::getColheads($table);

        $sql = array();
        if (!ArrayUtils::isArrayOfArrays($data)) {
            $data = array($data);
        }
        foreach ($data as $row) {
            $insert_row = array();
            foreach ($colheads as $col => $default) {
                if (!array_key_exists($col, $row)) {
                    if ($default !== null) {
                        // Just insert null mysql will handle the rest.
                        $insert_row[] = 'null';
                    } else {
                        throw new Exception(sprintf(
                            'Not all columns specified for insert into %s',
                            $table
                        ));
                    }
                } else if (is_null($row[$col])) {
                    $insert_row[] = 'null';
                } else if (mb_detect_encoding($row[$col]) !== 'ASCII') {
                    $insert_row[] = 'null';
                    $e = new Exception(sprintf(
                        'Trying to insert a foreign character into table %s',
                        $table
                    ));
                    ExceptionUtils::logDisplayEmailException($e, 'd');
                } else {
                    $insert_data = $row[$col];
                    if (strpos($insert_data, "'") !== false) {
                        $insert_data = str_replace("'", '"', $insert_data);
                        $e = new Exception(sprintf(
                            'Trying to insert a single quote (now converted -> %s) into %s',
                            $insert_data,
                            $table
                        ));
                        ExceptionUtils::logDisplayEmailException($e, 'd');
                    }
                    $insert_row[] = "'$insert_data'";
                }
            }
            $sql[] = sprintf('(%s)', implode(',', $insert_row));
        }

        $final_sql = sprintf(
            'INSERT INTO %s (%s) VALUES %s',
            $table,
            implode(',', array_keys($colheads)),
            implode(',', $sql)
        );

        // If unit test return query. Otherwise insert into mysql.
        if ($table === self::UNIT_TEST_TABLE) {
            return $final_sql;
        }

        $result = mysqli_query($mysqli_connect, $final_sql);

        if (mysqli_error($mysqli_connect)) {
            throw new Exception(sprintf(
                '%s During Insert Into %s with query "%s"',
                mysqli_error($mysqli_connect),
                $table,
                $final_sql
            ));
        }

        mysqli_close($mysqli_connect);
        self::logEvent(__METHOD__, $table, $data);
    }

    // Function to try mysqli connect 10 times before failing.
    private static function connectToDatabase() {
        $attempts = 0;
        $mysqli_connect = self::mysqliConnect();
        while ($attempts < 9 && mysqli_connect_errno()) {
            $mysqli_connect = self::mysqliConnect();
            $attempts++;
        }
        if ($attempts === 10) {
            throw new Exception(sprintf(
                'Failed To Connect With Error %s',
                mysqli_connect_error()
            ));
        }
        return $mysqli_connect;
    }

    private static function mysqliConnect() {
        return mysqli_connect(HOST, MYSQL_USER, MYSQL_PASSWORD);
    }

    private static function validateCanWrite($method, $table, $data) {
        if ($table === null) {
            throw new Exception(sprintf(
                'No Table Provided For %s',
                $method
            ));
        } else if (!$data) {
            throw new Exception(sprintf(
                'No Data Provided For %s Into %s',
                $method,
                $table
            ));
        }
    }

    private static function getColheads($table) {
        $sql = sprintf(
            "SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_DEFAULT
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = '%s'
            AND TABLE_NAME = '%s'",
            DATABASE,
            $table
        );
        $data = exe_sql(DATABASE, $sql);
        $colheads = array();
        $is_all_nullable = 'YES';
        foreach ($data as $row) {
            $colheads[$row['COLUMN_NAME']] = $row['COLUMN_DEFAULT'];
            $is_all_nullable = $row['IS_NULLABLE'] === 'NO'
                ? 'NO'
                : $is_all_nullable;
        }
        if (!$colheads) {
            throw New Exception(sprintf(
                'No Columns In Table %s. Perhaps this is not a real table',
                $table
            ));
        } else if ($is_all_nullable === 'YES') {
            $e = new Exception(sprintf(
                'Table %s has no non-nullable values, consider fixing this',
                $table
            ));
            ExceptionUtils::logDisplayEmailException($e, 'd');
        }
        return $colheads;
    }

    private static function logEvent($method, $table, $data) {
        // Prevent looping.
        if ($table === self::MYSQL_REQUESTS) {
            return;
        }
        $logged_data = array(
            'ds' => date('Y-m-d'),
            'time' => time(),
            'request' => $method,
            'table_name' => $table,
            'rows' => count($data)
        );
        self::insert(self::MYSQL_REQUESTS, $logged_data);
    }
}

?>
