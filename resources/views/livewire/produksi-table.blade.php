<div class="relative overflow-x-auto shadow-md sm:rounded-lg">
    <div class="flex flex-wrap sm:flex-nowrap items-center justify-between space-y-3">

        <div class="flex flex-wrap items-center space-x-3 w-full">
            @include('livewire.searchdata')

            @include('livewire.dataperpage')
        </div>
        <a href="{{ route('produksis.create') }}" class="inline-flex rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            Tambah
        </a>
    </div>

    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="p-4">
                    No
                </th>
                <th scope="col" class="px-6 py-3">Kode Produksi</th>
                <th scope="col" class="px-6 py-3">Mulai Produksi</th>
                <th scope="col" class="px-6 py-3">Selesai Produksi</th>
                <th scope="col" class="px-6 py-3">Nama Produk</th>
                <th scope="col" class="px-6 py-3">Jenis Produksi</th>
                <th scope="col" class="px-6 py-3">Jumlah Produksi</th>
                {{-- <th scope="col" class="px-6 py-3">Total Item</th> --}}
                <th scope="col" class="px-6 py-3">Status</th>
                {{-- <th scope="col" class="px-6 py-3">Total Harga</th> --}}
                <th scope="col" class="px-6 py-3">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($produksis as $index => $produksi)
            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <td class="px-6 py-4"><div class="text-slate-800 dark:text-slate-100">{{ $produksis->firstItem() + $index }}</div></td>
                <td class="px-6 py-3">
                    @if($produksi->status_bahan_keluar === 'Disetujui')
                        <strong><u><a href="{{ route('produksis.edit', $produksi->id) }}">{{ $produksi->kode_produksi }}</a></u></strong>
                    @else
                        <span class="text-gray-500">{{ $produksi->kode_produksi }}</span>
                    @endif
                </td>
                <td class="px-6 py-3">{{ $produksi->mulai_produksi }}</td>
                <td class="px-6 py-3">{{ $produksi->selesai_produksi }}</td>
                <td class="px-6 py-3">{{ $produksi->nama_produk }}</td>
                <td class="px-6 py-3">{{ $produksi->jenis_produksi }}</td>
                <td class="px-6 py-3">{{ $produksi->jml_produksi }}</td>
                {{-- <td class="px-6 py-3">{{ $produksi->produksiDetails->sum('qty') }}</td> --}}
                <td class="px-6 py-3">{{ $produksi->status }}</td>
                {{-- <td class="px-6 py-3">Rp {{ number_format($produksi->produksiDetails->sum('sub_total'), 2, ',', '.') }}</td> --}}
                <td class="px-6 py-4">
                    <div class="row flex space-x-2">
                    <button wire:click="deleteProduksis({{$produksi->id}})" data-modal-target="deleteproduksi-modal" data-modal-toggle="deleteproduksi-modal" class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-red-600 hover:border-red-600 focus:text-white focus:bg-red-600 focus:border-red-600 active:border-red-600 active:text-white active:bg-red-600 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none" type="button">
                        <svg class="w-[16px] h-[16px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                        </svg>
                    </button>
                    </div>
                </td>
            </tr>
            @empty
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                    <td colspan="8" class="px-6 py-4 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                        </svg>
                        <h3 class="mt-2 text-sm font-semibold text-gray-900">Data Tidak Ditemukan!</h3>
                        <p class="mt-1 text-sm text-gray-500">Maaf, data yang Anda cari tidak ada</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <!-- Table -->
    <div class="px-6 py-4">
        {{$produksis->links()}}
    </div>
    {{-- MODAL --}}
    {{-- @include('pages.jenis-bahan.edit') --}}
    @include('pages.produksis.remove')
</div>
