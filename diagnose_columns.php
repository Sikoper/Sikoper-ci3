<?php
ini_set('memory_limit', '1024M');
require_once 'application/third_party/SimpleXLS.php';
$xls = \Shuchkin\SimpleXLS::parse('TABUNGAN 2026.xls');
$sheets = $xls->sheetNames();
$out = "Sheets: " . implode(', ', $sheets) . "\n\n";

// JAN
$jan_rows = $xls->rows(0);
$out .= "=== JAN Sheet (" . count($jan_rows) . " rows) ===\n";
$out .= "\nJAN Row 2 headers (non-empty):\n";
for ($c = 0; $c < min(110, count($jan_rows[1])); $c++) {
    $v = trim($jan_rows[1][$c] ?? '');
    if ($v !== '') $out .= "  [$c] = $v\n";
}
$out .= "\nJAN Row 4 data (non-empty):\n";
for ($c = 0; $c < count($jan_rows[3]); $c++) {
    $v = $jan_rows[3][$c] ?? '';
    if ($v !== '') $out .= "  [$c] = $v\n";
}
$out .= "Total cols: " . count($jan_rows[3]) . "\n";

$out .= "\nJAN bunga area (cols 90-105, rows 4-8):\n";
for ($r = 3; $r < 8; $r++) {
    $name = $jan_rows[$r][1] ?? 'N/A';
    $out .= "  Row" . ($r+1) . " ($name): ";
    for ($c = 90; $c < min(105, count($jan_rows[$r])); $c++) {
        $v = $jan_rows[$r][$c] ?? '';
        if ($v !== '') $out .= "[$c]=$v ";
    }
    $out .= "\n";
}
unset($jan_rows);

// FEB
$feb_rows = $xls->rows(1);
$out .= "\n=== FEB Sheet (" . count($feb_rows) . " rows) ===\n";
$out .= "\nFEB Row 2 headers (non-empty):\n";
for ($c = 0; $c < min(110, count($feb_rows[1])); $c++) {
    $v = trim($feb_rows[1][$c] ?? '');
    if ($v !== '') $out .= "  [$c] = $v\n";
}
$out .= "\nFEB Row 4 data (non-empty):\n";
for ($c = 0; $c < count($feb_rows[3]); $c++) {
    $v = $feb_rows[3][$c] ?? '';
    if ($v !== '') $out .= "  [$c] = $v\n";
}
$out .= "Total cols: " . count($feb_rows[3]) . "\n";

$out .= "\nFEB bunga area (cols 60-105, rows 4-8):\n";
for ($r = 3; $r < 8; $r++) {
    $norek = $feb_rows[$r][4] ?? 'N/A';
    $out .= "  Row" . ($r+1) . " (col4=$norek): ";
    for ($c = 60; $c < min(105, count($feb_rows[$r])); $c++) {
        $v = $feb_rows[$r][$c] ?? '';
        if ($v !== '') $out .= "[$c]=$v ";
    }
    $out .= "\n";
}

file_put_contents('diagnose_output.txt', $out);
echo "Done - see diagnose_output.txt\n";
