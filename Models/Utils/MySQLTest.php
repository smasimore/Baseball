<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'MySQL.php';

class MySQLTest extends PHPUnit_Framework_TestCase {

    public function providerInsert() {
        return array(
            array(
                MySQL::UNIT_TEST_TABLE,
                array('id' => 12345, 'name' => 'test'),
                sprintf(
                    'INSERT INTO %s (%s) VALUES (%s)',
                    MySQL::UNIT_TEST_TABLE,
                    'id,ts,name',
                    "'12345',null,'test'"
                )
            ),
            array(
                MySQL::UNIT_TEST_TABLE,
                array(
                    array('id' => 12345, 'name' => 'test'),
                    array('id' => 45678, 'name' => 'best'),
                ),
                sprintf(
                    'INSERT INTO %s (%s) VALUES (%s),(%s)',
                    MySQL::UNIT_TEST_TABLE,
                    'id,ts,name',
                    "'12345',null,'test'",
                    "'45678',null,'best'"
                )
            )
        );
    }

    /**
     * @expectedException Exception
     */
    public function testNoTable() {
        MySQL::insert(null, array('la' => 1));
    }

    /**
     * @expectedException Exception
     */
    public function testNoData() {
        MySQL::insert('test', array());
    }

    /**
     * @expectedException Exception
     */
    public function testNoColheadsFound() {
        MySQL::insert('made_up_table', array('la' => 1));
    }

    /**
     * @expectedException Exception
     */
    public function testNoDataForAllCols() {
        MySQL::insert(MySQL::UNIT_TEST_TABLE, array('id' => 1));
    }

    /**
     * @dataProvider providerInsert
     */
    public function testInsert($table, $data, $return) {
        $sql = MySQL::insert($table, $data);
        $this->assertEquals($sql, $return);
    }
}

?>
