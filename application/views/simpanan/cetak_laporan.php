<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Rekening Koran - <?= htmlspecialchars($nasabah->nama_lengkap, ENT_QUOTES, 'UTF-8') ?></title>
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
            color: #333;
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
            margin: 20px 0 15px 0;
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

        .account-details {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
            font-size: 10pt;
        }

        .account-details td {
            padding: 4px;
            vertical-align: top;
        }

        table.transactions {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 9pt;
        }

        .transactions th,
        .transactions td {
            border: 1px solid #000;
            padding: 5px 8px;
            text-align: left;
            vertical-align: top;
        }

        .transactions th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .summary-section {
            margin-top: 20px;
            width: 100%;
        }

        .summary-table {
            width: 45%;
            float: right;
            border-collapse: collapse;
            font-size: 10pt;
        }

        .summary-table td {
            padding: 4px 8px;
        }

        .summary-table .label {
            font-weight: bold;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 9pt;
            font-weight: bold;
            border-radius: 4px;
            color: #fff;
        }

        .badge-setor {
            background-color: #28a745;
        }

        .badge-tarik {
            background-color: #dc3545;
        }

        .badge-bunga {
            background-color: #6c757d;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        .footer-info {
            margin-top: 25px;
            font-size: 9pt;
            clear: both;
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

        .tanda-tangan .jabatan {
            font-size: 9pt;
        }
    </style>
    <link rel="icon" href="<?= base_url('assets') ?>/images/logo/sikoper.png">
</head>
<?php
$formatter = new \IntlDateFormatter('id_ID', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE);
$formatter->setPattern('d MMMM yyyy');
$tanggal_cetak = $formatter->format(new DateTime());
?>

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
            <h2>LAPORAN REKENING KORAN</h2>
            <p>Periode: <?= date('d M Y', strtotime($tanggal_mulai)) ?> s/d <?= date('d M Y', strtotime($tanggal_akhir)) ?></p>
        </div>

        <table class="account-details">
            <tr>
                <td width="18%"><strong>Nama Nasabah</strong></td>
                <td width="32%">: <?= htmlspecialchars($nasabah->nama_lengkap, ENT_QUOTES, 'UTF-8') ?></td>
                <td width="18%"><strong>Nomor Rekening</strong></td>
                <td width="32%">: <?= htmlspecialchars($tabungan->no_rekening, ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
                <td><strong>Alamat</strong></td>
                <td colspan="3">: <?= htmlspecialchars($nasabah->alamat, ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        </table>

        <table class="transactions">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="12%">Tanggal</th>
                    <th width="18%" class="text-right">Jumlah</th>
                    <th width="15%">Keterangan</th>
                    <th width="20%">Pegawai</th>
                    <th width="18%" class="text-right">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                $running_balance = $rekening['saldo_awal'];
                if (!empty($rekening['transaksi'])) :
                    foreach ($rekening['transaksi'] as $t) :
                        $running_balance += ($t->kredit - $t->debit);
                        $jenis = 'Bunga';
                        $badge_class = 'badge-bunga';

                        if ($t->keterangan == 'Setoran Tunai') {
                            $jenis = 'Setor';
                            $badge_class = 'badge-setor';
                        } elseif ($t->keterangan == 'Penarikan Tunai') {
                            $jenis = 'Tarik';
                            $badge_class = 'badge-tarik';
                        } elseif (strpos($t->keterangan, 'Bunga') !== false) {
                            $jenis = 'Bunga';
                            $badge_class = 'badge-bunga';
                        }
                        $jumlah = ($t->kredit > 0) ? $t->kredit : $t->debit;
                ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td class="text-center"><?= date('d-m-Y', strtotime($t->tanggal)) ?></td>
                            <td class="text-right">Rp <?= number_format($jumlah, 0, ',', '.') ?></td>
                            <td class="text-center"><span class="badge <?= $badge_class ?>"><?= $jenis ?></span></td>
                            <td><?= htmlspecialchars($t->pegawai) ?></td>
                            <td class="text-right">Rp <?= number_format($running_balance, 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6" class="text-center" style="padding: 20px;">Tidak ada data transaksi pada periode ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="summary-section clearfix">
            <table class="summary-table">
                <tr>
                    <td class="label">Saldo Awal</td>
                    <td class="text-right">:</td>
                    <td class="text-right">Rp <?= number_format($rekening['saldo_awal'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td class="label">Total Setoran</td>
                    <td class="text-right">:</td>
                    <td class="text-right">Rp <?= number_format($rekening['total_setor'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td class="label">Total Penarikan</td>
                    <td class="text-right">:</td>
                    <td class="text-right">Rp <?= number_format($rekening['total_tarik'], 0, ',', '.') ?></td>
                </tr>
                <tr style="border-top: 1px solid #000;">
                    <td class="label"><strong>SALDO AKHIR</strong></td>
                    <td class="text-right">:</td>
                    <td class="text-right"><strong>Rp <?= number_format($rekening['saldo_akhir'], 0, ',', '.') ?></strong></td>
                </tr>
            </table>
        </div>

        <div class="footer-info">
            <p>Dicetak pada: <?= $tanggal_cetak ?></p>
        </div>

        <table class="tanda-tangan">
            <tr>
                <td>
                    <p>Nasabah,</p>
                    <p class="nama" style="margin-top: 60px;"><?= htmlspecialchars($nasabah->nama_lengkap, ENT_QUOTES, 'UTF-8') ?></p>
                </td>
                <td></td>
                <td>
                    <p>Culik, <?= $tanggal_cetak ?></p>
                    <p class="jabatan">Petugas,</p>
                    <p class="nama" style="margin-top: 40px;">____________________</p>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>