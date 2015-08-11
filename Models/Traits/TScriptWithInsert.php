<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once __DIR__ .'/../Utils/MySQL.php';

trait TScriptWithInsert {

    // Require extends isn't a thing outside of hack but adding it as a note.
    // require extends ScriptWithWrite;

    protected function write() {
        MySQL::insert(
            $this->getWriteTable(),
            $this->getWriteData()
        );
    }
}
