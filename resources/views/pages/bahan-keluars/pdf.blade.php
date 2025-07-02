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
            font-size: 18px;
            text-transform: uppercase;
        }
        h3 {
            text-align: center;
            margin: 0;
            padding-top: 10px;
            font-size: 16px;
            font-weight: bold;
        }

        a {
            text-decoration: none;
            color: inherit;
        }
    </style>
</head>
<body>
    <table style=" width: 100%;border-collapse: collapse;">
        <tr>
            <th style="text-align: left;vertical-align: top;width: 30%;">
                <img style="display: block;max-width: 100%;height: auto;" src="{{ public_path('images/Picture.png') }}" alt="Logo">
            </th>
            <td style="border-bottom: 2px solid black;vertical-align: top;padding: 5px;text-align: left;">
                <h2 style="font-size: 18px;">PT. ARTA TEKNOLOGI COMUNINDO</h2>
                <p style="margin: 5px 0;line-height: 1.5;font-size: 12px;">Kadirojo I, Purwomartani, Kalasan, Sleman, Daerah Istimewa Yogyakarta <br> Ph./Fax. (0274) 4986899 Website: <a href="https://www.be-jogja.com" target="_blank">https://www.be-jogja.com</a>
                </p>
            </td>
        </tr>
    </table>

    <!-- Centered H3 -->
    <h3 class="pt-6">FORM PENGAJUAN PENGAMBILAN BARANG PT. ARTA TEKNOLOGI COMUNINDO</h3>

    <table style="border: 1px solid black;width: 100%;border-collapse: collapse;padding-top:10;">
        <tr style="text-align: left;vertical-align: top;">
            <td style="border: 1px solid black;width: 30%;"><strong>Kode Transaksi</strong></td>
            <td style="border: 1px solid black">: {{ $bahanKeluar->kode_transaksi }}</td>
        </tr>
        <tr style="text-align: left;vertical-align: top;">
            <td style="border: 1px solid black;width: 30%;"><strong>Divisi</strong></td>
            <td style="border: 1px solid black">: {{ $bahanKeluar->divisi }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid black;"><strong>Project</strong></td>
            <td style="border: 1px solid black">: {{ $bahanKeluar->tujuan }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid black;"><strong>Keterangan</strong></td>
            <td style="border: 1px solid black">: {{ $bahanKeluar->keterangan }}</td>
        </tr>
    </table>

    <table style="border: 1px solid black;width: 100%;border-collapse: collapse;padding-top:10;">
        <thead>
            <tr>
                <th style="border: 1px solid black; width: 5%;">No</th>
            <th style="border: 1px solid black; width: 10%;">Kode Barang</th>
            <th style="border: 1px solid black; width: 25%;">Nama</th> <!-- Kolom lebih lebar -->
            <th style="border: 1px solid black; width: 25%;">Penempatan</th> <!-- Kolom lebih lebar -->
            <th style="border: 1px solid black; width: 5%;">Qty</th>
            <th style="border: 1px solid black; width: 10%;">Satuan</th>
            <th style="border: 1px solid black; width: 10%;">Ceklis Purchasing</th>
            <th style="border: 1px solid black; width: 10%;">Ceklis Pengambil</th>
            <th style="border: 1px solid black; width: 25%;">Kondisi Barang</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalSubTotal = 0;
            @endphp
            @foreach ($bahanKeluar->bahanKeluarDetails as $index => $detail)
                @php
                    $details = json_decode($detail->details, true); // Decode JSON details column
                @endphp
                <tr>
                    <td style="border: 1px solid black; text-align: center;">{{ $index + 1 }}</td>
                    <td style="border: 1px solid black">{{ $detail->dataBahan->kode_bahan ?? null }}</td>
                    <td style="border: 1px solid black">
                        @if ($detail->dataBahan)
                            {{ $detail->dataBahan->nama_bahan }}
                        @elseif ($detail->dataProduk)
                            {{ $detail->dataProduk->nama_bahan }}
                            ({{ $detail->serial_number ?? 'N/A' }})
                        @else
                            Data tidak tersedia
                        @endif
                    </td>
                    <td style="border: 1px solid black;text-align: center;">{{ $detail->dataBahan->penempatan ?? null }}</td>
                    <td style="border: 1px solid black;text-align: center;">{{ $detail->qty }}</td>
                    <td style="border: 1px solid black;text-align: center;">{{ $detail->dataBahan->dataUnit->nama ?? 'Pcs' }}</td>
                    <td style="border: 1px solid black"></td>
                    <td style="border: 1px solid black"></td>
                    <td style="border: 1px solid black"></td>
                </tr>
                @php
                    $totalSubTotal += $detail->qty;
                @endphp
            @endforeach
            <tr>
                <td style="border: 1px solid black; text-align: left;"></td>
                <td style="border: 1px solid black; text-align: left;"></td>
                <td colspan="2" style="border: 1px solid black; text-align: right; font-weight: bold;"> Total Pengeluaran Barang </td>
                <td style="border: 1px solid black; text-align: center; border-right: none;">
                    {{ $totalSubTotal }}
                </td>
                <td colspan="4" style="border: 1px solid black; text-align: right; font-weight: bold;"> </td>
            </tr>
        </tbody>

    </table>

    <table style="width: 100%;border-collapse: collapse;padding-top:10;">
        <tr style="text-align: left; vertical-align: top;">
            <td style=" text-align: center;"><strong>Pengambil</strong></td>
            <td colspan="2" style=" text-align: center;"><strong>Purchasing</strong></td>
            <td style=" text-align: center;"><strong>Leader</strong></td>
        </tr>
        <tr>
            <td style="text-align: center; width: 25%;">
                <div style="height: 80px; width: 150px;"></div>
            </td>
            <td colspan="2" style="text-align: center; width: 25%;">
                <div style="height: 80px; width: 150px;"></div>
            </td>
            <td style="text-align: center; width: 25%;">
                <div style="height: 80px; width: 150px;"></div>
            </td>
        </tr>

        <tr style="text-align: left; vertical-align: top;">
            <td style="text-align: center;">
                {{ $bahanKeluar->dataUser->name ?? null }}
            </td>
            <td colspan="2" style="text-align: center;">{{ $purchasingUser->name }}</td>
            <td style="text-align: center;">{{ $leaderName ?? '' }}</td>
        </tr>


        <tr>
            <td colspan="4"  style="text-align: center;padding:7;"><strong> Mengetahui, </strong></td>
        </tr>
        <tr style="text-align: left; vertical-align: top;">
            <td style="text-align: center;">
                {{-- @if($hasProduk)
                    <strong>Produksi</strong>
                @endif --}}
            </td>
            <td colspan="2" style=" text-align: center;"><strong>Manager Admin</strong></td>
            <td style=" text-align: center;"><strong></strong></td>
        </tr>
        <tr>
            <td style="text-align: center; width: 33%;">
                <div style="height: 80px; width: 150px;"></div>
            </td>
            <td colspan="2" style="text-align: center; width: 33%;">
                <div style="height: 80px; width: 150px;"></div>
            </td>
            <td style="text-align: center; width: 33%;">
                <div style="height: 80px; width: 150px;"></div>
            </td>
        </tr>
        <tr style="text-align: left; vertical-align: top;">
            <td style="text-align: center;">
                {{-- @if($hasProduk)
                    RHOMADONI
                @endif --}}
            </td>
            <td colspan="2" style="text-align: center;">{{ $adminManagerceUser->name ?? '' }}</td>
            <td style="text-align: center;"></td>
        </tr>
        <tr>
            <td colspan="4"  style="text-align: center;padding:7;"><strong>  </strong></td>
        </tr>
        <tr style="text-align: left; vertical-align: top;">
            <td style="text-align: center;"></td>
            <td style="text-align: center;"></td>
            <td colspan="2" style="text-align: center;">Tgl Pengambilan: </td>
        </tr>
    </table>

    @livewireScripts
</body>
</html>
