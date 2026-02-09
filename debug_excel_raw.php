<?php
// Debug the preview data structure
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '1024M');

require_once __DIR__ . '/application/third_party/SimpleXLS.php';

$file_path = __DIR__ . '/TABUNGAN 2026.xls';
$xls = \Shuchkin\SimpleXLS::parse($file_path);
if (!$xls) die('Error: ' . \Shuchkin\SimpleXLS::parseError());

$sheets = $xls->sheetNames();
$feb_index = array_search('FEB', $sheets);
$rows = $xls->rows($feb_index);

// Simulate what preview_sheet_data returns
$row = $rows[3]; // First data row

$output = "=== PHP Row Data (FEB Row 4) ===\n\n";
$output .= "Total cells in row from SimpleXLS: " . count($row) . "\n\n";

$output .= "Cells 0-15:\n";
for ($i = 0; $i <= 15; $i++) {
    $val = isset($row[$i]) ? $row[$i] : 'NOT_SET';
    $output .= "  [$i]: '$val'\n";
}

$output .= "\nCells around setoran range (cols 5-12):\n";
for ($i = 5; $i <= 12; $i++) {
    $val = isset($row[$i]) ? $row[$i] : 'NOT_SET';
    $isEmpty = empty($val) ? 'EMPTY' : 'HAS_VALUE';
    $output .= "  [$i]: '$val' ($isEmpty)\n";
}

$output .= "\nCells around columns 37-42 (penarikan area):\n";
for ($i = 37; $i <= 42; $i++) {
    $val = isset($row[$i]) ? $row[$i] : 'NOT_SET';
    $isEmpty = empty($val) ? 'EMPTY' : 'HAS_VALUE';
    $output .= "  [$i]: '$val' ($isEmpty)\n";
}

$output .= "\n\n=== What preview_sheet_data would return ===\n\n";
$preview_data = [];
foreach ($row as $idx => $cell) {
    $preview_data[] = [
        'col_index' => $idx,
        'value' => $cell
    ];
}
$output .= "Total cells in preview_data: " . count($preview_data) . "\n\n";
$output .= "First 15 preview cells:\n";
for ($i = 0; $i < 15 && $i < count($preview_data); $i++) {
    $cell = $preview_data[$i];
    $output .= "  [{$cell['col_index']}]: '{$cell['value']}'\n";
}

file_put_contents(__DIR__ . '/debug_output.txt', $output);
echo "Output written to debug_output.txt\n";
