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
            // Test index empty array.
            array(
                array(),
                'name',
                true,
                false,
                array()
            ),
            // Test index by string.
            array(
                array(
                    $array
                ),
                'name',
                true,
                false,
                array(
                    'dan' => array(
                        'test' => 5,
                        'name' => 'dan',
                        'color' => 'green'
                    )
                )
            ),
            // Test index by array of strings.
            array(
                array(
                    $array
                ),
                array('name', 'color'),
                true,
                false,
                array(
                    'dangreen' => $array
                )
            ),
            // Test index non-unique.
            array(
                array(
                    $array,
                    $array
                ),
                'name',
                false,
                true,
                array(
                    'dan' => array(
                        $array,
                        $array
                    )
                )
            ),
            // Test index non strict.
            array(
                array(
                    $array,
                    $array,
                ),
                'name',
                false,
                false,
                array(
                    'dan' => $array
                )
            )
        );
    }

    public function providerIndexByException() {
        return array(
            // Test indexing non array of arrays.
            array(
                array('test' => 1),
                'test'
            ),
            // Test index doesn't exist (triggers dupe index exception).
            array(
                array(
                    array('test' => 1),
                    array('test' => 2)
                ),
                'name'
            ),
            // Test trying to unique index a non-unique array.
            array(
                array(
                    array('test' => 1),
                    array('test' => 1)
                ),
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
    public function testIndexBy(
        $data,
        $index_arr,
        $strict,
        $non_unique,
        $expected
    ) {
        $this->assertEquals(
            $expected,
            index_by($data, $index_arr, $strict, $non_unique)
        );
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
