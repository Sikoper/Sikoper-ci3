<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Bunga <?= $tipe ?> - <?= $periode ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.5cm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10pt;
            margin: 0;
            padding: 0;
        }

        .content-wrapper {
            padding: 0.5cm;
            box-sizing: border-box;
        }

        .logo-left {
            position: absolute;
            width: 60px;
            left: 30px;
            top: 20px;
            height: 60px;
        }

        .logo-right {
            position: absolute;
            right: 30px;
            top: 20px;
            width: 60px;
            height: 60px;
        }

        .kop {
            text-align: center;
            line-height: 1.3;
            margin-bottom: 10px;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
        }

        .kop h2 {
            font-size: 14pt;
            margin: 0;
        }

        .kop h3 {
            font-size: 12pt;
            margin: 0;
        }

        .kop p {
            font-size: 9pt;
            margin: 0;
        }

        .judul-laporan {
            text-align: center;
            margin: 20px 0;
        }

        .judul-laporan h2 {
            font-size: 14pt;
            margin: 0;
            text-decoration: underline;
        }

        .judul-laporan p {
            font-size: 10pt;
            margin: 5px 0 0 0;
        }

        .tabel-laporan {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-top: 15px;
        }

        .tabel-laporan th,
        .tabel-laporan td {
            border: 1px solid #000;
            padding: 5px 8px;
        }

        .tabel-laporan th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .tabel-laporan td {
            vertical-align: top;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .tabel-laporan tfoot td {
            font-weight: bold;
            background-color: #f0f0f0;
        }

        .footer-info {
            margin-top: 30px;
            font-size: 9pt;
        }

        .tanda-tangan {
            margin-top: 30px;
            width: 100%;
        }

        .tanda-tangan td {
            width: 33%;
            text-align: center;
            vertical-align: top;
            padding: 10px;
        }

        .tanda-tangan .nama {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 60px;
        }
    </style>
    <link rel="icon" href="<?= base_url('assets') ?>/images/logo/sikoper.png">
</head>

<body>
    <div class="content-wrapper">
        <img class="logo-left" src="<?= base_url('assets') ?>/images/logo/desa-culik.jpg" alt="logo-desa">
        <img class="logo-right" src="<?= base_url('assets') ?>/images/logo/koperasi.jpg" alt="logo-koperasi">
        
        <div class="kop">
            <h2>USAHA SIMPAN PINJAM BALI SEJAHTERA</h2>
            <h3>DESA ADAT CULIK</h3>
            <p>Jln. Ketut Natih, Br Dinas Gerit, Desa Culik, Kec. Abang, Kab. Karangasem</p>
        </div>

        <div class="judul-laporan">
            <h2>LAPORAN BUNGA <?= strtoupper($tipe) ?></h2>
            <p>Periode: <?= $periode ?></p>
        </div>

        <table class="tabel-laporan">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="15%">Tanggal</th>
                    <th width="15%">No. Rekening</th>
                    <th width="25%">Nama Nasabah</th>
                    <th width="15%">Bunga (%)</th>
                    <th width="15%">Jumlah Bunga</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($list)): ?>
                    <?php $no = 0; foreach ($list as $row): $no++; ?>
                    <tr>
                        <td class="text-center"><?= $no ?></td>
                        <td class="text-center"><?= date('d-m-Y', strtotime($row->tanggal_transaksi)) ?></td>
                        <td><?= $row->no_rekening ?></td>
                        <td><?= $row->nama_lengkap ?></td>
                        <td class="text-center"><?= number_format($row->rate_bunga, 2, ',', '.') ?>%</td>
                        <td class="text-right">Rp <?= number_format($row->jumlah_transaksi, 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada data bunga pada periode ini</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-right">TOTAL BUNGA:</td>
                    <td class="text-right">Rp <?= number_format($total_bunga, 0, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="footer-info">
            <p>Dicetak pada: <?= $tanggal_cetak ?></p>
        </div>

        <table class="tanda-tangan">
            <tr>
                <td></td>
                <td></td>
                <td>
                    <p>Culik, <?= $tanggal_cetak ?></p>
                    <p>Petugas,</p>
                    <p class="nama">____________________</p>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
