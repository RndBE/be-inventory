<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
    <x-app.memuat />

    @if (session('success'))
        <x-app.alert type="success">
            <span class="font-medium">{{ session('success') }}</span>
        </x-app.alert>
    @endif

    @if (session('error'))
        <x-app.alert type="error">
            <span class="font-medium">{{ session('error') }}</span>
        </x-app.alert>
    @endif
    <div class="sm:flex sm:justify-between sm:items-center mb-2">

        <div class="mb-4 sm:mb-0">
            <h6 class="text-2xl text-gray-800 dark:text-gray-100 font-bold">Rekapitulasi Aset</h6>
        </div>

        <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
            <ul class="flex flex-wrap -m-1">
                <li class="m-1">
                    @include('livewire.searchdata', ['debounceMs' => 400])
                </li>
                <li class="m-1">
                    @include('livewire.dataperpage')
                </li>
                <li class="m-1">

                </li>
                <li class="m-1">
                    {{-- Import ditegakkan controller dengan tambah-rekap-aset, jadi
                         tombolnya ikut digerbangi permission yang sama. Tanpa @can,
                         yang tidak berhak melihat tombolnya lalu kena 403. --}}
                    @can('tambah-rekap-aset')
                        @include('pages.rekap_aset.import')
                    @endcan
                </li>
                <li class="m-1">
                    @can('tambah-rekap-aset')
                        <a href="{{ route('rekap-aset.create') }}" class="mt-2 block w-fit rounded-md py-1.5 px-3 bg-indigo-600 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            Tambah
                        </a>
                    @endcan
                </li>

            </ul>
        </div>
    </div>

    {{-- Filter penempatan: ruangan & PIC.
         Jumlah aset dicantumkan di setiap pilihan supaya sebaran aset terbaca
         tanpa harus memilihnya satu per satu. Pilihan yang tidak punya aset
         tidak dimunculkan sama sekali — memilihnya cuma menghasilkan tabel kosong. --}}
    @php
        $kelasSelect = 'rounded-md border-0 py-1.5 pl-3 pr-8 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-600';
        // search ikut dihitung, supaya tombol "Reset filter" juga muncul saat yang
        // aktif hanya pencarian — sebelumnya tabel bisa tersaring tanpa satu pun
        // cara membersihkannya selain menghapus teksnya manual.
        $adaFilter = $filterRuangan !== '' || $filterPic !== '' || $search !== '';
    @endphp
    <div class="flex flex-wrap items-center gap-2 pt-1">
        <label class="flex items-center gap-1.5 text-xs font-medium text-gray-600 dark:text-gray-400">
            Ruangan
            <select wire:model.live="filterRuangan" class="{{ $kelasSelect }}">
                <option value="">Semua ruangan</option>
                @foreach ($opsiRuangan as $opsi)
                    <option value="{{ $opsi['nilai'] }}">{{ $opsi['label'] }} ({{ $opsi['jumlah'] }})</option>
                @endforeach
            </select>
        </label>

        <label class="flex items-center gap-1.5 text-xs font-medium text-gray-600 dark:text-gray-400">
            PIC
            <select wire:model.live="filterPic" class="{{ $kelasSelect }}">
                <option value="">Semua PIC</option>
                @foreach ($opsiPic as $opsi)
                    <option value="{{ $opsi['nilai'] }}">{{ $opsi['label'] }} ({{ $opsi['jumlah'] }})</option>
                @endforeach
            </select>
        </label>

        @if ($adaFilter)
            <button type="button" wire:click="resetFilter"
                class="rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                Reset filter
            </button>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ $rekap_asets->total() }} aset cocok
            </span>
        @endif
    </div>
    <div class="relative overflow-x-auto pt-2">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            {{-- Diredupkan + dikunci klik selama permintaan berjalan. pointer-events-none
                 sengaja hanya di <table>, bukan di pembungkusnya, supaya kolom pencarian
                 dan filter di toolbar tetap bisa dipakai sambil menunggu. --}}
            <table wire:loading.class.delay="opacity-50 pointer-events-none"
                class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="p-4">
                            No
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Nomor Aset
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Link Gambar
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Tanggal Perolehan
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Barang Aset
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Merek / Tipe
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Jumlah
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Harga Perolehan
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Kondisi
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Keterangan
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Penanggung Jawab
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Divisi
                        </th>
                        <th scope="col" class="px-6 py-3">
                            PIC Pemegang
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Ruangan
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Status Pinjam
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rekap_asets as $index => $row)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4"><div class="text-slate-800 dark:text-slate-100">{{ $rekap_asets->firstItem() + $index }}</div></td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <div class="text-gray-900 dark:text-white">{{ $row->nomor_aset }}</div>
                                <div class="text-xs text-gray-500">
                                    Input {{ $row->created_at?->format('d/m/Y') ?? '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-3">
                                @if($row->link_gambar)
                                    @php
                                        // Satu sumber penguraian untuk semua tempat — lihat
                                        // GoogleDriveHelper untuk daftar bentuk tautan yang ditangani.
                                        $thumbnail = \App\Helpers\GoogleDriveHelper::thumbnail($row->link_gambar, 120);
                                    @endphp
                                    @if($thumbnail)
                                        <button wire:click="showGambar({{ $row->id }})" type="button"
                                            class="block group relative overflow-hidden rounded-lg border border-gray-200 hover:border-indigo-400 transition-all duration-150 shadow-sm hover:shadow-md"
                                            title="Klik untuk lihat gambar penuh">
                                            <img
                                                src="{{ $thumbnail }}"
                                                alt="Foto aset {{ $row->nomor_aset }}"
                                                loading="lazy" decoding="async"
                                                class="w-16 h-16 object-cover rounded-lg group-hover:scale-105 transition-transform duration-150"
                                                onerror="this.onerror=null; this.src=''; this.parentElement.innerHTML='<span class=\'text-xs text-gray-400\'>Gagal load</span>';"
                                            >
                                            <span class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-white opacity-0 group-hover:opacity-100 transition drop-shadow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
                                                </svg>
                                            </span>
                                        </button>
                                    @else
                                        <a href="{{ $row->link_gambar }}" target="_blank"
                                            class="text-xs text-indigo-600 hover:underline">
                                            Lihat File
                                        </a>
                                    @endif
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-3">{{ $row->tgl_perolehan }}</td>
                            <td class="px-6 py-3">{{ $row->barangAset->nama_barang  ?? null }}</td>
                            <td class="px-6 py-3">
                                @if($row->merek)
                                    {{ $row->merek }}
                                @else
                                    <span class="text-xs text-gray-400" title="Belum diisi — dicetak sebagai '-' di BAST">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-center">{{ $row->jumlah_aset }}</td>
                            <td class="px-6 py-3">Rp {{ number_format($row->harga_perolehan, 2, ',', '.') }}</td>
                            <td class="px-6 py-3">
                                @if($row->kondisi === 'Baik')
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">
                                        {{ $row->kondisi }}
                                    </span>
                                @elseif($row->kondisi === 'Rusak')
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-800">
                                        {{ $row->kondisi }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-800">
                                        {{ $row->kondisi }}
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-3">{{ $row->keterangan }}</td>
                            <td class="px-6 py-3">{{ $row->dataUser->name ?? null }}</td>
                            <td class="px-6 py-3">{{ $row->dataUser->dataJobPosition->nama ?? null }}</td>
                            <td class="px-6 py-3">{{ $row->dataPic->name ?? '-' }}</td>
                            <td class="px-6 py-3">{{ $row->dataRuangan->nama_ruangan ?? '-' }}</td>
                            <td class="px-6 py-3">
                                @if($row->peminjamanAktif)
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">
                                        Dipinjam
                                    </span>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $row->peminjamanAktif->peminjamanAset->dataUser->name ?? '-' }}
                                        @if($row->peminjamanAktif->peminjamanAset->tgl_pinjam)
                                            <br>sejak {{ $row->peminjamanAktif->peminjamanAset->tgl_pinjam }}
                                        @endif
                                    </div>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-800">
                                        Tersedia
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="row flex space-x-2">
                                    <!-- Detail Button -->
                                    <!-- Edit Button -->
                                    @can('edit-rekap-aset')
                                        <a href="{{ route('rekap-aset.edit', $row->id) }}" class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-yellow-600 hover:border-yellow-600 focus:text-white focus:bg-yellow-600 focus:border-yellow-600 active:border-yellow-600 active:text-white active:bg-yellow-600 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                                            <svg class="w-[16px] h-[16px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
                                            </svg>
                                        </a>
                                    @endcan

                                    {{-- Riwayat perpindahan PIC & ruangan --}}
                                    <button wire:click="showRiwayat({{ $row->id }})" type="button"
                                        title="Riwayat perpindahan PIC & ruangan"
                                        class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-teal-600 hover:border-teal-600">
                                        <svg class="w-[16px] h-[16px]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </button>

                                    {{-- Kembalikan ke manajemen. Hanya untuk aset yang ada PIC-nya
                                         dan tidak sedang dipinjam lewat pengajuan — yang dipinjam
                                         punya alur pengembaliannya sendiri di modul peminjaman. --}}
                                    @can('pengembalian-aset-manajemen')
                                        @if($row->ditugaskan_tetap)
                                            <button wire:click="openPengembalian({{ $row->id }})" type="button"
                                                title="Catat serah terima kembali ke manajemen"
                                                class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-amber-600 hover:border-amber-600">
                                                <svg class="w-[16px] h-[16px]" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 14l-4 -4l4 -4" /><path d="M5 10h11a4 4 0 1 1 0 8h-1" />
                                                </svg>
                                            </button>
                                        @endif
                                    @endcan

                                    {{-- Tombol Cetak Label --}}
                                    <a href="{{ route('rekap-aset.label', $row->id) }}" target="_blank"
                                        title="Cetak Label Barcode"
                                        class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-indigo-600 hover:border-indigo-600 focus:text-white focus:bg-indigo-600 focus:border-indigo-600 active:border-indigo-600 active:text-white active:bg-indigo-600 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                                        <svg class="w-[16px] h-[16px]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                    </a>

                                    {{-- hapus-rekap-aset, bukan hapus-barang: yang ditegakkan
                                         RekapAsetController sekarang yang pertama. hapus-barang
                                         milik data master Barang Aset, resource yang berbeda. --}}
                                    @can('hapus-rekap-aset')
                                        <button wire:click="deleteBarang({{$row->id}})" class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-red-600 hover:border-red-600 focus:text-white focus:bg-red-600 focus:border-red-600 active:border-red-600 active:text-white active:bg-red-600 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none" type="button">
                                        {{-- <button wire:click="deleteBarang({{$row->id}})" class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-red-600 hover:border-red-600 focus:text-white focus:bg-red-600 focus:border-red-600 active:border-red-600 active:text-white active:bg-red-600 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none" type="button"> --}}
                                            <svg class="w-[16px] h-[16px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                                            </svg>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td colspan="16" class="px-6 py-4 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Data Tidak Ditemukan!</h3>
                                <p class="mt-1 text-sm text-gray-500">Maaf, data yang Anda cari tidak ada</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4">
            {{ $rekap_asets->links() }}
        </div>
        {{-- MODAL --}}
        @if($isDeleteModalOpen)
            @include('pages.rekap_aset.remove')
        @endif
        @if($isShowGambarModalOpen)
            @include('pages.rekap_aset.gambar')
        @endif
        @if($isRiwayatModalOpen && $riwayatAset)
            @include('pages.rekap_aset.riwayat')
        @endif
        @if($isPengembalianModalOpen && $picPengembalian && $asetPengembalian)
            @include('pages.rekap_aset.pengembalian-manajemen')
        @endif
    </div>
</div>
{{-- Timer alert sudah pindah ke <x-app.alert>: setTimeout di DOMContentLoaded
     tidak pernah jalan untuk alert yang muncul dari aksi Livewire.

     Dua skrip lain yang tadinya di sini juga dibuang — keduanya mengacu ke
     #bulk-edit-button dan .checkbox-row yang sudah tidak ada di markup ini,
     jadi tidak pernah mengerjakan apa pun. --}}


