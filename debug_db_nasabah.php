<?php
/**
 * Debug script to check database status
 */
require_once 'index.php';

// Check nasabah table
$ci =& get_instance();

echo "=== DATABASE CHECK ===\n\n";

// Check sample nasabah data
echo "--- Sample tbnasabah (first 15) ---\n";
$nasabah = $ci->db->limit(15)->get('tbnasabah')->result();
foreach ($nasabah as $n) {
    echo "ID: {$n->id} | NIK: {$n->nik} | Nama: {$n->nama_lengkap}\n";
}

echo "\n\n--- Sample tbsimpanan (first 15) ---\n";
$simpanan = $ci->db->limit(15)->get('tbsimpanan')->result();
foreach ($simpanan as $s) {
    echo "ID: {$s->id} | NoRek: {$s->no_rekening} | NasabahID: {$s->nasabah_id} | Nama: " . ($s->nama_nasabah ?? '-') . " | Saldo: {$s->jumlah_simpanan}\n";
}

echo "\n\n--- Count stats ---\n";
$count_nasabah = $ci->db->count_all('tbnasabah');
$count_simpanan = $ci->db->count_all('tbsimpanan');
echo "Total tbnasabah: $count_nasabah\n";
echo "Total tbsimpanan: $count_simpanan\n";

// Check how many have placeholder names
$placeholder = $ci->db->like('nama_lengkap', 'Nasabah T', 'after')->count_all_results('tbnasabah');
echo "Nasabah with placeholder names (Nasabah T...): $placeholder\n";

echo "\n=== Done ===\n";
