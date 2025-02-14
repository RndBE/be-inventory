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
                                    <span>{{ number_format($unitPrice, 0, ',', '.') }}</span>
                                @else
                                    <span style="text-decoration: line-through; color: red;">
                                        {{ number_format($unitPrice, 0, ',', '.') }}
                                    </span>
                                    <br>
                                    <span>{{ number_format($newUnitPrice, 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                @if($detail['new_sub_total'] === 0 || $detail['new_sub_total'] === null)
                                    <span>{{ number_format($subTotal, 0, ',', '.') }}</span>
                                @else
                                    <span style="text-decoration: line-through; color: red;">
                                        {{ number_format($subTotal, 0, ',', '.') }}
                                    </span>
                                    <br>
                                    <span>{{ number_format($detail['new_sub_total'], 0, ',', '.') }}</span>
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
                            <td class="px-6 py-4 text-right text-black"><strong>Ongkos Kirim</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                {{ number_format($ongkir, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 text-right text-black"><strong>Asuransi</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                {{ number_format($asuransi, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 text-right text-black"><strong>Layanan</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                {{ number_format($layanan, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 text-right text-black"><strong>Jasa Aplikasi</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                {{ number_format($jasaAplikasi, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                            <td class="px-6 py-4 text-right text-black"><strong>Total Anggaran</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                @php
                                    $totalWithExtras = $grandTotal + $ongkir + $asuransi + $layanan + $jasaAplikasi;
                                @endphp
                                <span><strong>Rp.</strong> {{ number_format($totalWithExtras, 0, ',', '.') }}</span>
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
                                        <span>{{ number_format($unitPrice, 0, ',', '.') }}</span>
                                    @else
                                        <span style="text-decoration: line-through; color: red;">
                                            {{ number_format($unitPrice, 0, ',', '.') }}
                                        </span>
                                        <br>
                                        <span>{{ number_format($newUnitPrice, 0, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                    @if($detail['new_sub_total'] === 0 || $detail['new_sub_total'] === null)
                                        <span>{{ number_format($subTotal, 0, ',', '.') }}</span>
                                    @else
                                        <span style="text-decoration: line-through; color: red;">
                                            {{ number_format($subTotal, 0, ',', '.') }}
                                        </span>
                                        <br>
                                        <span>{{ number_format($detail['new_sub_total'], 0, ',', '.') }}</span>
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
                                    <span>{{ number_format($shipping_cost, 0, ',', '.') }}</span>
                                @else
                                    <span style="text-decoration: line-through; color: red;">
                                        {{ number_format($shipping_cost, 0, ',', '.') }}
                                    </span>
                                    <br>
                                    <span>{{ number_format($new_shipping_cost, 0, ',', '.') }}</span>
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
                                    <span>{{ number_format($full_amount_fee, 0, ',', '.') }}</span>
                                @else
                                    <span style="text-decoration: line-through; color: red;">
                                        {{ number_format($full_amount_fee, 0, ',', '.') }}
                                    </span>
                                    <br>
                                    <span>{{ number_format($new_full_amount_fee, 0, ',', '.') }}</span>
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
                                    <span>{{ number_format($value_today_fee, 0, ',', '.') }}</span>
                                @else
                                    <span style="text-decoration: line-through; color: red;">
                                        {{ number_format($value_today_fee, 0, ',', '.') }}
                                    </span>
                                    <br>
                                    <span>{{ number_format($new_value_today_fee, 0, ',', '.') }}</span>
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
                                <span><strong>Rp.</strong> {{ number_format($totalWithExtras, 0, ',', '.') }}</span>
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
                                    <span>{{ number_format($unitPrice, 0, ',', '.') }}</span>
                                @else
                                    <span style="text-decoration: line-through; color: red;">
                                        {{ number_format($unitPrice, 0, ',', '.') }}
                                    </span>
                                    <br>
                                    <span>{{ number_format($newUnitPrice, 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                @if($detail['new_sub_total'] === 0 || $detail['new_sub_total'] === null)
                                    <span>{{ number_format($subTotal, 0, ',', '.') }}</span>
                                @else
                                    <span style="text-decoration: line-through; color: red;">
                                        {{ number_format($subTotal, 0, ',', '.') }}
                                    </span>
                                    <br>
                                    <span>{{ number_format($detail['new_sub_total'], 0, ',', '.') }}</span>
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
                            <td class="px-6 py-4 text-right text-black"><strong>Total Anggaran</strong></td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                @php
                                    $totalWithExtras = $grandTotal;
                                @endphp
                                <span><strong>Rp.</strong> {{ number_format($totalWithExtras, 0, ',', '.') }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
