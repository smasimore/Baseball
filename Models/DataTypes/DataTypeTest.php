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
                    SQLWhereParams::GREATER_OR_EQUAL => array('nest' => 5),
                    SQLWhereParams::LESS_OR_EQUAL => array('quest' => 4)
                ),
                "SELECT * FROM test WHERE nest >= 5 AND quest <= 4"
            ),
            array(
                array(
                    SQLWhereParams::EQUAL => array('rest' => 1),
                    SQLWhereParams::NOT_EQUAL =>array('vest' => 5),
                    SQLWhereParams::GREATER_OR_EQUAL =>array('best' => 4),
                    SQLWhereParams::LESS_OR_EQUAL =>array('quest' => 2)
                ),
                "SELECT * FROM test WHERE rest = 1 AND vest <> 5 ".
                "AND best >= 4 AND quest <= 2"
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
                    SQLWhereParams::GREATER_OR_EQUAL => array('best' => null)
                )
            ),
            array(
                array(
                    SQLWhereParams::LESS_OR_EQUAL => array('nest' => false)
                )
            )
        );
    }

    public function providerFilterException() {
        return array(
            array(
                array('dan' => array('name' => 'dan', 'color' => 'blue')),
                null
            )
        );
    }

    public function providerFilter() {
        return array(
            array(
                array(
                    'dan' => array('name' => 'dan', 'color' => 'blue'),
                    'sarah' => array('name' => 'sarah', 'color' => 'red'),
                    'test' => array('name' => 'dan', 'color' => 'orange')
                ),
                true,
                array('name' => 'dan'),
                array(
                    'dan' => array('name' => 'dan', 'color' => 'blue'),
                    'test' => array('name' => 'dan', 'color' => 'orange')
                )
            ),
            array(
                array(
                    array('name' => 'dan', 'color' => null, 'num' => 1),
                    array('name' => 'dan', 'color' => 'blue'),
                    array('name' => 'dan', 'color' => null, 'num' => 2)
                ),
                false,
                array('color' => null),
                array(
                    array('name' => 'dan', 'color' => null, 'num' => 1),
                    array('name' => 'dan', 'color' => null, 'num' => 2)
                )
            ),
            array(
                array(array('name' => 'dan', 'color' => null)),
                false,
                array('color' => 'blue'),
                array()
            ),
            array(
                array(
                    array('name' => 'dan', 'color' => null),
                    array('name' => 'dan', 'color' => 'blue'),
                ),
                false,
                array('name' => 'dan', 'color' => null),
                array(array('name' => 'dan', 'color' => null)),
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
            array('setColumns', 'getData')
        );
        $this->mockDataTypeClass->method('getTable')->willReturn('test');

        // Create ReflectionMethod to test private function.
        $this->getSQLMethod = new ReflectionMethod('DataType', 'getSQL');
        $this->getSQLMethod->setAccessible(true);
    }

    /**
     * @dataProvider providerFilter
     */
    public function testFilterData($data, $keep_keys, $filter, $expected) {
        $dt = $this->mockDataTypeClass;
        $dt->method('getData')->willReturn($data);
        $filtered_data = $dt->getFilteredData($filter, $keep_keys);
        $this->assertEquals($expected, $filtered_data);
    }

    /**
     * @dataProvider providerFilterException
     * @expectedException Exception
     */
    public function testFilterDataException($data, $filter) {
        $dt = $this->mockDataTypeClass;
        $dt->method('getData')->willReturn($data);
        $dt->getFilteredData($filter);
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
