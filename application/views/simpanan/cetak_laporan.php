<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
        }

        h2 {
            margin: 0 0 15px 0; /* Added margin-bottom to h2 */
            padding: 0;
        }
        h4 {
            margin: 0 0 10px 0; /* Default margin-bottom for h4 */
            padding: 0;
        }
        p {
            margin: 0 0 15px 0; /* Added margin-bottom to paragraphs */
        }
        /* Specific overrides for h4 */
        h4:has(+ table) {
            margin-bottom: 10px; /* Margin before tables */
        }
        h4:not(:has(+ table)) {
            margin-bottom: 15px; /* Margin after totals */
        }
    </style>
</head>

<body>
    <h2>Laporan Simpanan</h2>
    <h4><?= $nasabah->nama_lengkap ?> (<?= $simpanan->no_rekening ?>)</h4>
    <?php if (!empty($tanggal_mulai) && !empty($tanggal_akhir)): ?>
        <p>Periode: <?= date('d-m-Y', strtotime($tanggal_mulai)) ?> s/d <?= date('d-m-Y', strtotime($tanggal_akhir)) ?></p>
    <?php endif; ?>

    <?php
    // Initialize totals to 0
    $total_setor = 0;
    $total_tarik = 0;
    ?>

    <?php if ($jenis_laporan == 1 || $jenis_laporan == 3): ?>
        <h4>Data Setoran</h4>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                // Ensure $setoran is an array before iterating
                if (is_array($setoran) || is_object($setoran)) {
                    foreach ($setoran as $s): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= date('d-m-Y', strtotime($s->tanggal_setoran)) ?></td>
                            <td>Rp <?= number_format($s->jumlah_setoran, 0, ',', '.') ?></td>
                        </tr>
                    <?php $total_setor += $s->jumlah_setoran;
                    endforeach;
                } else {
                    echo '<tr><td colspan="3">Tidak ada data setoran.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <h4>Total Setoran: Rp <?= number_format($total_setor, 0, ',', '.') ?></h4>
    <?php endif; ?>

    <?php if ($jenis_laporan == 2 || $jenis_laporan == 3): ?>
        <h4>Data Penarikan</h4>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                // Ensure $penarikan is an array before iterating
                if (is_array($penarikan) || is_object($penarikan)) {
                    foreach ($penarikan as $p): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= date('d-m-Y', strtotime($p->tanggal_penarikan)) ?></td>
                            <td>Rp <?= number_format($p->jumlah_penarikan, 0, ',', '.') ?></td>
                        </tr>
                    <?php $total_tarik += $p->jumlah_penarikan;
                    endforeach;
                } else {
                    echo '<tr><td colspan="3">Tidak ada data penarikan.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <h4>Total Penarikan: Rp <?= number_format($total_tarik, 0, ',', '.') ?></h4>
    <?php endif; ?>

    <?php if ($jenis_laporan == 3): ?>
        <h4>Saldo Akhir: Rp <?= number_format($total_setor - $total_tarik, 0, ',', '.') ?></h4>
    <?php endif; ?>
</body>

</html>