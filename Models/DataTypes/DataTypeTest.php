<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'DataType.php';

class DataTypeTest extends PHPUnit_Framework_TestCase {

    private $mockDataTypeClass;
    private $getSQLMethod;

    public function providerSetColumns() {
        return array(
            array(
                null,
                'SELECT * FROM test'
            ),
            array(
                array('house', 'mouse'),
                'SELECT house, mouse FROM test'
            )
        );
    }

    public function providerWhereParams() {
        return array(
            array(
                array(
                    'test' => 'best',
                    'west' => null,
                    'quest' => 5
                ),
                array(),
                "SELECT * FROM test WHERE test = 'best' AND west is null ".
                "AND quest = 5"
            ),
            array(
                array(),
                array(
                    'nest' => 5,
                    'chest' => null,
                    'guest' => 'pest'
                ),
                "SELECT * FROM test WHERE nest <> 5 AND chest is not null ".
                "AND guest <> 'pest'"
            ),
            array(
                array(
                    'rest' => 1
                ),
                array(
                    'vest' => 5
                ),
                "SELECT * FROM test WHERE rest = 1 AND vest <> 5"
            ),
            array(
                array(),
                array(),
                "SELECT * FROM test"
            )
        );
    }

    protected function setup() {
        // 7 Args via http://stackoverflow.com/questions/8040296.
        $this->mockDataTypeClass = $this->getMockForAbstractClass(
            'DataType',
            array(),
            '',
            TRUE,
            TRUE,
            TRUE,
            array('getNotParams', 'setColumns')
        );
        $this->mockDataTypeClass->method('getTable')->willReturn('test');

        // Create ReflectionMethod to test private function.
        $this->getSQLMethod = new ReflectionMethod('DataType', 'getSQL');
        $this->getSQLMethod->setAccessible(true);
    }

    /**
     * @dataProvider providerWhereParams
     */
    public function testFormatForWhere($params, $not_params, $expected) {
        $dt = $this->mockDataTypeClass;
        $dt->method('getParams')->willReturn($params);
        $dt->method('getNotParams')->willReturn($not_params);
        $this->assertEquals($expected, $this->getSQLMethod->invoke($dt));
    }

    /**
     * @dataProvider providerSetColumns
     */
    public function testSetColumns($columns, $expected) {
        $dt = $this->mockDataTypeClass;
        $dt->setColumns($columns);
        $this->assertEquals($expected, $this->getSQLMethod->invoke($dt));
    }
}
