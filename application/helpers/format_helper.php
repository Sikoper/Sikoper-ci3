<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Format Helper
 * Berisi fungsi-fungsi bantuan untuk memformat data yang sering digunakan di aplikasi.
 */

if (!function_exists('rupiah')) {
    /**
     * Memformat angka menjadi format mata uang Rupiah
     * @param int|float $angka
     * @return string
     */
    function rupiah($angka)
    {
        // Pastikan input berupa angka atau jika kosong beri nilai 0 agar tidak error
        if (empty($angka) || !is_numeric($angka)) {
            $angka = 0;
        }
        return "Rp " . number_format($angka, 0, ',', '.');
    }
}

if (!function_exists('tanggal_indo')) {
    /**
     * Memformat tanggal menjadi format Bahasa Indonesia (misal: 17 Agustus 1945)
     * @param string $tanggal Format Y-m-d atau Y-m-d H:i:s
     * @return string
     */
    function tanggal_indo($tanggal)
    {
        if (empty($tanggal) || $tanggal == '0000-00-00' || $tanggal == '0000-00-00 00:00:00') {
            return '-';
        }

        $bulan = array(
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        );

        // Ambil bagian tanggalnya saja (jika ada waktu)
        $waktu = '';
        if (strpos($tanggal, ' ') !== false) {
            $parts = explode(' ', $tanggal);
            $tanggal = $parts[0];
            $waktu = ', ' . date('H:i', strtotime($parts[1]));
        } else {
            $tanggal = date('Y-m-d', strtotime($tanggal));
        }

        $pecahkan = explode('-', $tanggal);

        // pastikan format array sesuai Y-m-d
        if (count($pecahkan) == 3) {
            return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0] . $waktu;
        }
        
        return $tanggal;
    }
}
