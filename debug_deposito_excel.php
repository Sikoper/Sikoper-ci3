<?php
/**
 * Debug script to check Excel column structure for PEMBAYARAN BUNGA DEPOSITO
 */

// Output to file instead of browser
ob_start();

require_once 'application/third_party/SimpleXLS.php';

// Find the latest deposito upload file
$upload_dir = 'uploads/import/';
$files = glob($upload_dir . 'deposito_*.xls');

if (empty($files)) {
    die("No deposito Excel files found in uploads/import/");
}

// Get the most recent file
usort($files, function ($a, $b) {
    return filemtime($b) - filemtime($a);
});
$file_path = $files[0];

echo "<h2>Analyzing: $file_path</h2>";

$xls = Shuchkin\SimpleXLS::parse($file_path);
if (!$xls) {
    die("Failed to parse Excel: " . Shuchkin\SimpleXLS::parseError());
}

$sheets = $xls->sheetNames();
echo "<h3>Sheets found:</h3>";
echo "<pre>" . print_r($sheets, true) . "</pre>";

// Find PEMBAYARAN BUNGA sheet
$sheet_bunga = array_search('PEMBAYARAN BUNGA DEPOSITO', $sheets);
if ($sheet_bunga === false) {
    die("Sheet 'PEMBAYARAN BUNGA DEPOSITO' not found");
}

$rows = $xls->rows($sheet_bunga);

// Show header rows
echo "<h3>Header Rows (Rows 1-3):</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
for ($i = 0; $i < 3; $i++) {
    echo "<tr>";
    echo "<td><strong>Row $i</strong></td>";
    foreach ($rows[$i] as $colIdx => $cell) {
        $colLetter = chr(65 + ($colIdx % 26));
        if ($colIdx >= 26) {
            $colLetter = chr(64 + intval($colIdx / 26)) . $colLetter;
        }
        echo "<td style='padding: 2px 5px; background: #f0f0f0;'><small>$colLetter($colIdx)</small><br>" . htmlspecialchars($cell) . "</td>";
    }
    echo "</tr>";
}
echo "</table>";

// Find NI MADE JUNIATI (row with NIN=13)
echo "<h3>Looking for NI MADE JUNIATI (NIN=13):</h3>";
for ($i = 3; $i < count($rows); $i++) {
    $row = $rows[$i];
    $nin = intval($row[0] ?? 0);
    $nama = trim($row[1] ?? '');

    if ($nin == 13 || stripos($nama, 'JUNIATI') !== false) {
        echo "<p><strong>Found at row $i:</strong></p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr>";
        foreach ($row as $colIdx => $cell) {
            $colLetter = chr(65 + ($colIdx % 26));
            if ($colIdx >= 26) {
                $colLetter = chr(64 + intval($colIdx / 26)) . $colLetter;
            }
            $cellVal = is_numeric($cell) && $cell > 1000 ? number_format($cell, 0, ',', '.') : $cell;
            echo "<td style='padding: 5px; background: " . ($cell !== '' && $cell !== null ? '#ffffcc' : '#ffffff') . ";'>";
            echo "<small>$colLetter($colIdx)</small><br><strong>" . htmlspecialchars($cellVal) . "</strong></td>";
        }
        echo "</tr>";
        echo "</table>";

        // Check specific columns
        echo "<h4>Key columns for this row:</h4>";
        echo "<ul>";
        echo "<li>Column 3 (D): " . ($row[3] ?? 'empty') . "</li>";
        echo "<li>Column 4 (E): " . ($row[4] ?? 'empty') . "</li>";
        echo "<li>Column 5 (F): " . ($row[5] ?? 'empty') . "</li>";
        echo "<li>Column 6 (G): " . ($row[6] ?? 'empty') . "</li>";
        echo "<li>Column 7 (H): " . ($row[7] ?? 'empty') . "</li>";
        echo "</ul>";
        break;
    }
}

echo "<p><a href='deposito/import'>← Back to Import</a></p>";

// Write output to file
$output = ob_get_clean();
file_put_contents('debug_deposito_output.txt', strip_tags(str_replace(['<br>', '</li>', '</tr>'], "\n", $output)));
echo "Output written to debug_deposito_output.txt\n";
echo strip_tags(str_replace(['<br>', '</li>', '</tr>'], "\n", $output));
