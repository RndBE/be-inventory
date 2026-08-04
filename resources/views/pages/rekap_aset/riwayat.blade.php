<div x-data="{ isOpen: @entangle('isRiwayatModalOpen') }"
    x-show="isOpen"
    class="fixed inset-0 flex items-center justify-center z-50 w-full h-full overflow-y-auto"
    style="background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px);"
    @keydown.escape.window="isOpen = false; $wire.closeModal();"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100">

    <div class="relative p-4 w-full max-w-3xl" x-show="isOpen"
        @click.outside="isOpen = false; $wire.closeModal();">
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                <div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Riwayat Aset
                    </h3>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-300">
                        {{ $riwayatAset->barangAset->nama_barang ?? '-' }} &middot; {{ $riwayatAset->nomor_aset }}
                    </p>
                </div>
                <button wire:click="closeModal" type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>

            {{-- Tab --}}
            <div class="border-b border-gray-200 dark:border-gray-600">
                <ul class="flex flex-wrap -mb-px text-sm font-medium">
                    <li class="me-2">
                        <button wire:click="setTabRiwayat('peminjaman')" type="button"
                            class="inline-block p-4 border-b-2 rounded-t-lg {{ $tabRiwayat === 'peminjaman' ? 'text-purple-600 border-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                            Dipinjam Siapa Saja
                            <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700">{{ $riwayatAset->riwayatPeminjaman->count() }}</span>
                        </button>
                    </li>
                    <li class="me-2">
                        <button wire:click="setTabRiwayat('perpindahan')" type="button"
                            class="inline-block p-4 border-b-2 rounded-t-lg {{ $tabRiwayat === 'perpindahan' ? 'text-purple-600 border-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                            PIC &amp; Ruangan
                            <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700">{{ $riwayatAset->riwayatMutasi->count() }}</span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="p-5 max-h-[60vh] overflow-y-auto">

                {{-- ============ TAB: DIPINJAM SIAPA SAJA ============ --}}
                @if($tabRiwayat === 'peminjaman')
                    @forelse($riwayatAset->riwayatPeminjaman as $pinjam)
                        @php
                            $header = $pinjam->peminjamanAset;
                            $masihDipinjam = $pinjam->status_pengembalian !== 'Dikembalikan';
                        @endphp
                        <div @class([
                                'mb-3 last:mb-0 rounded-lg border p-4',
                                'border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-900/20' => $masihDipinjam,
                                'border-gray-200 dark:border-gray-600' => !$masihDipinjam,
                            ])>
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        {{ $header->dataUser->name ?? '-' }}
                                        <span class="text-xs font-normal text-gray-500 dark:text-gray-400">
                                            &middot; {{ $header->divisi ?? '-' }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $header->kode_peminjaman }}</div>
                                </div>

                                @if($masihDipinjam)
                                    <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">
                                        Sedang dipinjam
                                    </span>
                                @else
                                    <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">
                                        Sudah kembali
                                    </span>
                                @endif
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                                <div>
                                    <div class="text-xs uppercase text-gray-400">Dipinjam</div>
                                    <div class="text-gray-900 dark:text-white">{{ $header->tgl_pinjam ?? '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs uppercase text-gray-400">Dikembalikan</div>
                                    <div class="text-gray-900 dark:text-white">
                                        @if($pinjam->tgl_kembali)
                                            {{ \Carbon\Carbon::parse($pinjam->tgl_kembali)->format('Y-m-d') }}
                                        @else
                                            <span class="text-amber-700 dark:text-amber-500">belum kembali</span>
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <div class="text-xs uppercase text-gray-400">Lama</div>
                                    <div class="text-gray-900 dark:text-white">
                                        @php
                                            $mulai = $header->tgl_pinjam ? \Carbon\Carbon::parse($header->tgl_pinjam)->startOfDay() : null;
                                            $akhir = $pinjam->tgl_kembali ? \Carbon\Carbon::parse($pinjam->tgl_kembali)->startOfDay() : now()->startOfDay();
                                        @endphp
                                        {{ $mulai ? $mulai->diffInDays($akhir) . ' hari' : '-' }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-xs uppercase text-gray-400">Kondisi Kembali</div>
                                    <div>
                                        @if($pinjam->kondisi_kembali === 'Baik')
                                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">Baik</span>
                                        @elseif($pinjam->kondisi_kembali === 'Rusak')
                                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">Rusak</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if($header->keperluan)
                                <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                                    <span class="font-semibold">Keperluan:</span> {{ $header->keperluan }}
                                </div>
                            @endif

                            @if($pinjam->catatan_pengembalian)
                                <div class="text-xs text-gray-600 dark:text-gray-400">
                                    <span class="font-semibold">Catatan kembali:</span> {{ $pinjam->catatan_pengembalian }}
                                </div>
                            @endif

                            @foreach($pinjam->buktiFoto as $bukti)
                                <a href="{{ $bukti->url }}" target="_blank"
                                    class="mt-2 me-1 inline-block h-16 w-16 overflow-hidden rounded-lg border border-gray-200 hover:border-indigo-400"
                                    title="Bukti foto pengembalian">
                                    <img src="{{ $bukti->url }}" alt="Bukti pengembalian" class="h-full w-full object-cover">
                                </a>
                            @endforeach
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <svg class="mx-auto h-10 w-10 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l-4-4 4-4m-4 4h11a4 4 0 110 8h-1" />
                            </svg>
                            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Aset ini belum pernah dipinjam</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Yang tercatat di sini hanya peminjaman yang asetnya benar-benar keluar,
                                yaitu sudah disetujui General Affair dan diketahui HRD.
                            </p>
                        </div>
                    @endforelse
                @endif

                {{-- ============ TAB: PIC & RUANGAN ============ --}}
                @if($tabRiwayat === 'perpindahan')
                    @forelse($riwayatAset->riwayatMutasi as $baris)
                        <div class="flex gap-3 pb-4 last:pb-0">
                            <div class="flex flex-col items-center">
                                <span @class([
                                    'flex h-8 w-8 shrink-0 items-center justify-center rounded-full',
                                    'bg-indigo-100 text-indigo-700' => $baris->jenis === 'PIC',
                                    'bg-teal-100 text-teal-700' => $baris->jenis !== 'PIC',
                                ])>
                                    @if($baris->jenis === 'PIC')
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    @else
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    @endif
                                </span>
                                @if(!$loop->last)
                                    <span class="mt-1 w-px flex-1 bg-gray-200 dark:bg-gray-600"></span>
                                @endif
                            </div>

                            <div class="flex-1 pb-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span @class([
                                        'rounded px-2 py-0.5 text-xs font-semibold',
                                        'bg-indigo-100 text-indigo-800' => $baris->jenis === 'PIC',
                                        'bg-teal-100 text-teal-800' => $baris->jenis !== 'PIC',
                                    ])>{{ $baris->jenis }}</span>
                                    {{-- Tanggal kejadian, bukan waktu pengetikan. Kalau keduanya
                                         beda, waktu pencatatannya ikut disebut supaya terlihat
                                         bahwa catatannya dibuat belakangan. --}}
                                    @if($baris->dicatat_belakangan)
                                        <span class="text-xs text-gray-400">{{ $baris->tgl_kejadian->format('d/m/Y') }}</span>
                                        <span class="text-xs text-amber-600 dark:text-amber-500">
                                            (dicatat {{ $baris->created_at?->format('d/m/Y H:i') }})
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">{{ $baris->created_at?->format('d/m/Y H:i') }}</span>
                                    @endif
                                </div>
                                <div class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $baris->ringkasan }}
                                </div>

                                {{-- Alasan perpindahan. Tanpa ini, mutasi otomatis dari
                                     peminjaman, pengembalian, dan offboarding terlihat sama
                                     saja dengan perubahan yang diketik manual. --}}
                                @if($baris->keterangan)
                                    <div class="mt-0.5 flex items-start gap-1 text-xs text-indigo-700 dark:text-indigo-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-px h-3.5 w-3.5 shrink-0">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" />
                                        </svg>
                                        <span>{{ $baris->keterangan }}</span>
                                    </div>
                                @endif

                                {{-- Bukti foto serah terima ke manajemen, kalau perpindahan ini
                                     berasal dari pencatatan tersebut. --}}
                                @if($baris->pengembalianManajemen && $baris->pengembalianManajemen->buktiFoto->isNotEmpty())
                                    <div class="mt-1.5 flex flex-wrap gap-1">
                                        @foreach($baris->pengembalianManajemen->buktiFoto as $bukti)
                                            <a href="{{ $bukti->url }}" target="_blank"
                                                title="Bukti serah terima ke manajemen"
                                                class="inline-block h-14 w-14 overflow-hidden rounded-lg border border-gray-200 hover:border-indigo-400 dark:border-gray-600">
                                                <img src="{{ $bukti->url }}" alt="Bukti serah terima" class="h-full w-full object-cover">
                                            </a>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Dicatat oleh {{ $baris->pencatat->name ?? 'sistem' }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <svg class="mx-auto h-10 w-10 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Belum ada riwayat</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Riwayat akan terisi otomatis begitu PIC atau ruangan aset ini diubah.
                            </p>
                        </div>
                    @endforelse
                @endif
            </div>
        </div>
    </div>
</div>
