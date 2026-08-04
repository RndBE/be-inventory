{{--
    Daftar Berita Acara Serah Terima Aset.

    Kerangkanya mengikuti halaman daftar lain di sistem ini (Pengajuan Peminjaman,
    Rekapitulasi Aset): wadah halaman + judul + alert di dalam komponen, bilah tab,
    lalu tabel di dalam pembungkus ber-shadow. Sebelumnya halaman ini memakai kartu
    dan header kecil ala halaman form, sehingga terlihat berbeda dari yang lain.
--}}
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
            <h6 class="text-2xl text-gray-800 dark:text-gray-100 font-bold">Serah Terima Aset</h6>
            <p class="text-sm text-gray-500 dark:text-gray-400">Berita acara offboarding karyawan</p>
        </div>

        <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
        </div>
    </div>

    <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
        <ul class="flex overflow-x-auto whitespace-nowrap bg-gray-100 dark:bg-gray-700 rounded-lg" role="tablist">
            @foreach(['semua' => 'Semua', 'draft' => 'Draft', 'selesai' => 'Selesai'] as $tab => $label)
                <li class="me-2" role="presentation">
                    <button wire:click="setTab('{{ $tab }}')" type="button"
                        class="inline-block p-4 border-b-2 rounded-t-lg {{ $selectedTab === $tab ? 'text-purple-600 border-purple-600' : 'border-transparent text-gray-600 dark:text-gray-300' }}">
                        {{ $label }}
                    </button>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="relative overflow-x-auto">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <div class="flex flex-column sm:flex-row flex-wrap space-y-4 sm:space-y-0 items-center justify-between pb-4">
                <div class="mb-4 sm:mb-0">
                </div>
                <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                    @include('livewire.searchdata', ['debounceMs' => 400])
                    @include('livewire.dataperpage')
                    @can('tambah-serah-terima-aset')
                        <a href="{{ route('serah-terima-aset.create') }}"
                            class="mt-2 block w-fit rounded-md py-1.5 px-3 bg-indigo-600 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            Buat BAST
                        </a>
                    @endcan
                </div>
            </div>

            {{-- Diredupkan + dikunci klik selama permintaan berjalan. pointer-events-none
                 sengaja hanya di <table>, bukan di pembungkusnya, supaya kolom pencarian
                 dan filter di toolbar tetap bisa dipakai sambil menunggu. --}}
            <table wire:loading.class.delay="opacity-50 pointer-events-none"
                class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-sm text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="p-4">No</th>
                        <th scope="col" class="px-6 py-3">Nomor BAST</th>
                        <th scope="col" class="px-6 py-3">Karyawan</th>
                        <th scope="col" class="px-6 py-3">Alasan / Tgl Efektif</th>
                        <th scope="col" class="px-6 py-3">Aset</th>
                        <th scope="col" class="px-6 py-3">Status</th>
                        <th scope="col" class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($daftarBast as $index => $bast)
                        <tr wire:click="showDetail({{ $bast->id }})"
                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer transition">
                            <td class="p-4">{{ $daftarBast->firstItem() + $index }}</td>
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ $bast->kode_bast }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-gray-900 dark:text-white">{{ $bast->dataKaryawan->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Atasan: {{ $bast->dataAtasan->name ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>{{ $bast->alasan_keluar }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $bast->tgl_efektif }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($bast->bebas_aset)
                                    <span class="rounded border border-green-400 bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">
                                        Bebas aset
                                    </span>
                                @else
                                    <span>{{ $bast->aset_diserahkan->count() }} diserahkan</span>
                                @endif
                                @if($bast->aset_sudah_kembali->isNotEmpty())
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $bast->aset_sudah_kembali->count() }} sudah kembali</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($bast->selesai)
                                    <span class="inline-block rounded-full border border-green-400 bg-green-100 px-3 py-1 text-xs font-medium text-green-800">
                                        Selesai
                                    </span>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($bast->tgl_selesai)->format('d/m/y') }}
                                    </div>
                                @else
                                    <span class="inline-block rounded-full border border-amber-400 bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800">
                                        Draft
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                {{-- Dropdown diposisikan fixed, bukan absolute. Pembungkus tabel
                                     memakai overflow-x-auto yang membentuk konteks kliping, sehingga
                                     menu absolute akan terpotong begitu melewati batas wadahnya —
                                     dan itu pasti terjadi kalau barisnya sedikit. --}}
                                <div x-data="{
                                        open: false, x: 0, y: 0, keAtas: false,
                                        buka() {
                                            if (this.open) { this.open = false; return; }
                                            const k = $refs.tombol.getBoundingClientRect();
                                            // Balik ke atas kalau ruang di bawah tidak cukup.
                                            this.keAtas = k.bottom + 200 > window.innerHeight;
                                            this.x = k.right;
                                            this.y = this.keAtas ? k.top - 8 : k.bottom + 8;
                                            this.open = true;
                                        },
                                    }"
                                    @scroll.window="open = false"
                                    @resize.window="open = false"
                                    class="relative">
                                    <button x-ref="tombol" @click.stop="buka()" type="button"
                                        class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-blue-600 hover:border-blue-600">
                                        Opsi
                                    </button>
                                    {{-- Tanpa x-transition: transisi menampilkan elemen lebih dulu,
                                         lalu style posisinya menyusul di frame berikutnya — sekejap
                                         menu terlihat di pojok kiri atas sebelum melompat ke tempatnya. --}}
                                    <div x-show="open" @click.away="open = false" x-cloak
                                        x-bind:style="`left:${x}px; top:${y}px; transform: translate(-100%, ${keAtas ? '-100%' : '0'});`"
                                        class="fixed w-56 bg-white dark:bg-gray-700 border dark:border-gray-600 rounded-md shadow-lg z-50">

                                        <button @click.stop wire:click="showDetail({{ $bast->id }})" type="button"
                                            class="w-full px-4 py-2 text-sm text-slate-600 dark:text-gray-200 hover:bg-indigo-600 hover:text-white text-left">
                                            Lihat Detail
                                        </button>

                                        {{-- Dibuka di tab baru: dokumennya kini ditampilkan, bukan
                                             diunduh, jadi tanpa ini daftar BAST akan tertinggal. --}}
                                        <a href="{{ route('serah-terima-aset.pdf', $bast->id) }}" @click.stop
                                            target="_blank" rel="noopener"
                                            class="block w-full px-4 py-2 text-sm text-slate-600 dark:text-gray-200 hover:bg-teal-600 hover:text-white text-left">
                                            Lihat Dokumen BAST
                                        </a>

                                        {{-- Tindakan tak terbalikkan: melepas aset dan menonaktifkan
                                             karyawan. Konfirmasinya lewat modal, bukan confirm()
                                             bawaan peramban, supaya akibatnya bisa dirinci. --}}
                                        @can('selesaikan-serah-terima-aset')
                                            @if(!$bast->selesai)
                                                <button @click.stop="open = false" wire:click="openSelesai({{ $bast->id }})" type="button"
                                                    class="w-full px-4 py-2 text-sm text-slate-600 dark:text-gray-200 hover:bg-green-600 hover:text-white text-left">
                                                    Tandai Selesai
                                                </button>
                                            @endif
                                        @endcan
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="bg-white dark:bg-gray-800">
                            <td colspan="7" class="px-6 py-8 text-center">
                                <svg class="mx-auto h-10 w-10 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 3v4a1 1 0 0 0 1 1h4M5 8V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-3"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-200">Belum ada BAST</h3>
                                <p class="mt-1 text-sm text-gray-500">Dokumen serah terima aset akan muncul di sini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4">{{ $daftarBast->links() }}</div>
    </div>

    {{-- MODAL --}}
    @if($isDetailModalOpen && $detailBast)
        @include('pages.serah-terima-aset.detail')
    @endif
    @if($isSelesaiModalOpen && $bastSelesai)
        @include('pages.serah-terima-aset.selesaikan')
    @endif
</div>
