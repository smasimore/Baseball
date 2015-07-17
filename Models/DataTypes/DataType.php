<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ . '/../Utils/mysqlDEPRECATED.php';
include_once __DIR__ . '/../Utils/GlobalUtils.php';
include_once __DIR__ . '/../Constants/Tables.php';
include_once __DIR__ . '/../Constants/SQLWhereParams.php';

abstract class DataType {

    protected $data;
    private $columns;

    public function __construct() {}

    /*
     * @return string table name
     */
    abstract protected function getTable();

    /*
     * @return array<array<string, mixed>> params for where statement
     * Arrays can be:
     * (1) SQLWhereParams::EQUAL
     * (2) NOT_EQUAL
     * (3) GREATER_THAN
     * (4) LESS_THAN
     */
    abstract protected function getParams();

    /*
     * @return string - name of mysql db
     */
    protected function getDatabase() {
        return DATABASE;
    }

    /*
     * @return void - function to modify $this->data
     */
    protected function formatData() {}

    /*
     * @return array<string> column names. Null return indicates select all.
     */
    protected function getColumns() {
        return null;
    }

    final public function setColumns($columns) {
        $this->columns = $columns;
        return $this;
    }

    final public function getFilteredData($filter_pairs, $keep_keys = false) {
        if (!count($filter_pairs)) {
            throw new Exception('Must Filter By Array With 1+ $key => $values');
        }
        $filtered_arr = array();
        foreach ($this->getData() as $data_key => $data) {
            $meets_filter_conditions = true;
            foreach ($filter_pairs as $filter_key => $filter_value) {
                if (idx($data, $filter_key) !== $filter_value) {
                    $meets_filter_conditions = false;
                    break;
                }
            }
            if ($meets_filter_conditions) {
                if ($keep_keys) {
                    $filtered_arr[$data_key] = $data;
                } else {
                    $filtered_arr[] = $data;
                }
            }
        }
        return $filtered_arr;
    }

    final public function gen() {
        $query = $this->getSQL();
        $this->data = exe_sql($this->getDatabase(), $query);

        if (!$this->data) {
            throw new Exception("No data available for $query");
        }

        $this->formatData();

        return $this;
    }

    public function getData() {
        return $this->data;
    }

    private function getSQL() {
        // Columns will !== null if callsite uses setColumns() to override
        // columns set by child DataType (using getColumns()).
        if ($this->columns) {
            $columns = implode(', ', $this->columns);

        // If getColumns is not null, child DataType is specifying columns.
        } else if ($this->getColumns()) {
            $columns = implode(', ', $this->getColumns());

        // If $this->columns and getColumns are null, select all.
        } else {
            $columns = '*';
        }

        return sprintf(
            'SELECT %s FROM %s%s',
            $columns,
            $this->getTable(),
            $this->getWhereStmt()
        );
    }

    private function getWhereStmt() {
        $params_arr = $this->getParams();
        if (!$params_arr) {
            return null;
        }
        $where_stmt = ' WHERE ';
        foreach ($params_arr as $param_type => $params) {
            if (!$params) {
                return null;
            }
            $where_stmt .= $where_stmt !== ' WHERE ' ? ' AND ' : '';
            $where_stmt .= $this->getWhereParams($params, $param_type);
        }
        return $where_stmt;
    }

    private function getWhereParams(
        $params,
        $param_type
    ) {
        $where_params = '';
        foreach ($params as $key => $value) {
            $operator = SQLWhereParams::getOperator($param_type);
            switch (gettype($value)) {
                case 'string':
                    $value = "'$value'";
                    break;
                case 'NULL':
                    if (SQLWhereParams::isGreaterThanLessThan($param_type)) {
                        throw new Exception('Cannot Have >/< null');
                    }
                    $value = 'null';
                    $operator = $operator === '=' ? 'is' : 'is not';
                    break;
                case 'boolean':
                    if (SQLWhereParams::isGreaterThanLessThan($param_type)) {
                        throw new Exception('Cannot Have >/< false');
                    }
                    $value = (int)$value;
                    break;
            }

            // Last param.
            if ($key === key(array_slice($params, -1, 1, TRUE))) {
                $where_params .= "$key $operator $value";
            } else {
                $where_params .= "$key $operator $value AND ";
            }
        }
        return $where_params;
    }
}
