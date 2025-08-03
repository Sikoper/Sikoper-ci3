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

        .content-wrapper {
            padding: 1.3cm 1.4cm;
            box-sizing: border-box;
        }

        .logo-desa {
            position: absolute;
            width: 80px;
            left: 150px;
            top: 50px;
            height: 80px;
        }

        .logo-koperasi {
            position: absolute;
            right: 150px;
            top: 50px;
            width: 80px;
            height: 80px;
        }

        .page-container {
            width: 100%;
            box-sizing: border-box;
        }

        #halaman-depan {
            page-break-after: always;
        }

        .kop {
            text-align: center;
            line-height: 1.3;
            margin-bottom: 8px;
        }

        .kop h2 {
            font-size: 13pt;
            margin: 0;
        }

        .kop h3 {
            font-size: 11.5pt;
            margin: 0;
        }

        .kop p {
            font-size: 9pt;
            margin: 0;
        }

        .nomor-seri {
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 11pt;
        }

        .isi-utama {
            margin-top: 0.6cm;
        }

        .isi-utama table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            table-layout: fixed;
        }

        .isi-utama td {
            vertical-align: top;
            padding: 1px 3px;
            word-break: break-word;
        }

        .kwitansi {
            margin-top: 80px;
            font-size: 11pt;
            padding-top: 5px;
            line-height: 1.4;
        }

        .tanda-tangan-area {
            margin-top: 0px;
            font-size: 9pt;
            display: flex;
            right: 0;
        }

        .tanda-tangan-box {
            text-align: center;
            width: 45%;
        }

        .tanda-tangan-box .nama {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 20px;
        }

        .ketentuan ol {
            padding-left: 18px;
            margin: 0;
            line-height: 1.3;
            font-size: 8.5pt;
        }

        .ketentuan li {
            margin-bottom: 3px;
        }

        .surat-pernyataan-box {
            border: 1px solid #555;
            padding: 5px;
            font-size: 8.8pt;
            min-height: 350px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .surat-pernyataan-box table td {
            padding: 1px 0;
        }

        .surat-pernyataan-box p {
            margin-bottom: 5px;
        }

        .tanda-tangan-nasabah {
            width: 250px;
            margin-left: auto;
            margin-right: 0;
            text-align: center;
            margin-top: 8px;
            font-size: 8.5pt;
        }

        .materai-box {
            border: 1px solid #333;
            width: 60px;
            height: 30px;
            margin: 0px auto;
            padding-top: 10px;
            font-size: 6pt;
        }

        .tabel-bunga {
            width: 100%;
            font-size: 8pt;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .tabel-bunga th,
        .tabel-bunga td {
            border: 1px solid #000;
            text-align: center;
            padding: 7px;
        }
    </style>
    <link rel="icon" href="<?= base_url('assets') ?>/images/logo/sikoper.png">
</head>
<?php
$formatter = new \IntlDateFormatter('id_ID', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE);
$formatter->setPattern('d MMMM yyyy');
?>

<body>
    <div class="content-wrapper">
        <div id="halaman-depan" class="page-container">
            <img class="logo-desa" src="<?= base_url('assets') ?>/images/logo/desa-culik.jpg" alt="logo-desa">
            <img class="logo-koperasi" src="<?= base_url('assets') ?>/images/logo/koperasi.jpg" alt="logo-koperasi">
            <p class="nomor-seri">No Seri: <?= $nomor_sertifikat ?></p>
            <div class="kop">
                <h2>SURAT SIMPANAN BERJANGKA</h2>
                <h3>USAHA SIMPAN PINJAM BALI SEJAHTERA</h3>
                <h3>DESA ADAT CULIK</h3>
                <p>Jln. Ketut Natih, Br Dinas Gerit, Desa Culik, Kec. Abang, Kab. Karangasem</p>
            </div>
            <div class="isi-utama">
                <table style="width: 100%;">
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
                        <td style="font-style: italic; white-space: pre-wrap;"><?= $terbilang ?></td>
                    </tr>
                </table>
                <p style="margin-top: 15px; line-height: 1.6;">Untuk Simpanan Berjangka dalam waktu <?= $durasi ?> bulan,
                    mulai tanggal <?= $formatter->format(new DateTime($tanggal_deposito)) ?> sampai dengan tanggal
                    <?= $formatter->format(new DateTime($tanggal_jatuh_tempo)) ?>, bunga <?= $suku_bunga ?>% perbulan,
                    dengan syarat-syarat yang telah ditentukan oleh <b>Usaha Simpan Pinjam Bali Sejahtera Desa Adat Culik.</b></p>
            </div>
            <div class="tanda-tangan-area">
                <div class="tanda-tangan-box" style="float: right;">
                    <div class="tanda-tangan-box" style="float: right;">
                        <p class="tanggal">Culik, <?= date('d F Y') ?></p>
                        <p class="jabatan">Usaha Simpan Pinjam Bali Sejahtera
                            <br /> KEPALA
                        </p>
                        <div class="materai-box">MATERAI TEMPEL</div>
                        <p class="nama"><?= $nama_pimpinan ?></p>
                    </div>
                </div>
            </div>
            <div class="kwitansi">
                <h3 style="font-weight: bold; text-decoration: underline;">KWITANSI</h3>
                <p style="line-height: 1.6;">Telah diterima Simpanan Berjangka No. <?= $nomor_sertifikat ?>. <br /> Yang bertandatangan dibawah ini, menyatakan telah menerima sejumlah uang Simpanan Berjangka seperti disebut diatas dari Usaha <b>Simpan Pinjam Bali Sejahtera Desa Adat Culik.</b>
                </p>
            </div>
            <br />
            <div class="tanda-tangan-area">
                <div class="tanda-tangan-box" style="float: right;">
                    <div class="tanda-tangan-box" style="float: right;">
                        <p class="tanggal">Culik, <?= $formatter->format(new DateTime()); ?></p>
                        <br />
                        <br />
                        <p class="nama"><?= $nama_nasabah ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <div id="halaman-belakang" class="page-container">
            <table style="width: 100%; border-collapse: collapse; vertical-align: top;">
                <tr style="vertical-align: top;">
                    <td style="width: 50%; padding-right: 15px;">
                        <div class="ketentuan">
                            <h3 style="text-decoration: underline; font-weight: bold; margin:0; margin-bottom: 5px; font-size: 8.5pt;">Ketentuan Deposito:</h3>
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
                    </td>
                    <td style="width: 50%;">
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
                                    <td>Tempat, Tanggal Lahir</td>
                                    <td>:</td>
                                    <td><?= $tempat_lahir ?>, <?= $formatter->format(new DateTime($tanggal_lahir)); ?></td>
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
                            <div class="tanda-tangan-nasabah" style="width: 250px; margin-left: auto; margin-right: 0; text-align: center;">
                                <p>Culik, <?= $formatter->format(new DateTime($tanggal_deposito)); ?></p>
                                <p>Yang tersebut di atas</p>
                                <div class="materai-box">MATERAI TEMPEL</div>
                                <p class="nama"><?= $nama_nasabah ?></p>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <table style="width: 100%; border-collapse: collapse;">
                <tbody>
                    <tr style="vertical-align: top;">
                        <td style="width: 50%; padding-right: 5px;">
                            <table class="tabel-bunga">
                                <tbody>
                                    <tr>
                                        <th>No</th>
                                        <th>Tgl</th>
                                        <th>Bunga</th>
                                        <th>No.Bukti</th>
                                        <th>Paraf</th>
                                    </tr>
                                    <?php for ($i = 1; $i <= 6; $i++) echo "<tr><td>$i</td><td></td><td></td><td></td><td></td></tr>"; ?>
                                </tbody>
                            </table>
                        </td>
                        <td style="width: 50%; padding-left: 5px;">
                            <table class="tabel-bunga">
                                <tbody>
                                    <tr>
                                        <th>No</th>
                                        <th>Tgl</th>
                                        <th>Bunga</th>
                                        <th>No.Bukti</th>
                                        <th>Paraf</th>
                                    </tr>
                                    <?php for ($i = 7; $i <= 12; $i++) echo "<tr><td>$i</td><td></td><td></td><td></td><td></td></tr>"; ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>