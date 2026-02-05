<?php
/**
 * Check database data for NIN 482 (T482 rekening) and compare with Excel
 */

// Simple database connection
$mysqli = new mysqli('localhost', 'root', '', 'sikoper3');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$output_file = 'debug_db_nin482.txt';
ob_start();

$no_rekening = 'T482';

echo "=== DATA DATABASE UNTUK NO. REKENING $no_rekening ===\n\n";

// 1. Get simpanan data
echo "=== TBSIMPANAN ===\n";
$result = $mysqli->query("SELECT * FROM tbsimpanan WHERE no_rekening = '$no_rekening'");
if ($result && $row = $result->fetch_assoc()) {
    $simpanan_id = $row['id'];
    echo "ID: " . $row['id'] . "\n";
    echo "Nasabah ID: " . $row['nasabah_id'] . "\n";
    echo "No Rekening: " . $row['no_rekening'] . "\n";
    echo "Nama Nasabah: " . ($row['nama_nasabah'] ?? 'N/A') . "\n";
    echo "Jumlah Simpanan: Rp " . number_format($row['jumlah_simpanan'], 0, ',', '.') . "\n";
    echo "Total Setoran: Rp " . number_format($row['total_setoran'] ?? 0, 0, ',', '.') . "\n";
    echo "Total Penarikan: Rp " . number_format($row['total_penarikan'] ?? 0, 0, ',', '.') . "\n";
    echo "Created At: " . $row['created_at'] . "\n";
} else {
    echo "Tidak ditemukan!\n";
    $simpanan_id = null;
}

// 2. Get nasabah data
if (!empty($row['nasabah_id'])) {
    echo "\n=== TBNASABAH ===\n";
    $nasabah_id = $row['nasabah_id'];
    $result2 = $mysqli->query("SELECT * FROM tbnasabah WHERE id = $nasabah_id");
    if ($result2 && $row2 = $result2->fetch_assoc()) {
        echo "ID: " . $row2['id'] . "\n";
        echo "Nama Lengkap: " . $row2['nama_lengkap'] . "\n";
        echo "NIK: " . $row2['nik'] . "\n";
        echo "Alamat: " . $row2['alamat'] . "\n";
    }
}

// 3. Get detail setoran
if ($simpanan_id) {
    echo "\n=== DETAIL SETORAN (tbdetail_simpanan) ===\n";
    $result3 = $mysqli->query("SELECT * FROM tbdetail_simpanan WHERE simpanan_id = $simpanan_id ORDER BY tanggal_setoran");
    $total_setoran = 0;
    $count = 0;
    if ($result3) {
        while ($row3 = $result3->fetch_assoc()) {
            $count++;
            $total_setoran += $row3['jumlah_setoran'];
            if ($count <= 20) {
                echo "$count. " . $row3['tanggal_setoran'] . " - Rp " . number_format($row3['jumlah_setoran'], 0, ',', '.') . "\n";
            }
        }
        if ($count > 20) {
            echo "... dan " . ($count - 20) . " transaksi lainnya\n";
        }
        echo "Total: $count transaksi, Rp " . number_format($total_setoran, 0, ',', '.') . "\n";
    }

    // 4. Get detail penarikan
    echo "\n=== DETAIL PENARIKAN (tbdetail_penarikan) ===\n";
    $result4 = $mysqli->query("SELECT * FROM tbdetail_penarikan WHERE simpanan_id = $simpanan_id ORDER BY tanggal_penarikan");
    $total_penarikan = 0;
    $count = 0;
    if ($result4) {
        while ($row4 = $result4->fetch_assoc()) {
            $count++;
            $total_penarikan += $row4['jumlah_penarikan'];
            echo "$count. " . $row4['tanggal_penarikan'] . " - Rp " . number_format($row4['jumlah_penarikan'], 0, ',', '.') . " (Status: " . $row4['status'] . ")\n";
        }
        echo "Total: $count transaksi, Rp " . number_format($total_penarikan, 0, ',', '.') . "\n";
    }
}

echo "\n\n=== PERBANDINGAN DENGAN EXCEL ===\n";
echo "Excel NIN 482:\n";
echo "  - Nama: NI WAYAN SEKAR\n";
echo "  - Saldo Awal Jan: Rp 406.677\n";
echo "  - Setoran Jan: Rp 3.100.000 (19 transaksi)\n";
echo "  - Penarikan Jan: Rp 3.250.000 (1 transaksi hari 27)\n";
echo "  - Saldo Akhir (Col 102): Rp 256.777\n";

$mysqli->close();

file_put_contents($output_file, ob_get_clean());
echo "Output written to: $output_file\n";
