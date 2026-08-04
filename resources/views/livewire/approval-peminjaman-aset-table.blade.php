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
            <h6 class="text-2xl text-gray-800 dark:text-gray-100 font-bold">Approval Peminjaman Aset</h6>
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
                'semua' => 'Semua Pengajuan',
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
                        $loginUser = Auth::user();
                        $statusColors = [
                            'Belum disetujui' => 'bg-blue-100 text-blue-800 border-blue-400',
                            'Disetujui' => 'bg-green-100 text-green-800 border-green-100',
                            'Ditolak' => 'bg-red-100 text-red-800 border-red-100',
                        ];
                    @endphp
                    @forelse($peminjamans as $index => $peminjaman)
                        @php
                            // Aturan "siapa boleh memutus tahap apa" diambil dari model, bukan
                            // disusun ulang di sini. Sebelumnya syarat garis komando hanya ada
                            // di Blade sementara controller cuma memeriksa permission, jadi
                            // tombolnya tersembunyi tapi POST-nya tetap tembus.
                            //
                            // Pakai komentar PHP (//), bukan komentar Blade: isi blok @php
                            // diteruskan apa adanya sebagai PHP, jadi komentar Blade di dalamnya
                            // tidak dibuang dan membuat view-nya gagal dikompilasi.
                            $bolehMemutus = fn ($tahap) => $peminjaman->beradaDiGarisKomando($loginUser, $tahap)
                                && $peminjaman->tahapBelumDiputus($tahap)
                                && $peminjaman->tahapSebelumnyaSudahDisetujui($tahap);

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
                                         wadahnya — paling terasa di sini karena menunya bisa
                                         berisi enam item. --}}
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
                                                // jumlah itemnya bergantung permission dan tahap approval,
                                                // jadi bisa satu sampai enam baris.
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

                                            {{-- Approve Leader: hanya atasan langsung pengaju --}}
                                            @can('approve-leader-peminjaman-aset')
                                                @if ($bolehMemutus('leader'))
                                                    <button @click.stop="tutup()" wire:click="openApprove({{ $peminjaman->id }}, 'leader')" type="button"
                                                        class="w-full px-4 py-2 text-sm text-slate-600 dark:text-gray-200 hover:bg-yellow-600 hover:text-white flex items-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-5 me-2 -ms-1">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /><path d="M15 19l2 2l4 -4" />
                                                        </svg>Approve Leader
                                                    </button>
                                                @endif
                                            @endcan

                                            {{-- Approve Manager: setelah leader setuju --}}
                                            @can('approve-manager-peminjaman-aset')
                                                @if ($bolehMemutus('manager'))
                                                    <button @click.stop="tutup()" wire:click="openApprove({{ $peminjaman->id }}, 'manager')" type="button"
                                                        class="w-full px-4 py-2 text-sm text-slate-600 dark:text-gray-200 hover:bg-yellow-600 hover:text-white flex items-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-5 me-2 -ms-1">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /><path d="M15 19l2 2l4 -4" />
                                                        </svg>Approve Manager
                                                    </button>
                                                @endif
                                            @endcan

                                            {{-- Approve General Affair: gerbang terakhir, sekaligus cek ketersediaan aset --}}
                                            @can('approve-ga-peminjaman-aset')
                                                @if ($bolehMemutus('ga'))
                                                    <button @click.stop="tutup()" wire:click="openApprove({{ $peminjaman->id }}, 'ga')" type="button"
                                                        class="w-full px-4 py-2 text-sm text-slate-600 dark:text-gray-200 hover:bg-green-600 hover:text-white flex items-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-5 me-2 -ms-1">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 12l2 2l4 -4" /><path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" />
                                                        </svg>Approve General Affair
                                                    </button>
                                                @endif
                                            @endcan

                                            {{-- Mengetahui HRD: gerbang terakhir sebelum aset boleh keluar --}}
                                            @can('approve-hrd-peminjaman-aset')
                                                @if ($bolehMemutus('hrd'))
                                                    <button @click.stop="tutup()" wire:click="openApprove({{ $peminjaman->id }}, 'hrd')" type="button"
                                                        class="w-full px-4 py-2 text-sm text-slate-600 dark:text-gray-200 hover:bg-purple-600 hover:text-white flex items-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-5 me-2 -ms-1">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                                                        </svg>Mengetahui (HRD)
                                                    </button>
                                                @endif
                                            @endcan

                                            {{-- Catat pengembalian: hanya untuk aset yang sudah benar-benar keluar.
                                                 Satu tombol untuk seluruh pengajuan — aset mana yang dicatat dipilih
                                                 di dalam modal, supaya menu ini tidak memanjang mengikuti jumlah aset. --}}
                                            @can('pengembalian-peminjaman-aset')
                                                @if ($peminjaman->boleh_dikeluarkan)
                                                    @php
                                                        $belumKembali = $peminjaman->peminjamanAsetDetails
                                                            ->where('status_pengembalian', '!=', 'Dikembalikan')->count();
                                                    @endphp
                                                    <button @click.stop="tutup()" wire:click="openPengembalian({{ $peminjaman->id }})" type="button"
                                                        class="w-full px-4 py-2 text-sm text-slate-600 dark:text-gray-200 hover:bg-teal-600 hover:text-white flex items-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-5 me-2 -ms-1 shrink-0">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 14l-4 -4l4 -4" /><path d="M5 10h11a4 4 0 1 1 0 8h-1" />
                                                        </svg>
                                                        Catat Pengembalian
                                                        @if ($belumKembali > 0)
                                                            <span class="ms-auto inline-flex items-center justify-center rounded-full bg-teal-100 px-1.5 text-xs font-semibold text-teal-800">
                                                                {{ $belumKembali }}
                                                            </span>
                                                        @endif
                                                    </button>
                                                @endif
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td colspan="10" class="px-6 py-4 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-500">Data Tidak Ditemukan!</h3>
                                <p class="mt-1 text-sm text-gray-500">Belum ada pengajuan peminjaman aset pada tab ini</p>
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
        @if($isApproveModalOpen)
            @include('pages.peminjaman-aset.approve')
        @endif
        @if($isPengembalianModalOpen && $peminjamanPengembalian)
            @include('pages.peminjaman-aset.pengembalian')
        @endif
    </div>
</div>

{{-- Timer alert sudah pindah ke <x-app.alert>: setTimeout di DOMContentLoaded
     tidak pernah jalan untuk alert yang muncul dari aksi Livewire. --}}
