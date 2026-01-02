<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Rekening Koran Deposito - <?= htmlspecialchars($nasabah->nama_lengkap, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.2cm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 9pt;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .content-wrapper {
            padding: 0.3cm;
            box-sizing: border-box;
        }

        .logo-left {
            position: absolute;
            width: 55px;
            left: 25px;
            top: 15px;
            height: 55px;
        }

        .logo-right {
            position: absolute;
            right: 25px;
            top: 15px;
            width: 55px;
            height: 55px;
        }

        .kop {
            text-align: center;
            line-height: 1.2;
            margin-bottom: 8px;
            border-bottom: 3px double #000;
            padding-bottom: 8px;
        }

        .kop h2 {
            font-size: 13pt;
            margin: 0;
        }

        .kop h3 {
            font-size: 11pt;
            margin: 0;
        }

        .kop p {
            font-size: 8pt;
            margin: 0;
        }

        .judul-laporan {
            text-align: center;
            margin: 12px 0 10px 0;
        }

        .judul-laporan h2 {
            font-size: 12pt;
            margin: 0;
            text-decoration: underline;
        }

        .judul-laporan p {
            font-size: 9pt;
            margin: 3px 0 0 0;
        }

        .account-details {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
            font-size: 9pt;
        }

        .account-details td {
            padding: 2px 4px;
            vertical-align: top;
        }

        table.transactions {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 8pt;
        }

        .transactions th,
        .transactions td {
            border: 1px solid #333;
            padding: 3px 5px;
            text-align: left;
            vertical-align: middle;
        }

        .transactions th {
            background-color: #e9e9e9;
            font-weight: bold;
            text-align: center;
            font-size: 8pt;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Type indicator - compact text style */
        .type-indicator {
            display: inline-block;
            padding: 1px 4px;
            font-size: 7pt;
            font-weight: 600;
            border-radius: 2px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: center;
        }

        .keterangan-cell {
            text-align: center;
        }

        .type-setor {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .type-tarik {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .type-bunga {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .type-bunga-tarik {
            background-color: #e2e3e5;
            color: #383d41;
            border: 1px solid #d6d8db;
        }

        .summary-section {
            margin-top: 15px;
            width: 100%;
        }

        .summary-table {
            width: 50%;
            float: right;
            border-collapse: collapse;
            font-size: 8pt;
        }

        .summary-table td {
            padding: 2px 6px;
        }

        .summary-table .label {
            font-weight: bold;
        }

        .summary-table .deduction {
            color: #721c24;
        }

        .summary-table .total-row td {
            border-top: 1px solid #000;
            padding-top: 4px;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        .footer-info {
            margin-top: 20px;
            font-size: 8pt;
            clear: both;
        }

        .tanda-tangan {
            margin-top: 25px;
            width: 100%;
            font-size: 9pt;
        }

        .tanda-tangan td {
            width: 33%;
            text-align: center;
            vertical-align: top;
            padding: 8px;
        }

        .tanda-tangan .nama {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 50px;
        }

        .tanda-tangan .jabatan {
            font-size: 8pt;
        }

        .saldo-awal-row td {
            background-color: #f8f9fa;
            font-weight: bold;
        }
    </style>
    <link rel="icon" href="<?= base_url('assets') ?>/images/logo/sikoper.png">
</head>
<?php
$formatter = new \IntlDateFormatter('id_ID', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE);
$formatter->setPattern('d MMMM yyyy');
$tanggal_cetak = $formatter->format(new DateTime());

$periode_mulai = !empty($tanggal_mulai) ? $tanggal_mulai : $deposito->tanggal_deposito;
$periode_akhir = !empty($tanggal_akhir) ? $tanggal_akhir : date('Y-m-d');
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
            <h2>LAPORAN REKENING KORAN DEPOSITO</h2>
            <p>Periode: <?= date('d M Y', strtotime($periode_mulai)) ?> s/d <?= date('d M Y', strtotime($periode_akhir)) ?></p>
        </div>

        <table class="account-details">
            <tr>
                <td width="15%"><strong>Nama Nasabah</strong></td>
                <td width="35%">: <?= htmlspecialchars($nasabah->nama_lengkap, ENT_QUOTES, 'UTF-8') ?></td>
                <td width="15%"><strong>No. Rekening</strong></td>
                <td width="35%">: <?= htmlspecialchars($deposito->no_rekening, ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
                <td><strong>Alamat</strong></td>
                <td colspan="3">: <?= htmlspecialchars($nasabah->alamat, ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        </table>

        <table class="transactions">
            <thead>
                <tr>
                    <th width="4%">No</th>
                    <th width="12%">Tanggal</th>
                    <th width="20%">Keterangan</th>
                    <th width="16%" class="text-right">Debit</th>
                    <th width="16%" class="text-right">Kredit</th>
                    <th width="14%">Pegawai</th>
                    <th width="16%" class="text-right">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                $running_balance = $rekening['saldo_awal'];
                ?>
                <tr class="saldo-awal-row">
                    <td colspan="6">Saldo Awal Periode</td>
                    <td class="text-right">Rp <?= number_format($rekening['saldo_awal'], 0, ',', '.') ?></td>
                </tr>

                <?php if (!empty($rekening['transaksi'])) :
                    foreach ($rekening['transaksi'] as $t) :
                        $running_balance += ($t->kredit - $t->debit);

                        $type_class = 'type-setor';
                        $type_label = 'SETOR';
                        if ($t->jenis == 'setoran') {
                            $type_class = 'type-setor';
                            $type_label = 'SETOR';
                        } elseif ($t->jenis == 'bunga') {
                            $type_class = 'type-bunga';
                            $type_label = 'BUNGA';
                        } elseif ($t->jenis == 'penarikan') {
                            $type_class = 'type-tarik';
                            $type_label = 'TARIK';
                        } elseif ($t->jenis == 'penarikan_bunga') {
                            $type_class = 'type-bunga-tarik';
                            $type_label = 'TARIK BUNGA';
                        }
                ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td class="text-center"><?= date('d-m-Y', strtotime($t->tanggal)) ?></td>
                            <td style="text-align: center;"><span class="type-indicator <?= $type_class ?>"><?= $type_label ?></span></td>
                            <td class="text-right"><?= ($t->debit > 0) ? number_format($t->debit, 0, ',', '.') : '-' ?></td>
                            <td class="text-right"><?= ($t->kredit > 0) ? number_format($t->kredit, 0, ',', '.') : '-' ?></td>
                            <td><?= htmlspecialchars($t->pegawai ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-right"><?= number_format($running_balance, 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 15px;">Tidak ada data transaksi pada periode ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="summary-section clearfix">
            <table class="summary-table">
                <tr>
                    <td class="label">Saldo Awal</td>
                    <td class="text-right">:</td>
                    <td class="text-right"><?= number_format($rekening['saldo_awal'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td class="label">Setoran Pokok</td>
                    <td class="text-right">:</td>
                    <td class="text-right"><?= number_format($rekening['total_setor'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td class="label">Bunga Diterima</td>
                    <td class="text-right">:</td>
                    <td class="text-right"><?= number_format($rekening['total_bunga'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td class="label">Bunga Ditarik</td>
                    <td class="text-right">:</td>
                    <td class="text-right deduction">(<?= number_format($rekening['total_bunga_ditarik'] ?? 0, 0, ',', '.') ?>)</td>
                </tr>
                <tr>
                    <td class="label">Penarikan Pokok</td>
                    <td class="text-right">:</td>
                    <td class="text-right deduction">(<?= number_format($rekening['total_tarik'], 0, ',', '.') ?>)</td>
                </tr>
                <tr class="total-row">
                    <td class="label"><strong>SALDO AKHIR</strong></td>
                    <td class="text-right">:</td>
                    <td class="text-right"><strong><?= number_format($rekening['saldo_akhir'], 0, ',', '.') ?></strong></td>
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
                    <p class="nama" style="margin-top: 50px;"><?= htmlspecialchars($nasabah->nama_lengkap, ENT_QUOTES, 'UTF-8') ?></p>
                </td>
                <td></td>
                <td>
                    <p>Culik, <?= $tanggal_cetak ?></p>
                    <p class="jabatan">Petugas,</p>
                    <p class="nama" style="margin-top: 35px;">____________________</p>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>