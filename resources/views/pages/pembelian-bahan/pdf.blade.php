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
            color: red;
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
    @if($jenis_pengajuan === 'Pembelian Bahan/Barang/Alat Lokal')
    <h3 class="pt-6">FORM PENGAJUAN BAHAN/BARANG/ALAT LOKAL PT. ARTA TEKNOLOGI COMUNINDO</h3>
    @endif
    @if($jenis_pengajuan === 'Pembelian Bahan/Barang/Alat Impor')
    <h3 class="pt-6">FORM PENGAJUAN BAHAN/BARANG/ALAT IMPOR PT. ARTA TEKNOLOGI COMUNINDO</h3>
    @endif
    @if($jenis_pengajuan === 'Pembelian Aset')
    <h3 class="pt-6">FORM PENGAJUAN ASET PT. ARTA TEKNOLOGI COMUNINDO</h3>
    @endif

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
    @if($jenis_pengajuan === 'Pembelian Bahan/Barang/Alat Lokal')
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
                    $totalWithExtras = 0;
                @endphp
                @foreach ($pembelianBahan->pembelianBahanDetails as $index => $detail)
                    @php
                        $unitPrices = json_decode($detail->details);
                        $newUnitPrices = json_decode($detail->new_details);

                        $unitPrice = $unitPrices->unit_price ?? 0;
                        $newUnitPrice = $newUnitPrices->new_unit_price ?? 0;
                        $jmlBahan = $detail->jml_bahan ?? 0;

                        $finalUnitPrice = $newUnitPrice > 0 ? $newUnitPrice : $unitPrice;
                        // Hitung subtotal untuk unit lama dan unit baru
                        $oldSubTotal = $jmlBahan * $unitPrice;
                        $newSubTotal = $jmlBahan * $newUnitPrice;
                        $newSubTotalFinal = $jmlBahan * $finalUnitPrice;

                        $totalWithExtras += $newSubTotalFinal;
                    @endphp
                    <tr>
                        <td style="border: 1px solid black; text-align: center;">{{ $index + 1 }}</td>
                        <td style="border: 1px solid black">{{ $detail->dataBahan->nama_bahan }}</td>
                        <td style="border: 1px solid black; word-wrap: break-word; white-space: normal; overflow-wrap: break-word;">
							@php
								$fullText = $detail->spesifikasi; // Teks spesifikasi
                                if (!$fullText || $fullText == '0') {
                                    $fullText = ''; // Set menjadi string kosong jika spesifikasi 0 atau null
                                }
								$isUrl = filter_var($fullText, FILTER_VALIDATE_URL); // Periksa apakah teks adalah URL
								$displayText = $isUrl && strlen($fullText) > 30
									? substr($fullText, 0, 30) . '...'
									: $fullText; // Potong hanya jika URL dan terlalu panjang
							@endphp

							@if ($isUrl)
								<a href="{{ $fullText }}" target="_blank" style="color: black; text-decoration: none;">
									{{ $displayText }}
								</a>
							@else
								{{ $fullText }}
							@endif
						</td>
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
                @endforeach
                @if($status === 'Disetujui')
                    <tr>
                        <td colspan="6" style="border: 1px solid black; text-align: right; font-weight: bold;">Ongkos Kirim</td>
                        <td style="border: 1px solid black; text-align: right; border-right: none;">
                            {{ number_format($ongkir) }}
                        </td>
                        <td style="border: 1px solid black; text-align: right; border-left: none;">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6" style="border: 1px solid black; text-align: right; font-weight: bold;">Asuransi</td>
                        <td style="border: 1px solid black; text-align: right; border-right: none;">
                            {{ number_format($asuransi) }}
                        </td>
                        <td style="border: 1px solid black; text-align: right; border-left: none;">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6" style="border: 1px solid black; text-align: right; font-weight: bold;">Layanan</td>
                        <td style="border: 1px solid black; text-align: right; border-right: none;">
                            {{ number_format($layanan) }}
                        </td>
                        <td style="border: 1px solid black; text-align: right; border-left: none;">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6" style="border: 1px solid black; text-align: right; font-weight: bold;">Jasa Aplikasi</td>
                        <td style="border: 1px solid black; text-align: right; border-right: none;">
                            {{ number_format($jasa_aplikasi) }}
                        </td>
                        <td style="border: 1px solid black; text-align: right; border-left: none;">
                        </td>
                    </tr>
                @else
                    @php
                        $totalWithExtras += 0;
                    @endphp
                @endif
                <tr>
                    <td colspan="6" style="border: 1px solid black; text-align: right; font-weight: bold;">Total Anggaran</td>
                    <td style="border: 1px solid black; text-align: right; border-right: none;">Rp.
                        @php
                            if ($status === 'Disetujui') {
                                $totalWithExtras += ($ongkir ?? 0) + ($asuransi ?? 0) + ($layanan ?? 0) + ($jasa_aplikasi ?? 0);
                            }
                        @endphp
                        {{ number_format($totalWithExtras, 0, ',', '.') }}
                    </td>
                    <td style="border: 1px solid black; text-align: right; border-left: none;">

                    </td>
                </tr>
            </tbody>
        </table>
    @endif
    @if($jenis_pengajuan === 'Pembelian Bahan/Barang/Alat Impor')
        <table style="border: 1px solid black;width: 100%;border-collapse: collapse;padding-top:10;">
            <thead>
                <tr>
                    <th style="border: 1px solid black;">No</th>
                    <th style="border: 1px solid black;">Nama</th>
                    <th style="border: 1px solid black;">Qty</th>
                    <th style="border: 1px solid black;">Harga Satuan (USD)</th>
                    <th style="border: 1px solid black;width: 20%;">Total Harga (USD)</th>
                    <th style="border: 1px solid black;">Harga Satuan (Rp)</th>
                    <th style="border: 1px solid black;width: 20%;">Total Harga (Rp)</th>
                    <th style="border: 1px solid black;">Ket</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalSubTotal = 0;
                    $totalSubTotalUSD = 0;
                    $newtotalSubTotal = 0;
                    $newtotalSubTotalUSD = 0;
                    $totalWithExtras = 0;
                    $totalWithExtrasUSD = 0;
                @endphp
                @foreach ($pembelianBahan->pembelianBahanDetails as $index => $detail)
                    @php
                        $unitPrices = json_decode($detail->details);
                        $unitPricesUSD = json_decode($detail->details_usd);

                        $newUnitPrices = json_decode($detail->new_details);
                        $newUnitPricesUSD = json_decode($detail->new_details_usd);

                        $unitPrice = $unitPrices->unit_price ?? 0;
                        $unitPriceUSD = $unitPricesUSD->unit_price_usd ?? 0;
                        $newUnitPrice = $newUnitPrices->new_unit_price ?? 0;
                        $newUnitPriceUSD = $newUnitPricesUSD->new_unit_price_usd ?? 0;
                        $jmlBahan = $detail->jml_bahan ?? 0;

                        $finalUnitPrice = $newUnitPrice > 0 ? $newUnitPrice : $unitPrice;
                        $finalUnitPriceUSD = $newUnitPriceUSD > 0 ? $newUnitPriceUSD : $unitPriceUSD;
                        // Hitung subtotal untuk unit lama dan unit baru
                        $oldSubTotal = $jmlBahan * $unitPrice;
                        $oldSubTotalUSD = $jmlBahan * $unitPriceUSD;
                        $newSubTotal = $jmlBahan * $newUnitPrice;
                        $newSubTotalUSD = $jmlBahan * $newUnitPriceUSD;
                        $newSubTotalFinal = $jmlBahan * $finalUnitPrice;
                        $newSubTotalFinalUSD = $jmlBahan * $finalUnitPriceUSD;

                        $totalWithExtras += $newSubTotalFinal;
                        $totalWithExtrasUSD += $newSubTotalFinalUSD;
                    @endphp
                    <tr>
                        <td style="border: 1px solid black; text-align: center;">{{ $index + 1 }}</td>
                        <td style="border: 1px solid black">{{ $detail->dataBahan->nama_bahan }}</td>
                        <td style="border: 1px solid black;text-align: center;">{{ $detail->jml_bahan }}</td>
                        <td style="border: 1px solid black; text-align: right; padding: 5px;">
                            <div>
                                @if($newUnitPricesUSD->new_unit_price_usd ?? false)
                                    <span class="line-through">{{ number_format(optional($unitPricesUSD)->unit_price_usd ?? 0, 2, '.', ',') }}</span>
                                @else
                                    {{ number_format(optional($unitPricesUSD)->unit_price_usd ?? 0, 2, '.', ',') }}
                                @endif
                            </div>
                            <div>
                                @if($newUnitPricesUSD->new_unit_price_usd ?? false)
                                    {{ number_format(optional($newUnitPricesUSD)->new_unit_price_usd ?? 0, 2, '.', ',') }}
                                @else
                                    <span class="invisible"></span>
                                @endif
                            </div>
                        </td>
                        <td style="border: 1px solid black;text-align: right;">
                            <div>
                                @if(optional($newUnitPricesUSD)->new_unit_price_usd)
                                    <span class="line-through">
                                        {{ number_format(($detail->jml_bahan * (optional($unitPricesUSD)->unit_price_usd ?? 0)), 2, '.', ',') }}
                                    </span>
                                @else
                                    {{ number_format(($detail->jml_bahan * (optional($unitPricesUSD)->unit_price_usd ?? 0)), 2, '.', ',') }}
                                @endif
                            </div>
                            <div>
                                @if(optional($newUnitPricesUSD)->new_unit_price_usd)
                                    {{ number_format(($detail->jml_bahan * (optional($newUnitPricesUSD)->new_unit_price_usd ?? 0)), 2, '.', ',') }}
                                @else
                                    <span class="invisible"></span>
                                @endif
                            </div>
                        </td>

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
                @endforeach
                <tr>
                    <td style="border: 1px solid black; text-align: right; border-left: none;"></td>
                    <td style="border: 1px solid black; text-align: left; border-left: none;font-weight: bold;">Shipping Cost</td>
                    <td style="border: 1px solid black; text-align: right; border-left: none;"></td>
                    <td style="border: 1px solid black; text-align: right; border-left: none;"></td>
                    <td style="border: 1px solid black; text-align: right; border-left: none;">
                        @if($new_shipping_cost_usd > 0)
                            <span class="line-through text-red-500">{{ number_format($shipping_cost_usd, 2, ',', '.') }}</span>
                            {{ number_format($new_shipping_cost_usd, 2, ',', '.') }}
                        @else
                            {{ number_format($shipping_cost_usd, 2, ',', '.') }}
                        @endif
                    </td>
                    <td style="border: 1px solid black; text-align: right; "></td>
                    <td style="border: 1px solid black; text-align: right; ">
                        @if($new_shipping_cost > 0)
                            <span class="line-through text-red-500">{{ number_format($shipping_cost, 0, ',', '.') }}</span>
                            {{ number_format($new_shipping_cost, 0, ',', '.') }}
                        @else
                            {{ number_format($shipping_cost, 0, ',', '.') }}
                        @endif
                    </td>
                    <td style="border: 1px solid black; text-align: right; "></td>
                </tr>
                <tr>
                    <td style="border: 1px solid black; text-align: right; border-left: none;"></td>
                    <td style="border: 1px solid black; text-align: left; border-left: none;font-weight: bold;">Full Amount Fee</td>
                    <td style="border: 1px solid black; text-align: right; border-left: none;"></td>
                    <td style="border: 1px solid black; text-align: right; border-left: none;"></td>
                    <td style="border: 1px solid black; text-align: right; border-left: none;">
                        @if($new_full_amount_fee_usd > 0)
                            <span class="line-through text-red-500">{{ number_format($full_amount_fee_usd, 2, ',', '.') }}</span>
                            {{ number_format($new_full_amount_fee_usd, 2, ',', '.') }}
                        @else
                            {{ number_format($full_amount_fee_usd, 2, ',', '.') }}
                        @endif
                    </td>
                    <td style="border: 1px solid black; text-align: right; "></td>
                    <td style="border: 1px solid black; text-align: right; ">
                        @if($new_full_amount_fee > 0)
                            <span class="line-through text-red-500">{{ number_format($full_amount_fee, 0, ',', '.') }}</span>
                            {{ number_format($new_full_amount_fee, 0, ',', '.') }}
                        @else
                            {{ number_format($full_amount_fee, 0, ',', '.') }}
                        @endif
                    </td>
                    <td style="border: 1px solid black; text-align: right; "></td>
                </tr>
                <tr>
                    <td style="border: 1px solid black; text-align: right; border-left: none;"></td>
                    <td style="border: 1px solid black; text-align: left; border-left: none;font-weight: bold;">Value Today Fee</td>
                    <td style="border: 1px solid black; text-align: right; border-left: none;"></td>
                    <td style="border: 1px solid black; text-align: right; border-left: none;"></td>
                    <td style="border: 1px solid black; text-align: right; border-left: none;">
                        @if($new_value_today_fee_usd > 0)
                            <span class="line-through text-red-500">{{ number_format($value_today_fee_usd, 2, ',', '.') }}</span>
                            {{ number_format($new_value_today_fee_usd, 2, ',', '.') }}
                        @else
                            {{ number_format($value_today_fee_usd, 2, ',', '.') }}
                        @endif
                    </td>
                    <td style="border: 1px solid black; text-align: right; "></td>
                    <td style="border: 1px solid black; text-align: right; ">
                        @if($new_value_today_fee > 0)
                            <span class="line-through text-red-500">{{ number_format($value_today_fee, 0, ',', '.') }}</span>
                            {{ number_format($new_value_today_fee, 0, ',', '.') }}
                        @else
                            {{ number_format($value_today_fee, 0, ',', '.') }}
                        @endif
                    </td>
                    <td style="border: 1px solid black; text-align: right; "></td>
                </tr>
                <tr>
                    <td colspan="6" style="border: 1px solid black; text-align: right; font-weight: bold;">Total Anggaran</td>
                    <td style="border: 1px solid black; text-align: right; border-right: none;">Rp.
                        @php
                            $finalTotal = $totalWithExtras + ($new_shipping_cost > 0 ? $new_shipping_cost : $shipping_cost)
                                        + ($new_full_amount_fee > 0 ? $new_full_amount_fee : $full_amount_fee)
                                        + ($new_value_today_fee > 0 ? $new_value_today_fee : $value_today_fee);
                        @endphp
                        {{ number_format($finalTotal, 0, ',', '.') }}
                    </td>
                    <td style="border: 1px solid black; text-align: right; border-left: none;">

                    </td>
                </tr>
            </tbody>
        </table>
    @endif
    @if($jenis_pengajuan === 'Pembelian Aset')
        <table style="border: 1px solid black;width: 100%;border-collapse: collapse;padding-top:10;">
            <thead>
                <tr>
                    <th style="border: 1px solid black;">No</th>
                    <th style="border: 1px solid black;">Nama</th>
                    <th style="border: 1px solid black;">Spesifikasi</th>
                    <th style="border: 1px solid black;">Qty</th>
                    <th style="border: 1px solid black;">Harga Satuan</th>
                    <th style="border: 1px solid black;width: 20%;">Total Harga</th>
                    <th style="border: 1px solid black;">Penanggung Jawab Aset</th>
                    <th style="border: 1px solid black;">Keterangan/Alasan Pembelian Aset</th>
                    <th style="border: 1px solid black;">Ket Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalSubTotal = 0;
                    $newtotalSubTotal = 0;
                    $totalWithExtras = 0;
                @endphp
                @foreach ($pembelianBahan->pembelianBahanDetails as $index => $detail)
                    @php
                        $unitPrices = json_decode($detail->details);
                        $newUnitPrices = json_decode($detail->new_details);

                        $unitPrice = $unitPrices->unit_price ?? 0;
                        $newUnitPrice = $newUnitPrices->new_unit_price ?? 0;
                        $jmlBahan = $detail->jml_bahan ?? 0;

                        $finalUnitPrice = $newUnitPrice > 0 ? $newUnitPrice : $unitPrice;
                        // Hitung subtotal untuk unit lama dan unit baru
                        $oldSubTotal = $jmlBahan * $unitPrice;
                        $newSubTotal = $jmlBahan * $newUnitPrice;
                        $newSubTotalFinal = $jmlBahan * $finalUnitPrice;

                        $totalWithExtras += $newSubTotalFinal;
                    @endphp
                    <tr>
                        <td style="border: 1px solid black; text-align: center;">{{ $index + 1 }}</td>
                        <td style="border: 1px solid black">{{ $detail->dataBahan->nama_bahan }}</td>
                        <td style="border: 1px solid black; word-wrap: break-word; white-space: normal; overflow-wrap: break-word;">
							@php
								$fullText = $detail->spesifikasi; // Teks spesifikasi
                                if (!$fullText || $fullText == '0') {
                                    $fullText = ''; // Set menjadi string kosong jika spesifikasi 0 atau null
                                }
								$isUrl = filter_var($fullText, FILTER_VALIDATE_URL); // Periksa apakah teks adalah URL
								$displayText = $isUrl && strlen($fullText) > 30
									? substr($fullText, 0, 30) . '...'
									: $fullText; // Potong hanya jika URL dan terlalu panjang
							@endphp

							@if ($isUrl)
								<a href="{{ $fullText }}" target="_blank" style="color: black; text-decoration: none;">
									{{ $displayText }}
								</a>
							@else
								{{ $fullText }}
							@endif
						</td>
                        <td style="border: 1px solid black;text-align: center;">{{ $detail->jml_bahan }}</td>
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
                        <td style="border: 1px solid black">{{ $detail->penanggungjawabaset }}</td>
                        <td style="border: 1px solid black">{{ $detail->alasan }}</td>
                        <td style="border: 1px solid black">{{ $detail->keterangan_pembayaran }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="5" style="border: 1px solid black; text-align: right; font-weight: bold;">Total Anggaran</td>
                    <td style="border: 1px solid black; text-align: right;">Rp.
                        {{ number_format($totalWithExtras, 0, ',', '.') }}
                    </td>
                    <td style="border: 1px solid black; text-align: right; border-left: none;">

                    </td>
                    <td style="border: 1px solid black; text-align: right;">

                    </td>
                    <td style="border: 1px solid black; text-align: right; border-left: none;">

                    </td>
                </tr>
            </tbody>
        </table>
    @endif
    @if($jenis_pengajuan === 'Pembelian Bahan/Barang/Alat Lokal' || $jenis_pengajuan === 'Pembelian Bahan/Barang/Alat Impor')
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
                    @if($status_leader === 'Disetujui' && $tandaTanganLeader)
                        <img src="{{ public_path('storage/' . $tandaTanganLeader) }}" alt="Tanda Tangan Leader" style="height: 80px; width: 150px; object-fit: contain;">
                    @else
                        <div style="height: 80px; width: 150px;"></div>
                    @endif
                </td>
                <td style="text-align: center; width: 25%;">
                    @if($status_purchasing === 'Disetujui' && $tandaTanganPurchasing)
                        <img src="{{ public_path('storage/' . $tandaTanganPurchasing) }}" alt="Tanda Tangan Purchasing" style="height: 80px; width: 150px; object-fit: contain;">
                    @else
                        <div style="height: 80px; width: 150px;"></div>
                    @endif
                </td>
                <td style="text-align: center; width: 25%;">
                    @if($status_manager === 'Disetujui' && $tandaTanganManager)
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

            <tr style="text-align: left; vertical-align: top;">
                <td style="text-align: center;">
                    {{ $pembelianBahan->tgl_pengajuan ? '(' . \Carbon\Carbon::parse($pembelianBahan->tgl_pengajuan)->translatedFormat('d F Y') . ')' : '' }}
                </td>
                <td style="text-align: center;">
                    {{ $pembelianBahan->tgl_approve_leader ? '(' . \Carbon\Carbon::parse($pembelianBahan->tgl_approve_leader)->translatedFormat('d F Y') . ')' : '' }}
                </td>
                <td style="text-align: center;">
                    {{ $pembelianBahan->tgl_approve_purchasing ? '(' . \Carbon\Carbon::parse($pembelianBahan->tgl_approve_purchasing)->translatedFormat('d F Y') . ')' : '' }}
                </td>
                <td style="text-align: center;">
                    {{ $pembelianBahan->tgl_approve_manager ? '(' . \Carbon\Carbon::parse($pembelianBahan->tgl_approve_manager)->translatedFormat('d F Y') . ')' : '' }}
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
                    @if($status_finance === 'Disetujui' && $tandaTanganFinance)
                        <img src="{{ public_path('storage/' . $tandaTanganFinance) }}" alt="Tanda Tangan Finance" style="height: 80px;">
                    @else
                        <div style="height: 80px; width: 150px;"></div>
                    @endif
                </td>
                <td colspan="2" style="text-align: center;">
                    @if($status_admin_manager === 'Disetujui' && $tandaTanganAdminManager)
                        <img src="{{ public_path('storage/' . $tandaTanganAdminManager) }}" alt="Tanda Tangan Finance" style="height: 80px;">
                    @else
                        <div style="height: 80px; width: 150px;"></div>
                    @endif
                </td>
                <td style="text-align: center; width: 33%;">
                    @if($status === 'Disetujui' && $tandaTanganDirektur)
                        <img src="{{ public_path('storage/' . $tandaTanganDirektur) }}" alt="Tanda Tangan Finance" style="height: 80px;">
                    @else
                        <div style="height: 80px; width: 150px;"></div>
                    @endif
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
            <tr style="text-align: left; vertical-align: top;">
                <td style="text-align: center;">
                    {{ $pembelianBahan->tgl_approve_finance ? '(' . \Carbon\Carbon::parse($pembelianBahan->tgl_approve_finance)->translatedFormat('d F Y') . ')' : '' }}
                </td>
                <td colspan="2" style="text-align: center;">
                    {{ $pembelianBahan->tgl_approve_admin_manager ? '(' . \Carbon\Carbon::parse($pembelianBahan->tgl_approve_admin_manager)->translatedFormat('d F Y') . ')' : '' }}
                </td>
                <td style="text-align: center;">
                    {{ $pembelianBahan->tgl_approve_direktur ? '(' . \Carbon\Carbon::parse($pembelianBahan->tgl_approve_direktur)->translatedFormat('d F Y') . ')' : '' }}
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
    @endif
    @if($jenis_pengajuan === 'Pembelian Aset')
        <table style="width: 100%;border-collapse: collapse;padding-top:10;">
            <tr style="text-align: left; vertical-align: top;">
                <td style=" text-align: center;"><strong>Pengaju</strong></td>
                <td style=" text-align: center;"><strong>PJ/Leader</strong></td>
                <td style=" text-align: center;"><strong>General Affair</strong></td>
                <td style=" text-align: center;"><strong>Purchasing</strong></td>
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
                    @if($status_leader === 'Disetujui' && $tandaTanganLeader)
                        <img src="{{ public_path('storage/' . $tandaTanganLeader) }}" alt="Tanda Tangan Leader" style="height: 80px; width: 150px; object-fit: contain;">
                    @else
                        <div style="height: 80px; width: 150px;"></div>
                    @endif
                </td>
                <td style="text-align: center; width: 25%;">
                    @if($status_general_manager === 'Disetujui' && $tandaTanganGeneral)
                        <img src="{{ public_path('storage/' . $tandaTanganGeneral) }}" alt="Tanda Tangan General" style="height: 80px; width: 150px; object-fit: contain;">
                    @else
                        <div style="height: 80px; width: 150px;"></div>
                    @endif
                </td>
                <td style="text-align: center; width: 25%;">
                    @if($status_purchasing === 'Disetujui' && $tandaTanganPurchasing)
                        <img src="{{ public_path('storage/' . $tandaTanganPurchasing) }}" alt="Tanda Tangan Purchasing" style="height: 80px; width: 150px; object-fit: contain;">
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
                    {{ $generalUser->name }}
                </td>
                <td style="text-align: center;">
                    {{ $purchasingUser->name }}
                </td>
            </tr>

            <tr style="text-align: left; vertical-align: top;">
                <td style="text-align: center;">
                    {{ $pembelianBahan->tgl_pengajuan ? '(' . \Carbon\Carbon::parse($pembelianBahan->tgl_pengajuan)->translatedFormat('d F Y') . ')' : '' }}
                </td>
                <td style="text-align: center;">
                    {{ $pembelianBahan->tgl_approve_leader ? '(' . \Carbon\Carbon::parse($pembelianBahan->tgl_approve_leader)->translatedFormat('d F Y') . ')' : '' }}
                </td>
                <td style="text-align: center;">
                    {{ $pembelianBahan->tgl_approve_general_manager ? '(' . \Carbon\Carbon::parse($pembelianBahan->tgl_approve_general_manager)->translatedFormat('d F Y') . ')' : '' }}
                </td>
                <td style="text-align: center;">
                    {{ $pembelianBahan->tgl_approve_purchasing ? '(' . \Carbon\Carbon::parse($pembelianBahan->tgl_approve_purchasing)->translatedFormat('d F Y') . ')' : '' }}
                </td>
            </tr>


            <tr>
                <td colspan="4"  style="text-align: center;padding:7;"><strong> Mengetahui, </strong></td>
            </tr>

            <tr style="text-align: left; vertical-align: top;">
                <td style=" text-align: center;"><strong>Manager</strong></td>
                <td style=" text-align: center;"><strong>Finance</strong></td>
                <td style=" text-align: center;"><strong>Manager Admin</strong></td>
                <td style=" text-align: center;"><strong>Direktur</strong></td>
            </tr>
            <tr>
                <td style="text-align: center; width: 25%;">
                    @if($status_manager === 'Disetujui' && $tandaTanganManager)
                        <img src="{{ public_path('storage/' . $tandaTanganManager) }}" alt="Tanda Tangan Manager" style="height: 80px; width: 150px; object-fit: contain;">
                    @else
                        <div style="height: 80px; width: 150px;"></div>
                    @endif
                </td>
                <td style="text-align: center; width: 25%;">
                    @if($status_finance === 'Disetujui' && $tandaTanganFinance)
                        <img src="{{ public_path('storage/' . $tandaTanganFinance) }}" alt="Tanda Tangan Finance" style="height: 80px;">
                    @else
                        <div style="height: 80px; width: 150px;"></div>
                    @endif
                </td>
                <td style="text-align: center; width: 25%;">
                    @if($status_admin_manager === 'Disetujui' && $tandaTanganAdminManager)
                        <img src="{{ public_path('storage/' . $tandaTanganAdminManager) }}" alt="Tanda Tangan Finance" style="height: 80px;">
                    @else
                        <div style="height: 80px; width: 150px;"></div>
                    @endif
                </td>
                <td style="text-align: center; width: 25%;">
                    @if($status === 'Disetujui' && $tandaTanganDirektur)
                        <img src="{{ public_path('storage/' . $tandaTanganDirektur) }}" alt="Tanda Tangan Finance" style="height: 80px;">
                    @else
                        <div style="height: 80px; width: 150px;"></div>
                    @endif
                </td>
            </tr>

            <tr style="text-align: left; vertical-align: top;">
                <td style="text-align: center;">
                    {{ $managerName ?? '' }}
                </td>
                <td style="text-align: center;">
                    {{ $financeUser->name }}
                </td>
                <td style="text-align: center;">
                    {{$adminManagerceUser->name}}
                </td>
                <td style="text-align: center;">
                    @if($pembelianBahan->dataUser->atasanLevel1)
                        {{ $pembelianBahan->dataUser->atasanLevel1->name }}
                    @else
                        {{ $pembelianBahan->dataUser->name }}
                    @endif
                </td>
            </tr>

            <tr style="text-align: left; vertical-align: top;">
                <td style="text-align: center;">
                    {{ $pembelianBahan->tgl_approve_manager ? '(' . \Carbon\Carbon::parse($pembelianBahan->tgl_approve_manager)->translatedFormat('d F Y') . ')' : '' }}
                </td>
                <td style="text-align: center;">
                    {{ $pembelianBahan->tgl_approve_finance ? '(' . \Carbon\Carbon::parse($pembelianBahan->tgl_approve_finance)->translatedFormat('d F Y') . ')' : '' }}
                </td>
                <td style="text-align: center;">
                    {{ $pembelianBahan->tgl_approve_admin_manager ? '(' . \Carbon\Carbon::parse($pembelianBahan->tgl_approve_admin_manager)->translatedFormat('d F Y') . ')' : '' }}
                </td>
                <td style="text-align: center;">
                    {{ $pembelianBahan->tgl_approve_direktur ? '(' . \Carbon\Carbon::parse($pembelianBahan->tgl_approve_direktur)->translatedFormat('d F Y') . ')' : '' }}
                </td>
            </tr>
            {{-- <tr style="text-align: left; vertical-align: top;">
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
            </tr> --}}
            <tr>
                <td colspan="4"  style="text-align: center;padding:7;"><strong>  </strong></td>
            </tr>
            <tr style="text-align: left; vertical-align: top;">
                <td style="text-align: center;"></td>
                <td style="text-align: center;"></td>
                <td colspan="2" style="text-align: center;">Pembayaran: </td>
            </tr>
        </table>
    @endif

    @livewireScripts
</body>
</html>
