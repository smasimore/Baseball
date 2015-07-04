<?php

// WILL DELETE THIS FILE AFTER ENTIRE CLASS IS WRITTEN.

include_once 'MySQL.php';

class TestMySQL {

    public function gen() {
        MySQL::insert(
            'test_mysql',
            array(
                array(
                    'id' => 12345,
                    'name' => 'Dan'
                )
            )
        );
    }

}

$la = (new TestMySQL)->gen();

?>
