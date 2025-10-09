<div x-data="{ openDetailSidebar: @entangle('isDetailOpen') }" @keydown.escape.window="openDetailSidebar = false">
    <!-- Overlay untuk klik di luar -->
    <div
        x-show="openDetailSidebar"
        @click="openDetailSidebar = false"
        x-transition.opacity
        class="fixed inset-0 bg-black bg-opacity-30 z-40"
        style="display: none"
    ></div>

    <!-- Sidebar -->
    <div
        x-show="openDetailSidebar"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed top-0 right-0 z-50 w-full max-w-md h-full bg-white shadow-lg overflow-y-auto"
        style="display: none"
        @click.outside="openDetailSidebar = false"
    >
        <div class="flex justify-between items-center px-4 py-3 border-b">
            <h2 class="text-lg font-semibold">Detail Transaksi</h2>
            <button @click="openDetailSidebar = false" class="text-gray-600 hover:text-red-600">✕</button>
        </div>

        <div class="p-4 border-b">
            @if($jenis_pengajuan === 'Pembelian Bahan/Barang/Alat Lokal')
                <div class="flex w-full items-center justify-center">
                    <div class="w-[350px] rounded bg-gray-50 px-6 m-4 shadow-lg">
                        <img src="{{ asset('images/logo_be2.png') }}" alt="chippz" class="mx-auto w-32 py-4" />
                        <div class="flex flex-col justify-center items-center gap-2">
                            <h4 class="font-semibold">PT. Arta Teknologi Comunindo</h4>
                            <p class="text-xs text-center">Kadirojo I, Purwomartani, Kec. Kalasan, Kabupaten Sleman, Daerah Istimewa Yogyakarta</p>
                        </div>
                        <div class="flex flex-col gap-3 border-b py-6 text-xs">
                            <p class="flex justify-between">
                                <span class="text-gray-400">Tgl disetujui:</span>
                                <span>{{ $tgl_keluar }}</span>
                            </p>
                            <p class="flex justify-between">
                                <span class="text-gray-400">Kode Transaksi:</span>
                                <span>{{ $kode_transaksi }}</span>
                            </p>
                            <p class="flex justify-between">
                                <span class="text-gray-400">Status:</span>
                                <span>{{ $status }}</span>
                            </p>
                            <p class="flex justify-between">
                                <span class="text-gray-400">Divisi:</span>
                                <span>{{ $divisi }}</span>
                            </p>
                        </div>
                        <div class="flex flex-col gap-3 pb-6 pt-2 text-xs">
                            <table class="w-full text-left">
                                <tbody>
                                    @if (!empty($this->pembelianBahanDetails))
                                        @php
                                            $totalWithExtras = 0;
                                        @endphp
                                        @foreach($this->pembelianBahanDetails as $detail)
                                            <tr class="flex">
                                                <td class="flex-1 py-1">
                                                    {{ $detail->dataBahan->nama_bahan }}
                                                    @if ($detail->jml_bahan > 0)
                                                        ({{ $detail->jml_bahan }})
                                                    @else
                                                        <span class="text-red-500">Belum tersedia</span>
                                                    @endif
                                                </td>
                                            </tr>
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
                                            <tr class="flex">
                                                <td class="min-w-[44px]">{{ $detail->jml_bahan }} x</td>

                                                <td class="flex-1">
                                                    @if($newUnitPrices->new_unit_price ?? false)
                                                        <span class="line-through text-red-500">{{ number_format($unitPrices->unit_price ?? 0) }}</span>
                                                    @else
                                                        {{ number_format($unitPrices->unit_price ?? 0, 2, ',', '.') }}
                                                    @endif
                                                </td>

                                                <td class="flex-1 pl-3">
                                                    @if($newUnitPrices->new_unit_price ?? false)
                                                        {{ number_format($newUnitPrices->new_unit_price ?? 0, 2, ',', '.') }}
                                                    @else
                                                        <span class="invisible"></span>
                                                    @endif
                                                </td>


                                                <td class="w-full text-right">
                                                    @if($newUnitPrices->new_unit_price ?? false)
                                                        <span class="line-through text-red-500">{{ number_format(($detail->jml_bahan) * ($unitPrices->unit_price ?? 0)) }}</span>
                                                    @else
                                                        {{ number_format(($detail->jml_bahan) * ($unitPrices->unit_price ?? 0), 2, ',', '.') }}
                                                    @endif

                                                    @if($newUnitPrices->new_unit_price ?? false)
                                                        {{ number_format(($detail->jml_bahan) * ($newUnitPrices->new_unit_price ?? 0), 2, ',', '.') }}
                                                    @else
                                                        <span class="invisible"></span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr class="flex">
                                            <td class="flex-1 py-1"></td>
                                            <td class="w-[150px] text-right"><strong>PPN: </strong></td>
                                            <td class="w-[150px] text-right">{{ number_format($ppn, 2, ',', '.') }}</td>
                                        </tr>
                                        <tr class="flex">
                                            <td class="flex-1 py-1"></td>
                                            <td class="w-[150px] text-right"><strong>Ongkos Kirim: </strong></td>
                                            <td class="w-[150px] text-right">{{ number_format($ongkir, 2, ',', '.') }}</td>
                                        </tr>
                                        <tr class="flex">
                                            <td class="flex-1 py-1"></td>
                                            <td class="w-[150px] text-right"><strong>Asuransi: </strong></td>
                                            <td class="w-[150px] text-right">{{ number_format($asuransi, 2, ',', '.') }}</td>
                                        </tr>
                                        <tr class="flex">
                                            <td class="flex-1 py-1"></td>
                                            <td class="w-[150px] text-right"><strong>Layanan: </strong></td>
                                            <td class="w-[150px] text-right">{{ number_format($layanan, 2, ',', '.') }}</td>
                                        </tr>
                                        <tr class="flex">
                                            <td class="flex-1 py-1"></td>
                                            <td class="w-[150px] text-right"><strong>Jasa Aplikasi: </strong></td>
                                            <td class="w-[150px] text-right">{{ number_format($jasa_aplikasi, 2, ',', '.') }}</td>
                                        </tr>
                                        <tr class="flex">
                                            <td class="flex-1 py-1"></td>
                                            <td class="w-[150px] text-right"><strong>Total Harga: </strong></td>
                                            <td class="w-[150px] text-right">Rp.
                                                @php
                                                    $totalWithExtras += ($ppn ?? 0) + ($ongkir ?? 0) + ($asuransi ?? 0) + ($layanan ?? 0) + ($jasa_aplikasi ?? 0);
                                                @endphp
                                                {{ number_format($totalWithExtras, 2, ',', '.') }}
                                            </td>
                                        </tr>

                                    @else
                                        <tr>
                                            <td colspan="3" class="text-center py-2">Tidak ada detail bahan keluar yang ditemukan.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                            <div class="border-b border border-dashed"></div>
                            <div class="py-4 justify-center items-center flex flex-col gap-2">
                                <p class="flex gap-2"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M21.3 12.23h-3.48c-.98 0-1.85.54-2.29 1.42l-.84 1.66c-.2.4-.6.65-1.04.65h-3.28c-.31 0-.75-.07-1.04-.65l-.84-1.65a2.567 2.567 0 0 0-2.29-1.42H2.7c-.39 0-.7.31-.7.7v3.26C2 19.83 4.18 22 7.82 22h8.38c3.43 0 5.54-1.88 5.8-5.22v-3.85c0-.38-.31-.7-.7-.7ZM12.75 2c0-.41-.34-.75-.75-.75s-.75.34-.75.75v2h1.5V2Z" fill="#000"></path><path d="M22 9.81v1.04a2.06 2.06 0 0 0-.7-.12h-3.48c-1.55 0-2.94.86-3.63 2.24l-.75 1.48h-2.86l-.75-1.47a4.026 4.026 0 0 0-3.63-2.25H2.7c-.24 0-.48.04-.7.12V9.81C2 6.17 4.17 4 7.81 4h3.44v3.19l-.72-.72a.754.754 0 0 0-1.06 0c-.29.29-.29.77 0 1.06l2 2c.01.01.02.01.02.02a.753.753 0 0 0 .51.2c.1 0 .19-.02.28-.06.09-.03.18-.09.25-.16l2-2c.29-.29.29-.77 0-1.06a.754.754 0 0 0-1.06 0l-.72.72V4h3.44C19.83 4 22 6.17 22 9.81Z" fill="#000"></path></svg> info@bejogja.com</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if($jenis_pengajuan === 'Pembelian Bahan/Barang/Alat Impor')
                <div class="flex w-full items-center justify-center">
                    <div class="w-[350px] rounded bg-gray-50 px-6 m-4 shadow-lg">
                        <img src="{{ asset('images/logo_be2.png') }}" alt="chippz" class="mx-auto w-32 py-4" />
                        <div class="flex flex-col justify-center items-center gap-2">
                            <h4 class="font-semibold">PT. Arta Teknologi Comunindo</h4>
                            <p class="text-xs text-center">Kadirojo I, Purwomartani, Kec. Kalasan, Kabupaten Sleman, Daerah Istimewa Yogyakarta</p>
                        </div>
                        <div class="flex flex-col gap-3 border-b py-6 text-xs">
                            <p class="flex justify-between">
                                <span class="text-gray-400">Tgl disetujui:</span>
                                <span>{{ $tgl_keluar }}</span>
                            </p>
                            <p class="flex justify-between">
                                <span class="text-gray-400">Kode Transaksi:</span>
                                <span>{{ $kode_transaksi }}</span>
                            </p>
                            <p class="flex justify-between">
                                <span class="text-gray-400">Status:</span>
                                <span>{{ $status }}</span>
                            </p>
                            <p class="flex justify-between">
                                <span class="text-gray-400">Divisi:</span>
                                <span>{{ $divisi }}</span>
                            </p>
                        </div>
                        <div class="flex flex-col gap-3 pb-6 pt-2 text-xs">
                            <table class="w-full text-left">
                                <tbody>
                                    @if (!empty($this->pembelianBahanDetails))
                                        @php
                                            $totalWithExtras = 0;
                                        @endphp
                                        @foreach($this->pembelianBahanDetails as $detail)
                                            <tr class="flex">
                                                <td class="flex-1 py-1">
                                                    {{ $detail->dataBahan->nama_bahan }}
                                                    @if ($detail->jml_bahan > 0)
                                                        ({{ $detail->jml_bahan }})
                                                    @else
                                                        <span class="text-red-500">Belum tersedia</span>
                                                    @endif
                                                </td>
                                            </tr>
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
                                            <tr class="flex">
                                                <td class="min-w-[44px]">{{ $detail->jml_bahan }} x</td>

                                                <td class="flex-1">
                                                    @if($newUnitPrices->new_unit_price ?? false)
                                                        <span class="line-through text-red-500">{{ number_format($unitPrices->unit_price ?? 0) }}</span>
                                                    @else
                                                        {{ number_format($unitPrices->unit_price ?? 0, 2, ',', '.') }}
                                                    @endif
                                                </td>

                                                <td class="flex-1 pl-3">
                                                    @if($newUnitPrices->new_unit_price ?? false)
                                                        {{ number_format($newUnitPrices->new_unit_price ?? 0, 2, ',', '.') }}
                                                    @else
                                                        <span class="invisible"></span>
                                                    @endif
                                                </td>


                                                <td class="w-full text-right">
                                                    @if($newUnitPrices->new_unit_price ?? false)
                                                        <span class="line-through text-red-500">{{ number_format(($detail->jml_bahan) * ($unitPrices->unit_price ?? 0)) }}</span>
                                                    @else
                                                        {{ number_format(($detail->jml_bahan) * ($unitPrices->unit_price ?? 0), 2, ',', '.') }}
                                                    @endif

                                                    @if($newUnitPrices->new_unit_price ?? false)
                                                        {{ number_format(($detail->jml_bahan) * ($newUnitPrices->new_unit_price ?? 0), 2, ',', '.') }}
                                                    @else
                                                        <span class="invisible"></span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr class="flex">
                                            <td class="flex-1 py-1"></td>
                                            <td class="w-[150px] text-right"><strong>Shipping Cost: </strong></td>
                                            <td class="w-[150px] text-right">
                                                @if($new_shipping_cost > 0)
                                                    <span class="line-through text-red-500">{{ number_format($shipping_cost, 2, ',', '.') }}</span>
                                                    {{ number_format($new_shipping_cost, 2, ',', '.') }}
                                                @else
                                                    {{ number_format($shipping_cost, 2, ',', '.') }}
                                                @endif
                                            </td>

                                        </tr>
                                        <tr class="flex">
                                            <td class="flex-1 py-1"></td>
                                            <td class="w-[150px] text-right"><strong>Full Amount Fee: </strong></td>
                                            <td class="w-[150px] text-right">
                                                @if($new_full_amount_fee > 0)
                                                    <span class="line-through text-red-500">{{ number_format($full_amount_fee, 2, ',', '.') }}</span>
                                                    {{ number_format($new_full_amount_fee, 2, ',', '.') }}
                                                @else
                                                    {{ number_format($full_amount_fee, 2, ',', '.') }}
                                                @endif
                                            </td>

                                        </tr>
                                        <tr class="flex">
                                            <td class="flex-1 py-1"></td>
                                            <td class="w-[150px] text-right"><strong>Value Today Fee: </strong></td>
                                            <td class="w-[150px] text-right">
                                                @if($new_value_today_fee > 0)
                                                    <span class="line-through text-red-500">{{ number_format($value_today_fee, 2, ',', '.') }}</span>
                                                    {{ number_format($new_value_today_fee, 2, ',', '.') }}
                                                @else
                                                    {{ number_format($value_today_fee, 2, ',', '.') }}
                                                @endif
                                            </td>

                                        </tr>
                                        <tr class="flex">
                                            <td class="flex-1 py-1"></td>
                                            <td class="w-[150px] text-right"><strong>Total Harga: </strong></td>
                                            <td class="w-[150px] text-right">Rp.
                                                @php
                                                    $finalTotal = $totalWithExtras + ($new_shipping_cost > 0 ? $new_shipping_cost : $shipping_cost)
                                                                + ($new_full_amount_fee > 0 ? $new_full_amount_fee : $full_amount_fee)
                                                                + ($new_value_today_fee > 0 ? $new_value_today_fee : $value_today_fee);
                                                @endphp
                                                {{ number_format($finalTotal, 2, ',', '.') }}
                                            </td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td colspan="3" class="text-center py-2">Tidak ada detail bahan keluar yang ditemukan.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                            <div class="border-b border border-dashed"></div>
                            <div class="py-4 justify-center items-center flex flex-col gap-2">
                                <p class="flex gap-2"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M21.3 12.23h-3.48c-.98 0-1.85.54-2.29 1.42l-.84 1.66c-.2.4-.6.65-1.04.65h-3.28c-.31 0-.75-.07-1.04-.65l-.84-1.65a2.567 2.567 0 0 0-2.29-1.42H2.7c-.39 0-.7.31-.7.7v3.26C2 19.83 4.18 22 7.82 22h8.38c3.43 0 5.54-1.88 5.8-5.22v-3.85c0-.38-.31-.7-.7-.7ZM12.75 2c0-.41-.34-.75-.75-.75s-.75.34-.75.75v2h1.5V2Z" fill="#000"></path><path d="M22 9.81v1.04a2.06 2.06 0 0 0-.7-.12h-3.48c-1.55 0-2.94.86-3.63 2.24l-.75 1.48h-2.86l-.75-1.47a4.026 4.026 0 0 0-3.63-2.25H2.7c-.24 0-.48.04-.7.12V9.81C2 6.17 4.17 4 7.81 4h3.44v3.19l-.72-.72a.754.754 0 0 0-1.06 0c-.29.29-.29.77 0 1.06l2 2c.01.01.02.01.02.02a.753.753 0 0 0 .51.2c.1 0 .19-.02.28-.06.09-.03.18-.09.25-.16l2-2c.29-.29.29-.77 0-1.06a.754.754 0 0 0-1.06 0l-.72.72V4h3.44C19.83 4 22 6.17 22 9.81Z" fill="#000"></path></svg> info@bejogja.com</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if($jenis_pengajuan === 'Pembelian Aset')
                <div class="flex w-full items-center justify-center">
                    <div class="w-[350px] rounded bg-gray-50 px-6 m-4 shadow-lg">
                        <img src="{{ asset('images/logo_be2.png') }}" alt="chippz" class="mx-auto w-32 py-4" />
                        <div class="flex flex-col justify-center items-center gap-2">
                            <h4 class="font-semibold">PT. Arta Teknologi Comunindo</h4>
                            <p class="text-xs text-center">Kadirojo I, Purwomartani, Kec. Kalasan, Kabupaten Sleman, Daerah Istimewa Yogyakarta</p>
                        </div>
                        <div class="flex flex-col gap-3 border-b py-6 text-xs">
                            <p class="flex justify-between">
                                <span class="text-gray-400">Tgl disetujui:</span>
                                <span>{{ $tgl_keluar }}</span>
                            </p>
                            <p class="flex justify-between">
                                <span class="text-gray-400">Kode Transaksi:</span>
                                <span>{{ $kode_transaksi }}</span>
                            </p>
                            <p class="flex justify-between">
                                <span class="text-gray-400">Status:</span>
                                <span>{{ $status }}</span>
                            </p>
                            <p class="flex justify-between">
                                <span class="text-gray-400">Divisi:</span>
                                <span>{{ $divisi }}</span>
                            </p>
                        </div>
                        <div class="flex flex-col gap-3 pb-6 pt-2 text-xs">
                            <table class="w-full text-left">
                                <tbody>
                                    @if (!empty($this->pembelianBahanDetails))
                                        @php
                                            $totalWithExtras = 0;
                                        @endphp
                                        @foreach($this->pembelianBahanDetails as $detail)
                                            <tr class="flex">
                                                <td class="flex-1 py-1">
                                                    {{ $detail->nama_bahan ?? $detail->dataBahan->nama_bahan ?? '-'}}
                                                    @if ($detail->jml_bahan > 0)
                                                        ({{ $detail->jml_bahan }})
                                                    @else
                                                        <span class="text-red-500">Belum tersedia</span>
                                                    @endif
                                                </td>
                                            </tr>
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
                                            <tr class="flex">
                                                <td class="min-w-[44px]">{{ $detail->jml_bahan }} x</td>

                                                <td class="flex-1">
                                                    @if($newUnitPrices->new_unit_price ?? false)
                                                        <span class="line-through text-red-500">{{ number_format($unitPrices->unit_price ?? 0) }}</span>
                                                    @else
                                                        {{ number_format($unitPrices->unit_price ?? 0, 2, ',', '.') }}
                                                    @endif
                                                </td>

                                                <td class="flex-1 pl-3">
                                                    @if($newUnitPrices->new_unit_price ?? false)
                                                        {{ number_format($newUnitPrices->new_unit_price ?? 0, 2, ',', '.') }}
                                                    @else
                                                        <span class="invisible"></span>
                                                    @endif
                                                </td>


                                                <td class="w-full text-right">
                                                    @if($newUnitPrices->new_unit_price ?? false)
                                                        <span class="line-through text-red-500">{{ number_format(($detail->jml_bahan) * ($unitPrices->unit_price ?? 0)) }}</span>
                                                    @else
                                                        {{ number_format(($detail->jml_bahan) * ($unitPrices->unit_price ?? 0), 2, ',', '.') }}
                                                    @endif

                                                    @if($newUnitPrices->new_unit_price ?? false)
                                                        {{ number_format(($detail->jml_bahan) * ($newUnitPrices->new_unit_price ?? 0), 2, ',', '.') }}
                                                    @else
                                                        <span class="invisible"></span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr class="flex">
                                            <td class="flex-1 py-1"></td>
                                            <td class="w-[150px] text-right"><strong>PPN: </strong></td>
                                            <td class="w-[150px] text-right">{{ number_format($ppn, 2, ',', '.') }}</td>
                                        </tr>
                                        <tr class="flex">
                                            <td class="flex-1 py-1"></td>
                                            <td class="w-[150px] text-right"><strong>Total Harga: </strong></td>
                                            <td class="w-[150px] text-right">Rp.
                                                @php
                                                    $totalWithExtras += ($ppn ?? 0);
                                                @endphp
                                                {{ number_format($totalWithExtras, 2, ',', '.') }}
                                            </td>
                                        </tr>

                                    @else
                                        <tr>
                                            <td colspan="3" class="text-center py-2">Tidak ada detail bahan keluar yang ditemukan.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                            <div class="border-b border border-dashed"></div>
                            <div class="py-4 justify-center items-center flex flex-col gap-2">
                                <p class="flex gap-2"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M21.3 12.23h-3.48c-.98 0-1.85.54-2.29 1.42l-.84 1.66c-.2.4-.6.65-1.04.65h-3.28c-.31 0-.75-.07-1.04-.65l-.84-1.65a2.567 2.567 0 0 0-2.29-1.42H2.7c-.39 0-.7.31-.7.7v3.26C2 19.83 4.18 22 7.82 22h8.38c3.43 0 5.54-1.88 5.8-5.22v-3.85c0-.38-.31-.7-.7-.7ZM12.75 2c0-.41-.34-.75-.75-.75s-.75.34-.75.75v2h1.5V2Z" fill="#000"></path><path d="M22 9.81v1.04a2.06 2.06 0 0 0-.7-.12h-3.48c-1.55 0-2.94.86-3.63 2.24l-.75 1.48h-2.86l-.75-1.47a4.026 4.026 0 0 0-3.63-2.25H2.7c-.24 0-.48.04-.7.12V9.81C2 6.17 4.17 4 7.81 4h3.44v3.19l-.72-.72a.754.754 0 0 0-1.06 0c-.29.29-.29.77 0 1.06l2 2c.01.01.02.01.02.02a.753.753 0 0 0 .51.2c.1 0 .19-.02.28-.06.09-.03.18-.09.25-.16l2-2c.29-.29.29-.77 0-1.06a.754.754 0 0 0-1.06 0l-.72.72V4h3.44C19.83 4 22 6.17 22 9.81Z" fill="#000"></path></svg> info@bejogja.com</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- @if ($isDetailOpen)
            <div class="p-4">
                <h2 class="text-lg font-semibold mb-2">Status Pengajuan</h2>

                <table class="w-full text-sm text-left border-collapse">
                    <tbody>
                        @foreach ($statusList as $role => $status)
                            @php
                                $statusColors = [
                                    'Belum disetujui' => 'bg-blue-100 text-blue-800 border-blue-400',
                                    'Disetujui' => 'bg-green-100 text-green-800 border-green-100',
                                    'Ditolak' => 'bg-red-100 text-red-800 border-red-100',
                                ];
                            @endphp
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="py-2 px-3 font-medium">{{ $role }}</td>
                                <td class="py-2 px-3">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium border {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800 border-gray-400' }}">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="py-2 px-3 text-gray-500 text-xs">
                                    @if (!empty($timeDiffs[$role]))
                                        +{{ $timeDiffs[$role] }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif --}}

    </div>
</div>
