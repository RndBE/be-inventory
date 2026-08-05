<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>FORM BAHAN PRODUK PRODUKSI JADI</title>
    <style>
        @page {
            margin: 28px 32px;
        }

        body {
            margin: 0;
            color: #000;
            font-family: Arial, sans-serif;
            font-size: 10px;
        }

        .company-header,
        .product-info,
        .materials {
            width: 100%;
            border-collapse: collapse;
        }

        .company-header .logo {
            width: 29%;
            text-align: left;
            vertical-align: top;
        }

        .company-header .logo img {
            display: block;
            width: 165px;
            height: auto;
        }

        .company-header .identity {
            padding: 3px 4px 6px;
            border-bottom: 2px solid #000;
            vertical-align: top;
        }

        .company-header h1 {
            margin: 0 0 4px;
            font-size: 16px;
        }

        .company-header p {
            margin: 0;
            font-size: 8px;
            line-height: 1.4;
        }

        h2 {
            margin: 12px 0 9px;
            text-align: center;
            font-size: 12px;
        }

        .product-info {
            margin-bottom: 9px;
        }

        .product-info td {
            padding: 1px 0;
        }

        .product-info .label {
            width: 25%;
            font-weight: bold;
        }

        .materials thead {
            display: table-header-group;
        }

        .materials tr {
            page-break-inside: avoid;
        }

        .materials th,
        .materials td {
            padding: 2px 4px;
            border: 1px solid #000;
            line-height: 1.1;
        }

        .materials th {
            text-align: center;
            font-weight: bold;
        }

        .center {
            text-align: center;
        }
    </style>
</head>
<body>
    <table class="company-header">
        <tr>
            <th class="logo">
                <img src="{{ public_path('images/Picture.png') }}" alt="Logo PT. Arta Teknologi Comunindo">
            </th>
            <td class="identity">
                <h1>PT. ARTA TEKNOLOGI COMUNINDO</h1>
                <p>
                    Kadirojo I, Purwomartani, Kalasan, Sleman, Daerah Istimewa Yogyakarta<br>
                    Ph./Fax. (0274) 4986899 Website: https://www.be-jogja.com
                </p>
            </td>
        </tr>
    </table>

    <h2>FORM BAHAN PRODUK PRODUKSI JADI PT. ARTA TEKNOLOGI COMUNINDO</h2>

    <table class="product-info">
        <tr>
            <td class="label">Nama Produk</td>
            <td>: {{ $produksiProdukJadi->dataProdukJadi->nama_produk ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Keterangan</td>
            <td>: {{ $produksiProdukJadi->keterangan ?? '-' }}</td>
        </tr>
    </table>

    <table class="materials">
        <thead>
            <tr>
                <th style="width: 7%;">No</th>
                <th style="width: 22%;">Kode Bahan</th>
                <th>Nama</th>
                <th style="width: 16%;">Min<br>Unit/Produksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($produksiProdukJadi->produksiProdukJadiDetails as $index => $detail)
                @php
                    $kode = $detail->dataBahan?->kode_bahan
                        ?? $detail->dataProduk?->dataBahan?->kode_bahan
                        ?? $detail->dataProdukJadi?->dataProduk?->kode_bahan
                        ?? '-';
                    $nama = $detail->dataBahan?->nama_bahan
                        ?? $detail->dataProduk?->nama_bahan
                        ?? $detail->dataProdukJadi?->nama_produk
                        ?? $detail->dataProdukJadi?->dataProduk?->nama_produk
                        ?? 'Data tidak tersedia';
                @endphp
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $kode }}</td>
                    <td>
                        {{ $nama }}
                        @if ($detail->serial_number)
                            ({{ $detail->serial_number }})
                        @endif
                    </td>
                    <td class="center">{{ $produksiProdukJadi->jml_produksi ?? 0 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="center">Tidak ada data bahan produksi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
