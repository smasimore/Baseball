<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'GlobalUtils.php';

class GlobalUtilsTest extends PHPUnit_Framework_TestCase {

    public function providerIdxTest() {
        $array = array(
            'test' => 5,
            'nest' => 'quest'
        );
        return array(
            array(
                $array,
                'test',
                null,
                5
            ),
            array(
                $array,
                'nonindex',
                null,
                null
            ),
            array(
                $array,
                'nonindex',
                99,
                99
            )
        );
    }

    public function providerIndexByTest() {
        $array = array(
            'test' => 5,
            'name' => 'dan',
            'color' => 'green'
        );
        return array(
            array(
                array(
                    $array
                ),
                'name',
                array(
                    'dan' => array(
                        'test' => 5,
                        'name' => 'dan',
                        'color' => 'green'
                    )
                )
            ),
            array(
                array(
                    $array
                ),
                array('name', 'color'),
                array(
                    'dangreen' => array(
                        'test' => 5,
                        'name' => 'dan',
                        'color' => 'green'
                    )
                )
            )
        );
    }

    public function providerIndexByException() {
        return array(
            // Test indexing empty array.
            array(
                array(),
                'test'
            ),
            // Test indexing non array of arrays.
            array(
                array('test' => 1),
                'test'
            )
        );
    }

    /**
     * @dataProvider providerIdxTest
     */
    public function testIdx($array, $key, $default, $expected) {
        $this->assertEquals($expected, idx($array, $key, $default));
    }

    /**
     * @dataProvider providerIndexByTest
     */
    public function testIndexBy($data, $index_arr, $expected) {
        $this->assertEquals($expected, index_by($data, $index_arr));
    }

    /**
     * @dataProvider providerIndexByException
     * @expectedException Exception
     */
    public function testIndexByException($data, $index_arr) {
        index_by($data, $index_arr);
    }
}

?>
