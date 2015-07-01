<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

trait TScriptWithInsert {

    // Require extends isn't a thing outside of hack but adding it as a note.
    // require extends ScriptWithWrite;

    protected function write() {
        multi_insert(
            DATABASE,
            $this->getWriteTable(),
            $this->getWriteData()
        );
    }
}
