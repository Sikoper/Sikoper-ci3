<?php
/**
 * Debug script to check HUTANG BUNGA sheet structure
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
$sheet_hutang = array_search('HUTANG BUNGA', $sheets);

if ($sheet_hutang === false) {
    die("Sheet not found");
}

$rows = $xls->rows($sheet_hutang);

// Find NI MADE JUNIATI (NIN 13) and DRS. I KETUT MARTA ARIANA (NIN 25)
echo "=== DEPOSITORS IN HUTANG BUNGA SHEET ===\n\n";

for ($i = 3; $i < count($rows); $i++) {
    $row = $rows[$i];
    $nin = intval($row[0] ?? 0);
    $nama = trim($row[1] ?? '');

    if ($nin == 13 || $nin == 25 || $nin == 27) {
        echo "NIN $nin: $nama\n";
        echo "  Col 3 (D): " . ($row[3] ?? 'empty') . "\n";
        echo "  Col 4 (E): " . ($row[4] ?? 'empty') . "\n";
        echo "  Col 5 (F): " . ($row[5] ?? 'empty') . "\n";
        echo "  Col 14 (O): " . ($row[14] ?? 'empty') . "\n";
        echo "  Col 15 (P): " . ($row[15] ?? 'empty') . "\n";
        echo "  Col 16 (Q): " . ($row[16] ?? 'empty') . "\n";

        // Show all non-empty columns
        echo "  All non-empty columns:\n";
        foreach ($row as $idx => $val) {
            if ($val !== '' && $val !== null && $val !== 0) {
                echo "    [$idx] = $val\n";
            }
        }
        echo "\n";
    }
}

// Show header row
echo "=== HEADER ROW ===\n";
$headerRow = $rows[1] ?? [];
foreach ($headerRow as $idx => $val) {
    if ($val !== '' && $val !== null) {
        echo "[$idx] = $val\n";
    }
}
