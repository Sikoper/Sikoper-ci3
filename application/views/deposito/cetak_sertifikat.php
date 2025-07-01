<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Sertifikat Deposito - <?= $nama_nasabah ?></title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1cm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            margin: 0;
            padding: 0;
        }

        #halaman-depan {
            page-break-after: always;
        }

        .page-container {
            width: 100%;
            height: 100%;
            position: relative;
        }

        .kop {
            text-align: center;
            line-height: 1.4;
        }

        .kop h2 {
            font-size: 15pt;
            margin: 0;
            font-weight: bold;
        }

        .kop h3 {
            font-size: 14pt;
            margin: 0;
            font-weight: bold;
        }

        .kop p {
            font-size: 11pt;
            margin: 0;
        }

        .nomor-seri {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 11pt;
        }

        .isi-utama {
            margin-top: 1cm;
        }

        .isi-utama table {
            width: 100%;
            border-collapse: collapse;
        }

        .isi-utama td {
            vertical-align: top;
            padding: 2px 0;
        }

        .kwitansi {
            margin-top: 20px;
            border-top: 1.5px dashed #000;
            padding-top: 8px;
        }

        .tanda-tangan-area {
            text-align: right;
            margin-top: 20px;
        }

        .tanda-tangan-box {
            display: inline-block;
            text-align: center;
            width: 45%;
        }

        .tanda-tangan-box .jabatan,
        .tanda-tangan-box .tanggal {
            margin: 0;
            padding: 0;
            font-size: 11pt;
        }

        .tanda-tangan-box .nama {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 60px;
        }

        .layout-dua-kolom {
            display: table;
            width: 100%;
            table-layout: fixed;
            border-spacing: 20px 0;
        }

        .kolom {
            display: table-cell;
            vertical-align: top;
        }

        .ketentuan ol {
            padding-left: 20px;
            font-size: 9pt;
            line-height: 1.4;
            margin-top: 0;
        }

        .ketentuan li {
            margin-bottom: 1.5px;
        }

        .surat-pernyataan-box {
            border: 1px solid #555;
            padding: 10px;
            font-size: 10pt;
        }

        .surat-pernyataan-box table td {
            padding: 1.5px 0;
        }

        .tanda-tangan-nasabah {
            text-align: center;
            float: right;
            margin-top: 10px;
        }

        .tanda-tangan-nasabah p {
            margin: 0;
            padding: 0;
            font-size: 10pt;
        }

        .tanda-tangan-nasabah .nama {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 10px;
        }

        .materai-box {
            border: 1.5px solid #333;
            width: 100px;
            height: 35px;
            text-align: center;
            padding-top: 15px;
            box-sizing: border-box;
            font-size: 8pt;
            color: #333;
            margin: 5px auto;
            font-weight: bold;
        }

        .tabel-bunga {
            font-size: 9pt;
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .tabel-bunga th,
        .tabel-bunga td {
            border: 1px solid black;
            text-align: center;
            padding: 4px;
            height: 16px;
        }
    </style>
</head>

<body>
    <div id="halaman-depan" class="page-container">
        <p class="nomor-seri">No Seri: <?= $nomor_sertifikat ?></p>
        <div class="kop">
            <h2>SURAT SIMPANAN BERJANGKA</h2>
            <h3>USAHA SIMPAN PINJAM BALI SEJAHTERA</h3>
            <h3>DESA ADAT CULIK</h3>
            <p>Jln. Ketut Natih, Br Dinas Gerit, Desa Culik, Kec. Abang, Kab. Karangasem</p>
        </div>
        <div class="isi-utama">
            <table style="width: 80%;">
                <tr>
                    <td style="width: 20%;">Telah terima dari</td>
                    <td style="width: 2%;">:</td>
                    <td style="width: 78%; font-weight: bold;"><?= $nama_nasabah ?></td>
                </tr>
                <tr>
                    <td>Alamat Nasabah</td>
                    <td>:</td>
                    <td><?= $alamat_nasabah ?></td>
                </tr>
                <tr>
                    <td>Uang Sejumlah</td>
                    <td>:</td>
                    <td style="font-weight: bold;">Rp. <?= number_format($jumlah_deposito, 2, ',', '.') ?></td>
                </tr>
                <tr>
                    <td>Dengan Huruf</td>
                    <td>:</td>
                    <td style="font-style: italic;"><?= $terbilang ?></td>
                </tr>
            </table>
            <p style="margin-top: 15px; line-height: 1.6;">Untuk Simpanan Berjangka dalam waktu <?= $durasi ?> bulan, mulai tanggal <?= date('d F Y', strtotime($tanggal_deposito)) ?> sampai dengan tanggal <?= date('d F Y', strtotime($tanggal_jatuh_tempo)) ?>, bunga <?= $suku_bunga ?>% perbulan, dengan syarat-syarat yang telah ditentukan oleh <b>Usaha Simpan Pinjam Bali Sejahtera Desa Adat Culik.</b></p>
        </div>
        <div class="kwitansi">
            <h3 style="text-align: center; font-weight: bold; margin-bottom: 5px; text-decoration: underline;">KWITANSI</h3>
            <p style="line-height: 1.6;">Telah diterima Simpanan Berjangka No. <?= $nomor_sertifikat ?>. Yang bertandatangan dibawah ini, menyatakan telah menerima sejumlah uang Simpanan Berjangka seperti disebut diatas dari Usaha Simpan Pinjam Bali Sejahtera Desa Adat Culik.</p>
        </div>
        <div class="tanda-tangan-area">
            <div class="tanda-tangan-box" style="float: right;">
                <p class="jabatan">BENDAHARA</p>
                <p class="nama"><?= $nama_bendahara ?></p>
            </div>
            <div class="tanda-tangan-box" style="float: left;">
                <p class="tanggal">Culik, <?= date('d F Y', strtotime($tanggal_deposito)) ?></p>
                <p class="jabatan">KEPALA</p>
                <p class="nama"><?= $nama_pimpinan ?></p>
            </div>
        </div>
    </div>

    <div id="halaman-belakang" class="page-container">
        <div class="layout-dua-kolom">
            <div class="kolom ketentuan">
                <h3 style="text-decoration: underline; font-weight: bold; margin:0; margin-bottom: 5px;">Ketentuan Deposito:</h3>
                <ol>
                    <li>Saldo Minimal Deposito sebesar Rp. 5.000.000,-</li>
                    <li>Biaya Administrasi Rp. 50.000,-</li>
                    <li>Jangka waktu Deposito minimal 6 Bulan dan maksimal 3 Tahun dengan suku bunga 0,8%/bulan atau 9,6%/tahun.</li>
                    <li>Penarikan kembali simpanan Deposito sebelum jangka waktu berakhir dikenakan pinalti atau denda sebesar 5% dari nominal Deposito dan Bunga berjalan tidak dibayar.</li>
                    <li>Bunga Simpanan Deposito dibayarkan setiap bulan sesuai tanggal penempatan.</li>
                    <li>Deposito ini dapat diperpanjang secara otomatis apabila tidak ditarik Deposan dalam jangka waktu 7 (tujuh) hari kalender dari tanggal jatuh tempo, dengan suku bunga yang berlaku saat perpanjangan.</li>
                    <li>Deposito dapat dipindahtangankan sesuai prosedur Usaha SPBS Desa Adat Culik.</li>
                    <li>Apabila Deposan meninggal dunia, uang simpanan dan bunga akan dibayarkan kepada ahli waris, sesuai surat pernyataan ahli waris yang disepakati Deposan.</li>
                    <li>Deposito ini dijamin dengan seluruh Harta dan Kekayaan yang dimiliki Usaha SPBS Desa Adat Culik.</li>
                    <li>Deposan wajib melaporkan setiap perubahan Nama, Alamat, Ahli waris dan Tandatangan ke Kantor Usaha Simpan Pinjam Bali Sejahtera.</li>
                    <li>Dalam hal terjadi kehilangan Bukti Deposito, maka Deposan harus segera melaporkan kepada Pihak Berwajib dan memberitahukan ke Kantor SPBS Desa Adat Culik.</li>
                    <li>Suku bunga Deposito dapat berubah sewaktu-waktu sesuai dengan keadaan Pasar, yang ditetapkan oleh rapat/musyawarah bersama (Penanggung Jawab, Pemeriksa, dan Kepala).</li>
                </ol>
            </div>
            <div class="kolom">
                <div class="surat-pernyataan-box">
                    <h3 style="text-align: center; text-decoration: underline; font-weight: bold; margin-top:0;">SURAT PERNYATAAN</h3>
                    <p>Yang bertandatangan di bawah ini:</p>
                    <table>
                        <tr>
                            <td style="width: 35%;">Nama</td>
                            <td style="width: 5%;">:</td>
                            <td style="font-weight: bold;"><?= $nama_nasabah ?></td>
                        </tr>
                        <tr>
                            <td>NIK</td>
                            <td>:</td>
                            <td><?= $nik_nasabah ?></td>
                        </tr>
                        <tr>
                            <td>Tempat Tanggal Lahir</td>
                            <td>:</td>
                            <td><?= $tempat_lahir ?>, <?= date('d F Y', strtotime($tanggal_lahir)) ?></td>
                        </tr>
                        <tr>
                            <td>Alamat</td>
                            <td>:</td>
                            <td><?= $alamat_nasabah ?></td>
                        </tr>
                        <tr>
                            <td>No. Telp</td>
                            <td>:</td>
                            <td><?= $telp_nasabah ?></td>
                        </tr>
                    </table>
                    <p style="margin-top: 10px; line-height: 1.5;">Dalam hal ini bertindak untuk dan atas nama Pemegang Simpanan Berjangka Nomor Seri <?= $nomor_sertifikat ?>, menyatakan telah menyetujui / menerima baik syarat-syarat yang telah ditetapkan oleh Usaha Simpan Pinjam Bali Sejahtera Desa Adat Culik.</p>
                    <div style="clear:both;"></div>
                    <div class="tanda-tangan-nasabah">
                        <p>Culik, <?= date('d F Y', strtotime($tanggal_deposito)) ?></p>
                        <p>Yang tersebut di atas</p>
                        <div class="materai-box">MATERAI TEMPEL</div>
                        <p class="nama"><?= $nama_nasabah ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="layout-dua-kolom">
            <div class="kolom">
                <table class="tabel-bunga">
                    <tr>
                        <th>No</th>
                        <th>Tgl</th>
                        <th>Bunga</th>
                        <th>No.Bukti</th>
                        <th>Paraf</th>
                    </tr><?php for ($i = 1; $i <= 6; $i++) echo "<tr><td>$i</td><td></td><td></td><td></td><td></td></tr>"; ?>
                </table>
            </div>
            <div class="kolom">
                <table class="tabel-bunga">
                    <tr>
                        <th>No</th>
                        <th>Tgl</th>
                        <th>Bunga</th>
                        <th>No.Bukti</th>
                        <th>Paraf</th>
                    </tr><?php for ($i = 7; $i <= 12; $i++) echo "<tr><td>$i</td><td></td><td></td><td></td><td></td></tr>"; ?>
                </table>
            </div>
        </div>
    </div>
</body>

</html>