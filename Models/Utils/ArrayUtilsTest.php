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

        // array, key to sort by, result
        return array(
            array(
                $array,
                'one',
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
                array(
                    array(
                        'one' => 3,
                        'two' => 1,
                        'three' => 2
                    ),
                    array(
                        'one' => 1,
                        'two' => 2,
                        'three' => 3,
                    ),
                    array (
                        'one' => 2,
                        'two' => 3,
                        'three' => 1,
                    )
                )
            ),
            array(
                $array,
                'three',
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
     * @dataProvider providerSortAssociativeArray
     */
    public function testSortAssociativeArray($array, $key, $return) {
        $this->assertEquals(
            ArrayUtils::sortAssociativeArray($array, $key, false),
            $return
        );
    }
}

?>
