<div>
    <div class="border-gray-900/10 pt-2">
        @if($jenis_pengajuan === 'Pembelian Bahan/Barang/Alat Lokal')
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3 w-1/5">Nama</th>
                            <th scope="col" class="px-6 py-3 w-0.5">Spesifikasi</th>
                            <th scope="col" class="px-6 py-3 text-center w-0.5">QTY</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Harga Satuan</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Total Harga</th>
                            <th scope="col" class="px-6 py-3 w-0.5">Ket Pembayaran</th>
                            <th scope="col" class="px-6 py-3 w-0.5">Status Pembayaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $grandTotal = 0;
                            $newGrandTotal = 0;
                            $ongkir = $ongkir ?? 0;
                            $asuransi = $asuransi ?? 0;
                            $layanan = $layanan ?? 0;
                            $jasaAplikasi = $jasa_aplikasi ?? 0;
                            $ppn = $ppn ?? 0;
                        @endphp
                        @foreach ($pengajuanDetails as $detail)
                        @php
                            $newDetails = is_string($detail['new_details']) ? json_decode($detail['new_details'], true) : $detail['new_details'];
                            // dd($newDetails);

                            $unitPrice = $unit_price[$detail['bahan']->id] ?? 0;
                            $newUnitPrice = $newDetails[0]['new_unit_price'] ?? 0;
                            $jmlBahan = $detail['jml_bahan'] ?? 0;

                            $finalUnitPrice = $newUnitPrice > 0 ? $newUnitPrice : $unitPrice;

                            // $subTotal = $jmlBahan * $finalUnitPrice;
                            // $grandTotal += $subTotal;

                            $subTotal = $jmlBahan * $unitPrice;
                            $newSubTotal = $jmlBahan * $newUnitPrice;
                            $newSubTotalFinal = $jmlBahan * $finalUnitPrice;

                            // Tambahkan ke grand total
                            $grandTotal += $newSubTotalFinal;
                        @endphp
                        <input type="hidden" name="pengajuanDetails" value="{{ json_encode($this->getCartItemsForStorage()) }}">
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ $detail['bahan']->nama_bahan }}</td>
                            <td class="px-6 py-4 text-gray-900 dark:text-white">
                                <span>{!! nl2br(e($detail['spesifikasi'] ?? '')) !!}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                                <span>{{ $detail['jml_bahan'] ?? 0 }}</span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                @if($newUnitPrice === 0 || $newUnitPrice === null)
                                    <span>{{ number_format($unitPrice, 2, ',', '.') }}</span>
                                @else
                                    <span style="text-decoration: line-through; color: red;">
                                        {{ number_format($unitPrice, 2, ',', '.') }}
                                    </span>
                                    <br>
                                    <span>{{ number_format($newUnitPrice, 2, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                @if($detail['new_sub_total'] === 0 || $detail['new_sub_total'] === null)
                                    <span>{{ number_format($subTotal, 2, ',', '.') }}</span>
                                @else
                                    <span style="text-decoration: line-through; color: red;">
                                        {{ number_format($subTotal, 2, ',', '.') }}
                                    </span>
                                    <br>
                                    <span>{{ number_format($detail['new_sub_total'], 2, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-900 dark:text-white">
                                <span>{!! nl2br(e($detail['keterangan_pembayaran'] ?? '')) !!}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                                <div class="inline-flex items-center">
                                    <label class="flex items-center cursor-pointer relative">
                                        <input disabled type="checkbox"
                                            @if($detail['status_pembelian'] == 1) checked @endif
                                            class="peer h-5 w-5 cursor-pointer transition-all appearance-none rounded shadow hover:shadow-md border border-slate-300 checked:bg-green-600 checked:border-green-600" />
                                        <span class="absolute text-white opacity-0 peer-checked:opacity-100 top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" stroke="currentColor" stroke-width="1">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        </span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 text-right text-black"><strong>PPN</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                {{ number_format($ppn, 2, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 text-right text-black"><strong>Ongkos Kirim</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                {{ number_format($ongkir, 2, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 text-right text-black"><strong>Asuransi</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                {{ number_format($asuransi, 2, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 text-right text-black"><strong>Layanan</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                {{ number_format($layanan,2, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 text-right text-black"><strong>Jasa Aplikasi</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                {{ number_format($jasaAplikasi, 2, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 text-right text-black"><strong>Total Anggaran</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                @php
                                    $totalWithExtras = $grandTotal + $ppn + $ongkir + $asuransi + $layanan + $jasaAplikasi;
                                @endphp
                                <span><strong>Rp.</strong> {{ number_format($totalWithExtras, 2, ',', '.') }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
        @if($jenis_pengajuan === 'Pembelian Bahan/Barang/Alat Impor')
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3 w-1/5">Nama</th>
                            <th scope="col" class="px-6 py-3 text-center w-0.5">QTY</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Harga Satuan (USD)</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Total Harga (USD)</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Harga Satuan (Rp)</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Total Harga (Rp)</th>
                            <th scope="col" class="px-6 py-3 w-0.5">Ket Pembayaran</th>
                            <th scope="col" class="px-6 py-3 w-0.5">Status Pembelian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $grandTotal = 0;
                            $grandTotalUSD = 0;
                            $newGrandTotal = 0;
                            $newGrandTotalUSD = 0;
                            $shipping_cost = $shipping_cost ?? 0;
                            $shipping_cost_usd = $shipping_cost_usd ?? 0;
                            $full_amount_fee = $full_amount_fee ?? 0;
                            $full_amount_fee_usd = $full_amount_fee_usd ?? 0;
                            $value_today_fee = $value_today_fee ?? 0;
                            $value_today_fee_usd = $value_today_fee_usd ?? 0;
                            $new_shipping_cost = $new_shipping_cost ?? 0;
                            $new_shipping_cost_usd = $new_shipping_cost_usd ?? 0;
                            $new_full_amount_fee = $new_full_amount_fee ?? 0;
                            $new_full_amount_fee_usd = $new_full_amount_fee_usd ?? 0;
                            $new_value_today_fee = $new_value_today_fee ?? 0;
                            $new_value_today_fee_usd = $new_value_today_fee_usd ?? 0;
                        @endphp
                        @foreach ($pengajuanDetails as $detail)
                            @php
                                $newDetails = is_string($detail['new_details']) ? json_decode($detail['new_details'], true) : $detail['new_details'];
                                $newDetailsUSD = is_string($detail['new_details_usd']) ? json_decode($detail['new_details_usd'], true) : $detail['new_details_usd'];
                                // dd($newDetails);

                                $unitPrice = $unit_price[$detail['bahan']->id] ?? 0;
                                $unitPriceUSD = $unit_price_usd[$detail['bahan']->id] ?? 0;
                                $newUnitPrice = $newDetails[0]['new_unit_price'] ?? 0;
                                $newUnitPriceUSD = $newDetailsUSD[0]['new_unit_price_usd'] ?? 0;
                                $jmlBahan = $detail['jml_bahan'] ?? 0;

                                $finalUnitPrice = $newUnitPrice > 0 ? $newUnitPrice : $unitPrice;
                                $finalUnitPriceUSD = $newUnitPriceUSD > 0 ? $newUnitPriceUSD : $unitPriceUSD;

                                $subTotal = $jmlBahan * $unitPrice;
                                $newSubTotal = $jmlBahan * $newUnitPrice;
                                $newSubTotalFinal = $jmlBahan * $finalUnitPrice;

                                $subTotalUSD = $jmlBahan * $unitPriceUSD;
                                $newSubTotalUSD = $jmlBahan * $newUnitPriceUSD;
                                $newSubTotalFinalUSD = $jmlBahan * $finalUnitPriceUSD;

                                // Tambahkan ke grand total
                                $grandTotal += $newSubTotalFinal;
                                $grandTotalUSD += $newSubTotalFinalUSD
                            @endphp
                            <input type="hidden" name="pengajuanDetails" value="{{ json_encode($this->getCartItemsForStorage()) }}">
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ $detail['bahan']->nama_bahan }}</td>
                                <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                                    <span>{{ $detail['jml_bahan'] ?? 0 }}</span>
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                    @if($newUnitPriceUSD === 0 || $newUnitPriceUSD === null)
                                        <span>{{ number_format($unitPriceUSD, 2, ',', '.') }}</span>
                                    @else
                                        <span style="text-decoration: line-through; color: red;">
                                            {{ number_format($unitPriceUSD, 2, ',', '.') }}
                                        </span>
                                        <br>
                                        <span>{{ number_format($newUnitPriceUSD, 2, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                    @if(empty($detail['new_sub_total_usd']) || $detail['new_sub_total_usd'] === 0)
                                        <span>{{ number_format($subTotalUSD, 2, ',', '.') }}</span>
                                    @else
                                        <span style="text-decoration: line-through; color: red;">
                                            {{ number_format($subTotalUSD, 2, ',', '.') }}
                                        </span>
                                        <br>
                                        <span>{{ number_format($detail['new_sub_total_usd'], 2, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                    @if($newUnitPrice === 0 || $newUnitPrice === null)
                                        <span>{{ number_format($unitPrice, 2, ',', '.') }}</span>
                                    @else
                                        <span style="text-decoration: line-through; color: red;">
                                            {{ number_format($unitPrice, 2, ',', '.') }}
                                        </span>
                                        <br>
                                        <span>{{ number_format($newUnitPrice, 2, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                    @if($detail['new_sub_total'] === 0 || $detail['new_sub_total'] === null)
                                        <span>{{ number_format($subTotal, 2, ',', '.') }}</span>
                                    @else
                                        <span style="text-decoration: line-through; color: red;">
                                            {{ number_format($subTotal, 2, ',', '.') }}
                                        </span>
                                        <br>
                                        <span>{{ number_format($detail['new_sub_total'], 2, ',', '.') }}</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-gray-900 dark:text-white">
                                    <span>{!! nl2br(e($detail['keterangan_pembayaran'] ?? '')) !!}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                                    <div class="inline-flex items-center">
                                        <label class="flex items-center cursor-pointer relative">
                                            <input disabled type="checkbox"
                                                @if($detail['status_pembelian'] == 1) checked @endif
                                                class="peer h-5 w-5 cursor-pointer transition-all appearance-none rounded shadow hover:shadow-md border border-slate-300 checked:bg-green-600 checked:border-green-600" />
                                            <span class="absolute text-white opacity-0 peer-checked:opacity-100 top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" stroke="currentColor" stroke-width="1">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 text-black"><strong>Shipping Cost</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 text-black"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                @if($new_shipping_cost_usd == 0 || is_null($new_shipping_cost_usd))
                                    <span>{{ number_format($shipping_cost_usd, 2, ',', '.') }}</span>
                                @else
                                    <span style="text-decoration: line-through; color: red;">
                                        {{ number_format($shipping_cost_usd, 2, ',', '.') }}
                                    </span>
                                    <br>
                                    <span>{{ number_format($new_shipping_cost_usd, 2, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                @if($new_shipping_cost == 0 || is_null($new_shipping_cost))
                                    <span>{{ number_format($shipping_cost, 2, ',', '.') }}</span>
                                @else
                                    <span style="text-decoration: line-through; color: red;">
                                        {{ number_format($shipping_cost, 2, ',', '.') }}
                                    </span>
                                    <br>
                                    <span>{{ number_format($new_shipping_cost, 2, ',', '.') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 text-black"><strong>Full Amount Fee</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 text-black"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                {{-- @if($new_full_amount_fee_usd === 0 || is_null($new_full_amount_fee))
                                    <span>{{ number_format($full_amount_fee_usd, 2, ',', '.') }}</span>
                                @else
                                    <span style="text-decoration: line-through; color: red;">
                                        {{ number_format($full_amount_fee_usd, 2, ',', '.') }}
                                    </span>
                                    <br>
                                    <span>{{ number_format($new_full_amount_fee_usd, 2, ',', '.') }}</span>
                                @endif --}}
                                @if($new_full_amount_fee_usd > 0)
                                    <span class="line-through text-red-500">{{ number_format($full_amount_fee_usd, 2, ',', '.') }}</span>
                                    {{ number_format($new_full_amount_fee_usd, 2, ',', '.') }}
                                @else
                                    {{ number_format($full_amount_fee_usd, 2, ',', '.') }}
                                @endif
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                @if($new_full_amount_fee == 0 || is_null($new_full_amount_fee))
                                    <span>{{ number_format($full_amount_fee, 2, ',', '.') }}</span>
                                @else
                                    <span style="text-decoration: line-through; color: red;">
                                        {{ number_format($full_amount_fee, 2, ',', '.') }}
                                    </span>
                                    <br>
                                    <span>{{ number_format($new_full_amount_fee, 2, ',', '.') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 text-black"><strong>Value Today Fee</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 text-black"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                {{-- @if($new_value_today_fee_usd === 0 || is_null($new_value_today_fee_usd))
                                    <span>{{ number_format($value_today_fee_usd, 2, ',', '.') }}</span>
                                @else
                                    <span style="text-decoration: line-through; color: red;">
                                        {{ number_format($value_today_fee_usd, 2, ',', '.') }}
                                    </span>
                                    <br>
                                    <span>{{ number_format($new_value_today_fee_usd, 2, ',', '.') }}</span>
                                @endif --}}
                                @if($new_value_today_fee_usd > 0)
                                    <span class="line-through text-red-500">{{ number_format($value_today_fee_usd, 2, ',', '.') }}</span>
                                    {{ number_format($new_value_today_fee_usd, 2, ',', '.') }}
                                @else
                                    {{ number_format($value_today_fee_usd, 2, ',', '.') }}
                                @endif
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                @if($new_value_today_fee == 0 || is_null($new_value_today_fee))
                                    <span>{{ number_format($value_today_fee, 2, ',', '.') }}</span>
                                @else
                                    <span style="text-decoration: line-through; color: red;">
                                        {{ number_format($value_today_fee, 2, ',', '.') }}
                                    </span>
                                    <br>
                                    <span>{{ number_format($new_value_today_fee, 2, ',', '.') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 text-right text-black"><strong>Total Anggaran</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                @php
                                    $totalWithExtrasUSD = $grandTotalUSD + ($new_shipping_cost_usd ?: $shipping_cost_usd) + ($new_full_amount_fee_usd ?: $full_amount_fee_usd) + ($new_value_today_fee_usd ?: $value_today_fee_usd);
                                @endphp
                                <span><strong>Rp.</strong> {{ number_format($totalWithExtrasUSD, 2, ',', '.') }}</span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                @php
                                    $totalWithExtras = $grandTotal + ($new_shipping_cost ?: $shipping_cost) + ($new_full_amount_fee ?: $full_amount_fee) + ($new_value_today_fee ?: $value_today_fee);
                                @endphp
                                <span><strong>Rp.</strong> {{ number_format($totalWithExtras, 2, ',', '.') }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
        @if($jenis_pengajuan === 'Pembelian Aset')
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3 w-1/5">Nama</th>
                            <th scope="col" class="px-6 py-3 w-0.5">Spesifikasi</th>
                            <th scope="col" class="px-6 py-3 text-center w-0.5">QTY</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Harga Satuan</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Total Harga</th>
                            <th scope="col" class="px-6 py-3 w-0.5">Penanggung Jawab Aset</th>
                            <th scope="col" class="px-6 py-3 w-0.5">Keterangan/Alasan Pembelian Aset</th>
                            <th scope="col" class="px-6 py-3 w-0.5">Tujuan Pembayaran</th>
                            <th scope="col" class="px-6 py-3 w-0.5">Status Pembelian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $grandTotal = 0;
                            $newGrandTotal = 0;
                            $ongkir = $ongkir ?? 0;
                            $asuransi = $asuransi ?? 0;
                            $layanan = $layanan ?? 0;
                            $jasaAplikasi = $jasa_aplikasi ?? 0;
                            $ppn = $ppn ?? 0;
                        @endphp
                        @foreach ($pengajuanDetails as $detail)
                        @php
                            $newDetails = is_string($detail['new_details']) ? json_decode($detail['new_details'], true) : $detail['new_details'];
                            // dd($newDetails);

                            $unitPrice = $unit_price[$detail['nama_bahan']] ?? 0;
                            $newUnitPrice = $newDetails[0]['new_unit_price'] ?? 0;
                            $jmlBahan = $detail['jml_bahan'] ?? 0;

                            $finalUnitPrice = $newUnitPrice > 0 ? $newUnitPrice : $unitPrice;

                            // $subTotal = $jmlBahan * $finalUnitPrice;
                            // $grandTotal += $subTotal;

                            $subTotal = $jmlBahan * $unitPrice;
                            $newSubTotal = $jmlBahan * $newUnitPrice;
                            $newSubTotalFinal = $jmlBahan * $finalUnitPrice;

                            // Tambahkan ke grand total
                            $grandTotal += $newSubTotalFinal;
                        @endphp
                        <input type="hidden" name="pengajuanDetails" value="{{ json_encode($this->getCartItemsForStorage()) }}">
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ $detail['nama_bahan'] }}</td>
                            <td class="px-6 py-4 text-gray-900 dark:text-white">
                                <span>{!! nl2br(e($detail['spesifikasi'] ?? '')) !!}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                                <span>{{ $detail['jml_bahan'] ?? 0 }}</span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                @if($newUnitPrice === 0 || $newUnitPrice === null)
                                    <span>{{ number_format($unitPrice, 2, ',', '.') }}</span>
                                @else
                                    <span style="text-decoration: line-through; color: red;">
                                        {{ number_format($unitPrice, 2, ',', '.') }}
                                    </span>
                                    <br>
                                    <span>{{ number_format($newUnitPrice, 2, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                @if($detail['new_sub_total'] === 0 || $detail['new_sub_total'] === null)
                                    <span>{{ number_format($subTotal, 2, ',', '.') }}</span>
                                @else
                                    <span style="text-decoration: line-through; color: red;">
                                        {{ number_format($subTotal, 2, ',', '.') }}
                                    </span>
                                    <br>
                                    <span>{{ number_format($detail['new_sub_total'], 2, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                                <span>{{ $detail['penanggungjawabaset'] ?? 0 }}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                                <span>{{ $detail['alasan'] ?? 0 }}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-900 dark:text-white">
                                <span>{!! nl2br(e($detail['keterangan_pembayaran'] ?? '')) !!}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                                <div class="inline-flex items-center">
                                    <label class="flex items-center cursor-pointer relative">
                                        <input disabled type="checkbox"
                                            @if($detail['status_pembelian'] == 1) checked @endif
                                            class="peer h-5 w-5 cursor-pointer transition-all appearance-none rounded shadow hover:shadow-md border border-slate-300 checked:bg-green-600 checked:border-green-600" />
                                        <span class="absolute text-white opacity-0 peer-checked:opacity-100 top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" stroke="currentColor" stroke-width="1">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        </span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 text-right text-black"><strong>PPN</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                {{ number_format($ppn, 2, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 text-right text-black"><strong>Total Anggaran</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                @php
                                    $totalWithExtras = $grandTotal + $ppn;
                                @endphp
                                <span><strong>Rp.</strong> {{ number_format($totalWithExtras, 2, ',', '.') }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
        @if($jenis_pengajuan === 'Pembelian Aset Lokal' )
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3 w-1/5">Nama</th>
                            <th scope="col" class="px-6 py-3 w-0.5">Spesifikasi</th>
                            <th scope="col" class="px-6 py-3 text-center w-0.5">QTY</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Harga Satuan</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Total Harga</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Penanggung Jawab Aset</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Keterangan/Alasan Pembelian Aset</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Tujuan Pembayaran</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Status Pembelian</th>

                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $grandTotal = 0;
                        @endphp

                        @foreach ($pembelianBahanDetails as $detail)
                            @php
                                $safeKey = $this->sanitizeKey($detail['nama_bahan']);
                                // $unitPrice = $unit_price_aset[$safeKey] ?? 0;
                                $unitPrice = $unit_price_aset[$safeKey] ?? ($detail['details']['unit_price'] ?? 0);

                                $jmlBahan = $detail['jml_bahan'] ?? 0;
                                $subTotal = $jmlBahan * $unitPrice;

                                $grandTotal += $subTotal;
                            @endphp

                            <input type="hidden" name="pembelianBahanDetails" value="{{ json_encode($this->getCartItemsForAset()) }}">
                            <input type="hidden" name="biaya" value="{{ json_encode($this->getCartItemsForStorageBiaya()) }}">
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                        {{ $detail['nama_bahan'] }}
                                </td>
                                <td class="px-6 py-4 text-gray-900 dark:text-white">
                                    <span>{!! nl2br(e($detail['spesifikasi'] ?? '')) !!}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                                    <span>{{ $detail['jml_bahan'] ?? 0 }}</span>
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                    @php
                                        // $namaBahan = $detail['nama_bahan'] ?? null;
                                        $safeKey = $detail['nama_bahan'];
                                        $namaBahan = $this->sanitizeKey($safeKey);
                                    @endphp

                                    @if($editingItemBahan === $namaBahan)
                                        <input
                                            autofocus
                                            wire:model="unit_price_raw.{{ $namaBahan }}"
                                            type="text"
                                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 text-right"
                                            placeholder="0"
                                            wire:blur="formatToRupiahPriceAset('{{ $namaBahan }}')"
                                            @if($status_finance === 'Disetujui') disabled @endif
                                        />
                                    @else
                                        @if($status_finance !== 'Disetujui')
                                            <span class="cursor-pointer" wire:click="editItemPriceLocalAset('{{ $namaBahan }}')">
                                                {{ number_format(
                                                    $unit_price_aset[$namaBahan] ??
                                                    ($detail['details']['unit_price'] ?? 0), 2, ',', '.'
                                                ) }}
                                            </span>
                                        @else
                                            <span>
                                                {{ number_format(
                                                    $unit_price_aset[$namaBahan] ??
                                                    ($detail['details']['unit_price'] ?? 0), 2, ',', '.'
                                                ) }}
                                            </span>
                                        @endif
                                    @endif
                                </td>

                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                    <span>{{ number_format($subTotal, 2, ',', '.') }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                                    <span>{{ $detail['penanggungjawabaset'] ?? 0 }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                                    <span>{{ $detail['alasan'] ?? 0 }}</span>
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                    <div class="flex justify-right items-right">
                                        <textarea
                                            wire:model="keterangan_pembayaran.{{ $namaBahan }}"
                                            wire:keyup="changeKeteranganAset('{{ $namaBahan }}')"
                                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            @if($status_finance === 'Disetujui') disabled @endif
                                        >{{ $detail['keterangan_pembayaran'] ?? '' }}</textarea>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                                    <div class="inline-flex items-center">
                                        <label class="flex items-center cursor-pointer relative">
                                            <input type="checkbox"
                                                wire:click="updateStatusPembelian({{ $detail['pembelian_bahan_id'] }}, {{ $detail['bahan']->id ?? 'null' }}, '{{ $detail['nama_bahan'] ?? '' }}')"
                                                @if($detail['status_pembelian'] == 1) checked @endif @unless(auth()->user()->hasRole(['purchasing', 'superadmin'])) disabled @endunless
                                                class="peer h-5 w-5 cursor-pointer transition-all appearance-none rounded shadow hover:shadow-md border border-slate-300 checked:bg-green-600 checked:border-green-600" />
                                            <span class="absolute text-white opacity-0 peer-checked:opacity-100 top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" stroke="currentColor" stroke-width="1">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        @foreach (['PPN' => 'ppn'] as $label => $field)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td colspan="2"></td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                <td class="px-6 py-4 text-right text-black"><strong>{{ $label }}</strong></td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                    @if($editingItemId === $field)
                                        <input
                                            autofocus
                                            wire:model="{{ $field }}_raw"
                                            type="text"
                                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 text-right"
                                            placeholder="0"
                                            wire:blur="formatToRupiahPPN('{{ $field }}')" @if($status_finance === 'Disetujui') disabled @endif
                                        />
                                    @else
                                        <span class="cursor-pointer" wire:click="editItem('{{ $field }}')">
                                            {{ number_format($this->$field, 2, ',', '.') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        <!-- Total Anggaran -->
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td colspan="3"></td>
                            <td class="px-6 py-4 text-right text-black"><strong>Total Anggaran</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                @php
                                    // Pastikan bahwa variabel-variabel memiliki nilai default jika tidak ada
                                    $ppn = $ppn ?? 0;

                                    // Hitung total dengan biaya tambahan
                                    $totalWithExtras = $grandTotal + $ppn;
                                @endphp
                                <span><strong>Rp.</strong> {{ number_format($totalWithExtras, 2, ',', '.') }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
        @if($jenis_pengajuan === 'Pembelian Aset Impor')
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3 w-1/5">Nama</th>
                            <th scope="col" class="px-6 py-3 w-0.5">Spesifikasi</th>
                            <th scope="col" class="px-6 py-3 text-center w-0.5">QTY</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Harga Satuan (USD)</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Total Harga (USD)</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Harga Satuan (Rp)</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Total Harga (Rp)</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Penanggung Jawab Aset</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Keterangan/Alasan Pembelian Aset</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Tujuan Pembayaran</th>
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Status Pembelian</th>

                        </tr>
                    </thead>
                    <tbody>
                        {{-- Similar structure to the local asset purchase table, but with USD and IDR pricing --}}
                        {{-- Implementation would go here --}}
                        @php
                            $grandTotal = 0;
                            $grandTotalUSD = 0;
                        @endphp
                        @foreach ($pembelianBahanDetails as $detail)
                            @php
                                $safeKey = $this->sanitizeKey($detail['nama_bahan']);
                                $unitPriceUSD = $unit_price_usd_aset[$safeKey] ?? ($detail['details_usd']['unit_price_usd_aset'] ?? 0);
                                $unitPriceIDR = $unit_price_aset[$safeKey] ?? ($detail['details']['unit_price'] ?? 0);

                                $jmlBahan = $detail['jml_bahan'] ?? 0;
                                $subTotalUSD = $jmlBahan * $unitPriceUSD;
                                $subTotalIDR = $jmlBahan * $unitPriceIDR;

                                $grandTotalUSD += $subTotalUSD;
                                $grandTotal += $subTotalIDR;
                            @endphp

                            <input type="hidden" name="pembelianBahanDetails" value="{{ json_encode($this->getCartItemsForAset()) }}">
                            <input type="hidden" name="biaya" value="{{ json_encode($this->getCartItemsForStorageBiaya()) }}">
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                        {{ $detail['nama_bahan'] }}
                                </td>
                                <td class="px-6 py-4 text-gray-900 dark:text-white">
                                    <span>{!! nl2br(e($detail['spesifikasi'] ?? '')) !!}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                                    <span>{{ $detail['jml_bahan'] ?? 0 }}</span>
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                    @php
                                        // $namaBahan = $detail['nama_bahan'] ?? null;
                                        $safeKey = $detail['nama_bahan'];
                                        $namaBahan = $this->sanitizeKey($safeKey);
                                    @endphp

                                    @if($editingItemBahan === 'usd_' . $namaBahan)
                                        <input
                                            autofocus
                                            wire:model="unit_price_usd_raw.{{ $namaBahan }}"
                                            type="number"
                                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 text-right"
                                            placeholder="0"
                                            wire:blur="formatToUSDPriceAset('{{ $namaBahan }}')"
                                            @if($status_finance === 'Disetujui') disabled @endif
                                        />
                                    @else
                                        @if($status_finance !== 'Disetujui')
                                            <span class="cursor-pointer" wire:click="editItemPriceImporAset('usd', '{{ $namaBahan }}')">
                                                {{ number_format(
                                                    $unit_price_usd_aset[$namaBahan] ??
                                                    ($detail['details_usd']['unit_price_usd_aset'] ?? 0), 2, ',', '.'
                                                ) }}
                                            </span>
                                        @else
                                            <span>
                                                {{ number_format(
                                                    $unit_price_usd_aset[$namaBahan] ??
                                                    ($detail['details_usd']['unit_price_usd_aset'] ?? 0), 2, ',', '.'
                                                ) }}
                                            </span>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                    <span>{{ number_format($subTotalUSD, 2, ',', '.') }}</span>
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                    @if($editingItemBahan === 'idr_' . $namaBahan)
                                        <input
                                            autofocus
                                            wire:model="unit_price_raw.{{ $namaBahan }}"
                                            type="text"
                                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 text-right"
                                            placeholder="0"
                                            wire:blur="formatToRupiahPriceAset('{{ $namaBahan }}')"
                                            @if($status_finance === 'Disetujui') disabled @endif
                                        />
                                    @else
                                        @if($status_finance !== 'Disetujui')
                                            <span class="cursor-pointer" wire:click="editItemPriceImporAset('idr', '{{ $namaBahan }}')">
                                                {{ number_format(
                                                    $unit_price_aset[$namaBahan] ??
                                                    ($detail['details']['unit_price'] ?? 0), 2, ',', '.'
                                                ) }}
                                            </span>
                                        @else
                                            <span>
                                                {{ number_format(
                                                    $unit_price_aset[$namaBahan] ??
                                                    ($detail['details']['unit_price'] ?? 0), 2, ',', '.'
                                                ) }}
                                            </span>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                    <span>{{ number_format($subTotalIDR, 2, ',', '.') }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                                    <span>{{ $detail['penanggungjawabaset'] ?? 0 }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                                    <span>{{ $detail['alasan'] ?? 0 }}</span>
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                    <div class="flex justify-right items-right">
                                        <textarea
                                            wire:model="keterangan_pembayaran.{{ $namaBahan }}"
                                            wire:keyup="changeKeteranganAset('{{ $namaBahan }}')"
                                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            @if($status_finance === 'Disetujui') disabled @endif
                                        >{{ $detail['keterangan_pembayaran'] ?? '' }}</textarea>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                                    <div class="inline-flex items-center">
                                        <label class="flex items-center">
                                            <input type="checkbox" wire:click="updateStatusPembelian({{ $detail['pembelian_bahan_id'] }}, {{ $detail['bahan']->id ?? 'null' }}, '{{ $detail['nama_bahan'] ?? '' }}')"
                                                @if($detail['status_pembelian'] == 1) checked @endif @unless(auth()->user()->hasRole(['purchasing', 'superadmin'])) disabled @endunless
                                                class="peer h-5 w-5 cursor-pointer transition-all appearance-none rounded shadow hover:shadow-md border border-slate-300 checked:bg-green-600 checked:border-green-600" />
                                            <span class="absolute text-white opacity-0 peer-checked:opacity-100 top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" stroke="currentColor" stroke-width="1">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        @foreach (['Shipping Cost' => ['shipping_cost_usd','shipping_cost'], 'Full Amount Fee' => ['full_amount_fee_usd','full_amount_fee'], 'Value Today Fee' => ['value_today_fee_usd','value_today_fee']] as $label => $fields)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="px-6 py-4 text-black"><strong>{{ $label }}</strong></td>
                                <td class="px-6 py-4 text-right text-black"></td>
                                <td class="px-6 py-4 text-right text-black"></td>

                                @foreach ($fields as $index => $field)
                                    @if ($index == 1)
                                        <td class="px-6 py-4 text-right text-black"></td>
                                    @endif

                                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                        @if($editingItemId === $field)
                                            <input
                                                autofocus
                                                wire:model="{{ $field }}_raw"
                                                type="text"
                                                class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 text-right"
                                                placeholder="0"
                                                wire:blur="formatToRupiah('{{ $field }}')"
                                                @if($status_finance === 'Disetujui') disabled @endif
                                            />
                                        @else
                                            @if($status_finance !== 'Disetujui')
                                                <span class="cursor-pointer" wire:click="editItem('{{ $field }}')">
                                                    @if(strpos($field, 'usd') !== false)
                                                        <strong></strong> {{ number_format($this->$field, 2, ',', '.') }}
                                                    @else
                                                        <strong></strong> {{ number_format($this->$field, 2, ',', '.') }}
                                                    @endif
                                                </span>
                                            @else
                                                <span>
                                                    @if(strpos($field, 'usd') !== false)
                                                        <strong></strong> {{ number_format($this->$field, 2, ',', '.') }}
                                                    @else
                                                        <strong></strong> {{ number_format($this->$field, 2, ',', '.') }}
                                                    @endif
                                                </span>
                                            @endif
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        <!-- Total Anggaran -->
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td colspan="3"></td>
                            <td class="px-6 py-4 text-right text-black"><strong>Total Anggaran (USD)</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                <span><strong>$</strong> {{ number_format($grandTotalUSD, 2, ',', '.') }}</span>
                            </td>
                            <td class="px-6 py-4 text-right text-black"><strong>Total Anggaran (Rp)</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                <span><strong>Rp</strong> {{ number_format($grandTotal, 2, ',', '.') }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
