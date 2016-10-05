<?php

$data = array_map('str_getcsv', file('export.csv'));


$resultData = [];
$cols = [];
array_shift($data);
foreach ($data as $row) {
    $cols[$row[1]] = $row[1];
    if (!isset($resultData[$row[0]])) {
        $resultData[$row[0]] = [];
    }
    $resultData[$row[0]][$row[1]] = $row[2];
}

$csvResult = implode(',', ['week'] + $cols) . PHP_EOL;

foreach ($resultData as $week => $row) {
    $rowResult = [$week];
    foreach ($cols as $col) {
        if (isset($row[$col])) {
            $rowResult[] = $row[$col];
        } else {
            $rowResult[] = 0;
        }
    }
    $csvResult .= implode(',', $rowResult) . PHP_EOL;
}

//var_dump($cols);
//var_dump($csvResult);
file_put_contents('export_formated.csv', $csvResult);
