<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'DataType.php';
include_once __DIR__ . '/../Constants/SQLWhereParams.php';

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
                    SQLWhereParams::EQUAL => array(
                        'test' => 'best',
                        'west' => null,
                        'quest' => 5
                    )
                ),
                "SELECT * FROM test WHERE test = 'best' AND west is null ".
                "AND quest = 5"
            ),
            array(
                array(
                    SQLWhereParams::NOT_EQUAL => array(
                        'nest' => 5,
                        'chest' => null,
                        'guest' => 'pest'
                    )
                ),
                "SELECT * FROM test WHERE nest <> 5 AND chest is not null ".
                "AND guest <> 'pest'"
            ),
            array(
                array(
                    SQLWhereParams::GREATER_THAN => array('nest' => 5),
                    SQLWhereParams::LESS_THAN => array('quest' => 4)
                ),
                "SELECT * FROM test WHERE nest > 5 AND quest < 4"
            ),
            array(
                array(
                    SQLWhereParams::EQUAL => array('rest' => 1),
                    SQLWhereParams::NOT_EQUAL =>array('vest' => 5),
                    SQLWhereParams::GREATER_THAN =>array('best' => 4),
                    SQLWhereParams::LESS_THAN =>array('quest' => 2)
                ),
                "SELECT * FROM test WHERE rest = 1 AND vest <> 5 ".
                "AND best > 4 AND quest < 2"
            ),
            array(
                array(),
                "SELECT * FROM test"
            ),
            array(
                array(
                    SQLWhereParams::EQUAL => array('rest' => null)
                ),
                "SELECT * FROM test WHERE rest is null"
            )
        );
    }

    public function providerNullGreaterLessThan() {
        return array(
            array(
                array(
                    SQLWhereParams::GREATER_THAN => array('best' => null)
                ),
                "Exception Should Throw Before This Hits"
            ),
            array(
                array(
                    SQLWhereParams::LESS_THAN => array('nest' => false)
                ),
                "Exception Should Throw Before This Hits"
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
            array('setColumns')
        );
        $this->mockDataTypeClass->method('getTable')->willReturn('test');

        // Create ReflectionMethod to test private function.
        $this->getSQLMethod = new ReflectionMethod('DataType', 'getSQL');
        $this->getSQLMethod->setAccessible(true);
    }

    /**
     * @dataProvider providerNullGreaterLessThan
     * @expectedException Exception
     */
    public function testGreaterThanNullException(
        $params
    ) {
        $dt = $this->mockDataTypeClass;
        $dt->method('getParams')->willReturn($params);
        $this->getSQLMethod->invoke($dt);
    }

    /**
     * @dataProvider providerWhereParams
     */
    public function testFormatForWhere(
        $params,
        $expected
    ) {
        $dt = $this->mockDataTypeClass;
        $dt->method('getParams')->willReturn($params);
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
