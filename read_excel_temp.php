<?php
// Check full row content for DES vs JAN
ini_set('memory_limit', '1024M');
require_once __DIR__ . '/application/third_party/SimpleXLS.php';

$file_path = __DIR__ . '/TABUNGAN 2026.xls';
$xls = \Shuchkin\SimpleXLS::parse($file_path);

echo "=== Comparing JAN row 4 vs DES row 3 ===\n\n";

// JAN sheet (index 0)
$rowsJan = $xls->rows(0);
echo "JAN Row 4 (full):\n";
$row = $rowsJan[3];
for ($j = 0; $j < min(10, count($row)); $j++) {
    $v = trim((string) $row[$j]);
    if ($v !== '')
        echo "  [$j] = '$v'\n";
}

echo "\n";

// DES sheet (index 11)
$rowsDes = $xls->rows(11);
echo "DES Row 3 (full):\n";
$row = $rowsDes[2];
for ($j = 0; $j < min(20, count($row)); $j++) {
    $v = trim((string) $row[$j]);
    if ($v !== '')
        echo "  [$j] = '" . substr($v, 0, 50) . "'\n";
}

echo "\n=== Check DES row with data (row 5-10) ===\n";
for ($i = 4; $i < 10; $i++) {
    $row = $rowsDes[$i];
    echo "DES Row $i: ";
    for ($j = 0; $j < 5; $j++) {
        $v = trim((string) $row[$j] ?? '');
        echo "[$j]='" . substr($v, 0, 20) . "' ";
    }
    echo "\n";
}

echo "\n=== Strategy: Use JAN sheet (index 0) for import ===\n";
echo "JAN has proper data structure with NAMA in column 1.\n";
