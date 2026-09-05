<div>
    <div class="relative sm:rounded-lg pt-2">
        @if (!$isFirstTimePengajuan && $bahanKeluars->isEmpty())
            <div id="alert-2" class="flex items-center p-4 mb-4 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800" role="alert">
                <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <span class="sr-only">Info</span>
                <div class="ms-3 text-sm font-medium">
                    Tidak ada pengajuan bahan proyek!
                </div>
                <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-green-400 dark:hover:bg-gray-700" data-dismiss-target="#alert-2" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
        @endif
        @if ($isFirstTimePengajuan && $bahanKeluars->isNotEmpty())
            <div class="mb-4" role="status">
                {{-- Satu kartu per pengajuan. Kotak luar sengaja netral: warna
                     status ada di kartunya, dan dua lapis warna sekaligus membuat
                     layar ini berteriak untuk keadaan yang biasa saja. --}}
                <div class="space-y-3">
                    @foreach ($bahanKeluars as $keluar)
                        <x-kartu-pengajuan-bahan-keluar :keluar="$keluar"
                            judul="Pengajuan Bahan Proyek"
                            konteks="proyek ini" />
                    @endforeach
                </div>
            </div>


        @endif
        @if (!$isFirstTimePengajuan && $bahanKeluars->isNotEmpty())
            <div class="mb-4" role="status">
                {{-- Satu kartu per pengajuan. Kotak luar sengaja netral: warna
                     status ada di kartunya, dan dua lapis warna sekaligus membuat
                     layar ini berteriak untuk keadaan yang biasa saja. --}}
                <div class="space-y-3">
                    @foreach ($bahanKeluars as $keluar)
                        <x-kartu-pengajuan-bahan-keluar :keluar="$keluar"
                            judul="Pengajuan Bahan Proyek"
                            konteks="proyek ini" />
                    @endforeach
                </div>
            </div>


        @endif
        @if (!$isBahanReturPending && !$isBahanRusakPending)
            <!-- No pending submissions -->
            <div id="alert-3" class="flex items-center p-4 mb-4 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800" role="alert">
                <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <span class="sr-only">Info</span>
                <div class="ms-3 text-sm font-medium">
                    Tidak ada pengajuan bahan retur atau bahan rusak!
                </div>
                <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-green-400 dark:hover:bg-gray-700" data-dismiss-target="#alert-3" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
        @else
            <!-- Display pending submissions count -->
            <div id="alert-3" class="flex items-center p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800" role="alert">
                <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <span class="sr-only">Info</span>
                <div class="ms-3 text-sm font-medium">
                    @if ($isBahanReturPending)
                        Ada {{ $pendingReturCount }} pengajuan retur yang belum disetujui!
                    @endif
                    @if ($isBahanRusakPending)
                        Ada {{ $pendingRusakCount }} pengajuan bahan rusak yang belum disetujui!
                    @endif
                </div>
                <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-red-50 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-gray-700" data-dismiss-target="#alert-3" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
        @endif
    </div>
    <div class=" border-gray-900/10 pt-2">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">

            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3 w-1/5">Bahan</th>
                        <th scope="col" class="px-6 py-3 w-0.5">Qty</th>
                        <th scope="col" class="px-6 py-3 text-right w-1">Details</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Sub Total</th>
                        <th scope="col" class="px-6 py-3 text-center w-0.5">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <input type="hidden" name="produkSampleDetails" value="{{ json_encode($this->getCartItemsForStorage()) }}">
                    @foreach ($produkSampleDetails as $detail)
                    @php
                        $cartKey = $detail['cart_key'] ?? ($detail['bahan_id'] ?? $detail['produk_id'] ?? $detail['produk_jadis_id']);
                        $bahanId = $detail['bahan_id'] ?? null;
                        $produkId = $detail['produk_id'] ?? null;
                        $produkJadiId = $detail['produk_jadis_id'] ?? null;

                        $grandTotal = 0;
                        $type = $bahanId ? 'bahan' : ($produkId ? 'produk' : 'produk_jadi');

                        // Bahan batangan menyimpan angka lot dalam cm. Master bahannya
                        // diambil sekali di sini supaya kolom Details bisa menampilkan
                        // jumlah batangnya, bukan angka cm mentah.
                        $bahanBarisLot = $bahanId ? App\Models\Bahan::find($bahanId) : null;

                        // Bahan batangan boleh diminta per batang atau per cm. Tanpa
                        // pilihan satuan, "2" akan terbaca 2 cm padahal maksudnya 2 batang.
                        $panjangStandarBaris = $this->panjangStandarUntuk($cartKey);
                        $labelBatangBaris = $panjangStandarBaris ? $this->namaUnitUntuk($cartKey) : null;
                    @endphp
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                            @if ($produkJadiId)
                                {{ App\Models\ProdukJadiDetails::find($produkJadiId)->nama_produk ?? 'Produk Jadi Tidak Ditemukan' }}
                            @elseif ($produkId)
                                {{ App\Models\BahanSetengahjadiDetails::find($produkId)->nama_bahan ?? 'Produk Setengah Jadi Tidak Ditemukan' }}
                            @elseif ($bahanId)
                                {{ App\Models\Bahan::find($bahanId)->nama_bahan ?? 'Bahan Tidak Ditemukan' }}
                            @else
                                Nama Tidak Ditemukan
                            @endif

                            @if (!empty($detail['serial_number']))
                                ({{ $detail['serial_number'] }})
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                            <div class="flex items-center">
                                <input
                                    value="{{ old('qty.'.$cartKey, $qty[$cartKey] ?? 0) }}"
                                    type="number"
                                    wire:model="qty.{{ $cartKey }}"
                                    wire:keyup="updateQuantity('{{ $type }}', '{{ $cartKey }}')"
                                    class="bg-gray-50 w-20 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1"
                                    placeholder="0" min="0" required
                                    @if($this->produksiStatus === 'Selesai') disabled @endif
                                />

                                @if ($panjangStandarBaris)
                                    <select wire:model="satuan.{{ $cartKey }}" wire:change="updateSatuan('{{ $cartKey }}')"
                                        class="ml-2 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2 py-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        @if($this->produksiStatus === 'Selesai') disabled @endif>
                                        <option value="batang">{{ $labelBatangBaris }}</option>
                                        <option value="cm">cm</option>
                                    </select>
                                @endif
                            </div>
                            @if ($panjangStandarBaris)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    keluar {{ number_format($this->qtyDasar($cartKey), 0, ',', '.') }} cm
                                    &middot; 1 {{ $labelBatangBaris }} = {{ $panjangStandarBaris }} cm
                                </p>
                            @endif
                        </td>
                        <td class="items-right px-6 py-4 text-right">
                            @foreach($detail['details'] as $d)
                                <div class="flex flex-col space-y-2">
                                    <div class="flex justify-end items-center">
                                        <x-rincian-lot
                                            :qty="$d['qty']"
                                            :unit-price="$d['unit_price'] ?? 0"
                                            :desimal-harga="2"
                                            :panjang-standar="$panjangStandarBaris ?? null"
                                            :nama-unit="$bahanBarisLot?->dataUnit?->nama" />

                                        @if($produksiStatus !== 'Selesai')
                                            <button wire:click="decreaseQuantityPerPrice('{{ $cartKey }}', {{ $d['unit_price'] }})" type="button" class="inline-flex items-center justify-center p-1 text-sm font-medium h-6 w-6 text-gray-500 bg-white border border-gray-300 rounded-full focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"><svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 2">
                                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h16"/>
                                                    </svg></button>

                                            <button wire:click="returQuantityPerPrice('{{ $cartKey }}', {{ $d['unit_price'] }})" type="button" class="inline-flex items-center justify-center p-1 text-sm font-medium h-6 w-6 text-gray-500 bg-white border border-gray-300 rounded-full focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-arrow-back">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M9 11l-4 4l4 4m-4 -4h11a4 4 0 0 0 0 -8h-1" />
                                            </svg></button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </td>

                        <td class="px-6 py-4 font-semibold text-right text-gray-900 dark:text-white">
                            <span><strong></strong> {{ number_format($detail['sub_total'] ?? 0, 2, ',', '.') }}</span>
                        </td>
                        <td class="px-6 py-4 flex justify-center items-center">
                            @if(isset($detail['newly_added']) && $detail['newly_added'])
                            @php
                                $removeKey = $detail['cart_key'] ?? ($detail['bahan_id'] ?? $detail['produk_id'] ?? $detail['produk_jadis_id']);
                            @endphp
                                <a href="#" class="font-medium text-red-600 dark:text-red-500 hover:underline" wire:click.prevent="removeItem('{{ $removeKey }}')"> <svg class="w-6 h-6 text-red-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                        width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm7.707-3.707a1 1 0 0 0-1.414 1.414L10.586 12l-2.293 2.293a1 1 0 1 0 1.414 1.414L12 13.414l2.293 2.293a1 1 0 0 0 1.414-1.414L13.414 12l2.293-2.293a1 1 0 0 0-1.414-1.414L12 10.586 9.707 8.293Z"
                                            clip-rule="evenodd"/>
                                    </svg> </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 text-right text-black"></td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                        <td class="px-6 py-4 text-right text-black"><strong>Total Harga</strong></td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                            <span><strong>Rp.</strong> {{ number_format($produksiTotal ?? 0, 2, ',', '.') }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-1">
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
                            <input type="hidden" name="bahanRusak" value="{{ json_encode($this->getCartItemsForBahanRusak()) }}">
                            @foreach($bahanRusak as $index => $rusak)
                                @php
                                    // Hitung maxQty langsung di Blade agar bisa dipakai untuk batas input
                                    $maxQty = 0;
                                    foreach ($produkSampleDetails as $detail) {
                                        $match = false;
                                        if (isset($detail['bahan_id']) && $detail['bahan_id'] == ($rusak['bahan_id'] ?? null)) $match = true;
                                        if (isset($detail['produk_id']) && $detail['produk_id'] == ($rusak['produk_id'] ?? null)) $match = true;
                                        if (isset($detail['produk_jadis_id']) && $detail['produk_jadis_id'] == ($rusak['produk_jadis_id'] ?? null)) $match = true;
                                        if ($match) {
                                            foreach ($detail['details'] as $d) {
                                                if ($d['unit_price'] == ($rusak['unit_price'] ?? 0)) {
                                                    $maxQty += $d['qty'];
                                                }
                                            }
                                        }
                                    }
                                @endphp
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                        @if (!empty($rusak['produk_jadis_id']))
                                            {{ App\Models\ProdukJadiDetails::find($rusak['produk_jadis_id'])->nama_produk ?? 'Produk Jadi Tidak Ditemukan' }}
                                        @elseif (!empty($rusak['produk_id']))
                                            {{ App\Models\BahanSetengahjadiDetails::find($rusak['produk_id'])->nama_bahan ?? 'Nama Produk Tidak Ditemukan' }}
                                        @elseif (!empty($rusak['bahan_id']))
                                            {{ App\Models\Bahan::find($rusak['bahan_id'])->nama_bahan ?? 'Nama Bahan Tidak Ditemukan' }}
                                        @else
                                            Nama Tidak Ditemukan
                                        @endif

                                        @if (!empty($rusak['serial_number']))
                                            ({{ $rusak['serial_number'] }})
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-end items-center gap-2">
                                            {{-- Input manual qty --}}
                                            {{-- <input type="text" pattern="[0-9]+([,\.][0-9]+)?" inputmode="decimal"
                                                class="bg-gray-50 w-20 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                wire:model.defer="bahanRusak.{{ $index }}.qty"
                                                wire:change="updateRusakQty({{ $rusak['bahan_id'] ?? $rusak['produk_id'] ?? $rusak['produk_jadis_id'] }}, {{ $rusak['unit_price'] }}, $event.target.value)"> --}}
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="{{ $this->panjangStandarBarisRusak($index) ? $this->maksInputRusak($index, $maxQty) : $maxQty }}"
                                                    oninput="if(parseFloat(this.value) > parseFloat(this.max)) this.value = this.max;"
                                                    inputmode="decimal"
                                                    class="bg-gray-50 w-20 border border-gray-300 text-gray-900 text-sm rounded-lg
                                                        focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1
                                                        dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400
                                                        dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                    wire:model.live="bahanRusak.{{ $index }}.qty"
                                                    wire:change="updateRusakQty(
                                                        {{ $rusak['bahan_id'] ?? $rusak['produk_id'] ?? $rusak['produk_jadis_id'] }},
                                                        {{ $rusak['unit_price'] }},
                                                        $event.target.value
                                                    )"
                                                />

                                                {{-- Bahan batangan boleh diisi per batang atau per cm. Default cm karena
                                                     yang pulang dari proyek umumnya potongan sisa; opsi batang untuk
                                                     batang utuh yang tidak terpakai. --}}
                                                @if ($this->panjangStandarBarisRusak($index))
                                                    <select wire:model="bahanRusak.{{ $index }}.satuan" wire:change="updateSatuanRusak({{ $index }})"
                                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2 py-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                                        <option value="cm">cm</option>
                                                        <option value="batang">{{ $this->namaUnitTampil($rusak['bahan_id'] ?? $rusak['id'] ?? null) }}</option>
                                                    </select>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">= {{ number_format($this->qtyDasarBarisRusak($index), 0, ',', '.') }} cm</span>
                                                @endif

                                            x {{ number_format($rusak['unit_price'] ?? 0, 2, ',', '.') }}

                                            {{-- Tombol hapus/cancel rusak --}}
                                            <button type="button"
                                                wire:click="returnToProduction(
                                                    '{{ !empty($rusak['bahan_id']) ? 'bahan' : (!empty($rusak['produk_id']) ? 'produk' : 'produk_jadi') }}',
                                                    {{ $rusak['bahan_id'] ?? $rusak['produk_id'] ?? $rusak['produk_jadis_id'] ?? 0 }},
                                                    {{ $rusak['unit_price'] ?? 0 }}
                                                )"
                                                class="text-blue-600 hover:underline">
                                                <svg class="w-6 h-6 text-red-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                                    width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="m16 10 3-3m0 0-3-3m3 3H5v3m3 4-3 3m0 0 3 3m-3-3h14v-3"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-right">
                                        {{ number_format(
                                            (floatval($rusak['unit_price'] ?? 0) * floatval(str_replace(',', '.', $rusak['qty'] ?? 0))),
                                            2, ',', '.'
                                        ) }}
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
                            <input type="hidden" name="bahanRetur" value="{{ json_encode($this->getCartItemsForBahanRetur()) }}">
                            @foreach($bahanRetur as $index => $retur)
                                @php
                                    // Hitung maxQty langsung di Blade agar bisa dipakai untuk batas input
                                    $maxQty = 0;
                                    foreach ($produkSampleDetails as $detail) {
                                        $match = false;
                                        if (isset($detail['bahan_id']) && $detail['bahan_id'] == ($retur['bahan_id'] ?? null)) $match = true;
                                        if (isset($detail['produk_id']) && $detail['produk_id'] == ($retur['produk_id'] ?? null)) $match = true;
                                        if (isset($detail['produk_jadis_id']) && $detail['produk_jadis_id'] == ($retur['produk_jadis_id'] ?? null)) $match = true;
                                        if ($match) {
                                            foreach ($detail['details'] as $d) {
                                                if ($d['unit_price'] == ($retur['unit_price'] ?? 0)) {
                                                    $maxQty += $d['qty'];
                                                }
                                            }
                                        }
                                    }
                                @endphp
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                        @if (!empty($retur['produk_jadis_id']))
                                            {{ App\Models\ProdukJadiDetails::find($retur['produk_jadis_id'])->nama_produk ?? 'Produk Jadi Tidak Ditemukan' }}
                                        @elseif (!empty($retur['produk_id']))
                                            {{ App\Models\BahanSetengahjadiDetails::find($retur['produk_id'])->nama_bahan ?? 'Nama Produk Tidak Ditemukan' }}
                                        @elseif (!empty($retur['bahan_id']))
                                            {{ App\Models\Bahan::find($retur['bahan_id'])->nama_bahan ?? 'Nama Bahan Tidak Ditemukan' }}
                                        @else
                                            Nama Tidak Ditemukan
                                        @endif

                                        @if (!empty($retur['serial_number']))
                                            ({{ $retur['serial_number'] }})
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-end items-center gap-2">
                                            {{-- Input manual qty --}}
                                            {{-- <input type="text" pattern="[0-9]+([,\.][0-9]+)?" inputmode="decimal"
                                                class="bg-gray-50 w-20 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                wire:model.defer="bahanRetur.{{ $index }}.qty"
                                                wire:change="updateReturQty({{ $retur['bahan_id'] ?? $retur['produk_id'] ?? $retur['produk_jadis_id'] }}, {{ $retur['unit_price'] }}, $event.target.value)"> --}}
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                max="{{ $this->panjangStandarBarisRetur($index) ? $this->maksInputRetur($index, $maxQty) : $maxQty }}"
                                                oninput="if(parseFloat(this.value) > parseFloat(this.max)) this.value = this.max;"
                                                inputmode="decimal"
                                                class="bg-gray-50 w-20 border border-gray-300 text-gray-900 text-sm rounded-lg
                                                    focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1
                                                    dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400
                                                    dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                wire:model.live="bahanRetur.{{ $index }}.qty"
                                                wire:change="updateReturQty(
                                                    {{ $retur['bahan_id'] ?? $retur['produk_id'] ?? $retur['produk_jadis_id'] }},
                                                    {{ $retur['unit_price'] }},
                                                    $event.target.value
                                                )"
                                            />

                                            {{-- Bahan batangan boleh diisi per batang atau per cm. Default cm karena
                                                 yang pulang dari proyek umumnya potongan sisa; opsi batang untuk
                                                 batang utuh yang tidak terpakai. --}}
                                            @if ($this->panjangStandarBarisRetur($index))
                                                <select wire:model="bahanRetur.{{ $index }}.satuan" wire:change="updateSatuanRetur({{ $index }})"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2 py-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                                    <option value="cm">cm</option>
                                                    <option value="batang">{{ $this->namaUnitTampil($retur['bahan_id'] ?? $retur['id'] ?? null) }}</option>
                                                </select>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">= {{ number_format($this->qtyDasarBarisRetur($index), 0, ',', '.') }} cm</span>
                                            @endif

                                            x {{ number_format($retur['unit_price'] ?? 0, 2, ',', '.') }}

                                            {{-- Tombol hapus/cancel retur --}}
                                            <button type="button"
                                                wire:click="returnReturToProduction(
                                                    '{{ !empty($retur['bahan_id']) ? 'bahan' : (!empty($retur['produk_id']) ? 'produk' : 'produk_jadi') }}',
                                                    {{ $retur['bahan_id'] ?? $retur['produk_id'] ?? $retur['produk_jadis_id'] ?? 0 }},
                                                    {{ $retur['unit_price'] ?? 0 }}
                                                )"
                                                class="text-blue-600 hover:underline">
                                                <svg class="w-6 h-6 text-red-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                                    width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="m16 10 3-3m0 0-3-3m3 3H5v3m3 4-3 3m0 0 3 3m-3-3h14v-3"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-right">
                                        {{ number_format(
                                            (floatval($retur['unit_price'] ?? 0) * floatval(str_replace(',', '.', $retur['qty'] ?? 0))),
                                            2, ',', '.'
                                        ) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
