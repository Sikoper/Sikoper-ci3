<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Laporan Rekening Koran Deposito</title>
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
            padding: 3px 8px;
            font-size: 10px;
            font-weight: bold;
            border-radius: 4px;
            color: #fff;
            text-transform: capitalize;
        }

        .badge-setor {
            background-color: #28a745;
        }

        .badge-tarik {
            background-color: #dc3545;
        }

        /* NEW: Added style for interest badge */
        .badge-bunga {
            background-color: #17a2b8;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        .summary-details hr {
            border: 0;
            border-top: 1px solid #ccc;
            margin: 5px 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>Laporan Transaksi Deposito</h2>
            <p>LPD Desa Adat Culik</p>
        </div>

        <table class="account-details">
            <tr>
                <td width="15%"><strong>Nama Nasabah</strong></td>
                <td width="35%">: <?= htmlspecialchars($nasabah->nama_lengkap, ENT_QUOTES, 'UTF-8') ?></td>
                <td width="15%"><strong>Nomor Rekening</strong></td>
                <td width="35%">: <?= htmlspecialchars($deposito->no_rekening, ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
                <td><strong>Alamat</strong></td>
                <td>: <?= htmlspecialchars($nasabah->alamat, ENT_QUOTES, 'UTF-8') ?></td>
                <td><strong>Periode</strong></td>
                <?php
                $periode_mulai = !empty($tanggal_mulai) ? $tanggal_mulai : $deposito->tanggal_deposito;
                $periode_akhir = !empty($tanggal_akhir) ? $tanggal_akhir : date('Y-m-d');
                ?>
                <td>: <?= date('d M Y', strtotime($periode_mulai)) ?> s/d <?= date('d M Y', strtotime($periode_akhir)) ?></td>
            </tr>
        </table>

        <table class="transactions">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="15%">Tanggal</th>
                    <th>Keterangan</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Kredit</th>
                    <th>Pegawai</th>
                    <th class="text-right">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                $running_balance = $rekening['saldo_awal'];
                ?>
                <tr>
                    <td colspan="6"><strong>Saldo Awal Periode</strong></td>
                    <td class="text-right"><strong><?= number_format($rekening['saldo_awal'], 0, ',', '.') ?></strong></td>
                </tr>

                <?php if (!empty($rekening['transaksi'])) :
                    foreach ($rekening['transaksi'] as $t) :
                        $running_balance += ($t->kredit - $t->debit);

                        // FIX: Correctly determine badge class for each transaction type
                        $badge_class = '';
                        if ($t->jenis == 'setoran') {
                            $badge_class = 'badge-setor';
                        } elseif ($t->jenis == 'bunga') {
                            $badge_class = 'badge-bunga';
                        } elseif ($t->jenis == 'penarikan') {
                            $badge_class = 'badge-tarik';
                        }
                ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= date('d-m-Y', strtotime($t->tanggal)) ?></td>
                            <td><span class="badge <?= $badge_class ?>"><?= htmlspecialchars($t->keterangan, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="text-right"><?= ($t->debit > 0) ? number_format($t->debit, 0, ',', '.') : '-' ?></td>
                            <td class="text-right"><?= ($t->kredit > 0) ? number_format($t->kredit, 0, ',', '.') : '-' ?></td>
                            <td><?= htmlspecialchars($t->pegawai ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-right"><?= number_format($running_balance, 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding: 20px;">Tidak ada data transaksi pada periode ini.</td>
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
                    <!-- UPDATED: Changed to "Total Setoran Pokok" for clarity -->
                    <td><strong>Total Setoran Pokok</strong></td>
                    <td class="text-right">:</td>
                    <!-- Calculate principal deposit by subtracting interest from total credit -->
                    <td class="text-right"><?= number_format($rekening['total_setor'] - $rekening['total_bunga'], 0, ',', '.') ?></td>
                </tr>
                <!-- NEW: Display total interest separately -->
                <tr>
                    <td><strong>Total Bunga</strong></td>
                    <td class="text-right">:</td>
                    <td class="text-right"><?= number_format($rekening['total_bunga'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td><strong>Total Penarikan</strong></td>
                    <td class="text-right">:</td>
                    <td class="text-right"><?= number_format($rekening['total_tarik'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td colspan="3">
                        <hr>
                    </td>
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