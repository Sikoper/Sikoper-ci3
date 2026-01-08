<?php
$mysqli = new mysqli('localhost', 'root', '', 'sikoper3');
if ($mysqli->connect_error)
    die("Connect Error: " . $mysqli->connect_error);

echo "=== TBSIMPANAN SAMPLE ===\n";
$res = $mysqli->query("SELECT id, no_rekening, nama_nasabah, jumlah_simpanan, tanggal_simpanan FROM tbsimpanan LIMIT 5");
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']}, NoRek: {$row['no_rekening']}, Nama: {$row['nama_nasabah']}, Saldo: {$row['jumlah_simpanan']}, Tgl: {$row['tanggal_simpanan']}\n";
}

echo "\n=== TBSIMPANAN COUNT & SUM ===\n";
$res = $mysqli->query("SELECT COUNT(*) as cnt, SUM(jumlah_simpanan) as total FROM tbsimpanan");
print_r($res->fetch_assoc());

echo "\n=== TBDETAIL_SIMPANAN COUNT & SUM ===\n";
$res = $mysqli->query("SELECT COUNT(*) as cnt, SUM(jumlah_setoran) as total FROM tbdetail_simpanan");
print_r($res->fetch_assoc());

echo "\n=== DETAIL SIMPANAN BY YEAR ===\n";
$res = $mysqli->query("SELECT YEAR(tanggal_setoran) as yr, COUNT(*) as cnt, SUM(jumlah_setoran) as total FROM tbdetail_simpanan GROUP BY YEAR(tanggal_setoran)");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n=== TANGGAL_SIMPANAN DISTRIBUTION ===\n";
$res = $mysqli->query("SELECT tanggal_simpanan, COUNT(*) as cnt FROM tbsimpanan GROUP BY tanggal_simpanan");
while ($row = $res->fetch_assoc()) {
    echo "{$row['tanggal_simpanan']}: {$row['cnt']} records\n";
}
