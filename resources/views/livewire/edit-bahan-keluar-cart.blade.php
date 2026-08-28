<div>
    <div class="relative sm:rounded-lg pt-2">

    </div>
    <div class="border-gray-900/10 pt-2">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3 w-1/5">Bahan</th>
                        <th scope="col" class="px-6 py-3 w-0.5">Stok</th>
                        <th scope="col" class="px-6 py-3 w-0.5">QTY</th>
                        @if ($status !== 'Disetujui')
                            <th scope="col" class="px-6 py-3 text-right w-0.5">Sub Total</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @php
                        $grandTotal1 = 0;
                        $grandTotal2 = 0;
                    @endphp
                    <input type="hidden" name="bahanKeluarDetails" value="{{ json_encode($this->getCartItemsForStorage()) }}">
                    @foreach ($bahanKeluarDetails as $detail)
                    {{-- @php
                        dd($bahanKeluarDetails );
                    @endphp --}}

                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                            {{ $detail['bahan']->nama_bahan ?? $detail['bahan']->nama_bahan ?? $detail['bahan']->nama_produk ?? null }}
                            @if (!empty($detail['serial_number']))
                                ({{ $detail['serial_number'] }})
                            @endif
                        </td>

                        @php
                            // Halaman ini cuma menampilkan; qty-nya readonly, jadi tidak perlu
                            // pilihan satuan. Yang ditampilkan adalah angka dalam satuan yang
                            // dipakai saat mengajukan — 10 batang tetap terbaca 10 — sementara
                            // panjang cm-nya jadi keterangan di sebelahnya.
                            $bahanBaris = $detail['bahan_id'] ? App\Models\Bahan::find($detail['bahan_id']) : null;
                            $panjangStandarBaris = $bahanBaris?->panjang_standar;
                            $idBaris = $detail['bahan_id'] ?? $detail['produk_id'] ?? $detail['produk_jadis_id'];
                            $satuanBaris = $satuanInput[$idBaris] ?? null;
                            $diajukanPerBatang = $panjangStandarBaris
                                && $satuanBaris === App\Helpers\SatuanBahanHelper::SATUAN_BATANG;
                            $qtyDasarBaris = $qty[$idBaris] ?? 0;
                        @endphp
                        <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                            <div class="flex items-center">
                                {{ $panjangStandarBaris
                                    ? $bahanBaris->formatQty($detail['stok'] ?? 0)
                                    : trim(number_format($detail['stok'] ?? 0, 2, ',', '.') . ' ' . ($bahanBaris?->dataUnit?->nama ?? '')) }}
                            </div>
                        </td>

                        <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                            <div class="flex items-center">
                                {{-- Terikat ke qtyTampil, bukan qty: qty adalah angka satuan ledger
                                     yang dipakai menghitung subtotal dan mengalokasikan lot, dan tidak
                                     boleh ikut berubah hanya karena tampilannya diganti. --}}
                                <input
                                    type="number"
                                    wire:model="qtyTampil.{{ $idBaris }}"
                                    class="bg-gray-50 w-20 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 border-transparent"
                                    placeholder="0" min="0"
                                    value="{{ $qtyTampil[$idBaris] ?? 0 }}" readonly
                                />
                                @if ($diajukanPerBatang)
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $bahanBaris->dataUnit->nama ?? 'Batang' }}</span>
                                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">= {{ number_format($qtyDasarBaris, 0, ',', '.') }} cm</span>
                                @elseif ($panjangStandarBaris)
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">cm</span>
                                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">= {{ $bahanBaris->formatQty($qtyDasarBaris) }}</span>
                                @elseif ($bahanBaris?->dataUnit?->nama)
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $bahanBaris->dataUnit->nama }}</span>
                                @endif
                            </div>
                        </td>

                        @if ($status !== 'Disetujui')
                            <td class="px-6 py-4 font-semibold text-right text-gray-900 dark:text-white">
                                <span><strong>Rp.</strong> {{ number_format($subtotals[$detail['bahan']->id ?? $detail['bahan']->produk_id] ?? 0, 2, ',', '.') }}</span>
                            </td>
                            @php $grandTotal1 += $subtotals[$detail['bahan']->id ?? $detail['bahan']->produk_id] ?? 0; @endphp
                        @endif

                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
