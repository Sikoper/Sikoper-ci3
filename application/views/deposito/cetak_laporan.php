<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Laporan Rekening Koran</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        h2,
        h4,
        p {
            margin: 0 0 10px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #dee2e6;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #f8f9fa;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: bold;
            border-radius: 6px;
            color: white;
        }

        .badge-setor {
            background-color: #28a745;
        }

        .badge-tarik {
            background-color: #dc3545;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>

<body>
    <h2>Laporan Rekening Tabungan</h2>
    <h4>atas nama <?= $nasabah->nama_lengkap ?> dengan nomor rekening (<?= $tabungan->no_rekening ?>)</h4>
    <?php if (!empty($tanggal_mulai) && !empty($tanggal_akhir)) : ?>
        <p>Periode: <?= date('d-m-Y', strtotime($tanggal_mulai)) ?> s/d <?= date('d-m-Y', strtotime($tanggal_akhir)) ?></p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Jumlah Uang</th>
                <th>Keterangan</th>
                <th>Pegawai</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; ?>
            <?php if (!empty($transaksi)) : ?>
                <?php foreach ($transaksi as $t) : ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= date('Y-m-d H:i:s', strtotime($t->tanggal)) ?></td>
                        <td class="text-right">Rp <?= number_format($t->jumlah, 2, ',', '.') ?></td>
                        <td>
                            <span class="badge <?= $t->keterangan === 'Setor' ? 'badge-setor' : 'badge-tarik' ?>">
                                <?= $t->keterangan ?>
                            </span>
                        </td>
                        <td><?= $t->pegawai ?? '-' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="5">Tidak ada data transaksi.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>

</html>