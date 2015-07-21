<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'ArrayUtils.php';

class ArrayUtilsTest extends PHPUnit_Framework_TestCase {

    public function providerRemoveColumns() {
        $array = array(
            array(
                'one' => 1,
                'two' => 2,
                'three' => 3
            ),
            array(
                'one' => 1,
                'two' => 2,
                'three' => 3
            )
        );

        // Array, cols, return
        return array(
            array($array, array(), $array),
            array($array, array('one', 'two', 'three'), array()),
            array(
                $array,
                array('one', 'three'),
                array(
                    array('two' => 2),
                    array('two' => 2)
                ),
            ),
            array(
                $array,
                array('two'),
                array(
                    array(
                        'one' => 1,
                        'three' => 3
                    ),
                    array(
                        'one' => 1,
                        'three' => 3
                    )
                )
            )
        );
    }

    public function providerIsArrayOfArrays() {
        return array(
            array(
                'test',
                false
            ),
            array(
                array('la' => 1),
                false
            ),
            array(
                array(
                    array('la' => 2)
                ),
                true
            )
        );
    }

    public function providerSortAssociativeArray() {
        $array = array(
            array(
                'one' => 1,
                'two' => 2,
                'three' => 3,
            ),
            array(
                'one' => 3,
                'two' => 1,
                'three' => 2
            ),
            array (
                'one' => 2,
                'two' => 3,
                'three' => 1,
            )
        );

        // array, key to sort by, order (ASC vs. DESC), keep keys, result
        return array(
            array(
                $array,
                'one',
                SortConstants::ASC,
                false,
                array(
                    array(
                        'one' => 1,
                        'two' => 2,
                        'three' => 3,
                    ),
                    array (
                        'one' => 2,
                        'two' => 3,
                        'three' => 1,
                    ),
                    array(
                        'one' => 3,
                        'two' => 1,
                        'three' => 2
                    )
                )
            ),
            array(
                $array,
                'two',
                SortConstants::DESC,
                false,
                array(
                    array(
                        'one' => 2,
                        'two' => 3,
                        'three' => 1,
                    ),
                    array(
                        'one' => 1,
                        'two' => 2,
                        'three' => 3,
                    ),
                    array(
                        'one' => 3,
                        'two' => 1,
                        'three' => 2
                    )
                )
            ),
            array(
                $array,
                'three',
                SortConstants::ASC,
                false,
                array(
                    array(
                        'one' => 2,
                        'two' => 3,
                        'three' => 1,
                    ),
                    array(
                        'one' => 3,
                        'two' => 1,
                        'three' => 2
                    ),
                    array(
                        'one' => 1,
                        'two' => 2,
                        'three' => 3,
                    )
                )
            )
        );
    }

    /**
     * @dataProvider providerRemoveColumns
     */
    public function testRemoveColumns($array, $cols, $return) {
        $this->assertEquals(
            ArrayUtils::removeColumns($array, $cols),
            $return
        );
    }

    /**
     * @dataProvider providerIsArrayOfArrays
     */
    public function testIsArrayOfArrays($array, $return) {
        $this->assertEquals(
            $return,
            ArrayUtils::IsArrayOfArrays($array)
        );
    }

    /**
     * @expectedException Exception
     */
    public function testRemoveColumnsException() {
        ArrayUtils::removeColumns(array('test'), 'test');
    }

    /**
     * @expectedException Exception
     */
    public function testRemoveColumnsException2() {
        ArrayUtils::removeColumns(array('test'), array());
    }

    /**
     * Test exception for non-SortConstants (i.e. 99) in the order param.
     * @expectedException Exception
     */
    public function testSortAssocArrayException() {
        ArrayUtils::sortAssociativeArray(
            array('test' => 1),
            'test',
            99,
            true
        );
    }

    /**
     * @dataProvider providerSortAssociativeArray
     */
    public function testSortAssociativeArray($array, $key, $order, $keys, $return) {
        $this->assertEquals(
            ArrayUtils::sortAssociativeArray($array, $key, $order, $keys),
            $return
        );
    }

    /**
     * @expectedException Exception
     */
    public function testFlattenArrayOfArraysException() {
        ArrayUtils::flatten(array('test'));
    }

    public function providerFlatten() {
        return array(
            array(
                array(
                    'test0' => array('one', 'two'),
                    'test1' => array('three', 'four')
                ),
                array('one', 'two', 'three', 'four')
            )
        );
    }

    /**
     * @dataProvider providerFlatten
     */
    public function testFlatten($array, $flat_array) {
        $this->assertEquals(
            ArrayUtils::flatten($array),
            $flat_array
        );
    }


}

?>
