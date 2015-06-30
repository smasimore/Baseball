<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ . '/Enum.php';

class GameStatus extends Enum {

    const NOT_STARTED = 0;
    const STARTED = 1;
    const FINISHED = 2;
}

?>
