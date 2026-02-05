<?php
ini_set('memory_limit', '2048M');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'application/third_party/SimpleXLS.php';

$file = 'TABUNGAN 2026.xls';
if (!file_exists($file))
    die("File not found\n");
$xls = \Shuchkin\SimpleXLS::parse($file);
if (!$xls)
    die("Parse error\n");

$sheets = $xls->sheetNames();
$targets = ['JAN', 'FEB'];

foreach ($targets as $month) {
    echo "\n=== SHEET: $month ===\n";
    $idx = array_search($month, $sheets);
    if ($idx === false) {
        echo "Sheet not found.\n";
        continue;
    }

    $rows = $xls->rows($idx);
    $found = false;
    foreach ($rows as $row) {
        // Col 2 is NO TAB
        if (isset($row[2]) && $row[2] == 482) {
            echo "Found Row T482:\n";
            // Print key columns
            $cols_to_check = [
                4 => 'Saldo Awal',
                101 => 'Bunga',
                102 => 'Saldo Akhir (Col 102)'
            ];
            foreach ($cols_to_check as $k => $label) {
                echo "[$k] $label: " . ($row[$k] ?? 'NULL') . "\n";
            }
            $found = true;
            break;
        }
    }
    if (!$found)
        echo "Row T482 not found in $month.\n";
}
