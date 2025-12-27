<div>
    @props(['variant' => ''])

    <x-app.secondary-header :variant="$attributes['headerVariant']">
        <div class="flex">
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <!-- Tambahkan tombol Simpan Laporan di sini -->
            </div>
        </div>

        <!-- Header: Right side -->
        <div class="flex items-center space-x-3">
            <div class="p-1 flex items-center justify-end gap-x-2">
                <a href="{{ route('laporan-proyek.index') }}" type="button"
                    class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">Kembali</a>
                <!-- Tombol Simpan -->
                @can('simpan-laporan')
                    <button type="button" wire:click="saveToLaporanProyek"
                        class="rounded-md px-3 py-2 text-sm font-semibold bg-indigo-600 text-white">
                        Simpan
                    </button>
                @endcan
            </div>
        </div>
    </x-app.secondary-header>


    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full mx-auto">

        <div class="sm:flex sm:justify-between sm:items-center mb-2">
        </div>

        <div
            class="w-full bg-white border border-gray-200 rounded-lg p-4 shadow sm:p-8 dark:bg-gray-800 dark:border-gray-700">
            <div class="space-y-6">
                {{-- detail --}}
                <div class=" border-gray-900/10 pb-2 mb-2">
                    <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
                        {{-- Kiri --}}
                        <div class="flex items-center">
                            <label for="kode_projek" class="block text-4xl font-medium leading-6 text-gray-900 mr-2">
                                {{ $proyek->kode_projek ?? 'Tidak Ada Data' }}
                            </label>
                        </div>

                        {{-- Kosong --}}
                        <div class="flex items-center relative"></div>

                        <div class="flex flex-col mt-6 w-full">
                            <table class="w-full border-collapse">
                                <tbody>
                                    <tr>
                                        <td class="px-4 py-2 font-medium text-gray-900 w-1/3 self-start">Nama Proyek
                                        </td>
                                        <td class="px-4 py-2 w-2/3 self-start">
                                            {{ $proyek->dataKontrak->nama_kontrak ?? 'Tidak Ada Data' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 font-medium text-gray-900 w-1/3 self-start">No SPK</td>
                                        <td class="px-4 py-2 w-2/3 self-start">
                                            {{ $proyek->dataKontrak->kode_kontrak ?? 'Tidak Ada Data' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Kanan: Tabel Kontrak --}}
                        <div class="flex flex-col mt-6 w-full">
                            <table class="w-full border-collapse">
                                <tbody>
                                    <tr>
                                        <td class="px-4 py-2 font-medium text-gray-900 w-1/3 self-start">Mulai Kontrak
                                        </td>
                                        <td class="px-4 py-2 w-2/3 self-start">
                                            {{ \Carbon\Carbon::parse($proyek->dataKontrak->mulai_kontrak)->translatedFormat('j F Y') ?? 'Tidak Ada Data' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 font-medium text-gray-900 w-1/3 self-start">Selesai Kontrak
                                        </td>
                                        <td class="px-4 py-2 w-2/3 self-start">
                                            {{ \Carbon\Carbon::parse($proyek->dataKontrak->selesai_kontrak)->translatedFormat('j F Y') ?? 'Tidak Ada Data' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 font-medium text-gray-900 w-1/3 self-start">Garansi</td>
                                        <td class="px-4 py-2 w-2/3 self-start">
                                            {{ $proyek->dataKontrak->garansi ?? 'Tidak Ada Data' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- tab --}}
                <div class="container mx-auto" x-data="{
                    tab: sessionStorage.getItem('activeTab') || 'tab1'
                }" x-init="sessionStorage.removeItem('activeTab')">
                    {{-- <div class="container mx-auto" x-data="{ tab: 'tab1' }"> --}}
                    <div class="overflow-x-auto">
                        <ul class="flex flex-wrap border-b mt-6">
                            <li class="-mb-px mr-1">
                                <a href="#"
                                    class="inline-block py-2 px-4 font-semibold hover:text-violet-800 transition duration-200"
                                    :class="{ 'bg-white text-violet-700 border-l border-t border-r': tab == 'tab1' }"
                                    @click.prevent="tab = 'tab1'">
                                    List Bahan/Produk
                                </a>
                            </li>
                            <li class="-mb-px mr-1">
                                <a href="#"
                                    class="inline-block py-2 px-4 text-violet-500 hover:text-violet-800 font-semibold transition duration-200"
                                    :class="{ 'bg-white text-violet-700 border-l border-t border-r': tab == 'tab2' }"
                                    @click.prevent="tab = 'tab2'">
                                    Biaya Tambahan
                                </a>
                            </li>
                            <li class="-mb-px mr-1">
                                <a href="#"
                                    class="inline-block py-2 px-4 text-violet-500 hover:text-violet-800 font-semibold transition duration-200"
                                    :class="{ 'bg-white text-violet-700 border-l border-t border-r': tab == 'tab3' }"
                                    @click.prevent="tab = 'tab3'">
                                    Bahan Rusak
                                </a>
                            </li>
                        </ul>
                        <div class="content bg-white border-l border-r border-b">
                            <div x-show="tab == 'tab1'">
                                <div class="relative overflow-x-auto shadow-md">
                                    <table
                                        class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                                        <thead
                                            class="text-xs uppercase text-black bg-white shadow-xl border-b dark:bg-gray-800 dark:border-gray-700">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 w-1/5">Bahan</th>
                                                <th scope="col" class="px-6 py-3 text-right w-1">Qty</th>
                                                <th scope="col" class="px-6 py-3 text-right w-1">Harga Satuan
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-right w-0.5">Sub Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($projekDetails as $detail)
                                                <tr
                                                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                                        @if (!empty($detail['produk_id']))
                                                            {{ App\Models\BahanSetengahjadiDetails::find($detail['produk_id'])->nama_bahan ?? 'Nama bahan Tidak Ditemukan' }}
                                                        @elseif (!empty($detail['produk_jadis_id']))
                                                            {{ App\Models\ProdukJadiDetails::find($detail['produk_jadis_id'])->nama_produk ?? 'Nama produk Tidak Ditemukan' }}
                                                        @elseif (!empty($detail['bahan_id']))
                                                            {{ App\Models\Bahan::find($detail['bahan_id'])->nama_bahan ?? 'Nama Bahan Tidak Ditemukan' }}
                                                        @else
                                                            Nama Tidak Ditemukan
                                                        @endif

                                                        @if (!empty($detail['serial_number']))
                                                            ({{ $detail['serial_number'] }})
                                                        @endif
                                                    </td>
                                                    @php
                                                        $detailsArray = json_decode($detail['details'], true);
                                                    @endphp

                                                    <td class="items-right px-6 py-4 text-right">
                                                        @if (is_array($detailsArray))
                                                            @foreach ($detailsArray as $d)
                                                                <div class="flex flex-col space-y-2">
                                                                    <div class="flex justify-end items-center">
                                                                        <p>{{ $d['qty'] }}</p>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                    </td>

                                                    <td class="items-right px-6 py-4 text-right">
                                                        @if (is_array($detailsArray))
                                                            @foreach ($detailsArray as $d)
                                                                <div class="flex flex-col space-y-2">
                                                                    <div class="flex justify-end items-center">
                                                                        <p>{{ number_format($d['unit_price'], 2, ',', '.') }}
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                    </td>

                                                    <td
                                                        class="px-6 py-4 font-semibold text-right text-gray-900 dark:text-white">
                                                        <span><strong></strong>
                                                            {{ number_format($detail['sub_total'], 2, ',', '.') }}</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <tr
                                                class="bg-white dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                <td
                                                    class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                                </td>
                                                <td
                                                    class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                                </td>
                                                <td class="px-6 py-4 text-right text-black"><strong>Total
                                                        Harga</strong></td>
                                                <td
                                                    class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                                    <span><strong>Rp.</strong>
                                                        {{ number_format($produksiTotal, 2, ',', '.') }}</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div x-show="tab == 'tab2'">
                                <div class="overflow-x-auto mt-4">
                                    <table class="w-full text-sm border">
                                        <thead
                                            class="text-xs uppercase text-black bg-white shadow-xl border-b dark:bg-gray-800 dark:border-gray-700">
                                            <tr>
                                                <th class="px-3 py-2">Tanggal</th>
                                                <th class="px-3 py-2">Nama</th>
                                                <th class="px-3 py-2">Keterangan</th>
                                                <th class="px-3 py-2 text-center">Qty</th>
                                                <th class="px-3 py-2">Satuan</th>
                                                <th class="px-3 py-2 text-right">Unit Price</th>
                                                <th class="px-3 py-2 text-right">Total</th>
                                            </tr>
                                        </thead>
                                        {{-- <tbody x-show="@js(count($itemsAset) > 0)"> --}}
                                        <tbody>
                                            {{-- @foreach ($savedItemsAset as $index => $item) --}}
                                            {{-- wire:key="preview-{{ $index }}"> --}}
                                            {{-- @foreach ($this->items as $index => $item) --}}

                                            {{-- menampilkan data yang ada di db --}}
                                            @foreach ($items as $row)
                                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                                                    {{-- wire:key="row-{{ $item['id'] ?? $item['uuid'] ?? $index }}"> --}} wire:key="{{ $row['id'] ?? $row['uuid'] }}">
                                                    <td class="px-4 py-2 align-top">
                                                        {{-- @if (isset($editingIndex) && $editingIndex === $item['id']) --}}
                                                        @if ($editingIndex === ($row['id'] ?? $row['uuid']))
                                                            <input type="date"
                                                                class="w-full px-2 py-1 bg-transparent border focus:ring-0"
                                                                wire:model.defer="savedItemsAset.{{ $row['id'] ?? $row['uuid'] }}.tanggal">
                                                            {{-- value="{{ \Carbon\Carbon::parse($item['tanggal'])->format('Y-m-d') }}"> --}}
                                                        @else
                                                            {{-- {{ \Carbon\Carbon::parse($item['tanggal'])->translatedFormat('d F Y') }} --}}
                                                            {{ $row['tanggal'] ? \Carbon\Carbon::parse($row['tanggal'])->translatedFormat('d F Y') : '-' }}
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2 max-w-[200px] align-top">
                                                        {{-- @if (isset($editingIndex) && $editingIndex === $item['id']) --}}
                                                        @if ($editingIndex === ($row['id'] ?? $row['uuid']))
                                                            <input type="text"
                                                                class="w-full px-2 py-1 bg-transparent border focus:ring-0"
                                                                wire:model.defer="savedItemsAset.{{ $row['id'] ?? $row['uuid'] }}.nama_biaya_tambahan">
                                                        @else
                                                            {{ $row['nama_biaya_tambahan'] ?? '' }}
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2 max-w-[200px] align-top">
                                                        {{-- @if (isset($editingIndex) && $editingIndex === $item['id']) --}}
                                                        @if ($editingIndex === ($row['id'] ?? $row['uuid']))
                                                            <textarea class="w-full px-2 py-1 bg-transparent border focus:ring-0"
                                                                wire:model.defer="savedItemsAset.{{ $row['id'] ?? $row['uuid'] }}.keterangan" rows="2"></textarea>
                                                        @else
                                                            {{ $row['keterangan'] ?? '' }}
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2 text-center align-top">
                                                        {{-- @if (isset($editingIndex) && $editingIndex === $item['id']) --}}
                                                        @if ($editingIndex === ($row['id'] ?? $row['uuid']))
                                                            <input type="number"
                                                                class="w-full px-2 py-1 bg-transparent border focus:ring-0"
                                                                wire:model.defer="savedItemsAset.{{ $row['id'] ?? $row['uuid'] }}.qty">
                                                        @else
                                                            {{ $row['qty'] ?? '' }}
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2 align-top">
                                                        {{-- @if (isset($editingIndex) && $editingIndex === $item['id']) --}}
                                                        @if ($editingIndex === ($row['id'] ?? $row['uuid']))
                                                            <input type="text"
                                                                class="w-full px-2 py-1 bg-transparent border focus:ring-0"
                                                                wire:model.defer="savedItemsAset.{{ $row['id'] ?? $row['uuid'] }}.satuan">
                                                        @else
                                                            {{ $row['satuan'] ?? '' }}
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2 align-top text-right">
                                                        {{-- @if (isset($editingIndex) && $editingIndex === $item['id']) --}}
                                                        @if ($editingIndex === ($row['id'] ?? $row['uuid']))
                                                            <input type="text"
                                                                class="w-full px-2 py-1 bg-transparent border focus:ring-0"
                                                                wire:model.defer="savedItemsAset.{{ $row['id'] ?? $row['uuid'] }}.unit_price">
                                                        @else
                                                            Rp
                                                            {{ number_format($row['unit_price'] ?? 0, 2, ',', '.') }}
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2 align-top text-right">
                                                        Rp {{ number_format($row['total_biaya'] ?? 0, 2, ',', '.') }}
                                                    </td>
                                                    <td class="px-4 py-2 text-center">
                                                        {{-- @if (isset($editingIndex) && $editingIndex === $index) --}}
                                                        @if ($editingIndex === ($row['id'] ?? $row['uuid']))
                                                            <button class="text-green-600"
                                                                wire:click="updateRow({{ $row['id'] ?? $row['uuid'] }})"><svg
                                                                    xmlns="http://www.w3.org/2000/svg" width="24"
                                                                    height="24" viewBox="0 0 24 24" fill="none"
                                                                    stroke="currentColor" stroke-width="2"
                                                                    stroke-linecap="round" stroke-linejoin="round"
                                                                    class="icon icon-tabler icons-tabler-outline icon-tabler-checks">
                                                                    <path stroke="none" d="M0 0h24v24H0z"
                                                                        fill="none" />
                                                                    <path d="M7 12l5 5l10 -10" />
                                                                    <path d="M2 12l5 5m5 -5l5 -5" />
                                                                </svg></button>
                                                            <button class="text-red-600" wire:click="cancelEdit"><svg
                                                                    xmlns="http://www.w3.org/2000/svg" width="24"
                                                                    height="24" viewBox="0 0 24 24" fill="none"
                                                                    stroke="currentColor" stroke-width="2"
                                                                    stroke-linecap="round" stroke-linejoin="round"
                                                                    class="icon icon-tabler icons-tabler-outline icon-tabler-xbox-x">
                                                                    <path stroke="none" d="M0 0h24v24H0z"
                                                                        fill="none" />
                                                                    <path
                                                                        d="M12 21a9 9 0 0 0 9 -9a9 9 0 0 0 -9 -9a9 9 0 0 0 -9 9a9 9 0 0 0 9 9z" />
                                                                    <path d="M9 8l6 8" />
                                                                    <path d="M15 8l-6 8" />
                                                                </svg></button>
                                                        @else
                                                            @can('edit-biaya-tambahan')
                                                                <button class="text-blue-600"
                                                                    wire:click="editRow({{ $row['id'] ?? $row['uuid'] }})">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24"
                                                                        height="24" viewBox="0 0 24 24" fill="none"
                                                                        stroke="currentColor" stroke-width="2"
                                                                        stroke-linecap="round" stroke-linejoin="round"
                                                                        class="icon icon-tabler icons-tabler-outline icon-tabler-pencil">
                                                                        <path stroke="none" d="M0 0h24v24H0z"
                                                                            fill="none" />
                                                                        <path
                                                                            d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
                                                                        <path d="M13.5 6.5l4 4" />
                                                                    </svg>
                                                                </button>
                                                            @endcan
                                                            @can('hapus-biaya-tambahan')
                                                                @if (isset($row['id']))
                                                                    <button class="text-red-600"
                                                                        wire:click="deleteSavedRow({{ $row['id'] }})"><svg
                                                                            xmlns="http://www.w3.org/2000/svg"
                                                                            width="24" height="24"
                                                                            viewBox="0 0 24 24" fill="none"
                                                                            stroke="currentColor" stroke-width="2"
                                                                            stroke-linecap="round"
                                                                            stroke-linejoin="round">
                                                                            <path stroke="none" d="M0 0h24v24H0z"
                                                                                fill="none" />
                                                                            <path d="M4 7l16 0" />
                                                                            <path d="M10 11l0 6" />
                                                                            <path d="M14 11l0 6" />
                                                                            <path
                                                                                d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                                            <path
                                                                                d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                                        </svg>
                                                                    </button>
                                                                @endif
                                                            @endcan
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach {{-- wire:key="row-{{ $index }}"> --}}
                                            @foreach ($itemsAset as $uuid => $item)
                                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                                                    wire:key="new-{{ $item['uuid'] }}">
                                                    <td class="px-4 py-2">
                                                        <input type="date"
                                                            class="w-full px-2 py-1 bg-transparent border-0 focus:ring-0 @if (empty($item['tanggal'])) border-b @endif"
                                                            wire:model="itemsAset.{{ $item['uuid'] }}.tanggal">
                                                    </td>
                                                    <td class="px-4 py-2">
                                                        <input type="text"
                                                            class="w-full px-2 py-1 bg-transparent border-0 focus:ring-0 @if (empty($item['nama_biaya_tambahan'])) border-b @endif"
                                                            wire:model="itemsAset.{{ $item['uuid'] }}.nama_biaya_tambahan">
                                                    </td>
                                                    <td class="px-4 py-2">
                                                        <textarea
                                                            class="w-full px-2 py-1 bg-transparent border-0 focus:ring-0 @if (empty($item['keterangan'])) border-b @endif"
                                                            wire:model="itemsAset.{{ $item['uuid'] }}.keterangan" rows="2"></textarea>
                                                    </td>
                                                    <td class="px-4 py-2 text-center">
                                                        <input type="number"
                                                            class="w-full px-2 py-1 bg-transparent border-0 focus:ring-0 @if (empty($item['qty'])) border-b @endif"
                                                            wire:model="itemsAset.{{ $item['uuid'] }}.qty">
                                                    </td>
                                                    <td class="px-4 py-2">
                                                        <input type="text"
                                                            class="w-full px-2 py-1 bg-transparent border-0 focus:ring-0 @if (empty($item['satuan'])) border-b @endif"
                                                            wire:model="itemsAset.{{ $item['uuid'] }}.satuan">
                                                    </td>
                                                    <td class="px-4 py-2">
                                                        <input type="text"
                                                            class="w-full px-2 py-1 bg-transparent border-0 focus:ring-0 @if (empty($item['unit_price'])) border-b @endif"
                                                            wire:model="itemsAset.{{ $item['uuid'] }}.unit_price">
                                                    </td>
                                                    <td class="px-4 py-2">

                                                    </td>
                                                    <td class="px-4 py-2 text-center">
                                                        <button class="text-red-600"
                                                            wire:click="removeRow('{{ $item['uuid'] }}')">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24"
                                                                height="24" viewBox="0 0 24 24" fill="none"
                                                                stroke="currentColor" stroke-width="2"
                                                                stroke-linecap="round" stroke-linejoin="round">
                                                                <path stroke="none" d="M0 0h24v24H0z"
                                                                    fill="none" />
                                                                <path d="M4 7l16 0" />
                                                                <path d="M10 11l0 6" />
                                                                <path d="M14 11l0 6" />
                                                                <path
                                                                    d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                                <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <tr>
                                                <td colspan="8" class="px-6 py-4 text-left">
                                                    @can('tambah-baris')
                                                        <button type="button" wire:click="addRow"
                                                            class="text-indigo-600">Tambah Baris</button>
                                                    @endcan
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    {{-- <tr>
                                        <td colspan="100%">
                                            <livewire:biaya-tambahan-excel :proyek-id="$proyek->id" />
                                        </td>
                                    </tr> --}}
                                </div>
                            </div>
                            {{-- <div x-show="tab == 'tab2'">
                                    @if (session()->has('success'))
                                        <div style="margin-bottom:12px;color:green;">
                                            {{ session('success') }}
                                        </div>
                                    @endif
                                    <div class="relative overflow-x-auto shadow-md">
                                        @dump($rows)
                                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                            <thead
                                                class="text-xs uppercase text-black bg-white shadow-xl border-b dark:bg-gray-800 dark:border-gray-700">
                                                <tr>
                                                    <th scope="col" class="px-6 py-3">Tanggal</th>
                                                    <th scope="col" class="px-6 py-3">Nama Biaya Tambahan</th>
                                                    <th scope="col" class="px-6 py-3">Keterangan</th>
                                                    <th scope="col" class="px-6 py-3">Qty</th>
                                                    <th scope="col" class="px-6 py-3">Satuan</th>
                                                    <th scope="col" class="px-6 py-3">Unit Price</th>
                                                    <th scope="col" class="px-6 py-3">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbod>
                                                @foreach ($rows as $i => $row)
                                                    <tr>
                                                        <td>
                                                            <input type="date"
                                                                wire:model="rows.{{ $i }}.tanggal">
                                                        </td>
                                                        <td>
                                                            <input type="text"
                                                                wire:model="rows.{{ $i }}.nama_biaya_tambahan">
                                                        </td>
                                                        <td>
                                                            <input type="text"
                                                                wire:model="rows.{{ $i }}.ket">
                                                        </td>
                                                        <td>
                                                            <input type="number" step="0.01"
                                                                wire:model="rows.{{ $i }}.qty">
                                                        </td>
                                                        <td>
                                                            <input type="text"
                                                                wire:model="rows.{{ $i }}.satuan">
                                                        </td>
                                                        <td>
                                                            <input type="number" step="0.01"
                                                                wire:model="rows.{{ $i }}.unit_price">
                                                        </td>
                                                        <td style="text-align:center;">
                                                            <button
                                                                wire:click="removeRow({{ $i }})">✕</button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbod>
                                        </table>
                                    </div>
                                    <div style="margin-top:12px;display:flex;gap:8px;">
                                        <button wire:click="addRow">+ Tambah Baris</button>
                                        <button wire:click="save">Simpan</button>
                                    </div>

                                    <style>
                                        th,
                                        td {
                                            border: 1px solid #ddd;
                                            padding: 6px;
                                            min-width: 140px;
                                        }

                                        input {
                                            width: 100%;
                                            border: none;
                                            outline: none;
                                            background: transparent;
                                        }

                                        button {
                                            padding: 6px 12px;
                                            border: none;
                                            border-radius: 6px;
                                            background: #0d6efd;
                                            color: #fff;
                                            cursor: pointer;
                                        }

                                        button:hover {
                                            opacity: .9;
                                        }
                                    </style>
                                </div> --}}
                            {{-- <div x-show="tab == 'tab2'">
                                    <div class="relative overflow-x-auto shadow-md">
                                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                            <thead class="text-xs uppercase text-black bg-white shadow-xl border-b dark:bg-gray-800 dark:border-gray-700">
                                                <tr>
                                                    <th scope="col" class="px-6 py-3">Tanggal</th>
                                                    <th scope="col" class="px-6 py-3">Nama</th>
                                                    <th scope="col" class="px-6 py-3">Keterangan</th>
                                                    <th scope="col" class="px-6 py-3 text-center">Qty</th>
                                                    <th scope="col" class="px-6 py-3">Satuan</th>
                                                    <th scope="col" class="px-6 py-3 text-right">Unit Price</th>
                                                    <th scope="col" class="px-6 py-3 text-right">Total Biaya</th>
                                                    <th scope="col" class="px-6 py-3 text-center"></th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                @foreach ($savedItemsAset as $index => $item)
                                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600" wire:key="saved-{{ $index }}">
                                                        <td class="px-4 py-2 align-top">
                                                            @if (isset($editingIndex) && $editingIndex === $index)
                                                                <input type="date" class="w-full px-2 py-1 bg-transparent border focus:ring-0"
                                                                    wire:model.defer="savedItemsAset.{{ $index }}.tanggal"
                                                                    value="{{ \Carbon\Carbon::parse($item['tanggal'])->format('Y-m-d') }}">
                                                            @else
                                                                {{ \Carbon\Carbon::parse($item['tanggal'])->translatedFormat('d F Y') }}
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2 max-w-[200px] align-top">
                                                            @if (isset($editingIndex) && $editingIndex === $index)
                                                                <input type="text" class="w-full px-2 py-1 bg-transparent border focus:ring-0" wire:model.defer="savedItemsAset.{{ $index }}.nama_biaya_tambahan">
                                                            @else
                                                                {{ $item['nama_biaya_tambahan'] }}
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2 max-w-[200px] align-top">
                                                            @if (isset($editingIndex) && $editingIndex === $index)
                                                                <textarea class="w-full px-2 py-1 bg-transparent border focus:ring-0" wire:model.defer="savedItemsAset.{{ $index }}.keterangan" rows="2"></textarea>
                                                            @else
                                                                {{ $item['keterangan'] }}
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2 text-center align-top">
                                                            @if (isset($editingIndex) && $editingIndex === $index)
                                                                <input type="number" class="w-full px-2 py-1 bg-transparent border focus:ring-0" wire:model.defer="savedItemsAset.{{ $index }}.qty">
                                                            @else
                                                                {{ $item['qty'] }}
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2 align-top">
                                                            @if (isset($editingIndex) && $editingIndex === $index)
                                                                <input type="text" class="w-full px-2 py-1 bg-transparent border focus:ring-0" wire:model.defer="savedItemsAset.{{ $index }}.satuan">
                                                            @else
                                                                {{ $item['satuan'] }}
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2 align-top text-right">
                                                            @if (isset($editingIndex) && $editingIndex === $index)
                                                                <input type="text" class="w-full px-2 py-1 bg-transparent border focus:ring-0" wire:model.defer="savedItemsAset.{{ $index }}.unit_price">
                                                            @else
                                                                Rp {{ number_format($item['unit_price'], 2, ',', '.') }}
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2 align-top text-right">
                                                            Rp {{ number_format($item['total_biaya'], 2, ',', '.') }}
                                                        </td>
                                                        <td class="px-4 py-2 text-center">
                                                            @if (isset($editingIndex) && $editingIndex === $index)
                                                                <button class="text-green-600" wire:click="updateRow({{ $index }})"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-checks"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 12l5 5l10 -10" /><path d="M2 12l5 5m5 -5l5 -5" /></svg></button>
                                                                <button class="text-red-600" wire:click="cancelEdit"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-xbox-x"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 21a9 9 0 0 0 9 -9a9 9 0 0 0 -9 -9a9 9 0 0 0 -9 9a9 9 0 0 0 9 9z" /><path d="M9 8l6 8" /><path d="M15 8l-6 8" /></svg></button>
                                                            @else
                                                                @can('edit-biaya-tambahan')
                                                                    <button class="text-blue-600" wire:click="editRow({{ $index }})">
                                                                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-pencil"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /></svg>
                                                                    </button>
                                                                @endcan

                                                                @can('hapus-biaya-tambahan')
                                                                    <button class="text-red-600" wire:click="deleteSavedRow({{ $item['id'] }})"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                                        <path d="M4 7l16 0" />
                                                                        <path d="M10 11l0 6" />
                                                                        <path d="M14 11l0 6" />
                                                                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                                        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                                        </svg>
                                                                    </button>
                                                                @endcan
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                @foreach ($itemsAset as $index => $item)
                                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600" wire:key="row-{{ $index }}">
                                                        <td class="px-4 py-2">
                                                            <input type="date" class="w-full px-2 py-1 bg-transparent border-0 focus:ring-0 @if (empty($item['tanggal'])) border-b @endif" wire:model="itemsAset.{{ $index }}.tanggal">
                                                        </td>
                                                        <td class="px-4 py-2">
                                                            <input type="text" class="w-full px-2 py-1 bg-transparent border-0 focus:ring-0 @if (empty($item['nama_biaya_tambahan'])) border-b @endif" wire:model="itemsAset.{{ $index }}.nama_biaya_tambahan">
                                                        </td>
                                                        <td class="px-4 py-2">
                                                            <textarea class="w-full px-2 py-1 bg-transparent border-0 focus:ring-0 @if (empty($item['keterangan'])) border-b @endif"
                                                                wire:model="itemsAset.{{ $index }}.keterangan"
                                                                rows="2"></textarea>
                                                        </td>
                                                        <td class="px-4 py-2 text-center">
                                                            <input type="number" class="w-full px-2 py-1 bg-transparent border-0 focus:ring-0 @if (empty($item['qty'])) border-b @endif" wire:model="itemsAset.{{ $index }}.qty">
                                                        </td>
                                                        <td class="px-4 py-2">
                                                            <input type="text" class="w-full px-2 py-1 bg-transparent border-0 focus:ring-0 @if (empty($item['satuan'])) border-b @endif" wire:model="itemsAset.{{ $index }}.satuan">
                                                        </td>
                                                        <td class="px-4 py-2">
                                                            <input type="text" class="w-full px-2 py-1 bg-transparent border-0 focus:ring-0 @if (empty($item['unit_price'])) border-b @endif" wire:model="itemsAset.{{ $index }}.unit_price">
                                                        </td>
                                                        <td class="px-4 py-2 text-center">
                                                            <button class="text-red-600" wire:click="removeRow({{ $index }})">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                                    <path d="M4 7l16 0" />
                                                                    <path d="M10 11l0 6" />
                                                                    <path d="M14 11l0 6" />
                                                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                                </svg>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                <tr>
                                                    <td colspan="8" class="px-6 py-4 text-left">
                                                        @can('tambah-baris')
                                                            <button type="button" wire:click="addRow" class="text-indigo-600">Tambah Baris</button>
                                                        @endcan
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div> --}}
                            <div x-show="tab == 'tab3'">
                                @php
                                    // Hitung total sub_total dari semua bahan rusak
                                    $totalHargaBahanRusak = 0;
                                @endphp
                                @foreach ($dataBahanRusak as $index => $detail)
                                    <div id="accordion-flush-{{ $index }}" data-accordion="collapse"
                                        data-active-classes="bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                                        data-inactive-classes="text-gray-500 dark:text-gray-400">

                                        <!-- Header Accordion -->
                                        <h2 id="accordion-flush-heading-{{ $index }}">
                                            <button type="button"
                                                class="flex items-center justify-between w-full py-5 font-medium rtl:text-right text-gray-500 border-b border-gray-200 dark:border-gray-700 dark:text-gray-400 gap-3 p-8"
                                                data-accordion-target="#accordion-flush-body-{{ $index }}"
                                                aria-expanded="false"
                                                aria-controls="accordion-flush-body-{{ $index }}">
                                                <span class="font-semibold">{{ $detail['kode_transaksi'] }}</span>
                                                <svg data-accordion-icon
                                                    class="w-3 h-3 shrink-0 transition-transform duration-200 ease-in-out"
                                                    aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                                    fill="none" viewBox="0 0 10 6">
                                                    <path stroke="currentColor" stroke-linecap="round"
                                                        stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5" />
                                                </svg>
                                            </button>
                                        </h2>

                                        <!-- Body Accordion -->
                                        <div id="accordion-flush-body-{{ $index }}" class="hidden"
                                            aria-labelledby="accordion-flush-heading-{{ $index }}"
                                            class="overflow-x-auto">
                                            <div class="relative overflow-x-auto shadow-md">
                                                <table
                                                    class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                                    <thead
                                                        class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                                        <tr>
                                                            <th scope="col" class="px-6 py-3">Nama Bahan/Produk
                                                            </th>
                                                            <th scope="col" class="px-6 py-3 text-center">
                                                                Jumlah</th>
                                                            <th scope="col" class="px-6 py-3 text-right">Harga
                                                            </th>
                                                            <th scope="col" class="px-6 py-3 text-right">Sub
                                                                Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @php $subTotalBahanRusak = 0; @endphp
                                                        @foreach ($detail['bahan_rusak_details'] as $detailItem)
                                                            @php
                                                                // Pastikan sub_total dihitung dengan benar
                                                                $currentSubTotal = is_array($detailItem)
                                                                    ? $detailItem['sub_total'] ?? 0
                                                                    : $detailItem->sub_total ?? 0;
                                                                $subTotalBahanRusak += $currentSubTotal;
                                                                $totalHargaBahanRusak += $currentSubTotal;
                                                            @endphp
                                                            <tr
                                                                class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                                                <td class="px-6 py-4">
                                                                    @if (!empty($detailItem['bahan_id']) && isset($dataBahan[$detailItem['bahan_id']]))
                                                                        {{ $dataBahan[$detailItem['bahan_id']] }}
                                                                    @elseif (!empty($detailItem['produk_id']) && isset($dataProduk[$detailItem['produk_id']]))
                                                                        {{ $dataProduk[$detailItem['produk_id']] }}
                                                                        ({{ $detailItem['serial_number'] ?? 'N/A' }})
                                                                    @elseif (!empty($detailItem['produk_jadis_id']) && isset($dataProdukJadi[$detailItem['produk_jadis_id']]))
                                                                        {{ $dataProdukJadi[$detailItem['produk_jadis_id']] }}
                                                                        ({{ $detailItem['serial_number'] ?? 'N/A' }})
                                                                    @else
                                                                        Data tidak tersedia
                                                                    @endif
                                                                </td>
                                                                <td class="px-6 py-4 text-center">
                                                                    {{ is_array($detailItem) ? $detailItem['qty'] ?? '-' : $detailItem->qty ?? '-' }}
                                                                </td>
                                                                <td class="px-6 py-4 text-right">
                                                                    {{ number_format($detailItem['unit_price'] ?? 0, 2, ',', '.') }}
                                                                </td>
                                                                <td class="px-6 py-4 text-right">
                                                                    {{ number_format($currentSubTotal, 2, ',', '.') }}
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                        <tr class="bg-gray-100 dark:bg-gray-900 font-semibold">
                                                            <td colspan="3" class="text-right px-6 py-4">Total
                                                            </td>
                                                            <td class="px-6 py-4 text-right">Rp.
                                                                {{ number_format($subTotalBahanRusak, 2, ',', '.') }}
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                                <!-- Tampilkan Total Harga Keseluruhan -->
                                <div class="relative overflow-x-auto shadow-md">
                                    <table class="w-full text-lg text-left text-gray-500 dark:text-gray-400">
                                        <thead
                                            class="text-sm text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-right">Total Harga
                                                    Keseluruhan</th>
                                                <th scope="col" class="px-6 py-3 text-right">Rp.
                                                    {{ number_format($totalHargaBahanRusak, 2, ',', '.') }}</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total --}}
            <div class=" border-gray-900/10 pb-2 mb-2">
                <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
                    {{-- Kiri --}}
                    <div class="flex items-center">
                        <label for="kode_projek" class="block text-4xl font-medium leading-6 text-gray-900 mr-2">
                        </label>
                    </div>

                    {{-- Kosong --}}
                    <div class="flex items-center relative"></div>

                    <div class="flex flex-col mt-6 w-full">
                        <table class="w-full border-collapse">
                            <tbody>
                                <tr>
                                    <td class="px-4 py-2 w-2/3 self-start">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 w-2/3 self-start">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Kanan: Tabel Kontrak --}}
                    <div class="flex flex-col mt-6 w-full">
                        <table class="w-full border-b border-collapse">
                            <tbody>
                                <tr>
                                    <td class="px-4 py-2 font-medium text-gray-900 self-start text-right">Biaya
                                        Bahan/Produk:</td>
                                    <td class="px-4 py-2 self-start text-right">
                                        <span><strong>Rp.</strong>
                                            {{ number_format($produksiTotal, 2, ',', '.') }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-medium text-gray-900 self-start text-right">Biaya
                                        Tambahan:</td>
                                    <td class="px-4 py-2 self-start text-right">
                                        <strong>Rp.</strong>
                                        {{ number_format($totalHargaBiayaTambahan, 2, ',', '.') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 font-medium text-gray-900 self-start text-right">Bahan
                                        Rusak:</td>
                                    <td class="px-4 py-2 text-right">
                                        <strong>Rp.</strong>
                                        {{ number_format($totalHargaBahanRusak, 2, ',', '.') }}
                                    </td>
                                </tr>
                            </tbody>
                            <tr class="bg-gray-200 font-bold">
                                <td class="px-4 py-2 font-medium text-gray-900 self-start text-right">Total
                                    Pengeluaran:</td>
                                <td class="px-4 py-2 text-right">
                                    Rp. {{ number_format($totalKeseluruhan, 2, ',', '.') }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.addEventListener('show-toast', event => {
            Swal.fire({
                toast: true,
                icon: event.detail[0].type ?? 'success',
                title: event.detail[0].message,
                animation: true,
                position: 'top-right',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
        });
    });
</script>

</div>
