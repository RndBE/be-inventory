<div>
    <div class="relative sm:rounded-lg pt-2">
        @if (!$isFirstTimePengambilanBahan && $bahanKeluars->isEmpty())
            <div id="alert-2" class="flex items-center p-4 mb-4 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800" role="alert">
                <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <span class="sr-only">Info</span>
                <div class="ms-3 text-sm font-medium">
                    Tidak ada pengajuan bahan!
                </div>
                <button type="button" class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-green-400 dark:hover:bg-gray-700" data-dismiss-target="#alert-2" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>
        @endif
        @if ($isFirstTimePengambilanBahan && $bahanKeluars->isNotEmpty())
            <div class="mb-4" role="status">
                {{-- Satu kartu per pengajuan. Kotak luar sengaja netral: warna
                     status ada di kartunya, dan dua lapis warna sekaligus membuat
                     layar ini berteriak untuk keadaan yang biasa saja. --}}
                <div class="space-y-3">
                    @foreach ($bahanKeluars as $keluar)
                        <x-kartu-pengajuan-bahan-keluar :keluar="$keluar"
                            judul="Pengajuan Bahan"
                            konteks="pengajuan ini" />
                    @endforeach
                </div>
            </div>

        @endif
        @if (!$isFirstTimePengambilanBahan && $bahanKeluars->isNotEmpty())
            <div class="mb-4" role="status">
                {{-- Satu kartu per pengajuan. Kotak luar sengaja netral: warna
                     status ada di kartunya, dan dua lapis warna sekaligus membuat
                     layar ini berteriak untuk keadaan yang biasa saja. --}}
                <div class="space-y-3">
                    @foreach ($bahanKeluars as $keluar)
                        <x-kartu-pengajuan-bahan-keluar :keluar="$keluar"
                            judul="Pengajuan Bahan"
                            konteks="pengajuan ini" />
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
    <div class="border-gray-900/10 pt-2">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">Bahan</th>
                        {{-- <th scope="col" class="px-6 py-3 w-0.5">Kebutuhan</th> --}}
                        <th scope="col" class="px-6 py-3 text-center">Qty</th>
                        {{-- <th scope="col" class="px-6 py-3 text-right w-0.5">Sub Total 1</th> --}}
                        <th scope="col" class="px-6 py-3 text-right">Details</th>
                        <th scope="col" class="px-6 py-3 text-right">Sub Total</th>
                        <th scope="col" class="px-6 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $grandTotal = 0;
                    @endphp
                    @foreach ($pengambilanBahanDetails as $detail)
                    <input type="hidden" name="pengambilanBahanDetails" value="{{ json_encode($this->getCartItemsForStorage()) }}">
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ $detail['bahan']->nama_bahan }}</td>
                        @php
                            // Bahan batangan stoknya tersimpan dalam cm. Tanpa pilihan satuan,
                            // "2" akan terbaca 2 cm padahal maksudnya 2 batang.
                            $panjangStandarBaris = $this->panjangStandarUntuk($detail['bahan']->id);
                            $labelBatangBaris = $panjangStandarBaris ? $this->namaUnitUntuk($detail['bahan']->id) : null;
                        @endphp
                        <td class="px-6 py-4 text-gray-900 dark:text-white text-center">
                            <div class="flex justify-center items-center">
                                <input value="{{ old('qty.'.$detail['bahan']->id, $qty[$detail['bahan']->id] ?? 0) }}"
                                    type="number"
                                    wire:model="qty.{{ $detail['bahan']->id }}"
                                    wire:keyup="updateQuantity({{ $detail['bahan']->id }})"
                                    class="bg-gray-50 w-20 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                    placeholder="0" min="0" required @if($this->produksiStatus === 'Selesai') disabled @endif/>

                                @if ($panjangStandarBaris)
                                    <select wire:model="satuan.{{ $detail['bahan']->id }}" wire:change="updateSatuan({{ $detail['bahan']->id }})"
                                        class="ml-2 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2 py-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        @if($this->produksiStatus === 'Selesai') disabled @endif>
                                        <option value="batang">{{ $labelBatangBaris }}</option>
                                        <option value="cm">cm</option>
                                    </select>
                                @endif
                            </div>
                            @if ($panjangStandarBaris)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    keluar {{ number_format($this->qtyDasar($detail['bahan']->id), 0, ',', '.') }} cm
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
                                        :unit-price="$d['unit_price']"
                                        :panjang-standar="$panjangStandarBaris ?? null"
                                        :nama-unit="$detail['bahan']->dataUnit->nama ?? null" />
                                    @if($produksiStatus !== 'Selesai')
                                        <button wire:click="decreaseQuantityPerPrice({{ $detail['bahan']->id }}, {{ $d['unit_price'] }})"
                                            class="inline-flex items-center justify-center p-1 text-sm font-medium h-6 w-6 text-gray-500 bg-white border border-gray-300 rounded-full focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
                                            type="button">
                                            {{-- <span class="sr-only">Decrease Quantity</span> --}}
                                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 2">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h16"/>
                                            </svg>
                                        </button>
                                        <button wire:click="returQuantityPerPrice({{ $detail['bahan']->id }}, {{ $d['unit_price'] }})"
                                            class="inline-flex items-center justify-center p-1 text-sm font-medium h-6 w-6 text-gray-500 bg-white border border-gray-300 rounded-full focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
                                            type="button">
                                            {{-- <span class="sr-only">Retur Quantity</span> --}}
                                            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-arrow-back"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 11l-4 4l4 4m-4 -4h11a4 4 0 0 0 0 -8h-1" /></svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </td>
                        <td class="px-6 py-4 font-semibold text-right text-gray-900 dark:text-white">
                            <span><strong></strong> {{ number_format($detail['sub_total'], 0, ',', '.') }}</span>
                        </td>
                        <td class="px-6 py-4 flex justify-center items-center">
                            @if(isset($detail['newly_added']) && $detail['newly_added'])
                                <a href="#" class="font-medium text-red-600 dark:text-red-500 hover:underline" wire:click.prevent="removeItem({{ $detail['bahan']->id }})">
                                    <svg class="w-6 h-6 text-red-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm7.707-3.707a1 1 0 0 0-1.414 1.414L10.586 12l-2.293 2.293a1 1 0 1 0 1.414 1.414L12 13.414l2.293 2.293a1 1 0 0 0 1.414-1.414L13.414 12l2.293-2.293a1 1 0 0 0-1.414-1.414L12 10.586 9.707 8.293Z" clip-rule="evenodd"/>
                                    </svg>
                                </a>
                            @endif
                        </td>
                    </tr>
                    @php
                        $subtotal = $subtotals[$detail['bahan']->id] ?? 0;
                        $grandTotal += $subtotal;
                    @endphp
                    @endforeach
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 text-right text-black"></td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"></td>
                        <td class="px-6 py-4 text-right text-black"><strong>Total Harga</strong></td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                            <span><strong>Rp.</strong> {{ number_format($produksiTotal, 0, ',', '.') }}</span>
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
                                    foreach ($pengambilanBahanDetails as $detail) {
                                        $match = false;
                                        if (isset($detail['bahan']->id) && $detail['bahan']->id == ($rusak['id'] ?? null)) $match = true;
                                        if ($match) {
                                            foreach ($detail['details'] as $d) {
                                                if ($d['unit_price'] == ($rusak['unit_price'] ?? 0)) {
                                                    $maxQty += $d['qty'];
                                                }
                                            }
                                        }
                                    }
                                @endphp
                                @php
                                    // Bahan batangan boleh ditandai rusak per batang atau per cm.
                                    // Batas maxQty di atas dihitung dalam cm, jadi ikut dikonversi
                                    // ke satuan yang dipilih — kalau tidak, batas untuk pilihan
                                    // batang akan terbaca jauh lebih longgar dari stok sebenarnya.
                                    $panjangStandarRusak = $this->panjangStandarBarisRusak($index);
                                    $labelRusak = $this->labelSatuanBarisRusak($index);
                                    $maxInputRusak = $panjangStandarRusak ? $this->maksInputRusak($index, $maxQty) : $maxQty;
                                @endphp
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                        {{ App\Models\Bahan::find($rusak['id'])->nama_bahan ?? 'Unknown' }}
                                        @if($panjangStandarRusak)
                                            <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">1 {{ $this->labelSatuanBarisRusak($index) === 'cm' ? 'Batang' : $labelRusak }} = {{ $panjangStandarRusak }} cm</span>
                                        @endif
                                    </td>
                                    {{-- <td class="px-6 py-4">
                                        <div class="flex justify-end items-center">
                                            {{ $rusak['qty'] }} x {{ number_format($rusak['unit_price'], 0, ',', '.') }}
                                            <button type="button" wire:click="returnToProduction({{ $rusak['id'] }}, {{ $rusak['unit_price'] }}, 1)" class="text-blue-600 hover:underline">
                                                <svg class="w-6 h-6 text-red-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16 10 3-3m0 0-3-3m3 3H5v3m3 4-3 3m0 0 3 3m-3-3h14v-3"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td> --}}
                                    <td class="px-6 py-4">
                                        <div class="flex justify-end items-center gap-2">
                                            {{-- Input manual qty --}}
                                            {{-- <input type="text" pattern="[0-9]+([,\.][0-9]+)?" inputmode="decimal"
                                                class="bg-gray-50 w-20 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                wire:model.defer="bahanRusak.{{ $index }}.qty"
                                                wire:change="updateRusakQty({{ $rusak['id'] }}, {{ $rusak['unit_price'] }}, $event.target.value)"> --}}
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                max="{{ $maxInputRusak }}"
                                                oninput="if(parseFloat(this.value) > parseFloat(this.max)) this.value = this.max;"
                                                inputmode="decimal"
                                                class="bg-gray-50 w-20 border border-gray-300 text-gray-900 text-sm rounded-lg
                                                    focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1
                                                    dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400
                                                    dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                wire:model.live="bahanRusak.{{ $index }}.qty"
                                                wire:change="updateRusakQty(
                                                    {{ $rusak['id'] }},
                                                    {{ $rusak['unit_price'] }},
                                                    $event.target.value
                                                )"
                                            />

                                            @if($panjangStandarRusak)
                                                <select wire:model="bahanRusak.{{ $index }}.satuan" wire:change="updateSatuanRusak({{ $index }})"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2 py-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                                    <option value="cm">cm</option>
                                                    <option value="batang">{{ $this->namaUnitTampil($rusak['id'] ?? null) }}</option>
                                                </select>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">= {{ number_format($this->qtyDasarBarisRusak($index), 0, ',', '.') }} cm</span>
                                            @endif

                                            x {{ number_format($rusak['unit_price'] ?? 0, 0, ',', '.') }} <br>

                                            {{-- Tombol hapus/cancel rusak --}}
                                            <button type="button"
                                                wire:click="returnToProduction({{ $rusak['id'] }}, {{ $rusak['unit_price'] }})"
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
                                        {{-- Dikalikan dari qty satuan dasar, bukan angka yang diketik:
                                             unit_price bahan batangan adalah harga per cm. --}}
                                        {{ number_format(round(($rusak['unit_price'] ?? 0) * $this->qtyDasarBarisRusak($index), 0), 0, ',', '.') }}

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
                                    foreach ($pengambilanBahanDetails as $detail) {
                                        $match = false;
                                        if (isset($detail['bahan']->id) && $detail['bahan']->id == ($retur['id'] ?? null)) $match = true;
                                        if ($match) {
                                            foreach ($detail['details'] as $d) {
                                                if ($d['unit_price'] == ($retur['unit_price'] ?? 0)) {
                                                    $maxQty += $d['qty'];
                                                }
                                            }
                                        }
                                    }
                                @endphp
                                @php
                                    // Bahan batangan boleh diretur per batang atau per cm. Yang
                                    // pulang dari proyek umumnya potongan, jadi default-nya cm —
                                    // opsi batang disediakan untuk batang utuh yang tidak terpakai.
                                    // Batas maxQty di atas dalam cm, jadi ikut dikonversi ke satuan
                                    // yang dipilih.
                                    $bahanReturModel = App\Models\Bahan::find($retur['id'] ?? null);
                                    $panjangStandarRetur = $this->panjangStandarBarisRetur($index);
                                    $maxInputRetur = $panjangStandarRetur ? $this->maksInputRetur($index, $maxQty) : $maxQty;
                                @endphp
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                        {{ $bahanReturModel->nama_bahan ?? 'Unknown' }}
                                        @if($panjangStandarRetur)
                                            <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">1 {{ $this->namaUnitTampil($retur['id'] ?? null) }} = {{ $panjangStandarRetur }} cm</span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex justify-end items-center gap-2">
                                            {{-- Input manual qty --}}
                                            {{-- <input type="text" pattern="[0-9]+([,\.][0-9]+)?" inputmode="decimal"
                                                class="bg-gray-50 w-20 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                wire:model.defer="bahanRetur.{{ $index }}.qty"
                                                wire:change="updateReturQty({{ $retur['id'] }}, {{ $retur['unit_price'] }}, $event.target.value)"> --}}
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                max="{{ $maxInputRetur }}"
                                                oninput="if(parseFloat(this.value) > parseFloat(this.max)) this.value = this.max;"
                                                inputmode="decimal"
                                                class="bg-gray-50 w-20 border border-gray-300 text-gray-900 text-sm rounded-lg
                                                    focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1
                                                    dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400
                                                    dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                                wire:model.live="bahanRetur.{{ $index }}.qty"
                                                wire:change="updateReturQty(
                                                    {{ $retur['id'] }},
                                                    {{ $retur['unit_price'] }},
                                                    $event.target.value
                                                )"
                                            />

                                            @if($panjangStandarRetur)
                                                <select wire:model="bahanRetur.{{ $index }}.satuan" wire:change="updateSatuanRetur({{ $index }})"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2 py-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                                    <option value="cm">cm</option>
                                                    <option value="batang">{{ $this->namaUnitTampil($retur['id'] ?? null) }}</option>
                                                </select>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">= {{ number_format($this->qtyDasarBarisRetur($index), 0, ',', '.') }} cm</span>
                                            @endif

                                            x {{ number_format($retur['unit_price'] ?? 0, 0, ',', '.') }} <br>

                                            {{-- Tombol hapus/cancel retur --}}
                                            <button type="button"
                                                wire:click="returnReturToProduction({{ $retur['id'] }}, {{ $retur['unit_price'] }})"
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
                                        {{-- Dikalikan dari qty satuan dasar, bukan angka yang diketik:
                                             unit_price bahan batangan adalah harga per cm. --}}
                                        {{ number_format(round(($retur['unit_price'] ?? 0) * $this->qtyDasarBarisRetur($index), 0), 0, ',', '.') }}

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
