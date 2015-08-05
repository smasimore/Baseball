<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once('/Users/constants.php');
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

        self::executeLogAndClose(
            $mysqli_connect,
            $final_sql,
            __METHOD__,
            $table,
            $data
        );
    }

    /*
     * Function MySQL::update()
     *
     * @param string $table     Name of table to update.
     * @param array $data       Array of $key => $value's to update.
     * @param array $where      Array of $key => $values to restrict update.
     *
     * @return void
     */
    public static function update($table, array $data, array $where) {
        if (!$where) {
            throw new Exception('Where Array Must Be Set For SQL Updates');
        }
        // Connect to mysql unless in unit test.
        if ($table !== self::UNIT_TEST_TABLE) {
            $mysqli_connect = self::connectToDatabase();
            mysqli_select_db($mysqli_connect, $database);
        }

        $update_list = '';
        foreach ($data as $key => $value) {
            $value = gettype($value) === 'string' ? "'$value'" : $value;
            $update_list .= "$key = $value, ";
        }
        $update_list = rtrim($update_list, ', ');

        $where_list = '';
        foreach ($where as $key => $value) {
            $value = gettype($value) === 'string' ? "'$value'" : $value;
            $where_list .= "$key = $value AND ";
        }
        $where_list = rtrim($where_list, ' AND ');

        $final_sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            $update_list,
            $where_list
        );

        // If unit test return query. Otherwise update mysql.
        if ($table === self::UNIT_TEST_TABLE) {
            return $final_sql;
        }

        self::executeLogAndClose(
            $mysqli_connect,
            $final_sql,
            __METHOD__,
            $table,
            $data
        );
    }

    public static function execute($sql, $database = DATABASE) {
        $mysqli_connect = self::connectToDatabase();
        mysqli_select_db($mysqli_connect, $database);

        $result = mysqli_query($mysqli_connect, $sql);

        $error = mysqli_error($mysqli_connect);
        self::checkMysqliError($error, __METHOD__, $sql);

        // Get data from mysqli result object.
        $result_set = array();
        for ($i = 0; $i < mysqli_num_rows($result); $i++) {
            $result_set[$i] = mysqli_fetch_assoc($result);
        }
        mysqli_close($mysqli_connect);

        $table = self::getTableNameFromSQL($sql);
        self::logEvent(__METHOD__, $table, $result_set);
        return $result_set;
    }

    private static function executeLogAndClose(
        $mysqli_connect,
        $final_sql,
        $method,
        $table,
        $data
    ) {
        mysqli_query($mysqli_connect, $final_sql);

        $error = mysqli_error($mysqli_connect);
        self::checkMysqliError($error, $method, $final_sql);
        mysqli_close($mysqli_connect);

        self::logEvent($method, $table, $data);
    }

    private static function checkMysqliError($error, $method, $sql) {
        if ($error) {
            throw new Exception(sprintf(
                'Error %s During %s with Query "%s"',
                $error,
                $method,
                $sql
            ));
        }
    }

    private static function getTableNameFromSQL($sql) {
        $table = return_between($sql, 'FROM ', ' ', EXCL);
        if (!$table) {
            $table = return_between($sql, 'from ', ' ', EXCL);
        }
        return trim($table);
    }

    // Function to try mysqli connect 10 times before failing.
    private static function connectToDatabase() {
        $attempts = 0;
        $mysqli_connect = self::mysqliConnect();
        while ($attempts < 9 && mysqli_connect_errno()) {
            $mysqli_connect = self::mysqliConnect();
            $attempts++;
        }
        if ($attempts === 9) {
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

    private static function getColheads($table, $database = DATABASE) {
        $sql = sprintf(
            "SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_DEFAULT
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = '%s'
            AND TABLE_NAME = '%s'",
            $database,
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
