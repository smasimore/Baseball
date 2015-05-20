<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once '/Users/constants.php';
include_once __DIR__ . '/../../Scripts/Include/mysql.php';
include_once __DIR__ . '/../Constants/Tables.php';

abstract class DataType {

    protected $data;
    private $columns;

    public function __construct() {}

    /*
     * @return string table name
     */
    abstract protected function getTable();

    /*
     * @return array<string, mixed> params for where statement
     */
    abstract protected function getParams();

    /*
     * @return array<string, mixed> params for !== where statement
     */
    protected function getNotParams() {
        return array();
    }

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

    final public function setColumns($columns) {
        $this->columns = $columns;
        return $this;
    }

    final public function gen() {
        $this->data = exe_sql($this->getDatabase(), $this->getSQL());

        $this->formatData();

        if (!$this->data) {
            throw new Exception("No data available for $query");
        }

        return $this;
    }

    final public function getData() {
        return $this->data;
    }

    /*
     * @return array<string> column names. Null return indicates select all.
     */
    private function getColumns() {
        return $this->columns;
    }

    private function getSQL() {
       $columns = $this->getColumns()
            ? implode(', ', $this->getColumns())
            : '*';

        return sprintf(
            'SELECT %s FROM %s%s',
            $columns,
            $this->getTable(),
            $this->getWhereStmt()
        );
    }

    private function getWhereStmt() {
        $params = $this->getParams();
        $not_params = $this->getNotParams();

        if (!$params && !$not_params) {
            return null;
        }

        $where_stmt = ' WHERE ';
        if ($params) {
            $where_stmt .= $this->getWhereParams($params);
        }

        if ($not_params) {
            $where_stmt .= $params ? ' AND ' : '';
            $where_stmt .= $this->getWhereParams($not_params, true);
        }

        return $where_stmt;
    }

    private function getWhereParams($params, $is_not_params = false) {
        $where_params = '';
        foreach ($params as $key => $value) {
            $operator = $is_not_params ? '<>' : '=';
            switch (gettype($value)) {
                case 'string':
                    $value = "'$value'";
                    break;
                case 'NULL':
                    $value = 'null';
                    $operator = $operator === '=' ? 'is' : 'is not';
                    break;
                case 'boolean':
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
