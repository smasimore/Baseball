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

    /**
     * @dataProvider providerRemoveColumns
     */
    public function testRemoveColumns($array, $cols, $return) {
        $this->assertEquals(
            $return,
            ArrayUtils::removeColumns($array, $cols)
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
}

?>
