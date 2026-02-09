<?php
/**
 * Verify FEB sheet column structure
 */
require_once 'application/third_party/SimpleXLS.php';

$file_path = 'TABUNGAN 2026.xls';
$xls = \Shuchkin\SimpleXLS::parse($file_path);
if (!$xls) {
    die("Error: " . \Shuchkin\SimpleXLS::parseError());
}

// Get FEB sheet (index 1)
$sheets = $xls->sheetNames();
echo "Sheets: " . implode(", ", $sheets) . "\n\n";

$feb_rows = $xls->rows(1);  // FEB is sheet index 1

echo "=== FEB Sheet Header Structure ===\n\n";

// Row 1 (index 0) - typically empty or title
echo "Row 1: ";
for ($c = 0; $c < 10; $c++) {
    echo "[$c]=" . ($feb_rows[0][$c] ?? '') . " | ";
}
echo "\n\n";

// Row 2 (index 1) - headers
echo "Row 2 (Headers):\n";
for ($c = 0; $c < 40; $c++) {
    $val = trim($feb_rows[1][$c] ?? '');
    if (!empty($val)) {
        echo "  [$c] = '$val'\n";
    }
}
echo "\n";

// Row 3 (index 2) - sub-headers with day numbers
echo "Row 3 (Day numbers):\n";
for ($c = 0; $c < 50; $c++) {
    $val = trim($feb_rows[2][$c] ?? '');
    if (!empty($val)) {
        echo "  [$c] = '$val'\n";
    }
}
echo "\n";

// Row 4 (index 3) - first data row
echo "=== First Data Row (Row 4) ===\n";
$data_row = $feb_rows[3];
echo "Total columns: " . count($data_row) . "\n\n";

// Print first 45 columns
for ($c = 0; $c < min(45, count($data_row)); $c++) {
    $val = $data_row[$c] ?? '';
    if ($val !== '' && $val !== null) {
        echo "  [$c] = $val";
        // Add label based on expected FEB structure
        if ($c == 0) echo " (NO)";
        if ($c == 1) echo " (NAMA?)";
        if ($c == 4) echo " (NO_TAB?)";
        if ($c == 5) echo " (ALAMAT?)";
        if ($c == 6) echo " (SALDO?)";
        if ($c >= 7 && $c <= 37) echo " (SETORAN Day " . ($c - 6) . "?)";
        if ($c >= 38 && $c <= 68) echo " (PENARIKAN Day " . ($c - 37) . "?)";
        echo "\n";
    }
}

echo "\n=== Looking for SETORAN values in columns 7-37 ===\n";
$found_setoran = false;
for ($row = 3; $row < min(20, count($feb_rows)); $row++) {
    for ($c = 7; $c <= 37; $c++) {
        $val = floatval($feb_rows[$row][$c] ?? 0);
        if ($val > 0) {
            echo "Row " . ($row + 1) . ", Col $c: " . number_format($val) . " (Day " . ($c - 6) . ")\n";
            $found_setoran = true;
        }
    }
}
if (!$found_setoran) {
    echo "No setoran values found in columns 7-37 for rows 4-20\n";
}

echo "\n=== Summary ===\n";
echo "Expected FEB structure:\n";
echo "  no_tab = 4\n";
echo "  alamat = 5\n";
echo "  saldo_awal = 6\n";
echo "  setoran_start = 7 (day 1)\n";
echo "  setoran_end = 37 (day 31)\n";
echo "  penarikan_start = 38\n";
echo "  penarikan_end = 68\n";
