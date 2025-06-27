<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Laporan Rekening Koran</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #333;
        }

        .container {
            width: 100%;
            margin: 0 auto;
        }

        .header,
        .footer {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h2 {
            margin: 0;
            padding: 0;
            font-size: 18px;
        }

        .header p {
            margin: 2px 0;
            font-size: 12px;
        }

        .account-details,
        .summary-details {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }

        .account-details td,
        .summary-details td {
            padding: 4px;
            vertical-align: top;
        }

        table.transactions {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .transactions th,
        .transactions td {
            border: 1px solid #999;
            padding: 5px;
            text-align: left;
            vertical-align: top;
        }

        .transactions th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .footer-summary {
            margin-top: 20px;
            float: right;
            width: 45%;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 10px;
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
            background-color: rgb(90, 90, 90);
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">
            <h2>Laporan Transaksi</h2>
            <p>LPD Desa Adat Culik</p>
        </div>

        <table class="account-details">
            <tr>
                <td width="15%"><strong>Nama Nasabah</strong></td>
                <td width="35%">: <?= htmlspecialchars($nasabah->nama_lengkap, ENT_QUOTES, 'UTF-8') ?></td>
                <td width="15%"><strong>Nomor Rekening</strong></td>
                <td width="35%">: <?= htmlspecialchars($tabungan->no_rekening, ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
                <td><strong>Alamat</strong></td>
                <td>: <?= htmlspecialchars($nasabah->alamat, ENT_QUOTES, 'UTF-8') ?></td>
                <td><strong>Periode</strong></td>
                <td>: <?= date('d M Y', strtotime($tanggal_mulai)) ?> s/d <?= date('d M Y', strtotime($tanggal_akhir)) ?></td>
            </tr>
        </table>

        <table class="transactions">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="15%">Tanggal</th>
                    <th class="text-right">Jumlah</th>
                    <th>Keterangan</th>
                    <th>Pegawai</th>
                    <th class="text-right">Saldo</th>
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

                        if ($t->keterangan == 'Setoran Tunai') {
                            $jenis = 'Setor';
                            $badge_class = 'badge-setor';
                        } elseif ($t->keterangan == 'Penarikan Tunai') {
                            $jenis = 'Tarik';
                            $badge_class = 'badge-tarik';
                        } elseif (strpos($t->keterangan, 'Bunga') !== false) {
                            $jenis = 'Bunga';
                            $badge_class = 'badge-bunga'; // class CSS sudah Anda definisikan
                        }
                        $jumlah = ($t->kredit > 0) ? $t->kredit : $t->debit;
                ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= date('d-m-Y', strtotime($t->tanggal)) ?></td>
                            <td class="text-right">Rp. <?= number_format($jumlah, 0, ',', '.') ?></td>
                            <td><span class="badge <?= $badge_class ?>"><?= $jenis ?></span></td>
                            <td><?= htmlspecialchars($t->pegawai) ?></td>
                            <td class="text-right"><?= number_format($running_balance, 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding: 20px;">Tidak ada data transaksi pada periode ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="footer-summary clearfix">
            <table class="summary-details">
                <tr>
                    <td><strong>Saldo Awal</strong></td>
                    <td class="text-right">:</td>
                    <td class="text-right" style="font-weight: bold;"><?= number_format($rekening['saldo_awal'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td><strong>Total Setoran</strong></td>
                    <td class="text-right">:</td>
                    <td class="text-right"><?= number_format($rekening['total_setor'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td><strong>Total Penarikan</strong></td>
                    <td class="text-right">:</td>
                    <td class="text-right"><?= number_format($rekening['total_tarik'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td><strong>SALDO AKHIR</strong></td>
                    <td class="text-right">:</td>
                    <td class="text-right"><strong><?= number_format($rekening['saldo_akhir'], 0, ',', '.') ?></strong></td>
                </tr>
            </table>
        </div>
    </div>

</body>

</html>