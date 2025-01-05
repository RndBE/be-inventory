<div>
    <div class="border-gray-900/10 pt-2">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3 w-1/5">Nama</th>
                        <th scope="col" class="px-6 py-3 w-0.5">Spesifikasi</th>
                        <th scope="col" class="px-6 py-3 text-center w-0.5">QTY</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Harga Satuan</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Total Harga</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">
                            <div class="flex justify-end items-start">
                                <span class="text-right">Harga Satuan</span>
                                <span class="text-[8px] bg-blue-100 me-2 px-2.5 py-0.5 rounded-full text-blue-800 dark:bg-gray-700 dark:text-blue-400 border border-blue-400">
                                    New
                                </span>
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">
                            <div class="flex justify-end items-start">
                                <span class="text-right">Total Harga</span>
                                <span class="text-[8px] bg-blue-100 me-2 px-2.5 py-0.5 rounded-full text-blue-800 dark:bg-gray-700 dark:text-blue-400 border border-blue-400">
                                    New
                                </span>
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3 w-0.5">Ket Pembayaran</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $grandTotal = 0;
                        $newGrandTotal = 0;
                    @endphp
                    @foreach ($pengajuanDetails as $detail)
                    @php
                        $newDetails = is_string($detail['new_details']) ? json_decode($detail['new_details'], true) : $detail['new_details'];
                        // dd($newDetails);

                        $unitPrice = $unit_price[$detail['bahan']->id] ?? 0;
                        $newUnitPrice = $newDetails[0]['new_unit_price'] ?? 0;
                        $jmlBahan = $detail['jml_bahan'] ?? 0;

                        // Hitung subtotal
                        $subTotal = $jmlBahan * $unitPrice;
                        $newSubTotal = $jmlBahan * $newUnitPrice;
                        // Tambahkan ke grand total
                        $grandTotal += $subTotal;
                        $newGrandTotal += $newSubTotal;
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
                            <span>{{ number_format($unitPrice, 0, ',', '.') }}</span>
                        </td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                            <span>{{ number_format($subTotal, 0, ',', '.') }}</span>
                        </td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                            <span>{{ number_format($newUnitPrice, 0, ',', '.') }}</span>
                        </td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                            <span>{{ number_format($detail['new_sub_total'], 0, ',', '.') }}</span>
                        </td>
                        <td class="px-6 py-4 text-gray-900 dark:text-white">
                            <span>{!! nl2br(e($detail['keterangan_pembayaran'] ?? '')) !!}</span>
                        </td>
                    </tr>
                    @endforeach
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                        <td class="px-6 py-4 text-right text-black"><strong>Total Anggaran</strong></td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                            <span><strong>Rp.</strong> {{ number_format($grandTotal, 0, ',', '.') }}</span>
                        </td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                            <span><strong>Rp.</strong> {{ number_format($newGrandTotal, 0, ',', '.') }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    {{-- <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
        @if($produksiStatus !== 'Selesai')
            <div class=" border-gray-900/10">
                <h1 class="mt-6"><strong>Bahan Rusak</strong></h1>
                <div class="relative overflow-x-auto shadow-md sm:rounded-lg pt-0">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3" style="width: 30%;">Bahan</th>
                                <th scope="col" class="px-6 py-3 text-right">Qty</th>
                                <th scope="col" class="px-6 py-3 text-right">Sub Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bahanRusak as $rusak)
                            <input type="hidden" name="bahanRusak" value="{{ json_encode($this->getCartItemsForBahanRusak()) }}">
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ App\Models\Bahan::find($rusak['id'])->nama_bahan ?? 'Unknown' }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-end items-center">
                                            {{ $rusak['qty'] }} x {{ number_format($rusak['unit_price'], 0, ',', '.') }}
                                            <button type="button" wire:click="returnToProduction({{ $rusak['id'] }}, {{ $rusak['unit_price'] }}, 1)" class="text-blue-600 hover:underline">
                                                <svg class="w-6 h-6 text-red-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16 10 3-3m0 0-3-3m3 3H5v3m3 4-3 3m0 0 3 3m-3-3h14v-3"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        {{ number_format($rusak['unit_price'] * $rusak['qty'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
        @if($produksiStatus !== 'Selesai')
            <div class=" border-gray-900/10">
                <h1 class="mt-6"><strong>Bahan Retur</strong></h1>
                <div class="relative overflow-x-auto shadow-md sm:rounded-lg pt-0">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3" style="width: 30%;">Bahan</th>
                                <th scope="col" class="px-6 py-3 text-right">Qty</th>
                                <th scope="col" class="px-6 py-3 text-right">Sub Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bahanRetur as $retur)
                            <input type="hidden" name="bahanRetur" value="{{ json_encode($this->getCartItemsForBahanRetur()) }}">
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ App\Models\Bahan::find($retur['id'])->nama_bahan ?? 'Unknown' }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-end items-center">
                                            {{ $retur['qty'] }} x {{ number_format($retur['unit_price'], 0, ',', '.') }}
                                            <button type="button" wire:click="returnReturToProduction({{ $retur['id'] }}, {{ $retur['unit_price'] }}, 1)" class="text-blue-600 hover:underline">
                                                <svg class="w-6 h-6 text-red-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16 10 3-3m0 0-3-3m3 3H5v3m3 4-3 3m0 0 3 3m-3-3h14v-3"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        {{ number_format($retur['unit_price'] * $retur['qty'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div> --}}

</div>
