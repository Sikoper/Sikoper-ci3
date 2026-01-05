<?php
require_once 'application/third_party/SimpleXLS.php';

$xls = \Shuchkin\SimpleXLS::parse('TABUNGAN 2026.xls');

$output = "";
$sheets = $xls->sheetNames();
$des_index = array_search('DES', $sheets);
if ($des_index === false)
    $des_index = 11;

$rows = $xls->rows($des_index);

// Show ALL columns of first 3 data rows to find where NAMA is
$output .= "=== COMPLETE DATA FOR ROW 3-5 (All columns with data) ===\n";
for ($i = 3; $i < min(6, count($rows)); $i++) {
    $row = $rows[$i];
    $output .= "\n--- ROW $i ---\n";
    for ($c = 0; $c < count($row); $c++) {
        $val = trim($row[$c] ?? '');
        if (!empty($val)) {
            $output .= "  Col[$c]: $val\n";
        }
    }
}

// Also check if REKAP BUNGA sheet has names
$output .= "\n\n=== CHECKING REKAP BUNGA SHEET (index 12) ===\n";
$rekap_rows = $xls->rows(12);
$output .= "Total rows: " . count($rekap_rows) . "\n";

// Show first rows of REKAP BUNGA
for ($i = 0; $i < min(10, count($rekap_rows)); $i++) {
    $row = $rekap_rows[$i];
    $output .= "\n--- ROW $i ---\n";
    for ($c = 0; $c < min(10, count($row)); $c++) {
        $val = trim($row[$c] ?? '');
        if (!empty($val)) {
            $output .= "  Col[$c]: $val\n";
        }
    }
}

file_put_contents('debug_output.txt', $output);
echo "Done\n";
