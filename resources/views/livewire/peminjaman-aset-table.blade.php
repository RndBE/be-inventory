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
            <h6 class="text-2xl text-gray-800 dark:text-gray-100 font-bold">Pengajuan Peminjaman</h6>
        </div>

        <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
        </div>
    </div>

    <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
        {{-- dark:bg-gray-700 wajib ada: tanpa itu bilahnya tetap abu terang di mode
             gelap sementara teksnya mewarisi dark:text-gray-400 dari body, jadi
             nyaris tak terbaca. border-transparent untuk tab non-aktif juga wajib —
             border-b-2 tanpa warna memberi garis bawah abu di semua tab, sehingga
             tab yang aktif tidak lagi menonjol. --}}
        <ul class="flex overflow-x-auto whitespace-nowrap bg-gray-100 dark:bg-gray-700 rounded-lg scrollbar-hide" role="tablist">
            @foreach([
                'semua' => 'Semua',
                'pengajuan' => 'Pengajuan',
                'diproses' => 'Sedang Dipinjam',
                'selesai' => 'Selesai',
                'ditolak' => 'Ditolak',
            ] as $tab => $label)
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
                    @if(auth()->user()->hasAnyRole(['superadmin', 'general_affair']))
                        <select wire:model.live="filterDivisi"
                            class="mt-2 block rounded-md border-0 py-1.5 pl-3 pr-8 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm dark:bg-gray-700 dark:text-gray-300 dark:ring-gray-600">
                            <option value="">Semua Divisi</option>
                            @foreach(['Administrasi','General Affair','HRD','HSE','Marketing','OP','Produksi','Publikasi','Purchasing','RnD','Sekretaris','Software','Teknisi'] as $divisi)
                                <option value="{{ $divisi }}">{{ $divisi }}</option>
                            @endforeach
                        </select>
                    @endif
                    @include('livewire.searchdata', ['debounceMs' => 400])
                    @include('livewire.dataperpage')
                    @can('tambah-peminjaman-aset')
                        <a href="{{ route('peminjaman-aset.create') }}" class="mt-2 block w-fit rounded-md py-1.5 px-2 bg-indigo-600 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            Tambah
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
                        <th scope="col" class="px-6 py-3">Kode Peminjaman</th>
                        <th scope="col" class="px-6 py-3">Tanggal Pengajuan</th>
                        <th scope="col" class="px-6 py-3">Tanggal Pinjam</th>
                        <th scope="col" class="px-6 py-3">Aset</th>
                        <th scope="col" class="px-6 py-3">Pengaju</th>
                        <th scope="col" class="px-6 py-3">Keperluan</th>
                        <th scope="col" class="px-6 py-3">Status Pengajuan</th>
                        <th scope="col" class="px-6 py-3">Status Pengembalian</th>
                        <th scope="col" class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $statusColors = [
                            'Belum disetujui' => 'bg-blue-100 text-blue-800 border-blue-400',
                            'Disetujui' => 'bg-green-100 text-green-800 border-green-100',
                            'Ditolak' => 'bg-red-100 text-red-800 border-red-100',
                        ];
                    @endphp
                    @forelse($peminjamans as $index => $peminjaman)
                        @php
                            $statusList = [
                                'Leader' => $peminjaman->status_leader ?? 'Belum disetujui',
                                'Manager' => $peminjaman->status_manager ?? 'Belum disetujui',
                                'General Affair' => $peminjaman->status ?? 'Belum disetujui',
                                'HRD (Mengetahui)' => $peminjaman->status_hrd ?? 'Belum disetujui',
                            ];

                            $dateList = [
                                'Pengajuan' => $peminjaman->tgl_pengajuan,
                                'Leader' => $peminjaman->tgl_approve_leader,
                                'Manager' => $peminjaman->tgl_approve_manager,
                                'General Affair' => $peminjaman->tgl_approve_ga,
                                'HRD (Mengetahui)' => $peminjaman->tgl_approve_hrd,
                            ];

                            // Selisih waktu antar tahap approval
                            $previousDate = null;
                            $timeDiffs = [];
                            foreach ($dateList as $key => $date) {
                                if ($previousDate && $date) {
                                    $timeDiffs[$key] = \Carbon\Carbon::parse($date)->diffForHumans(\Carbon\Carbon::parse($previousDate), ['parts' => 2, 'short' => true]);
                                } else {
                                    $timeDiffs[$key] = null;
                                }
                                $previousDate = $date;
                            }
                        @endphp
                        <tr wire:click="showDetail({{ $peminjaman->id }})" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-100 cursor-pointer transition">
                            <td class="px-6 py-4"><div class="text-slate-800 dark:text-slate-100">{{ $peminjamans->firstItem() + $index }}</div></td>
                            <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ $peminjaman->kode_peminjaman }}
                            </th>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $peminjaman->tgl_pengajuan }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $peminjaman->tgl_pinjam }}</td>
                            <td class="px-6 py-4">
                                @include('pages.peminjaman-aset.daftar-aset-status', ['peminjaman' => $peminjaman])
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $peminjaman->dataUser?->name ?? '-' }}
                                </div>
                                <div class="text-xs text-gray-500">{{ $peminjaman->divisi }}</div>
                                <div class="text-xs text-gray-500">Tujuan: {{ $peminjaman->dataRuangan->nama_ruangan ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ $peminjaman->keperluan }}</div>
                            </td>
                            <td class="px-6 py-4 min-w-[420px]">
                                <table class="w-full text-sm text-left border-collapse">
                                    <tbody>
                                        @foreach ($statusList as $role => $status)
                                            @php
                                                $kendala = $peminjaman->kendalaApproval($role);
                                            @endphp
                                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                                <td class="py-2 px-3 text-gray-700 font-medium">{{ $role }}</td>
                                                <td class="py-2 px-3">
                                                    {{-- inline-block + nowrap: kalau span-nya inline dan teksnya pecah dua baris,
                                                         padding & rounded-full ikut pecah per baris dan pill-nya jadi rusak. --}}
                                                    <span class="inline-block whitespace-nowrap px-3 py-1 rounded-full text-xs font-medium border
                                                        {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800 border-gray-400' }}">
                                                        {{ $status }}
                                                    </span>

                                                    @if ($status === 'Ditolak' && $peminjaman->catatan)
                                                        <div class="mt-1 text-xs text-red-600">
                                                            Catatan: {{ $peminjaman->catatan }}
                                                        </div>
                                                    @endif

                                                    @if ($kendala)
                                                        <div class="mt-1 text-xs text-amber-700">
                                                            Kendala: {{ $kendala }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="py-2 px-3 text-gray-500 text-xs">
                                                    @if (!empty($timeDiffs[$role]))
                                                        +{{ $timeDiffs[$role] }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ $peminjaman->status_pengembalian }}</div>
                                @if($peminjaman->lama_dipinjam !== null)
                                    <div class="text-xs text-gray-500">sudah {{ $peminjaman->lama_dipinjam }} hari</div>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                <div class="row flex space-x-2">
                                    {{-- Menu diposisikan fixed, bukan absolute. Pembungkus tabel
                                         memakai overflow-x-auto yang membentuk konteks kliping,
                                         sehingga menu absolute terpotong begitu melewati batas
                                         wadahnya — dan itu pasti terjadi kalau barisnya sedikit. --}}
                                    <div x-data="{
                                            open: false, siap: false, x: 0, y: 0,
                                            buka() {
                                                if (this.open) { this.tutup(); return; }
                                                this.open = true;
                                                // Tinggi menu baru bisa diukur setelah ia dirender, jadi
                                                // posisinya dihitung di $nextTick. Selama satu frame itu
                                                // menu masih transparan, supaya perpindahan posisinya
                                                // tidak terlihat berkedip.
                                                this.$nextTick(() => { this.hitung(); this.siap = true; });
                                            },
                                            tutup() { this.open = false; this.siap = false; },
                                            hitung() {
                                                const k = this.$refs.tombol.getBoundingClientRect();
                                                // Tinggi menu yang sebenarnya, bukan angka perkiraan:
                                                // jumlah itemnya bergantung siapa yang login.
                                                const tinggi = this.$refs.menu.offsetHeight;
                                                const jarak = 8;
                                                const bawah = k.bottom + jarak;
                                                const atas = k.top - jarak - tinggi;
                                                // Ke atas hanya kalau ruang di bawah kurang DAN di atas
                                                // lebih lega, bukan sekadar kurang.
                                                const keAtas = tinggi > window.innerHeight - bawah
                                                    && k.top > window.innerHeight - k.bottom;
                                                // Dijepit ke dalam viewport. Wajib, karena menu fixed tidak
                                                // ikut tergulir: bagian yang melewati tepi layar hilang
                                                // begitu saja dan tidak bisa dijangkau.
                                                this.y = Math.max(jarak, Math.min(
                                                    keAtas ? atas : bawah,
                                                    window.innerHeight - tinggi - jarak
                                                ));
                                                this.x = k.right;
                                            },
                                        }"
                                        @scroll.window="tutup()"
                                        @resize.window="tutup()"
                                        class="relative">
                                        <button x-ref="tombol" @click.stop="buka()" type="button"
                                            class="rounded-md border border-slate-300 py-1 px-2 text-center text-sm transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-blue-600 hover:border-blue-600">
                                            Opsi
                                        </button>

                                        {{-- Tanpa x-transition: transisi menampilkan elemen lebih dulu,
                                             lalu style posisinya menyusul di frame berikutnya — sekejap
                                             menu terlihat di pojok kiri atas sebelum melompat ke tempatnya.

                                             translateX(-100%) meratakan tepi kanan menu ke tepi kanan
                                             tombol; posisi vertikalnya sudah jadi nilai akhir di y, jadi
                                             tidak ada geseran arah lain. --}}
                                        <div x-ref="menu" x-show="open" @click.away="tutup()" x-cloak
                                            x-bind:style="`left:${x}px; top:${y}px; transform: translateX(-100%);`"
                                            x-bind:class="siap ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                                            class="fixed w-56 bg-white dark:bg-gray-700 border dark:border-gray-600 rounded-md shadow-lg z-50">

                                            <button @click.stop="tutup()" wire:click="showDetail({{ $peminjaman->id }})" type="button"
                                                class="w-full px-4 py-2 text-sm text-slate-600 dark:text-gray-200 hover:bg-indigo-600 hover:text-white flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-5 me-2 -ms-1">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                                                </svg> Lihat Detail
                                            </button>

                                            {{-- Ubah isi pengajuan: hanya pengaju, dan hanya selama
                                                 belum ada approver yang memutuskan. --}}
                                            @if ($peminjaman->bolehDiubahOleh(Auth::user()))
                                                <a href="{{ route('peminjaman-aset.edit', $peminjaman->id) }}" @click.stop
                                                    class="w-full px-4 py-2 text-sm text-slate-600 dark:text-gray-200 hover:bg-yellow-600 hover:text-white flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-5 me-2 -ms-1 shrink-0">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" />
                                                    </svg> Ubah Pengajuan
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td colspan="10" class="px-6 py-4 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-500">Data Tidak Ditemukan!</h3>
                                <p class="mt-1 text-sm text-gray-500">Maaf, data yang Anda cari tidak ada</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4">
            {{ $peminjamans->links() }}
        </div>

        {{-- MODAL --}}
        @if($detailPeminjaman)
            @include('pages.peminjaman-aset.detail')
        @endif
    </div>
</div>

{{-- Timer alert sudah pindah ke <x-app.alert>: setTimeout di DOMContentLoaded
     tidak pernah jalan untuk alert yang muncul dari aksi Livewire. --}}
