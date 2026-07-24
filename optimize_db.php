<?php
/**
 * Skrip Optimasi Database (Database Indexing)
 * Dijalankan sekali saat deployment di komputer klien untuk mempercepat pencarian data (offline mode)
 */

// Load CodeIgniter Environment
define('ENVIRONMENT', 'development');
define('BASEPATH', __DIR__ . '/system/');
define('APPPATH', __DIR__ . '/application/');
define('VIEWPATH', __DIR__ . '/application/views/');
define('FCPATH', __DIR__ . '/');

require_once BASEPATH . 'core/CodeIgniter.php';

// Cek instance CI
$CI =& get_instance();
$CI->load->database();

echo "<h2>Mulai Optimasi Database...</h2>";

$indexes_to_create = [
    'tbnasabah' => [
        ['name' => 'idx_nama_lengkap', 'columns' => 'nama_lengkap'],
        ['name' => 'idx_nik', 'columns' => 'nik']
    ],
    'tbdeposito' => [
        ['name' => 'idx_nasabah_id', 'columns' => 'nasabah_id'],
        ['name' => 'idx_no_rekening', 'columns' => 'no_rekening'],
        ['name' => 'idx_status', 'columns' => 'status'],
        ['name' => 'idx_tanggal_deposito', 'columns' => 'tanggal_deposito']
    ],
    'tbsimpanan' => [
        ['name' => 'idx_nasabah_id_simpanan', 'columns' => 'nasabah_id'],
        ['name' => 'idx_no_rekening_simpanan', 'columns' => 'no_rekening']
    ],
    'tb_bunga_deposito_log' => [
        ['name' => 'idx_deposito_id_log', 'columns' => 'deposito_id'],
        ['name' => 'idx_status_penarikan', 'columns' => 'status_penarikan']
    ],
    'tbpenarikan_deposito' => [
        ['name' => 'idx_deposito_id_penarikan', 'columns' => 'deposito_id']
    ]
];

foreach ($indexes_to_create as $table => $indexes) {
    // Pastikan tabel ada
    if (!$CI->db->table_exists($table)) {
        echo "<p style='color:orange;'>Tabel <b>{$table}</b> tidak ditemukan. Dilewati.</p>";
        continue;
    }

    echo "<h3>Memeriksa tabel: {$table}</h3>";
    echo "<ul>";
    
    foreach ($indexes as $index) {
        $idx_name = $index['name'];
        $cols = $index['columns'];
        
        // Cek apakah index sudah ada
        $query = $CI->db->query("SHOW INDEX FROM {$table} WHERE Key_name = '{$idx_name}'");
        if ($query->num_rows() > 0) {
            echo "<li style='color:gray;'>Index <b>{$idx_name}</b> sudah ada.</li>";
        } else {
            // Buat index baru
            $sql = "ALTER TABLE {$table} ADD INDEX {$idx_name} ({$cols})";
            if ($CI->db->query($sql)) {
                echo "<li style='color:green;'>Berhasil menambahkan index <b>{$idx_name}</b> pada kolom ({$cols}).</li>";
            } else {
                echo "<li style='color:red;'>Gagal menambahkan index <b>{$idx_name}</b>.</li>";
            }
        }
    }
    
    echo "</ul>";
}

echo "<h2>✅ Optimasi Selesai!</h2>";
echo "<p>Silakan tutup halaman ini. Ingat untuk menghapus file <code>optimize_db.php</code> jika aplikasi ini sudah berjalan dan diakses oleh publik.</p>";
