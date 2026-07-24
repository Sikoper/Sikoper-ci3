# Sikoper - Sistem Informasi Koperasi

## Arsitektur Aplikasi (Untuk Programmer Selanjutnya)

Aplikasi ini menggunakan kerangka kerja (framework) **CodeIgniter 3**. Mulai Juli 2026, telah dilakukan _refactoring_ besar-besaran untuk merapikan *Controller* yang membengkak (*Fat Controllers*) agar struktur kode menjadi lebih berstandar _Clean Architecture (MVC)_.

Mohon perhatikan panduan berikut jika Anda akan menambahkan fitur atau memperbaiki _bug_:

### 1. Hindari Menulis Logika Bisnis Panjang di Controller
- **Aturan Baru:** `Controller` hanya digunakan untuk menerima *request* pengguna (menangkap input form), melakukan validasi sederhana, dan memanggil fungsi dari `Model` atau `Library`.
- **Contoh Refactoring:**
  Fungsi-fungsi berat seperti menyimpan data ke banyak tabel secara bersamaan (*transaction*), menghitung periode tanggal (*DatePeriod*), atau membuat *log* otomatis tidak lagi berada di dalam file Controller (seperti `Simpanan.php` atau `Deposito.php`).
  Semuanya telah dipindah ke dalam `Model` yang relevan, contohnya:
  - `Deposito_model::save_deposito_with_logs()`
  - `Simpanan_model::save_simpanan_full()`

### 2. Fitur Impor Excel Dialihkan ke Library (Service Pattern)
Proses impor dari Excel yang melibatkan ratusan baris kode untuk *parsing* (seperti `SimpleXLS`) dapat membuat Model maupun Controller menjadi sangat panjang dan sulit dibaca.
- **Aturan Baru:** Pembacaan data *sheet* demi *sheet* dari file `.xls` atau `.xlsx` telah diisolasi menjadi **Service Library**.
- Anda dapat menemukan kode impor di dalam folder `application/libraries/`:
  - `Tabungan_import_service.php`
  - `Deposito_import_service.php`
- Model (seperti `Tabungan_model` atau `Deposito_model`) hanya akan memuat *library* tersebut jika pengguna menjalankan fungsi impor, sehingga tidak membebani memori di proses lainnya.

### 3. Keamanan Direktori Uploads
- Telah ditambahkan file `.htaccess` di dalam direktori `uploads/` yang berisi instruksi untuk menolak (_Deny_) akses langsung eksekusi ke ekstensi file skrip seperti `.php`, `.phtml`, dll.
- **Aturan Baru:** Jika Anda membuat fitur *upload* gambar atau file baru, letakkan di dalam atau di bawah folder `uploads/` agar secara otomatis mewarisi perlindungan keamanan ini. Jangan membuat folder unggahan baru di struktur paling luar (_root_).

### 4. Helper Baru
- Jika Anda membutuhkan *formatting* (seperti Format Rupiah standar Koperasi), gunakan helper yang sudah disatukan. Jangan membuat fungsi `format_rupiah` ganda di dalam banyak file.

## Kinerja Database
Jika data nasabah dan transaksi sudah mencapai ribuan, pastikan untuk memeriksa keberadaan **Indeks** (Indexes) di database (MySQL/MariaDB), terutama untuk:
1. `nasabah_id` dan `no_rekening` di tabel utama (seperti `tbdeposito`, `tbsimpanan`, `tbtabungan`).
2. `tanggal_perhitungan` dan `status_penarikan` di tabel log.

Terima kasih karena telah menjaga aplikasi ini tetap bersih, aman, dan mudah dipelihara!
