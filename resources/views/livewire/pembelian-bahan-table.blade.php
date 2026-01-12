<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
    @if (session('success'))
        <div id="successAlert"
            class="flex items-center p-4 mb-4 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800"
            role="alert">
            <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                fill="currentColor" viewBox="0 0 20 20">
                <path
                    d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
            </svg>
            <span class="sr-only">Info</span>
            <div>
                <strong class="font-bold">Success!</strong>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div id="errorAlert"
            class="flex items-center p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800"
            role="alert">
            <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                fill="currentColor" viewBox="0 0 20 20">
                <path
                    d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
            </svg>
            <span class="sr-only">Info</span>
            <div>
                <strong class="font-bold">Error!</strong>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
        </div>
    @endif
    <div class="sm:flex sm:justify-between sm:items-center mb-2">
        <div class="mb-4 sm:mb-0">
            <h6 class="text-2xl text-gray-800 dark:text-gray-100 font-bold">Pembelian Bahan</h6>
        </div>

        <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">

        </div>
    </div>




    <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
        <ul class="flex overflow-x-auto whitespace-nowrap bg-gray-100 rounded-lg scrollbar-hide" role="tablist">
            <li class="me-2" role="presentation">
                <button wire:click="setTab('semua')"
                    class="inline-block p-4 border-b-2 rounded-t-lg {{ $selectedTab == 'semua' ? 'text-purple-600 border-purple-600' : '' }}">
                    Semua
                </button>
            </li>
            <li class="me-2" role="presentation">
                <button wire:click="setTab('pengajuan')"
                    class="inline-block p-4 border-b-2 rounded-t-lg {{ $selectedTab == 'pengajuan' ? 'text-purple-600 border-purple-600' : '' }}">
                    Pengajuan
                </button>
            </li>
            <li class="me-2" role="presentation">
                <button wire:click="setTab('diproses')"
                    class="inline-block p-4 border-b-2 rounded-t-lg {{ $selectedTab == 'diproses' ? 'text-purple-600 border-purple-600' : '' }}">
                    Diproses
                </button>
            </li>
            <li class="me-2" role="presentation">
                <button wire:click="setTab('selesai')"
                    class="inline-block p-4 border-b-2 rounded-t-lg {{ $selectedTab == 'selesai' ? 'text-purple-600 border-purple-600' : '' }}">
                    Selesai
                </button>
            </li>
        </ul>
    </div>

    <div class="relative overflow-x-auto">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <div
                class="flex flex-column sm:flex-row flex-wrap space-y-4 sm:space-y-0 items-center justify-between pb-4">
                <div class="mb-4 sm:mb-0">
                </div>
                <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                    @include('livewire.searchdata')
                    @include('livewire.dataperpage')
                    @include('pages.pembelian-bahan.export')
                </div>
            </div>
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400" overflow-hidden>
                <thead class="text-sm text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="p-4">No</th>
                        <th scope="col" class="px-6 py-3">Kode Pengajuan</th>
                        {{-- <th scope="col" class="px-6 py-3">Kode Pengajuan</th> --}}
                        <th scope="col" class="px-6 py-3">Tanggal Pengajuan</th>
                        <th scope="col" class="px-6 py-3">Tanggal Selesai</th>
                        <th scope="col" class="px-6 py-3">Tujuan</th>
                        <th scope="col" class="px-6 py-3">Pengaju</th>
                        {{-- <th scope="col" class="px-6 py-3">Total Item</th> --}}
                        <th scope="col" class="px-6 py-3">Jenis Pengajuan</th>
                        <th scope="col" class="px-6 py-3">Status Pengajuan</th>
                        <th scope="col" class="px-6 py-3">Status Pembelian</th>
                        <th scope="col" class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pembelian_bahans as $index => $pembelian_bahan)
                        <tr wire:click="showPembelianBahanDetail({{ $pembelian_bahan->id }})"
                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-100 cursor-pointer transition">
                            <td class="px-6 py-4">
                                <div class="text-slate-800 dark:text-slate-100">
                                    {{ $pembelian_bahans->firstItem() + $index }}</div>
                            </td>
                            <th scope="row"
                                class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{-- <button class="text-blue-600 hover:underline" type="button" wire:click="showPembelianBahan({{$pembelian_bahan->id}})">
                                    {{ $pembelian_bahan->kode_transaksi }}
                                </button> --}}
                                {{-- <button class="text-blue-600 hover:underline" type="button"> --}}
                                {{ $pembelian_bahan->kode_transaksi }}
                                {{-- </button> --}}
                            </th>
                            {{-- <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ $pembelian_bahan->dataPengajuan->kode_pengajuan ?? null }}
                            </th> --}}
                            <td class="px-6 py-4">{{ $pembelian_bahan->tgl_pengajuan }}</td>
                            <td class="px-6 py-4">{{ $pembelian_bahan->tgl_keluar }}</td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $pembelian_bahan->tujuan ?? null }}
                                </div>
                                <div class="text-xs text-gray-500">{{ $pembelian_bahan->keterangan }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $pembelian_bahan->dataUser?->name ?? '-' }}
                                </div>
                                <div class="text-xs text-gray-500">{{ $pembelian_bahan->divisi }}</div>
                            </td>
                            {{-- <td class="px-6 py-4">{{ $pembelian_bahan->pembelianBahanDetails->sum('jml_bahan') }}</td> --}}
                            <td class="px-6 py-4">{{ $pembelian_bahan->jenis_pengajuan }}</td>
                            <td class="px-6 py-4 min-w-[500px]">
                                @php
                                    // use Carbon\Carbon;

                                    // Daftar status dan tanggal approval
                                    $statusList = [];
                                    $dateList = [];

                                    // Tambahkan tanggal pengajuan sebagai awal
                                    $dateList['Pengajuan'] = $pembelian_bahan->tgl_pengajuan;

                                    $jenis = $pembelian_bahan->jenis_pengajuan;

                                    // Urutan berdasarkan jenis pengajuan
                                    if (
                                        $jenis === 'Pembelian Aset' ||
                                        $jenis === 'Pembelian Aset Lokal' ||
                                        $jenis === 'Pembelian Aset Impor'
                                    ) {
                                        $statusList = [
                                            'Leader' => $pembelian_bahan->status_leader ?? 'Belum disetujui',
                                            'General Affair' =>
                                                $pembelian_bahan->status_general_manager ?? 'Belum disetujui',
                                            'Purchasing' => $pembelian_bahan->status_purchasing ?? 'Belum disetujui',
                                            'Manager' => $pembelian_bahan->status_manager ?? 'Belum disetujui',
                                            'Finance' => $pembelian_bahan->status_finance ?? 'Belum disetujui',
                                            'Manager Admin' =>
                                                $pembelian_bahan->status_admin_manager ?? 'Belum disetujui',
                                            'Direktur' => $pembelian_bahan->status ?? 'Belum disetujui',
                                        ];

                                        $dateList += [
                                            'Leader' => $pembelian_bahan->tgl_approve_leader,
                                            'General Affair' => $pembelian_bahan->tgl_approve_general_manager,
                                            'Purchasing' => $pembelian_bahan->tgl_approve_purchasing,
                                            'Manager' => $pembelian_bahan->tgl_approve_manager,
                                            'Finance' => $pembelian_bahan->tgl_approve_finance,
                                            'Manager Admin' => $pembelian_bahan->tgl_approve_admin_manager,
                                            'Direktur' => $pembelian_bahan->tgl_approve_direktur,
                                        ];
                                    } else {
                                        $statusList = [
                                            'Leader' => $pembelian_bahan->status_leader ?? 'Belum disetujui',
                                            'Purchasing' => $pembelian_bahan->status_purchasing ?? 'Belum disetujui',
                                            'Manager' => $pembelian_bahan->status_manager ?? 'Belum disetujui',
                                            'Finance' => $pembelian_bahan->status_finance ?? 'Belum disetujui',
                                            'Manager Admin' =>
                                                $pembelian_bahan->status_admin_manager ?? 'Belum disetujui',
                                            'Direktur' => $pembelian_bahan->status ?? 'Belum disetujui',
                                        ];

                                        $dateList += [
                                            'Leader' => $pembelian_bahan->tgl_approve_leader,
                                            'Purchasing' => $pembelian_bahan->tgl_approve_purchasing,
                                            'Manager' => $pembelian_bahan->tgl_approve_manager,
                                            'Finance' => $pembelian_bahan->tgl_approve_finance,
                                            'Manager Admin' => $pembelian_bahan->tgl_approve_admin_manager,
                                            'Direktur' => $pembelian_bahan->tgl_approve_direktur,
                                        ];
                                    }

                                    $statusColors = [
                                        'Belum disetujui' => 'bg-blue-100 text-blue-800 border-blue-400',
                                        'Disetujui' => 'bg-green-100 text-green-800 border-green-100',
                                        'Ditolak' => 'bg-red-100 text-red-800 border-red-100',
                                    ];

                                    // Hitung selisih waktu antar approval
                                    $previousDate = null;
                                    $timeDiffs = [];

                                    foreach ($dateList as $key => $date) {
                                        if ($previousDate && $date) {
                                            $timeDiffs[$key] = \Carbon\Carbon::parse($date)->diffForHumans(
                                                \Carbon\Carbon::parse($previousDate),
                                                ['parts' => 2, 'short' => true],
                                            );
                                        } else {
                                            $timeDiffs[$key] = null;
                                        }
                                        $previousDate = $date;
                                    }
                                @endphp
                                <table class="w-full text-sm text-left border-collapse">
                                    <tbody>
                                        @foreach ($statusList as $role => $status)
                                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                                <td class="py-2 px-3 text-gray-700 font-medium">{{ $role }}
                                                </td>

                                                <td class="py-2 px-3">
                                                    <span
                                                        class="px-3 py-1 rounded-full text-xs font-medium border
                                                        {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800 border-gray-400' }}">
                                                        {{ $status }}
                                                    </span>

                                                    @if ($status === 'Ditolak' && isset($pembelian_bahan->catatan))
                                                        <div class="mt-1 text-xs text-red-600">
                                                            Catatan: {{ $pembelian_bahan->catatan }}
                                                        </div>
                                                    @endif

                                                    {{-- Tambahkan icon PDF khusus Direktur --}}
                                                    @if ($role === 'Direktur' && $pembelian_bahan->dokumen)
                                                        <a href="{{ asset('storage/' . $pembelian_bahan->dokumen) }}"
                                                            target="_blank"
                                                            class="ml-2 inline-flex items-center text-red-600 hover:text-red-800"
                                                            title="Lihat dokumen PDF">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                                fill="currentColor" viewBox="0 0 24 24">
                                                                <path d="M6 2a2 2 0 0 0-2 2v16c0
                                                                        1.1.9 2 2 2h12a2 2 0 0 0 2-2V8l-6-6H6zm7
                                                                        7V3.5L18.5 9H13z" />
                                                            </svg>
                                                        </a>
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

                            <td class="px-6 py-4">
                                <select @click.stop wire:model="selectedStatus.{{ $pembelian_bahan->id }}"
                                    wire:change="updateStatus({{ $pembelian_bahan->id }})"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-25 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                    @unless (auth()->user()->hasRole(['purchasing', 'superadmin'])) disabled @endunless>
                                    <option value="Status Pembelian" disabled>Status Pembelian</option>
                                    <option value="Pengajuan">Pengajuan</option>
                                    <option value="Diproses">Diproses</option>
                                    <option value="Selesai">Selesai</option>
                                </select>
                            </td>

                            <td class="px-6 py-4">
                                <div class="row flex space-x-2">
                                    <div class="relative" x-data="{ open: false, dropUp: false }" x-init="$nextTick(() => {
                                        let button = $refs.button.getBoundingClientRect();
                                        let windowHeight = window.innerHeight;
                                        if (button.bottom + 200 > windowHeight) {
                                            dropUp = true;
                                        }
                                    })">
                                        <button @click.stop @click="open = !open"
                                            class="rounded-md border border-slate-300 py-1 px-2 text-center text-sm transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-blue-600 hover:border-blue-600"
                                            type="button" x-ref="button">
                                            Opsi
                                        </button>

                                        <!-- Dropdown Menu -->
                                        <div x-show="open" @click.away="open = false" x-transition
                                            x-bind:class="dropUp ? 'bottom-full mb-2' : 'mt-2'"
                                            class="absolute right-0 w-48 bg-white border rounded-md shadow-lg z-50">

                                            {{-- @if ($pembelian_bahan->status_finance === 'Disetujui') --}}
                                            <a @click.stop
                                                href="{{ route('pengajuan-pembelian-bahan.downloadPdfPo', $pembelian_bahan->id) }}"
                                                target="__blank"
                                                class="block px-4 py-2 text-sm text-slate-600 hover:bg-red-600 hover:text-white flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="w-6 h-5 me-2 -ms-1 icon icon-tabler icons-tabler-outline icon-tabler-file-type-pdf">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                                    <path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4" />
                                                    <path d="M5 18h1.5a1.5 1.5 0 0 0 0 -3h-1.5v6" />
                                                    <path d="M17 18h2" />
                                                    <path d="M20 15h-3v6" />
                                                    <path d="M11 15v6h1a2 2 0 0 0 2 -2v-2a2 2 0 0 0 -2 -2h-1z" />
                                                </svg> Unduh Form PO
                                            </a>
                                            <a @click.stop
                                                href="{{ route('pengajuan-pembelian-bahan.downloadPdf', $pembelian_bahan->id) }}"
                                                target="__blank"
                                                class="block px-4 py-2 text-sm text-slate-600 hover:bg-red-600 hover:text-white flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="w-6 h-5 me-2 -ms-1 icon icon-tabler icons-tabler-outline icon-tabler-file-type-pdf">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                                    <path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4" />
                                                    <path d="M5 18h1.5a1.5 1.5 0 0 0 0 -3h-1.5v6" />
                                                    <path d="M17 18h2" />
                                                    <path d="M20 15h-3v6" />
                                                    <path d="M11 15v6h1a2 2 0 0 0 2 -2v-2a2 2 0 0 0 -2 -2h-1z" />
                                                </svg> Unduh Form
                                            </a>
                                            {{-- @endif --}}

                                            @if ($pembelian_bahan->link)
                                                <button @click.stop
                                                    wire:click="showInvoice({{ $pembelian_bahan->id }})"
                                                    class="w-full block px-4 py-2 text-sm text-slate-600 hover:bg-yellow-600 hover:text-white flex items-center"
                                                    type="button"><svg xmlns="http://www.w3.org/2000/svg"
                                                        width="24" height="24" viewBox="0 0 24 24"
                                                        fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round"
                                                        class="w-6 h-5 me-2 -ms-1 icon icon-tabler icons-tabler-outline icon-tabler-eye-dollar">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                                        <path
                                                            d="M13.193 17.924c-.39 .05 -.788 .076 -1.193 .076c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.005 0 5.592 1.394 7.761 4.181" />
                                                        <path
                                                            d="M21 15h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5" />
                                                        <path d="M19 21v1m0 -8v1" />
                                                    </svg> Lihat Invoice
                                                </button>
                                            @endif

                                            @can('edit-pengajuan-purchasing')
                                                {{-- @if ($pembelian_bahan->status_finance !== 'Disetujui' && $pembelian_bahan->status_finance !== 'Ditolak') --}}
                                                <a @click.stop {{-- href="{{ route('pengajuan-pembelian-bahan.edit', ['id' => $pembelian_bahan->id, 'page' => $pembelian_bahans->currentPage()]) }}" --}}
                                                    href="{{ route('pengajuan-pembelian-bahan.edit', ['pengajuan_pembelian_bahan' => $pembelian_bahan->id, 'page' => $pembelian_bahans->currentPage()]) }}"
                                                    class="block px-4 py-2 text-sm text-slate-600 hover:bg-yellow-600 hover:text-white flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                        class="w-6 h-5 me-2 -ms-1 icon icon-tabler icons-tabler-outline icon-tabler-edit">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path
                                                            d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" />
                                                        <path
                                                            d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" />
                                                        <path d="M16 5l3 3" />
                                                    </svg> Edit Pengajuan
                                                </a>
                                                {{-- @endif --}}
                                            @endcan

                                            {{-- @if ($pembelian_bahan->status !== 'Disetujui' && $pembelian_bahan->status !== 'Ditolak') --}}
                                            @can('edit-approvepembelian-leader')
                                                @php
                                                    $pengaju = $pembelian_bahan->dataUser;
                                                    $loginUser = Auth::user();
                                                    $isAtasanLevel3 = $pengaju?->atasan_level3_id == $loginUser->id;
                                                    $isAtasanLevel2 = $pengaju?->atasan_level2_id == $loginUser->id;
                                                @endphp
                                                @if ($isAtasanLevel3 || (!$pengaju?->atasan_level3_id && $isAtasanLevel2))
                                                    @if ($pembelian_bahan->status_leader !== 'Disetujui' && $pembelian_bahan->status_leader !== 'Ditolak')
                                                        <button @click.stop
                                                            wire:click="editLeaderPembelianBahan({{ $pembelian_bahan->id }}, {{ $pembelian_bahans->currentPage() }})"
                                                            class="w-full block px-4 py-2 text-sm text-slate-600 hover:bg-yellow-600 hover:text-white flex items-center"
                                                            type="button"><svg xmlns="http://www.w3.org/2000/svg"
                                                                width="24" height="24" viewBox="0 0 24 24"
                                                                fill="none" stroke="currentColor" stroke-width="2"
                                                                stroke-linecap="round" stroke-linejoin="round"
                                                                class="w-6 h-5 me-2 -ms-1 icon icon-tabler icons-tabler-outline icon-tabler-pencil-check">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                <path
                                                                    d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
                                                                <path d="M13.5 6.5l4 4" />
                                                                <path d="M15 19l2 2l4 -4" />
                                                            </svg>Approve Leader
                                                        </button>
                                                    @endif
                                                @endif
                                            @endcan
                                            @can('edit-approvepembelian-gm')
                                                @if (
                                                    $pembelian_bahan->status_leader == 'Disetujui' &&
                                                        $pembelian_bahan->status_general_manager !== 'Disetujui' &&
                                                        $pembelian_bahan->status_general_manager !== 'Ditolak' &&
                                                        in_array($pembelian_bahan->jenis_pengajuan, ['Pembelian Aset', 'Pembelian Aset Lokal', 'Pembelian Aset Impor']))
                                                    <button @click.stop
                                                        wire:click="editGMPembelianBahan({{ $pembelian_bahan->id }}, {{ $pembelian_bahans->currentPage() }})"
                                                        class="w-full block px-4 py-2 text-sm text-slate-600 hover:bg-yellow-600 hover:text-white flex items-center"
                                                        type="button"><svg xmlns="http://www.w3.org/2000/svg"
                                                            width="24" height="24" viewBox="0 0 24 24"
                                                            fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round"
                                                            class="w-6 h-5 me-2 -ms-1 icon icon-tabler icons-tabler-outline icon-tabler-pencil-check">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path
                                                                d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
                                                            <path d="M13.5 6.5l4 4" />
                                                            <path d="M15 19l2 2l4 -4" />
                                                        </svg>Approve General Affair
                                                    </button>
                                                @endif
                                            @endcan
                                            @can('edit-approve-purchasing')
                                                @if (
                                                    $pembelian_bahan->status_leader == 'Disetujui' &&
                                                        $pembelian_bahan->status_purchasing !== 'Disetujui' &&
                                                        $pembelian_bahan->status_purchasing !== 'Ditolak')
                                                    <button @click.stop
                                                        wire:click="editPurchasingPembelianBahan({{ $pembelian_bahan->id }}, {{ $pembelian_bahans->currentPage() }})"
                                                        class="w-full block px-4 py-2 text-sm text-slate-600 hover:bg-yellow-600 hover:text-white flex items-center"
                                                        type="button"><svg xmlns="http://www.w3.org/2000/svg"
                                                            width="24" height="24" viewBox="0 0 24 24"
                                                            fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round"
                                                            class="w-6 h-5 me-2 -ms-1 icon icon-tabler icons-tabler-outline icon-tabler-pencil-check">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path
                                                                d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
                                                            <path d="M13.5 6.5l4 4" />
                                                            <path d="M15 19l2 2l4 -4" />
                                                        </svg>Approve Purchasing
                                                    </button>
                                                @endif
                                            @endcan
                                            @can('edit-approve-manager')
                                                @php
                                                    $user = Auth::user();
                                                @endphp

                                                @if (
                                                    ($user->hasRole('software manager') && $pembelian_bahan->status_purchasing === 'Disetujui') ||
                                                        !$user->hasRole('software manager'))
                                                    @if ($pembelian_bahan->status_manager !== 'Disetujui' && $pembelian_bahan->status_manager !== 'Ditolak')
                                                        <button @click.stop
                                                            wire:click="editManagerPembelianBahan({{ $pembelian_bahan->id }}, {{ $pembelian_bahans->currentPage() }})"
                                                            class="w-full block px-4 py-2 text-sm text-slate-600 hover:bg-yellow-600 hover:text-white flex items-center"
                                                            type="button">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="24"
                                                                height="24" viewBox="0 0 24 24" fill="none"
                                                                stroke="currentColor" stroke-width="2"
                                                                stroke-linecap="round" stroke-linejoin="round"
                                                                class="w-6 h-5 me-2 -ms-1 icon icon-tabler icons-tabler-outline icon-tabler-pencil-check">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                <path
                                                                    d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
                                                                <path d="M13.5 6.5l4 4" />
                                                                <path d="M15 19l2 2l4 -4" />
                                                            </svg>
                                                            Approve Manager
                                                        </button>
                                                    @endif
                                                @endif
                                            @endcan

                                            @can('edit-pembelian-bahan')
                                                {{-- Approve Finance --}}
                                                @if (
                                                    $pembelian_bahan->status_manager === 'Disetujui' &&
                                                        $pembelian_bahan->status_finance !== 'Disetujui' &&
                                                        $pembelian_bahan->status_finance !== 'Ditolak')
                                                    <button @click.stop
                                                        wire:click="editFinancePembelianBahan({{ $pembelian_bahan->id }}, {{ $pembelian_bahans->currentPage() }})"
                                                        class="w-full block px-4 py-2 text-sm text-slate-600 hover:bg-yellow-600 hover:text-white flex items-center"
                                                        type="button">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24"
                                                            height="24" viewBox="0 0 24 24" fill="none"
                                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            class="w-6 h-5 me-2 -ms-1 icon icon-tabler icons-tabler-outline icon-tabler-pencil-check">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path
                                                                d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
                                                            <path d="M13.5 6.5l4 4" />
                                                            <path d="M15 19l2 2l4 -4" />
                                                        </svg>
                                                        Approve Finance
                                                    </button>
                                                @endif

                                                {{-- Approve Admin Manager (muncul kalau Finance sudah Disetujui) --}}
                                                @if (
                                                    $pembelian_bahan->status_finance === 'Disetujui' &&
                                                        $pembelian_bahan->status_admin_manager !== 'Disetujui' &&
                                                        $pembelian_bahan->status_admin_manager !== 'Ditolak')
                                                    <button @click.stop
                                                        wire:click="editAdminManagerPembelianBahan({{ $pembelian_bahan->id }}, {{ $pembelian_bahans->currentPage() }})"
                                                        class="w-full block px-4 py-2 text-sm text-slate-600 hover:bg-yellow-600 hover:text-white flex items-center"
                                                        type="button">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24"
                                                            height="24" viewBox="0 0 24 24" fill="none"
                                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            class="w-6 h-5 me-2 -ms-1 icon icon-tabler icons-tabler-outline icon-tabler-pencil-check">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path
                                                                d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
                                                            <path d="M13.5 6.5l4 4" />
                                                            <path d="M15 19l2 2l4 -4" />
                                                        </svg>
                                                        Approve Admin Manager
                                                    </button>
                                                @endif

                                                {{-- Approve Direktur (muncul kalau Admin Manager sudah Disetujui) --}}
                                                @if (
                                                    $pembelian_bahan->status_admin_manager === 'Disetujui' &&
                                                        $pembelian_bahan->status !== 'Disetujui' &&
                                                        $pembelian_bahan->status !== 'Ditolak')
                                                    <button @click.stop
                                                        wire:click="editPembelianBahan({{ $pembelian_bahan->id }}, {{ $pembelian_bahans->currentPage() }})"
                                                        class="w-full block px-4 py-2 text-sm text-slate-600 hover:bg-yellow-600 hover:text-white flex items-center"
                                                        type="button">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24"
                                                            height="24" viewBox="0 0 24 24" fill="none"
                                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            class="w-6 h-5 me-2 -ms-1 icon icon-tabler icons-tabler-outline icon-tabler-pencil-check">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path
                                                                d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
                                                            <path d="M13.5 6.5l4 4" />
                                                            <path d="M15 19l2 2l4 -4" />
                                                        </svg>
                                                        Approve Direktur
                                                    </button>
                                                @endif
                                            @endcan

                                            @can('update-harga-pembelian-bahan')
                                                @if ($pembelian_bahan->status == 'Disetujui')
                                                    <a @click.stop {{-- href="{{ route('pengajuan-pembelian-bahan.editHarga', $pembelian_bahan->id) }}" --}}
                                                        href="{{ route('pengajuan-pembelian-bahan.editHarga', ['id' => $pembelian_bahan->id, 'page' => $pembelian_bahans->currentPage()]) }}"
                                                        class="w-full block px-4 py-2 text-sm text-slate-600 hover:bg-yellow-600 hover:text-white flex items-center">
                                                        <svg class="w-6 h-5 me-2 -ms-1" xmlns="http://www.w3.org/2000/svg"
                                                            width="24" height="24" viewBox="0 0 24 24"
                                                            fill="none" stroke="currentColor" stroke-width="2"
                                                            stroke-linecap="round" stroke-linejoin="round"
                                                            class="icon icon-tabler icons-tabler-outline icon-tabler-pencil-dollar">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path
                                                                d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
                                                            <path d="M13.5 6.5l4 4" />
                                                            <path
                                                                d="M21 15h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5" />
                                                            <path d="M19 21v1m0 -8v1" />
                                                        </svg> Update Harga
                                                    </a>
                                                @endif
                                            @endcan
                                            @can('upload-link-invoice')
                                                <button @click.stop
                                                    wire:click="uploadInvoice({{ $pembelian_bahan->id }}, {{ $pembelian_bahans->currentPage() }})"
                                                    class="w-full block px-4 py-2 text-sm text-slate-600 hover:bg-yellow-600 hover:text-white flex items-center"
                                                    type="button">
                                                    <svg class="w-6 h-5 me-2 -ms-1" xmlns="http://www.w3.org/2000/svg"
                                                        width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        class="icon icon-tabler icons-tabler-outline icon-tabler-file-dollar">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                                        <path
                                                            d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                                                        <path d="M14 11h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5" />
                                                        <path d="M12 17v1m0 -8v1" />
                                                    </svg>
                                                    Upload Invoice
                                                </button>
                                            @endcan

                                            <button @click.stop
                                                wire:click="uploadDokumen({{ $pembelian_bahan->id }}, {{ $pembelian_bahans->currentPage() }})"
                                                class="w-full block px-4 py-2 text-sm text-slate-600 hover:bg-yellow-600 hover:text-white flex items-center"
                                                type="button">
                                                <svg class="w-6 h-5 me-2 -ms-1" xmlns="http://www.w3.org/2000/svg"
                                                    width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    class="icon icon-tabler icons-tabler-outline icon-tabler-file-dollar">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                                    <path
                                                        d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                                                    <path d="M14 11h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5" />
                                                    <path d="M12 17v1m0 -8v1" />
                                                </svg>
                                                Upload Dokumen
                                            </button>

                                            @can('hapus-pembelian-bahan')
                                                @if ($pembelian_bahan->status !== 'Disetujui')
                                                    <button @click.stop
                                                        wire:click="deletePembelianBahan({{ $pembelian_bahan->id }}, {{ $pembelian_bahans->currentPage() }})"
                                                        class="w-full block px-4 py-2 text-sm text-slate-600 hover:bg-red-600 hover:text-white flex items-center"
                                                        type="button">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24"
                                                            height="24" viewBox="0 0 24 24" fill="none"
                                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            class="w-6 h-5 me-2 -ms-1 icon icon-tabler icons-tabler-outline icon-tabler-trash">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M4 7l16 0" />
                                                            <path d="M10 11l0 6" />
                                                            <path d="M14 11l0 6" />
                                                            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                        </svg>
                                                        </svg> Hapus Pengajuan
                                                    </button>
                                                @endif
                                            @endcan
                                            {{-- @endif --}}
                                        </div>
                                    </div>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td colspan="12" class="px-6 py-4 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                    class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-500">Data Tidak
                                    Ditemukan!</h3>
                                <p class="mt-1 text-sm text-gray-500">Maaf, data yang Anda cari tidak ada</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        </div>
        <!-- Table -->
        <div class="px-6 py-4">
            {{ $pembelian_bahans->links() }}
        </div>
        {{-- MODAL --}}
        {{-- @include('pages.pembelian-bahan.test') --}}

        @include('pages.pembelian-bahan.sidebar-detail')

        @if ($isShowInvoiceModalOpen)
            @include('pages.pembelian-bahan.invoice')
        @endif
        @if ($isApproveLeaderModalOpen)
            @include('pages.pembelian-bahan.approval-leader')
        @endif
        @if ($isApproveGMModalOpen)
            @include('pages.pembelian-bahan.approval-general-manager')
        @endif
        @if ($isApproveManagerModalOpen)
            @include('pages.pembelian-bahan.approval-manager')
        @endif
        @if ($isApprovePurchasingModalOpen)
            @include('pages.pembelian-bahan.approval-purchasing')
        @endif
        @if ($isApproveFinanceModalOpen)
            @include('pages.pembelian-bahan.approval-finance')
        @endif
        @if ($isApproveAdminManagerModalOpen)
            @include('pages.pembelian-bahan.approval-admin-manager')
        @endif
        @if ($isApproveDirekturModalOpen)
            @include('pages.pembelian-bahan.approval-direktur')
        @endif
        @if ($isDeleteModalOpen)
            @include('pages.pembelian-bahan.remove')
        @endif
        @if ($isShowModalOpen)
            @include('pages.pembelian-bahan.test')
        @endif
        @if ($isUploadInvoiceModalOpen)
            @include('pages.pembelian-bahan.upload-invoice')
        @endif
        @if ($isUploadDokumenModalOpen)
            @include('pages.pembelian-bahan.upload-dokumen')
        @endif
    </div>
</div>


<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Atur waktu delay dalam milidetik (contoh: 5000 = 5 detik)
        const delay = 5000;

        // Menghilangkan alert sukses
        const successAlert = document.getElementById('successAlert');
        if (successAlert) {
            setTimeout(() => {
                successAlert.style.display = 'none';
            }, delay);
        }

        // Menghilangkan alert error
        const errorAlert = document.getElementById('errorAlert');
        if (errorAlert) {
            setTimeout(() => {
                errorAlert.style.display = 'none';
            }, delay);
        }
    });
</script>
