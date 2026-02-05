<?php
/**
 * Comprehensive Excel structure analysis for TABUNGAN 2026.xls
 */
require_once 'application/third_party/SimpleXLS.php';

$file_path = 'TABUNGAN 2026.xls';
$output_file = 'debug_excel_structure.txt';

ob_start();

$xls = \Shuchkin\SimpleXLS::parse($file_path);
if (!$xls) {
    echo "Error: " . \Shuchkin\SimpleXLS::parseError();
    file_put_contents($output_file, ob_get_clean());
    die();
}

$sheets = $xls->sheetNames();
echo "=== ANALISIS STRUKTUR EXCEL TABUNGAN 2026 ===\n";
echo "Sheets: " . implode(", ", $sheets) . "\n\n";

// Analyze JAN sheet header structure
echo "=== HEADER ROW ANALYSIS (JAN SHEET) ===\n";
$jan_rows = $xls->rows(0);

// Look at header rows (usually row 1-3)
for ($r = 0; $r < 3; $r++) {
    echo "Row " . ($r + 1) . ":\n";
    $row = $jan_rows[$r];
    for ($c = 0; $c < min(count($row), 80); $c++) {
        $val = trim($row[$c] ?? '');
        if (!empty($val)) {
            echo "  [$c] = '$val'\n";
        }
    }
    echo "\n";
}

// Find a data row (row 4 = index 3) to understand columns
echo "=== DATA ROW SAMPLE (Row 4, First Nasabah) ===\n";
$sample_row = $jan_rows[3];
echo "Total columns in row: " . count($sample_row) . "\n\n";

// Print all columns with values
for ($c = 0; $c < count($sample_row); $c++) {
    $val = $sample_row[$c] ?? '';
    if ($val !== '' && $val !== null) {
        echo "  [$c] = " . substr(str_replace("\n", " ", $val), 0, 50) . "\n";
    }
}

// Now specifically look at NIN 482 structure
echo "\n=== NIN 482 COLUMN ANALYSIS (JAN) ===\n";
for ($i = 3; $i < count($jan_rows); $i++) {
    $row = $jan_rows[$i];
    $no_tab = intval($row[2] ?? 0);

    if ($no_tab == 482) {
        echo "Found at row " . ($i + 1) . "\n";
        echo "Total columns: " . count($row) . "\n\n";

        // Print first 10 columns (metadata)
        echo "METADATA COLUMNS (0-4):\n";
        for ($c = 0; $c <= 4; $c++) {
            echo "  [$c] = '" . ($row[$c] ?? '') . "'\n";
        }

        // Analyze column ranges
        echo "\nSETORAN COLUMNS (expected 5-35 for days 1-31):\n";
        $setoran_cols = [];
        for ($c = 5; $c <= 35; $c++) {
            $val = floatval($row[$c] ?? 0);
            if ($val > 0) {
                $setoran_cols[] = "[$c]=Rp" . number_format($val, 0, ',', '.');
            }
        }
        echo "  " . implode(", ", $setoran_cols) . "\n";

        echo "\nPENARIKAN COLUMNS (expected 36-66 for days 1-31):\n";
        $penarikan_cols = [];
        for ($c = 36; $c <= 66; $c++) {
            $val = floatval($row[$c] ?? 0);
            if ($val > 0) {
                $penarikan_cols[] = "[$c]=Rp" . number_format($val, 0, ',', '.');
            }
        }
        echo "  " . implode(", ", $penarikan_cols) . "\n";

        echo "\nCOLUMNS 67-80 (what's here?):\n";
        for ($c = 67; $c <= min(80, count($row) - 1); $c++) {
            $val = $row[$c] ?? '';
            if ($val !== '' && $val !== null && $val != 0) {
                echo "  [$c] = $val\n";
            }
        }

        echo "\nLAST 20 COLUMNS:\n";
        $start = max(0, count($row) - 20);
        for ($c = $start; $c < count($row); $c++) {
            $val = $row[$c] ?? '';
            if ($val !== '' && $val !== null && $val != 0) {
                echo "  [$c] = $val\n";
            }
        }

        break;
    }
}

// Check header row 2 or 3 for column labels
echo "\n=== HEADER INTERPRETATION ===\n";
$header_row = $jan_rows[1]; // Row 2 (index 1) usually has day numbers
echo "Row 2 sample (looking for day numbers):\n";
for ($c = 0; $c < min(count($header_row), 80); $c++) {
    $val = trim($header_row[$c] ?? '');
    if (!empty($val) && (is_numeric($val) || preg_match('/^(SETORAN|PENARIKAN|SALDO|TOTAL|JUMLAH)/i', $val))) {
        echo "  [$c] = '$val'\n";
    }
}

file_put_contents($output_file, ob_get_clean());
echo "Output written to: $output_file\n";
