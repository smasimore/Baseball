<?php

include_once __DIR__ . '/HistoricalOddsDataType.php';
include_once __DIR__ . '/../Utils/ArrayUtils.php';

$data = (new HistoricalOddsDataType())
    ->setSeason(1999, 2014)
    ->gen()
    ->getData();

echo sprintf("Number of rows: %d \n", count($data));
echo "First row: ";
print_r(ArrayUtils::head($data));

?>
