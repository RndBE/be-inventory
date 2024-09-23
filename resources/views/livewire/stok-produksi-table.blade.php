<div class="relative overflow-x-auto shadow-md sm:rounded-lg">
    <div class="flex flex-wrap sm:flex-nowrap items-center justify-between space-y-3">

        <div class="flex flex-wrap items-center space-x-3 w-full">
            @include('livewire.searchdata')

            @include('livewire.dataperpage')
        </div>
        <a href="{{ route('bahan-keluars.create') }}" class="inline-flex rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            Tambah
        </a>
    </div>

    <section class="bg-gray-50 py-8 antialiased dark:bg-gray-900 md:py-12">
        <div class="mx-auto max-w-screen-xl px-4 2xl:px-0">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @forelse($stokProduksis as $index => $row)
                    <div class="w-full max-w-sm bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
                        <div class="px-6 py-4">
                            <img src="{{ $row->dataBahan->gambar ? asset('images/' . $row->dataBahan->gambar) : asset('images/image-4@2x.jpg') }}" alt="Gambar {{ $row->nama_bahan }}" class="h-32 w-full object-cover rounded-lg">
                        </div>
                        <div class="px-5 pb-5">
                            <a href="#">
                                <h5 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $row->dataBahan->nama_bahan }}</h5>
                            </a>
                            <div class="flex items-center mt-2.5 mb-5">
                                <div class="flex items-center space-x-1 rtl:space-x-reverse">
                                    {{ $row->dataBahan->kode_bahan }}
                                </div>
                                <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-blue-200 dark:text-blue-800 ms-3">{{ $row->total_stok }} {{ $row->dataBahan->dataUnit->nama }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-3xl font-bold text-gray-900 dark:text-white"></span>
                            </div>
                        </div>
                    </div>
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
            </div>
            <div class="w-full text-center">
                {{ $stokProduksis->links() }}
            </div>
        </div>
    </section>
</div>
