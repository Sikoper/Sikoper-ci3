<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Nasabah</title>
    <style>
        @page {
            margin: 10px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            margin: 0;
            padding: 0;
        }

        .info-container {
            border: 1px solid #000;
            padding: 5px;
            width: 100%;
            border-collapse: collapse;
        }

        .info-container td {
            padding: 2px 4px;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            width: 35%;
        }

        .colon {
            width: 5%;
            text-align: center;
        }

        .value {
            text-align: start;
            width: 50%;
        }

        .title {
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
    </style>

</head>

<body>
    <table class="info-container">
        <tr>
            <td class="label">Nama</td>
            <td class="colon">:</td>
            <td class="value"><?= $nama_nasabah ?></td>
        </tr>
        <tr>
            <td class="label">No. Rekening</td>
            <td class="colon">:</td>
            <td class="value"><?= $no_rekening ?></td>
        </tr>
        <tr>
            <td class="label">Jenis Rekening</td>
            <td class="colon">:</td>
            <td class="value"><?= $jenis_tabungan ?></td>
        </tr>
        <tr>
            <td class="label">Tanggal Dibuat</td>
            <td class="colon">:</td>
            <td class="value"><?= date('d/m/Y', strtotime($tanggal_simpanan)) ?></td>
        </tr>
    </table>
</body>

</html>