<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

ini_set('memory_limit', '-1');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// TODO(smas): Anything not UI-related should not be in here.
include_once __DIR__ . '/../includes/db_connect.php';
include_once __DIR__ . '/../includes/functions.php';
include_once __DIR__ . '/../includes/ui_elements.php';
include_once __DIR__ .'/../ui/PageHeader.php';
include_once __DIR__ .'/../ui/DataTable.php';
include_once __DIR__ .'/../ui/Slider.php';
include_once __DIR__ .'/../ui/UOList.php';
include_once __DIR__ .'/../ui/Selector.php';
include_once __DIR__ .'/../ui/Div.php';
include_once __DIR__ .'/../ui/Font.php';
include_once __DIR__ .'/../../Models/Constants/Colors.php';
include_once __DIR__ .'/../../Models/Utils/StringUtils.php';
include_once __DIR__ .'/../../Models/Utils/GlobalUtils.php';
include_once __DIR__ .'/../../Models/Utils/ArrayUtils.php';
include_once __DIR__ .'/../../Models/Utils/DateTimeUtils.php';
include_once __DIR__ .'/../../Models/Utils/ROIUtils.php';
include_once __DIR__ .'/../../Models/Utils/OddsUtils.php';

abstract class Page {

    protected $errors = array();
    protected $loggedIn;

    private $header = null;

    public function __construct($logged_in) {
        $this->loggedIn = $logged_in;

        if (!$this->loggedIn && $_SERVER['REQUEST_URI'] != '/' &&
            strpos($_SERVER['REQUEST_URI'], 'index') === false) {
            header('Location: /index.php', true);
            die();
        }
    }

    /**
     * Put html rendering here.
     */
    abstract protected function renderPage();

    /**
     * Fetch any data you need here.
     */
    protected function gen() { }

    /**
     * Return title and subtitle.
     */
    protected function getHeaderParams() {
        return array(null, null);
    }

    /* Override this if you want to display the page even if there are
     * errors. E.g. for LoginPage.
     */
    protected function renderPageIfErrors() {
        return false;
    }

    public function render() {
        try {
            $this->gen();
        } catch (Exception $e) {
            $this->errors[] = sprintf(
                'Message: %s Stack Trace: %s',
                $e->getMessage(),
                $e->getTraceAsString()
            );
        }

        list($title, $subtitle) = $this->getHeaderParams();
        $this->setHeader($title, $subtitle);

        $this->renderErrors();

        if ($this->errors && !$this->renderPageIfErrors()) {
            return;
        }

        $this->renderPage();

        return $this;
    }

    private function setHeader($header, $sub_header_arr = null) {
        $this->header = (new PageHeader())
            ->setLoggedIn($this->loggedIn)
            ->setTitle($header)
            ->setSubtitleArr($sub_header_arr);
        $this->renderHeader();
    }

    private function renderHeader() {
        if (!$this->header) {
            $this->header = (new PageHeader())->setLoggedIn($this->loggedIn);
        }
        $this->header->render();

        // Needed to prevent overlap of elements below header.
        echo "<div style='clear:both;'></div>";
    }

    private function renderErrors() {
        if (!$this->errors) {
            return;
        }

        $errors_list = (new UOList())
            ->setItems($this->errors)
            ->setItemClass('error_box');
        $errors_list->render();
    }
}

?>
