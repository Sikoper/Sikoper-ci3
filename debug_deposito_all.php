<?php
/**
 * Debug script to check all data for specific depositors across all sheets
 */

require_once 'application/third_party/SimpleXLS.php';

$upload_dir = 'uploads/import/';
$files = glob($upload_dir . 'deposito_*.xls');

if (empty($files)) {
    die("No deposito Excel files found");
}

usort($files, function ($a, $b) {
    return filemtime($b) - filemtime($a);
});
$file_path = $files[0];

$xls = Shuchkin\SimpleXLS::parse($file_path);
if (!$xls) {
    die("Failed to parse Excel");
}

$sheets = $xls->sheetNames();
echo "File: $file_path\n";
echo "Sheets: " . implode(', ', $sheets) . "\n\n";

// Check DAFTAR DEPOSAN for NIN 13, 25
$sheet_deposan = array_search('DAFTAR DEPOSAN', $sheets);
if ($sheet_deposan !== false) {
    echo "=== DAFTAR DEPOSAN SHEET ===\n";
    $rows = $xls->rows($sheet_deposan);

    // Header
    echo "Header Row 1:\n";
    foreach ($rows[1] as $idx => $val) {
        if ($val !== '' && $val !== null) {
            echo "  [$idx] = $val\n";
        }
    }

    echo "\nData for NIN 13, 25:\n";
    for ($i = 3; $i < count($rows); $i++) {
        $row = $rows[$i];
        $nin = intval($row[0] ?? 0);

        if ($nin == 13 || $nin == 25) {
            echo "\nNIN $nin:\n";
            foreach ($row as $idx => $val) {
                if ($val !== '' && $val !== null) {
                    $valStr = is_numeric($val) && $val > 1000 ? number_format($val, 0, ',', '.') : $val;
                    echo "  [$idx] = $valStr\n";
                }
            }
        }
    }
}

// Check PRIN sheet
$sheet_prin = array_search('PRIN', $sheets);
if ($sheet_prin !== false) {
    echo "\n\n=== PRIN SHEET ===\n";
    $rows = $xls->rows($sheet_prin);

    echo "Header Row 0-2:\n";
    for ($h = 0; $h < min(3, count($rows)); $h++) {
        echo "Row $h: ";
        foreach ($rows[$h] as $idx => $val) {
            if ($val !== '' && $val !== null) {
                echo "[$idx]='$val' ";
            }
        }
        echo "\n";
    }
}
