<?php
// Copyright 2013-Present, Saber Tooth Ventures, LLC

include_once 'Page.php';
include_once __DIR__ .'/../../Models/Traits/TPageWithDate.php';
include_once __DIR__ .'/../../Models/DataTypes/ErrorsDataType.php';

class ErrorLogPage extends Page {

    use TPageWithDate;

    private $errorsData;

    final protected function gen() {
        $this->errorsData = (new ErrorsDataType())
            ->gen()
            ->getData();
    }

    final protected function getHeaderParams() {
        return array(
            $this->date,
            null
        );
    }

    final protected function renderPage() {
        $errors = $this->getAggregateErrors();
        (new DataTable())
            ->setData($errors)
            ->setID('aggregate_errors')
            ->render();
    }

    private function getAggregateErrors() {
        $errors = array();
        foreach ($this->errorsData as $error) {
            $error_name = $error['error'];
            $ds = $error['ds'];
            if (idx($errors, $error_name) === null) {
                $errors[$error_name] = array(
                    'date_of_last_error' => $ds,
                    'name' => $error_name,
                    'count' => 1
                );
            } else {
                $errors[$error_name]['count'] += 1;
                $errors[$error_name]['date_of_last_error'] = 
                    $ds > $errors[$error_name]['date_of_last_error']
                    ? $ds
                    : $errors[$error_name]['date_of_last_error'];
            }
        }
        return ArrayUtils::sortAssociativeArray(
            $errors, 
            'date_of_last_error', 
            SortConstants::DESC
        );
    }   
}
?>
