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


    <title>FORM BAHAN PRODUK PRODUKSI</title>
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
    <h3 class="pt-6">FORM BAHAN PRODUK PRODUKSI PT. ARTA TEKNOLOGI COMUNINDO</h3>

    <table style="width: 100%;border-collapse: collapse;padding-top:10;">
        <tr style="text-align: left;vertical-align: top;">
            <td style="width: 30%;"><strong>Nama Produk</strong></td>
            <td>: {{ $produkProduksis->dataBahan->nama_bahan }}</td>
        </tr>
        {{-- <tr style="text-align: left;vertical-align: top;">
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
        </tr> --}}
    </table>

    <table style="border: 1px solid black;width: 100%;border-collapse: collapse;padding-top:10;">
        <thead>
            <tr>
                <th style="border: 1px solid black; width: 5%;">No</th>
            <th style="border: 1px solid black; width: 10%;">Kode Bahan</th>
            <th style="border: 1px solid black; width: 25%;">Nama</th>
            <th style="border: 1px solid black; width: 5%;">Min Unit/Produksi</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalSubTotal = 0;
            @endphp
            @foreach ($produkProduksis->produkProduksiDetails as $index => $detail)
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
                    <td style="border: 1px solid black;text-align: center;">{{ $detail->jml_bahan ?? null }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @livewireScripts
</body>
</html>
