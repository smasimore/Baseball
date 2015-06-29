<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once(HOME_PATH.'Scripts/Include/sweetfunctions.php');
include_once(HOME_PATH.'Models/Constants/Tables.php');

class ExceptionUtils {

    public static function logDisplayEmailException($e, $email_filter = null) {
        self::display($e);
        self::logSQL($e);
        self::email($e, $email_filter);
    }

    private static function email($e, $email_filter) {
        // Only send email if one hasn't been sent on that day for that error.
        $sql = sprintf(
            "SELECT *
            FROM %s
            WHERE ds = '%s'
            AND error = '%s'
            AND trace = '%s'",
            Tables::ERRORS,
            date('Y-m-d'),
            $e->getMessage(),
            str_replace("'", '"', $e->getTraceAsString())
        );
        $data = exe_sql(DATABASE, $sql);
        if (!$data) {
            send_email(
                $e->getMessage(),
                $e->getTraceAsString(),
                $email_filter
            );
        }
    }

    private static function logSQL($e) {
        $data = array(
            array(
                'ds' => date('Y-m-d'),
                'error' => $e->getMessage(),
                'trace' => str_replace("'", '"', $e->getTraceAsString())
            )
        );
        multi_insert(
            DATABASE,
            Tables::ERRORS,
            $data,
            array('ds', 'error', 'trace')
        );
    }

    private static function display($e) {
        echo $e->getMessage();
        echo $e->getTraceAsString();
    }
}

?>
