<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
    @if(session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 5000
            });
        </script>
    @endif

    @if(session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: '{{ session('error') }}',
                showConfirmButton: true,
                timer: 5000
            });
        </script>
    @endif
    <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
        <ul class="flex overflow-x-auto whitespace-nowrap bg-gray-100 rounded-lg scrollbar-hide" role="tablist">
            <li class="me-2" role="presentation">
                <button wire:click="setTab('SudahMasukGudang')" class="inline-block p-4 border-b-2 rounded-t-lg {{ $selectedTab == 'SudahMasukGudang' ? 'text-purple-600 border-purple-600' : '' }}">
                    Sudah Masuk Gudang
                </button>
            </li>
            <li class="me-2" role="presentation">
                <button wire:click="setTab('BelumMasukGudang')" class="inline-block p-4 border-b-2 rounded-t-lg {{ $selectedTab == 'BelumMasukGudang' ? 'text-purple-600 border-purple-600' : '' }}">
                    Belum Masuk Gudang
                </button>
            </li>
        </ul>
    </div>

    <div class="intro-y col-span-12 flex flex-wrap sm:flex-no-wrap items-center mt-2">
        @can('tambah-qc-bahan-masuk')
        <a href="{{ route('quality-page.qc-bahan-masuk.wizard') }}">
            <button class="button text-white bg-theme-primary shadow-md mr-2">
                Tambah QC
            </button>
        </a>
        @endcan
        <a href="{{ route('purchases.index') }}">
            <button class="button text-white bg-indigo-600 hover:bg-indigo-500 shadow-md mr-2">
                Bahan Masuk
            </button>
        </a>
        <div class="hidden md:block mx-auto text-gray-600">Menampilkan {{ $qcList->firstItem() }} sampai {{ $qcList->lastItem() }} dari {{ $qcList->total() }} data</div>
        <div class="w-full sm:w-auto mt-3 sm:mt-0 sm:ml-auto md:ml-0">
            <input type="text" wire:model.live='search' class="input w-56 box pr-10 placeholder-theme-13" placeholder="Cari...">
            <i class="w-4 h-4 absolute my-auto inset-y-0 mr-3 right-0" data-feather="search"></i>
        </div>
    </div>


    <div class="intro-y col-span-12 overflow-auto lg:overflow-visible mt-5">
        <table class="table table-report -mt-2">
            <thead>
                <tr>
                    <th>Kode QC</th>
                    <th class="text-center">Tanggal QC</th>
                    <th class="text-center">Petugas QC</th>
                    <th class="text-center">Petugas Input</th>
                    <th class="text-center">Tanggal Masuk Gudang</th>
                    <th class="text-center w-72">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($qcList as $item)
                {{-- {{ dd($item->id_qc_bahan_masuk) }} --}}

                    <tr class="intro-x">
                        <td>
                            <a href="#" class="font-medium">{{ $item->kode_qc }}</a>
                            <div class="text-gray-600 text-xs">{{ $item->pembelianBahan->kode_transaksi }}</div>
                        </td>
                        <td class="text-center">{{ $item->tanggal_qc->format('d-m-Y H:i:s') }}</td>
                        <td class="text-center">{{ $item->petugasQc->name }}</td>
                        <td class="text-center">{{ $item->petugasInputQc->name }}</td>
                        <td class="text-center">
                            {{ $item->tanggal_masuk_gudang ? $item->tanggal_masuk_gudang->format('Y-m-d H:i:s') : '-' }}
                        </td>
                        <td class="table-report__action w-72">
                            <div class="flex justify-start items-start space-x-4">
                                <a class="flex items-center text-theme-1"
                                href="{{ route('quality-page.qc-bahan-masuk.view', $item->id_qc_bahan_masuk) }}">
                                    <i data-feather="check-square" class="w-4 h-4 mr-1"></i> View
                                </a>
                                @can('addgudang-qc-bahan-masuk')
                                    @if(!$item->tanggal_masuk_gudang)
                                        <button wire:click="prosesKeGudang({{ $item->id_qc_bahan_masuk }})"
                                                class="flex items-center text-green-600">
                                            <i data-feather="box" class="w-4 h-4 mr-1"></i> Add to Gudang
                                        </button>
                                    @endif
                                @endcan
                                @can('hapus-qc-bahan-masuk')
                                    <a class="flex items-center text-theme-6 cursor-pointer"
                                    wire:click="confirmDelete({{ $item->id_qc_bahan_masuk }})">
                                        <i data-feather="trash-2" class="w-4 h-4 mr-1"></i> Hapus
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-gray-500">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-5">
            {{ $qcList->links() }}
        </div>
    </div>
    @if($showDeleteModal)
        <div
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 transition-opacity duration-300"
            wire:click="$set('showDeleteModal', false)"
        >
            <div
                class="bg-white rounded-lg shadow-lg w-full max-w-md p-6"
                wire:click.stop
            >
                <div class="flex items-start justify-between">
                    <h2 class="text-xl font-bold text-gray-800">Konfirmasi Hapus</h2>
                    <button
                        wire:click="$set('showDeleteModal', false)"
                        class="text-gray-400 hover:text-gray-600 transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 8.586l4.95-4.95a1 1 0 111.414 1.414L11.414 10l4.95 4.95a1 1 0 01-1.414 1.414L10 11.414l-4.95 4.95a1 1 0 01-1.414-1.414L8.586 10 3.636 5.05a1 1 0 011.414-1.414L10 8.586z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <p class="mt-4 text-gray-600">Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak bisa dibatalkan.</p>

                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        wire:click="$set('showDeleteModal', false)"
                        class="px-4 py-2 border rounded text-gray-700 hover:bg-gray-100"
                    >
                        Batal
                    </button>
                    <button
                        wire:click="deleteConfirmed"
                        class="px-4 py-2 bg-theme-1 text-white rounded hover:bg-theme-1/90"
                    >
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
