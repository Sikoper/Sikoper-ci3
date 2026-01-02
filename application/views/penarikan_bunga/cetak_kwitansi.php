<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" href="<?= base_url('assets') ?>/images/logo/sikoper.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi Penarikan Bunga - <?= $no_rekening ?></title>
    <style>
        @page {
            size: 28cm 9cm;
            margin: 5mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 10px;
        }

        .kwitansi-container {
            width: 27cm;
            height: 8cm;
            border: 1px solid #000;
            padding: 8px 15px;
            display: flex;
            flex-direction: column;
        }

        .kwitansi-header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }

        .kwitansi-header .logo {
            width: 50px;
            height: 50px;
            margin-right: 15px;
        }

        .kwitansi-header .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .kwitansi-header .header-text {
            flex: 1;
        }

        .kwitansi-header h2 {
            margin: 0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .kwitansi-header h3 {
            margin: 2px 0 0;
            font-size: 10px;
            font-weight: normal;
        }

        .kwitansi-header .kwitansi-number {
            text-align: right;
            font-size: 9px;
        }

        .kwitansi-header .kwitansi-number strong {
            font-size: 11px;
        }

        .kwitansi-title {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            margin: 5px 0;
            text-decoration: underline;
        }

        .kwitansi-body {
            display: flex;
            flex: 1;
            gap: 15px;
        }

        .info-section {
            flex: 1;
        }

        .info-section table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-section td {
            padding: 2px 0;
            vertical-align: top;
            font-size: 10px;
        }

        .label {
            width: 100px;
            font-weight: bold;
        }

        .colon {
            width: 10px;
        }

        .amount-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .amount-box {
            background: #fff8e1;
            border: 2px solid #ff9800;
            padding: 8px;
            text-align: center;
            margin-bottom: 5px;
        }

        .amount-box .label-text {
            font-size: 9px;
            margin-bottom: 3px;
            color: #e65100;
        }

        .amount-box .amount {
            font-size: 18px;
            font-weight: bold;
            color: #e65100;
        }

        .amount-box .terbilang {
            font-size: 9px;
            font-style: italic;
            margin-top: 3px;
        }

        .sisa-bunga {
            text-align: center;
            font-size: 10px;
            padding: 5px;
            background: #e3f2fd;
            border: 1px solid #2196f3;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
            padding-top: 5px;
            border-top: 1px dashed #999;
        }

        .signature-box {
            text-align: center;
            width: 30%;
            font-size: 9px;
        }

        .signature-line {
            margin-top: 25px;
            border-top: 1px solid #000;
            padding-top: 3px;
        }

        .print-button {
            text-align: center;
            margin-top: 15px;
        }

        .print-button button {
            padding: 8px 20px;
            font-size: 12px;
            cursor: pointer;
            margin: 0 5px;
        }

        .badge-bunga {
            display: inline-block;
            background: #ff9800;
            color: #fff;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
            margin-left: 5px;
        }

        @media print {
            .print-button {
                display: none;
            }
            body {
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <div class="kwitansi-container">
        <div class="kwitansi-header">
            <div class="logo">
                <img src="<?= base_url('assets/images/logo/sikoper.png') ?>" alt="Logo">
            </div>
            <div class="header-text">
                <h2>Koperasi Simpan Pinjam</h2>
                <h3>Jl. Contoh Alamat No. 123, Kota | Telp: (0411) 123-4567</h3>
            </div>
            <div class="kwitansi-number">
                <strong>BUKTI PENARIKAN BUNGA</strong><span class="badge-bunga">DEPOSITO</span><br>
                No: <?= $no_kwitansi ?><br>
                <?= date('d/m/Y H:i', strtotime($tanggal_penarikan)) ?>
            </div>
        </div>

        <div class="kwitansi-body">
            <div class="info-section">
                <table>
                    <tr>
                        <td class="label">No. Rekening</td>
                        <td class="colon">:</td>
                        <td><strong><?= $no_rekening ?></strong></td>
                    </tr>
                    <tr>
                        <td class="label">Nama Nasabah</td>
                        <td class="colon">:</td>
                        <td><strong><?= $nama_nasabah ?></strong></td>
                    </tr>
                    <tr>
                        <td class="label">Jenis Rekening</td>
                        <td class="colon">:</td>
                        <td><?= $jenis_tabungan ?></td>
                    </tr>
                    <tr>
                        <td class="label">Keterangan</td>
                        <td class="colon">:</td>
                        <td>Penarikan Bunga Deposito</td>
                    </tr>
                </table>
            </div>

            <div class="amount-section">
                <div class="amount-box">
                    <div class="label-text">JUMLAH BUNGA DITARIK</div>
                    <div class="amount">Rp <?= number_format($jumlah_penarikan, 0, ',', '.') ?></div>
                    <div class="terbilang">(<?= ucwords($terbilang) ?>)</div>
                </div>
                <div class="sisa-bunga">
                    <strong>Sisa Bunga Tersedia:</strong> Rp <?= number_format($sisa_bunga, 0, ',', '.') ?>
                </div>
            </div>
        </div>

        <div class="signature-section">
            <div class="signature-box">
                Nasabah
                <div class="signature-line"><?= $nama_nasabah ?></div>
            </div>
            <div class="signature-box">
                Petugas
                <div class="signature-line"><?= $nama_pegawai ?></div>
            </div>
        </div>
    </div>

    <div class="print-button">
        <button onclick="window.print()">🖨️ Cetak Kwitansi</button>
        <button onclick="window.close()">✖️ Tutup</button>
    </div>
</body>

</html>
