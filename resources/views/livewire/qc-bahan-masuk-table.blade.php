<div>
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

    <div class="intro-y col-span-12 flex flex-wrap sm:flex-no-wrap items-center mt-2">
        <a href="{{ route('quality-page.qc-bahan-masuk.wizard') }}">
            <button class="button text-white bg-theme-primary shadow-md mr-2">
                Tambah QC
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
                            {{ $item->tanggal_masuk_gudang ? $item->tanggal_masuk_gudang->format('d-m-Y H:i:s') : '-' }}
                        </td>
                        <td class="table-report__action w-72">
                            <div class="flex justify-start items-start space-x-4">
                                <a class="flex items-center text-theme-1"
                                href="{{ route('quality-page.qc-bahan-masuk.view', $item->id_qc_bahan_masuk) }}">
                                    <i data-feather="check-square" class="w-4 h-4 mr-1"></i> View
                                </a>

                                @if(!$item->tanggal_masuk_gudang)
                                    <button wire:click="prosesKeGudang({{ $item->id_qc_bahan_masuk }})"
                                            class="flex items-center text-green-600">
                                        <i data-feather="box" class="w-4 h-4 mr-1"></i> Add to Gudang
                                    </button>
                                @endif

                                <a class="flex items-center text-theme-6"
                                wire:click="confirmDelete({{ $item->id }})">
                                    <i data-feather="trash-2" class="w-4 h-4 mr-1"></i> Hapus
                                </a>
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

    @if ($showDeleteModal)
    <div class="modal" id="delete-confirmation-modal" style="display:block">
        <div class="modal__content">
            <div class="p-5 text-center">
                <i data-feather="x-circle" class="w-16 h-16 text-theme-6 mx-auto mt-3"></i>
                <div class="text-3xl mt-5">Yakin ingin menghapus?</div>
                <div class="text-gray-600 mt-2">Proses ini tidak dapat dibatalkan.</div>
            </div>
            <div class="px-5 pb-8 text-center">
                <button wire:click="$set('showDeleteModal', false)" class="button w-24 border text-gray-700 mr-1">Batal</button>
                <button wire:click="deleteConfirmed" class="button w-24 bg-theme-6 text-white">Hapus</button>
            </div>
        </div>
    </div>
    @endif
</div>
