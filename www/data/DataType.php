<?php

include_once '/Users/constants.php';
include_once __DIR__ . '/../../Scripts/Include/mysql.php';

abstract class DataType {

    protected $data;

    public function __construct() {}

    /*
     * @return string table name
     */
    abstract protected function getTable();

    /*
     * @return array<string> column names. Null return indicates select all.
     */
    abstract protected function getColumns();

    /*
     * @return array<string, mixed> params for where statement
     */
    abstract protected function getParams();

    protected function getDatabase() {
        return DATABASE;
    }

    public function gen() {
        $columns = $this->getColumns()
            ? implode(', ', $this->getColumns())
            : '*';
        $table = $this->getTable();
        $where = $this->formatParamsForWhereStmt();

        $query = "SELECT $columns FROM $table$where";

        $this->data = exe_sql($this->getDatabase(), $query);

        if (!$this->data) {
            throw new Exception("No data available for $query");
        }
    }

    private function formatParamsForWhereStmt() {
        $params = $this->getParams();

        if (!$params) {
            return null;
        }

        $where_stmt = ' WHERE ';
        foreach ($params as $key => $value) {
            if (gettype($value) === 'string') {
                $value = "'$value'";
            }

            // Last param.
            if ($key === key(array_slice($params, -1, 1, TRUE))) {
                $where_stmt .= "$key = $value";
            } else {
                $where_stmt .= "$key = $value AND ";
            }
        }

        return $where_stmt;
    }

}
