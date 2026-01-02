<?php if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!function_exists('safe_base64_encode')) {
    function safe_base64_encode($string)
    {
        // Logika existing: replace +/= dengan -_?
        return strtr(base64_encode($string), '+/=', '-_?');
    }
}

if (!function_exists('safe_base64_decode')) {
    function safe_base64_decode($string)
    {
        // Logika existing: kembalikan ke standar base64
        return base64_decode(strtr($string, '-_?', '+/='));
    }
}

// Aliases for compatibility or cleaner usage if needed
if (!function_exists('encrypt_url')) {
    function encrypt_url($string)
    {
        return safe_base64_encode($string);
    }
}

if (!function_exists('decrypt_url')) {
    function decrypt_url($string)
    {
        return safe_base64_decode($string);
    }
}

if (!function_exists('format_durasi')) {
    function format_durasi($bulan)
    {
        if ($bulan === null || !is_numeric($bulan) || $bulan <= 0) {
            return '-';
        }
        $bulan_int = intval($bulan);
        $tahun = floor($bulan_int / 12);
        $sisa_bulan = $bulan_int % 12;
        $output_parts = [];
        if ($tahun > 0) {
            $output_parts[] = "{$tahun} tahun";
        }
        if ($sisa_bulan > 0) {
            $output_parts[] = "{$sisa_bulan} bulan";
        }
        if (empty($output_parts)) {
            return "{$bulan_int} bulan";
        }
        $output_str = implode(' ', $output_parts);
        if ($bulan_int >= 12) {
            $output_str .= " (Total: {$bulan_int} bulan)";
        }
        return $output_str;
    }
}

if (!function_exists('terbilang')) {
    function terbilang($angka)
    {
        $angka = intval(abs($angka));
        $baca = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        $terbilang = '';

        if ($angka < 12) {
            $terbilang = $baca[$angka];
        } else if ($angka < 20) {
            $terbilang = $baca[$angka - 10] . ' belas';
        } else if ($angka < 100) {
            $terbilang = terbilang(intval($angka / 10)) . ' puluh ' . terbilang($angka % 10);
        } else if ($angka < 200) {
            $terbilang = 'seratus ' . terbilang($angka - 100);
        } else if ($angka < 1000) {
            $terbilang = terbilang(intval($angka / 100)) . ' ratus ' . terbilang($angka % 100);
        } else if ($angka < 2000) {
            $terbilang = 'seribu ' . terbilang($angka - 1000);
        } else if ($angka < 1000000) {
            $terbilang = terbilang(intval($angka / 1000)) . ' ribu ' . terbilang($angka % 1000);
        } else if ($angka < 1000000000) {
            $terbilang = terbilang(intval($angka / 1000000)) . ' juta ' . terbilang($angka % 1000000);
        } else if ($angka < 1000000000000) {
            $terbilang = terbilang(intval($angka / 1000000000)) . ' miliar ' . terbilang($angka % 1000000000);
        } else if ($angka < 1000000000000000) {
            $terbilang = terbilang(intval($angka / 1000000000000)) . ' triliun ' . terbilang($angka % 1000000000000);
        }

        return trim(preg_replace('/\s+/', ' ', $terbilang));
    }
}

if (!function_exists('terbilang_rupiah')) {
    function terbilang_rupiah($angka_float)
    {
        $rupiah = floor($angka_float);
        $sen = round(($angka_float - $rupiah) * 100);

        $terbilang_rupiah = terbilang($rupiah) . ' rupiah';

        if ($sen > 0) {
            $terbilang_sen = ' koma ' . terbilang($sen) . ' sen';
            return $terbilang_rupiah . $terbilang_sen;
        }

        return $terbilang_rupiah;
    }
}
