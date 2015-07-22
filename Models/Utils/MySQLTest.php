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

    public function providerExecute() {
        return array(
            array(
                sprintf(
                    'SELECT * FROM %s WHERE id = %d',
                    MySQL::UNIT_TEST_TABLE,
                    12345
                ),
                array(
                    array(
                        'id' => 12345,
                        'ts' => '2015-07-04 07:33:56',
                        'name' => 'Dan'
                    )
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
    public function testInsert($table, $data, $expected) {
        $sql = MySQL::insert($table, $data);
        $this->assertEquals($sql, $expected);
    }

    /**
     * @dataProvider providerExecute
     */
    public function testExecute($sql, $expected) {
        $data = MySQL::execute($sql);
        print_r($data);
        $this->assertEquals($data, $expected);
    }
}

?>
