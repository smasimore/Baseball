<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

/**
 * Basically this is ComprehensiveLintEngine with some Text Tweaks
 */
final class BaseballLintEngine extends ArcanistLintEngine {

  public function buildLinters() {
    $linters = array();

    $paths = $this->getPaths();

    foreach ($paths as $key => $path) {
      if (preg_match('@^externals/@', $path)) {
        // Third-party stuff lives in /externals/; don't run lint engines
        // against it.
        unset($paths[$key]);
      }
    }

    $text_paths = preg_grep('/\.(php|css|hpp|cpp|l|y|py|pl)$/', $paths);
    $text_linter = new ArcanistTextLinter();
    $linters[] = id(new ArcanistGeneratedLinter())->setPaths($text_paths);
    $linters[] = id(new ArcanistNoLintLinter())->setPaths($text_paths);
    $linters[] = id(
        $text_linter->setCustomSeverityMap(
            array(
                ArcanistTextLinter::LINT_TAB_LITERAL => 
                ArcanistLintSeverity::SEVERITY_DISABLED,
                ArcanistTextLinter::LINT_DOS_NEWLINE =>
                ArcanistLintSeverity::SEVERITY_DISABLED,
                ArcanistTextLinter::LINT_BAD_CHARSET =>
                ArcanistLintSeverity::SEVERITY_DISABLED
            )
        ))->setPaths($text_paths);

    $linters[] = id(new ArcanistFilenameLinter())->setPaths($paths);

/*

    $linters[] = id(new ArcanistXHPASTLinter())
      ->setPaths(preg_grep('/\.php$/', $paths));

    $py_paths = preg_grep('/\.py$/', $paths);
    $linters[] = id(new ArcanistPyFlakesLinter())->setPaths($py_paths);
    $linters[] = id(new ArcanistPEP8Linter())
      ->setFlags($this->getPEP8WithTextOptions())
      ->setPaths($py_paths);

    $linters[] = id(new ArcanistRubyLinter())
      ->setPaths(preg_grep('/\.rb$/', $paths));

    $linters[] = id(new ArcanistJSHintLinter())
      ->setPaths(preg_grep('/\.js$/', $paths));
*/

    return $linters;
  }

  protected function getPEP8WithTextOptions() {
    // E101 is subset of TXT2 (Tab Literal).
    // E501 is same as TXT3 (Line Too Long).
    // W291 is same as TXT6 (Trailing Whitespace).
    // W292 is same as TXT4 (File Does Not End in Newline).
    // W293 is same as TXT6 (Trailing Whitespace).
    return array('--ignore=E101,E501,W291,W292,W293');
  }

}
