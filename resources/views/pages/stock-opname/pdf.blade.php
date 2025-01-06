<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400..700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <!-- Styles -->
    @livewireStyles


    <title>FORM PENGAJUAN BAHAN</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }

        h2 {
            margin: 0;
            padding: 0;
        }
        a {
            text-decoration: none;
            color: inherit;
        }
        h3 {
            text-align: center;
            margin: 0;
            padding-top: 10;
            font-size: 16px;
            font-weight: bold;
        }
        .line-through {
            text-decoration: line-through;
            color: red; /* Atau sesuaikan dengan warna yang diinginkan */
        }

    </style>
</head>
<body>
    <table>
        <tr>
            <th style="text-align: left;vertical-align: top;width: 30%;">
                <img style="display: block;max-width: 250px;height: auto;" src="{{ public_path('images/Picture.png') }}" alt="Logo">
            </th>
            <td style="border-bottom: 2px solid black;">
                <h2>PT. ARTA TEKNOLOGI COMUNINDO</h2>
                <p>Perum Pesona Bandara C-5 Juwangen Purwomartani, Kalasan, Sleman, Daerah Istimewa Yogyakarta
                Ph./Fax. (0274) 4986899 Website: <a href="https://www.be-jogja.com" target="_blank">https://www.be-jogja.com</a>
                </p>
            </td>
        </tr>
    </table>

    <!-- Centered H3 -->
    <h3 class="pt-6">FORM PENGAJUAN STOCK OPNAME PT. ARTA TEKNOLOGI COMUNINDO</h3>

    <table style="border: 0px solid black;width: 100%;border-collapse: collapse;padding-top:10;">
        <tr style="text-align: left;vertical-align: top;">
            <td style="border: 0px solid black;width: 25%;"><strong>Nomor Referensi</strong></td>
            <td style="border: 0px solid black">: {{ $stockOpname->nomor_referensi }}</td>
        </tr>
        <tr>
            <td style="border: 0px solid black;"><strong>Tanggal</strong></td>
            <td style="border: 0px solid black">: {{ $formattedDate }}</td>
        </tr>
        <tr>
            <td style="border: 0px solid black;"><strong>Keterangan</strong></td>
            <td style="border: 0px solid black">: {{ $stockOpname->keterangan }}</td>
        </tr>
    </table>

    <table style="border: 0px solid black;width: 100%;border-collapse: collapse;padding-top:10;">
        <thead style="background-color:#d9dddc;">
            <tr>
                <th style="border: 0px solid black;">Kode</th>
                <th style="border: 0px solid black;">Nama Barang</th>
                <th style="border: 0px solid black;">Satuan</th>
                <th style="border: 0px solid black;">Tersedia (Sistem)</th>
                <th style="border: 0px solid black;">Tersedia (Fisik)</th>
                <th style="border: 0px solid black;">Selisih</th>
                <th style="border: 0px solid black;">Harga Satuan</th>
                <th style="border: 0px solid black;width: 10%;">Total Harga</th>
                <th style="border: 0px solid black;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($stockOpname->stockOpnameDetails as $index => $detail)
                <tr>
                    <td style="border:0px solid black; text-align: left;">{{ $detail->dataBahan->kode_bahan }}</td>
                    <td style="border:0px solid black">{{ $detail->dataBahan->nama_bahan }}</td>
                    <td style="border:0px solid black;text-align: center;">{{ $detail->dataBahan->dataUnit->nama }}</td>
                    <td style="border:0px solid black;text-align: center;">{{ $detail->tersedia_sistem }}</td>
                    <td style="border:0px solid black;text-align: center;">{{ $detail->tersedia_fisik }}</td>
                    <td style="border:0px solid black;text-align: center;">{{ $detail->selisih }}</td>
                    <td style="border: 0px solid black;text-align: right;">{{ $detail->alokasi_harga }}</td>
                    <td style="border: 0px solid black;text-align: right;">{{ number_format($detail->total_harga, 0, ',', '.') }}</td>
                    <td style="border: 0px solid black">{{ $detail->keterangan }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot style="background-color:#d9dddc;">
            <tr>
                <th colspan="5" style="border: 0px solid black;text-align: right;">Total</th>
                <th style="border: 0px solid black;">{{ $totalSelisih }}</th>
                <th style="border: 0px solid black;"></th>
                <th style="border: 0px solid black;width: 10%;text-align: right;">{{ number_format($totalHargaAll, 0, ',', '.') }}</th>
                <th style="border: 0px solid black;"></th>
            </tr>
        </tfoot>
    </table>

    <table style="border: 0px solid black;width: 100%;border-collapse: collapse;padding-top:30;">
        <tr style="border: 0px solid black;text-align: left; vertical-align: top;">
            <td colspan="2" style="border: 0px solid black; text-align: center;"><strong>Pengaju</strong></td>
            <td colspan="2" style="border: 0px solid black; text-align: center;"><strong>Manager</strong></td>
        </tr>
        <tr style="border: 0px solid black;">
            <td colspan="2" style="border: 0px solid black;text-align: center; width: 25%;">
                <div style="height: 80px; width: 150px;"></div>
            </td>
            <td colspan="2" style="border: 0px solid black;text-align: center; width: 25%;">
            </td>
        </tr>

        <tr style="border: 0px solid black;text-align: left; vertical-align: top;">
            <td colspan="2" style="border: 0px solid black;text-align: center;">
                {{ $stockOpname->pengajuUser->name }}
            </td>
            <td colspan="2" style="border: 0px solid black;text-align: center;">
                {{ $managerName ?? '' }}
            </td>
        </tr>


        <tr style="border: 0px solid black;">
            <td colspan="4"  style="border: 0px solid black;text-align: center;padding:7;"><strong> Mengetahui, </strong></td>
        </tr>
        <tr style="border: 0px solid black;text-align: left; vertical-align: top;">
            <td colspan="2" style=" border: 0px solid black;text-align: center;"><strong>Manager Admin</strong></td>
            <td colspan="2" style=" border: 0px solid black;text-align: center;"><strong>Direktur</strong></td>
        </tr>
        <tr>
            <td colspan="2" style="border: 0px solid black;text-align: center;">
                <div style="height: 80px; width: 150px;"></div>
            </td>
            <td colspan="2"style="border: 0px solid black;text-align: center; width: 33%;">
                <div style="height: 80px; width: 150px;"></div>
            </td>
        </tr>
        <tr style="border: 0px solid black;text-align: left; vertical-align: top;">
            <td colspan="2" style="border: 0px solid black;text-align: center;">{{$adminManagerceUser->name}}</td>
            <td colspan="2" style="border: 0px solid black;text-align: center;">{{$direkturName}}</td>
        </tr>
    </table>

    @livewireScripts
</body>
</html>
