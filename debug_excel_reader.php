<?php
define('APPPATH', __DIR__ . '/application/');
require_once APPPATH . 'third_party/SimpleXLS.php';

$file_path = __DIR__ . '/TABUNGAN 2026.xls';
$xls = Shuchkin\SimpleXLS::parse($file_path);
$sheets = $xls->sheetNames();

echo "=== ALL SHEETS ===\n";
foreach ($sheets as $idx => $name) {
    echo "[$idx] $name\n";
}

// REKAP BUNGA sheet (index 12 based on previous output)
echo "\n\n=== REKAP BUNGA SHEET DETAIL ===\n";
$rekap_idx = 12;
$rows = $xls->rows($rekap_idx);

echo "Sheet name: " . $sheets[$rekap_idx] . "\n";
echo "Total rows: " . count($rows) . "\n\n";

// Show header structure (rows 0-5)
echo "--- HEADER ROWS 0-5 ---\n";
for ($r = 0; $r <= 5; $r++) {
    echo "Row $r: ";
    $parts = [];
    foreach ($rows[$r] as $c => $v) {
        $v = trim((string) $v);
        if (!empty($v)) {
            $parts[] = "[$c]=$v";
        }
    }
    echo implode(" | ", $parts) . "\n";
}

// Show data rows (6-10)
echo "\n--- DATA ROWS 6-10 ---\n";
for ($r = 6; $r <= 10; $r++) {
    echo "Row $r:\n";
    foreach ($rows[$r] as $c => $v) {
        $v = trim((string) $v);
        if (!empty($v)) {
            echo "  [$c] => $v\n";
        }
    }
}

// Check how many columns there are
echo "\n--- COLUMN COUNT (row 3 header) ---\n";
$max_col = 0;
foreach ($rows[3] as $c => $v) {
    if (!empty(trim((string) $v))) {
        $max_col = $c;
        echo "[$c] => $v\n";
    }
}
