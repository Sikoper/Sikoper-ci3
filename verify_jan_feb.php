<?php
require_once 'application/third_party/SimpleXLS.php';

ini_set('memory_limit', '2048M');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Config
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'sikoper3';

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Excel File
$file = 'TABUNGAN 2026.xls';
if (!file_exists($file)) {
    die("File Excel not found.\n");
}
$xls = \Shuchkin\SimpleXLS::parse($file);
if (!$xls) {
    die("Excel Parse Error: " . \Shuchkin\SimpleXLS::parseError() . "\n");
}

$sheets = $xls->sheetNames();

function get_excel_totals($xls, $month, $sheets)
{
    $idx = array_search($month, $sheets);
    if ($idx === false)
        return null;

    $rows = $xls->rows($idx);
    $total_saldo_excel = 0;
    $count = 0;
    $sample_482 = 0;

    // Start from row 3 (index 3, row 4)
    for ($i = 3; $i < count($rows); $i++) {
        $row = $rows[$i];
        // Col 102/103 is usually Saldo Akhir ?
        // Based on debug output: 
        // [102] 256777
        // [103] 256777
        // Let's use column 102.
        $saldo = (float) ($row[102] ?? 0);
        $total_saldo_excel += $saldo;

        if (isset($row[2]) && $row[2] == 482) {
            $sample_482 = $saldo;
        }
        $count++;
    }
    return ['total' => $total_saldo_excel, 'sample_482' => $sample_482, 'count' => $count];
}

function get_db_totals($mysqli, $month_num)
{
    // Totals from tbsimpanan is CURRENT balance (accumulated).
    // To get historical balance for JAN/FEB is hard without full history replay.
    // BUT we can check T482 specifically since that's the user's focus.

    // Check T482
    $sql = "SELECT jumlah_simpanan, nama_nasabah FROM tbsimpanan WHERE no_rekening = 'T482'";
    $res = $mysqli->query($sql);
    $row = $res->fetch_assoc();

    // Also calculate from details up to specific month end
    // Jan End: 2026-01-31
    // Feb End: 2026-02-28

    $date_limit = "2026-$month_num-31 23:59:59";

    $sql_trans = "
        SELECT 
            (SELECT COALESCE(SUM(jumlah_setoran),0) FROM tbdetail_simpanan 
             JOIN tbsimpanan ON tbdetail_simpanan.simpanan_id = tbsimpanan.id 
             WHERE tbsimpanan.no_rekening = 'T482' AND tanggal_setoran <= '$date_limit') as total_setoran,
             
            (SELECT COALESCE(SUM(jumlah_penarikan),0) FROM tbdetail_penarikan 
             LEFT JOIN tbsimpanan ON tbdetail_penarikan.simpanan_id = tbsimpanan.id 
             WHERE tbsimpanan.no_rekening = 'T482' AND tanggal_penarikan <= '$date_limit') as total_penarikan
    ";

    $res_trans = $mysqli->query($sql_trans);
    $row_trans = $res_trans->fetch_assoc();

    $calc_saldo = $row_trans['total_setoran'] - $row_trans['total_penarikan'];

    return ['current_db_saldo' => $row['jumlah_simpanan'] ?? 0, 'calc_saldo' => $calc_saldo, 'nama' => $row['nama_nasabah'] ?? 'Not Found'];
}

echo "=== VERIFICATION REPORT ===\n";

// JAN
$excel_jan = get_excel_totals($xls, 'JAN', $sheets);
$db_jan = get_db_totals($mysqli, '01');

echo "\n--- JANUARY (T482) ---\n";
echo "Excel Saldo Akhir: " . number_format($excel_jan['sample_482']) . "\n";
echo "DB Calculated Saldo (until 31 Jan): " . number_format($db_jan['calc_saldo']) . "\n";
echo "DB Current Master Saldo: " . number_format($db_jan['current_db_saldo']) . "\n";
echo "DB Name: " . $db_jan['nama'] . "\n";
echo "Match: " . ($excel_jan['sample_482'] == $db_jan['calc_saldo'] ? "YES ✅" : "NO ❌") . "\n";


// FEB
$excel_feb = get_excel_totals($xls, 'FEB', $sheets);
$db_feb = get_db_totals($mysqli, '02');

echo "\n--- FEBRUARY (T482) ---\n";
if ($excel_feb) {
    echo "Excel Saldo Akhir: " . number_format($excel_feb['sample_482']) . "\n";
} else {
    echo "Excel Saldo Akhir: Sheet FEB not found\n";
}
echo "DB Calculated Saldo (until 28 Feb): " . number_format($db_feb['calc_saldo']) . "\n";
echo "DB Current Master Saldo: " . number_format($db_feb['current_db_saldo']) . "\n";
echo "Match: " . (($excel_feb && $excel_feb['sample_482'] == $db_feb['calc_saldo']) ? "YES ✅" : "NO ❌") . "\n";

echo "\nNote: DB Calculated Saldo sums all transactions up to end of that month.\n";
