<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

    @if (session('success'))
        <div id="successAlert" class="flex items-center p-4 mb-4 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800" role="alert">
            <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/></svg>
            <div><strong class="font-bold">Success!</strong> <span class="font-medium">{{ session('success') }}</span></div>
        </div>
    @endif

    @if (session('error'))
        <div id="errorAlert" class="flex items-center p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800" role="alert">
            <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/></svg>
            <div><strong class="font-bold">Error!</strong> <span class="font-medium">{{ session('error') }}</span></div>
        </div>
    @endif

    {{-- Header --}}
    <div class="sm:flex sm:justify-between sm:items-center mb-4">
        <div class="mb-4 sm:mb-0">
            <h6 class="text-2xl text-gray-800 dark:text-gray-100 font-bold">Kalkulasi Restock Produk Jadi</h6>
            <p class="text-sm text-gray-500 mt-1">Pilih Product Number untuk menghitung kekurangan komponen</p>
        </div>
        <div class="flex gap-2">
            @if($showResult)
                <button wire:click="exportExcel"
                    class="rounded-md border border-green-600 bg-green-50 px-4 py-2 text-sm font-semibold text-green-700 shadow-sm hover:bg-green-100 flex items-center gap-2">
                    <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M14.707 7.793a1 1 0 0 0-1.414 0L11 10.086V1.5a1 1 0 0 0-2 0v8.586L6.707 7.793a1 1 0 1 0-1.414 1.414l4 4a1 1 0 0 0 1.416 0l4-4a1 1 0 0 0-.002-1.414Z"/>
                        <path d="M18 12h-2.55l-2.975 2.975a3.5 3.5 0 0 1-4.95 0L4.55 12H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2Zm-3 5a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"/>
                    </svg>
                    Export Excel
                </button>
                <button wire:click="resetKalkulasi"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                    ← Pilih Ulang
                </button>
            @else
                <button wire:click="kalkulasi"
                    class="rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm
                    {{ empty($selectedIds) ? 'opacity-50 cursor-not-allowed' : '' }}"
                    style="background-color:#b40404;"
                    onmouseover="this.style.backgroundColor='#a00303'"
                    onmouseout="this.style.backgroundColor='#b40404'"
                    @if(empty($selectedIds)) disabled @endif>
                    Kalkulasi ({{ count($selectedIds) }} Dipilih)
                </button>
            @endif
        </div>
    </div>

    @if(!$showResult)
    {{-- ====== SECTION: PILIH PRODUCT NUMBER ====== --}}
    <div class="mb-4 flex flex-wrap gap-2 items-center justify-between">
        <div class="flex gap-2 flex-wrap items-center">
            @include('livewire.searchdata')
        </div>
        @if(!empty($selectedIds))
            <span class="text-sm text-gray-600">Dipilih: <strong>{{ count($selectedIds) }}</strong> Product Number</span>
        @endif
    </div>

    <div class="w-full bg-white border border-gray-200 rounded-lg p-4 shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center">
            <label for="product_number_id" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4 dark:text-white">
                Product Number
                <sup class="text-red-500 text-base">*</sup>
            </label>
            <select id="product_number_id"
                wire:model="selectedProductId"
                wire:change="addSelectedProduct"
                class="dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 block rounded-md border-0 py-1.5 w-3/4 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                <option value="">-- Pilih Product Number --</option>
                @foreach($items as $produk)
                    @if($produk->dataBahan)
                        <option value="{{ $produk->dataBahan->id }}">{{ $produk->dataBahan->nama_bahan }}</option>
                    @endif
                @endforeach
            </select>
        </div>

        @if($selectedProducts->isNotEmpty())
            <div class="relative overflow-x-auto mt-4">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-3">Product Number Dipilih</th>
                            <th class="px-6 py-3 text-right">Jumlah Produksi</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($selectedProducts as $produk)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-6 py-3 font-semibold text-gray-900 dark:text-white">
                                    {{ $produk->dataBahan->nama_bahan ?? '-' }}
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <input type="number"
                                        min="1"
                                        wire:model.live="productionQty.{{ $produk->bahan_id }}"
                                        class="ml-auto w-28 rounded-md border-0 py-1.5 text-right text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                        placeholder="1">
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <button type="button"
                                        wire:click="removeSelectedProduct({{ $produk->bahan_id }})"
                                        class="text-sm font-medium text-red-600 hover:text-red-700">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @else
    {{-- ====== SECTION: HASIL KALKULASI ====== --}}
    @php
        $totalKurang   = collect($kalkulasiResult)->where('status', 'Kurang')->count();
        $totalPas      = collect($kalkulasiResult)->where('status', 'Pas')->count();
        $totalCukup    = collect($kalkulasiResult)->where('status', 'Cukup')->count();
        $totalEstimasi = collect($kalkulasiResult)->sum('total_kekurangan_biaya');
    @endphp

    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
        Hasil kalkulasi untuk <strong>{{ count($selectedIds) }}</strong> Product Number yang dipilih.
        <span class="text-red-700 font-semibold">{{ $totalKurang }} komponen kurang stok</span> &bull;
        <span class="text-yellow-600 font-semibold">{{ $totalPas }} komponen stok pas</span> &bull;
        <span class="text-green-700 font-semibold">{{ $totalCukup }} komponen stok cukup</span>.
        <br>
        Estimasi biaya total restock: <strong>Rp {{ number_format($totalEstimasi, 0, ',', '.') }}</strong>
    </div>

    @if(!empty($selectedSummary))
        <div class="mb-4 relative overflow-x-auto shadow-sm sm:rounded-lg border border-gray-200">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3">Product Number</th>
                        <th class="px-6 py-3 text-right">Jumlah Produksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($selectedSummary as $summary)
                        <tr class="bg-white border-b last:border-b-0 dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-6 py-3 font-semibold text-gray-900 dark:text-white">{{ $summary['nama_bahan'] }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white">
                                {{ number_format($summary['jumlah_produksi'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th class="p-4">No</th>
                    <th class="px-6 py-3">Kode Bahan</th>
                    <th class="px-6 py-3">Nama Bahan</th>
                    <th class="px-6 py-3">Unit</th>
                    <th class="px-6 py-3">Stok Sekarang</th>
                    <th class="px-6 py-3">Harga Terakhir</th>
                    <th class="px-6 py-3">Total Dibutuhkan</th>
                    <th class="px-6 py-3">Kekurangan</th>
                    <th class="px-6 py-3">Estimasi Biaya</th>
                    <th class="px-6 py-3">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kalkulasiResult as $index => $row)
                    <tr class="border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600
                        {{ $row['status'] === 'Kurang' ? 'bg-red-50' : ($row['status'] === 'Pas' ? 'bg-yellow-50' : 'bg-white') }}">
                        <td class="px-4 py-3">{{ $index + 1 }}</td>
                        <td class="px-6 py-3"><span class="font-mono text-xs">{{ $row['kode_bahan'] }}</span></td>
                        <td class="px-6 py-3 font-medium text-gray-900">{{ $row['nama_bahan'] }}</td>
                        <td class="px-6 py-3">{{ $row['unit'] }}</td>
                        <td class="px-6 py-3">
                            <span class="font-semibold {{ $row['stok_sekarang'] == 0 ? 'text-red-600' : 'text-gray-700' }}">
                                {{ number_format($row['stok_sekarang'], 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="px-6 py-3">Rp {{ number_format($row['harga_terakhir'], 0, ',', '.') }}</td>
                        <td class="px-6 py-3 font-semibold text-gray-700">
                            {{ number_format($row['total_butuh'], 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-3">
                            <span class="font-bold {{ $row['kekurangan'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                {{ $row['kekurangan'] > 0 ? '- ' . number_format($row['kekurangan'], 0, ',', '.') : '0' }}
                            </span>
                        </td>
                        <td class="px-6 py-3">
                            @if($row['total_kekurangan_biaya'] > 0)
                                <span class="font-semibold text-red-600">- Rp {{ number_format($row['total_kekurangan_biaya'], 0, ',', '.') }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-3">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold
                                {{ $row['status'] === 'Kurang' ? 'bg-red-100 text-red-700' : ($row['status'] === 'Pas' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700') }}">
                                {{ $row['status'] }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-6 py-8 text-center text-gray-500">
                            Tidak ada data bahan pada Product Number yang dipilih.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if(!empty($kalkulasiResult))
            <tfoot class="bg-gray-100 font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                <tr>
                    <td colspan="8" class="px-6 py-3 text-right">Total Estimasi Biaya Restock:</td>
                    <td class="px-6 py-3 text-orange-600 font-bold">Rp {{ number_format($totalEstimasi, 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    @endif
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const delay = 5000;
        const successAlert = document.getElementById('successAlert');
        if (successAlert) setTimeout(() => successAlert.style.display = 'none', delay);
        const errorAlert = document.getElementById('errorAlert');
        if (errorAlert) setTimeout(() => errorAlert.style.display = 'none', delay);
    });
</script>
