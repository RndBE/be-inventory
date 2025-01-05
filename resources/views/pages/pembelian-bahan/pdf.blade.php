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
                <p>Perum Pesona Bandara C-5 Juwangen Purwomartani, Kalasan, Sleman, <br> Daerah Istimewa Yogyakarta
                Ph./Fax. (0274) 4986899 Website: <br> <a href="https://www.be-jogja.com" target="_blank">https://www.be-jogja.com</a>
                </p>
            </td>
        </tr>
    </table>

    <!-- Centered H3 -->
    <h3 class="pt-6">FORM PENGAJUAN BAHAN/BARANG/ALAT LOKAL PT. ARTA TEKNOLOGI COMUNINDO</h3>

    <table style="border: 1px solid black;width: 100%;border-collapse: collapse;padding-top:10;">
        <tr style="text-align: left;vertical-align: top;">
            <td style="border: 1px solid black;width: 30%;"><strong>Divisi</strong></td>
            <td style="border: 1px solid black">: {{ $pembelianBahan->divisi }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid black;"><strong>Project</strong></td>
            <td style="border: 1px solid black">: {{ $pembelianBahan->tujuan }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid black;"><strong>Keterangan</strong></td>
            <td style="border: 1px solid black">: {{ $pembelianBahan->keterangan }}</td>
        </tr>
    </table>

    <table style="border: 1px solid black;width: 100%;border-collapse: collapse;padding-top:10;">
        <thead>
            <tr>
                <th style="border: 1px solid black;">No</th>
                <th style="border: 1px solid black;">Nama</th>
                <th style="border: 1px solid black;">Spesifikasi</th>
                <th style="border: 1px solid black;">Qty</th>
                <th style="border: 1px solid black;">Satuan</th>
                <th style="border: 1px solid black;">Harga Satuan</th>
                <th style="border: 1px solid black;width: 20%;">Total Harga</th>
                <th style="border: 1px solid black;">Ket Pembayaran</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalSubTotal = 0;
                $newtotalSubTotal = 0;
            @endphp
            @foreach ($pembelianBahan->pembelianBahanDetails as $index => $detail)
            @php
            $unitPrices = json_decode($detail->details);
            $newUnitPrices = json_decode($detail->new_details);
        @endphp
                <tr>
                    <td style="border: 1px solid black; text-align: center;">{{ $index + 1 }}</td>
                    <td style="border: 1px solid black">{{ $detail->dataBahan->nama_bahan }}</td>
                    <td style="border: 1px solid black">{{ $detail->spesifikasi }}</td>
                    <td style="border: 1px solid black;text-align: center;">{{ $detail->jml_bahan }}</td>
                    <td style="border: 1px solid black;text-align: center;">{{ $detail->dataBahan->dataUnit->nama }}</td>
                    <td style="border: 1px solid black; text-align: right; padding: 5px;">
                        <div>
                            @if($newUnitPrices->new_unit_price ?? false)
                                <span class="line-through">{{ number_format($unitPrices->unit_price ?? 0) }}</span>
                            @else
                                {{ number_format($unitPrices->unit_price ?? 0) }}
                            @endif
                        </div>
                        <div>
                            @if($newUnitPrices->new_unit_price ?? false)
                                {{ number_format($newUnitPrices->new_unit_price ?? 0) }}
                            @else
                                <span class="invisible"></span>
                            @endif
                        </div>
                    </td>
                    <td style="border: 1px solid black;text-align: right;">
                        <div>
                            @if($newUnitPrices->new_unit_price ?? false)
                                <span class="line-through">{{ number_format(($detail->jml_bahan) * ($unitPrices->unit_price ?? 0)) }}</span>
                            @else
                                {{ number_format(($detail->jml_bahan) * ($unitPrices->unit_price ?? 0)) }}
                            @endif
                        </div>
                        <div>
                            @if($newUnitPrices->new_unit_price ?? false)
                                {{ number_format(($detail->jml_bahan) * ($newUnitPrices->new_unit_price ?? 0)) }}
                            @else
                                <span class="invisible"></span>
                            @endif
                        </div>
                    </td>
                    <td style="border: 1px solid black">{{ $detail->keterangan_pembayaran }}</td>
                </tr>
                @php
                    $totalSubTotal += $detail->sub_total;
                    $newtotalSubTotal += $detail->new_sub_total;
                @endphp
            @endforeach
            <tr>
                <td colspan="6" style="border: 1px solid black; text-align: right; font-weight: bold;">Total Anggaran</td>
                <td style="border: 1px solid black; text-align: right; border-right: none;">
                    @if($newUnitPrices->new_unit_price ?? false)
                        <span class="line-through">Rp. {{ number_format($totalSubTotal) }}</span>
                    @else
                        Rp. {{ number_format($totalSubTotal) }}
                    @endif
                </td>
                <td style="border: 1px solid black; text-align: right; border-left: none;">
                    @if($newUnitPrices->new_unit_price ?? false)
                        Rp. {{ number_format($newtotalSubTotal) }}
                    @else
                        <span class="invisible"></span>
                    @endif
                </td>
            </tr>
        </tbody>

    </table>

    <table style="width: 100%;border-collapse: collapse;padding-top:10;">
        <tr style="text-align: left; vertical-align: top;">
            <td style=" text-align: center;"><strong>Pengaju</strong></td>
            <td style=" text-align: center;"><strong>PJ/Leader</strong></td>
            <td style=" text-align: center;"><strong>Purchasing</strong></td>
            <td style=" text-align: center;"><strong>Manager</strong></td>
        </tr>
        <tr>
            <td style="text-align: center; width: 25%;">
                @if($tandaTanganPengaju)
                    <img src="{{ public_path('storage/' . $tandaTanganPengaju) }}" alt="Tanda Tangan Pengaju" style="height: 80px; width: 150px; object-fit: contain;">
                @else
                <div style="height: 80px; width: 150px;"></div>
                @endif
            </td>
            <td style="text-align: center; width: 25%;">
                @if($tandaTanganLeader)
                    <img src="{{ public_path('storage/' . $tandaTanganLeader) }}" alt="Tanda Tangan Leader" style="height: 80px; width: 150px; object-fit: contain;">
                @else
                    <div style="height: 80px; width: 150px;"></div>
                @endif
            </td>
            <td style="text-align: center; width: 25%;">
                @if($tandaTanganPurchasing)
                    <img src="{{ public_path('storage/' . $tandaTanganPurchasing) }}" alt="Tanda Tangan Purchasing" style="height: 80px; width: 150px; object-fit: contain;">
                @else
                    <div style="height: 80px; width: 150px;"></div>
                @endif
            </td>
            <td style="text-align: center; width: 25%;">
                @if($tandaTanganManager)
                    <img src="{{ public_path('storage/' . $tandaTanganManager) }}" alt="Tanda Tangan Manager" style="height: 80px; width: 150px; object-fit: contain;">
                @else
                    <div style="height: 80px; width: 150px;"></div>
                @endif
            </td>
        </tr>

        <tr style="text-align: left; vertical-align: top;">
            <td style="text-align: center;">
                {{ $pembelianBahan->dataUser->name }}
            </td>
            <td style="text-align: center;">
                {{ $leaderName ?? '' }}
            </td>
            <td style="text-align: center;">
                {{ $purchasingUser->name }}
            </td>
            <td style="text-align: center;">
                {{ $managerName ?? '' }}
            </td>
        </tr>


        <tr>
            <td colspan="4"  style="text-align: center;padding:7;"><strong> Mengetahui, </strong></td>
        </tr>
        <tr style="text-align: left; vertical-align: top;">
            <td style=" text-align: center;"><strong>Finance</strong></td>
            <td colspan="2" style=" text-align: center;"><strong>Manager Admin</strong></td>
            <td style=" text-align: center;"><strong>Direktur</strong></td>
        </tr>
        <tr>
            <td style="text-align: center; width: 33%;">
                @if($tandaTanganFinance)
                    <img src="{{ public_path('storage/' . $tandaTanganFinance) }}" alt="Tanda Tangan Finance" style="height: 80px;">
                @else
                    <div style="height: 80px; width: 150px;"></div>
                @endif
            </td>
            <td colspan="2" style="text-align: center;">
                <div style="height: 80px; width: 150px;"></div>
            </td>
            <td style="text-align: center; width: 33%;">
                <div style="height: 80px; width: 150px;"></div>
            </td>
        </tr>
        <tr style="text-align: left; vertical-align: top;">
            <td style="text-align: center;">{{ $financeUser->name }}</td>
            <td colspan="2" style="text-align: center;">{{$adminManagerceUser->name}}</td>
            <td style="text-align: center;">
                @if($pembelianBahan->dataUser->atasanLevel1)
                    {{ $pembelianBahan->dataUser->atasanLevel1->name }}
                @else
                    {{ $pembelianBahan->dataUser->name }}
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="4"  style="text-align: center;padding:7;"><strong>  </strong></td>
        </tr>
        <tr style="text-align: left; vertical-align: top;">
            <td style="text-align: center;"></td>
            <td style="text-align: center;"></td>
            <td colspan="2" style="text-align: center;">Pembayaran: </td>
        </tr>
    </table>

    @livewireScripts
</body>
</html>
