{{-- konfirmasi menampung aset yang sedang dipinjam dan sedang menunggu persetujuan
     pengaju sebelum masuk keranjang. Null berarti tidak ada dialog terbuka. --}}
<div class="relative" x-data="{ konfirmasi: null }">
    <div class="flex flex-col gap-3">
        <div class="relative w-full">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <svg class="w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
                </svg>
            </div>
            <input type="text" wire:model.live.debounce.400ms="query"
                class="block w-full rounded-md border-0 py-2 pl-10 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm dark:text-gray-300 dark:bg-gray-700 dark:ring-gray-600"
                placeholder="Cari nomor aset, serial number, nama barang, atau ruangan...">
        </div>

        <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
            <input type="checkbox" wire:model.change="hanyaTersedia"
                wire:loading.attr="disabled" wire:target="hanyaTersedia"
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
            Hanya tampilkan yang tersedia
        </label>
    </div>

    <div wire:loading.delay wire:target="query,previousPage,nextPage,gotoPage"
        class="mt-3 text-xs text-gray-500 dark:text-gray-400">Memuat aset...</div>

    <div class="mt-4 grid min-h-[28rem] grid-cols-1 gap-4 sm:grid-cols-2"
        wire:loading.class.delay="pointer-events-none" wire:target="hanyaTersedia">
        @forelse($asetList as $aset)
            @php
                $sudahDipilih = in_array($aset->id, $terpilih);
                $peminjam = $aset->peminjamanAktif;

                // Satu sumber penguraian untuk semua tempat — lihat GoogleDriveHelper.
                $thumbnail = \App\Helpers\GoogleDriveHelper::thumbnail($aset->link_gambar, 400);
            @endphp

            {{-- flex-col + h-full supaya semua kartu setinggi baris grid, tombolnya sejajar --}}
            <div wire:key="aset-peminjaman-card-{{ $aset->id }}" @class([
                    'relative flex h-full flex-col rounded-lg border bg-white p-4 shadow-sm transition-colors duration-150 dark:bg-gray-800',
                    'border-indigo-500 ring-2 ring-indigo-200 dark:border-indigo-400' => $sudahDipilih,
                    'border-gray-200 hover:border-indigo-400 hover:shadow-md dark:border-gray-700' => !$sudahDipilih,
                ])>

                @if($sudahDipilih)
                    <span class="absolute right-3 top-3 z-10 inline-flex items-center gap-1 rounded-full bg-indigo-600 px-2 py-0.5 text-xs font-semibold text-white">
                        Dipilih
                    </span>
                @endif

                <div class="mb-3 h-40 w-full shrink-0 overflow-hidden rounded-md bg-gray-100 dark:bg-gray-700">
                    @if($thumbnail)
                        <img src="{{ $thumbnail }}"
                            alt="{{ $aset->barangAset->nama_barang ?? 'Aset' }}"
                            loading="lazy"
                            decoding="async"
                            class="h-full w-full object-cover"
                            onerror="this.onerror=null; this.style.display='none'; this.parentElement.classList.add('flex','items-center','justify-center'); this.parentElement.innerHTML='<span class=\'text-xs text-gray-400\'>Gambar tidak tersedia</span>';">
                    @else
                        <div class="flex h-full w-full items-center justify-center">
                            <span class="text-xs text-gray-400">Tidak ada gambar</span>
                        </div>
                    @endif
                </div>

                <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                    {{ $aset->barangAset->nama_barang ?? '-' }}
                </h4>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $aset->nomor_aset }}</p>
                @if($aset->serial_number)
                    <p class="text-xs text-gray-400">SN: {{ $aset->serial_number }}</p>
                @endif

                <div class="mt-3 flex flex-wrap items-center gap-1.5">
                    @if($peminjam)
                        <span class="rounded border border-amber-400 bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
                            Dipinjam
                        </span>
                    @else
                        <span class="rounded border border-green-400 bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">
                            Tersedia
                        </span>
                    @endif

                    {{-- Sengaja badge terpisah dari "Tersedia", bukan menggantikannya:
                         aset yang sudah ditugaskan tetap TETAP tersedia untuk dipinjam
                         sementara. Yang perlu diketahui pengaju cuma bahwa aset ini
                         sudah ada pemiliknya, jadi penempatannya akan berpindah. --}}
                    @if($aset->ditugaskan_tetap)
                        <span class="rounded border border-sky-400 bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-800"
                            title="Sudah ditugaskan tetap ke {{ $aset->dataPic->name ?? 'seseorang' }}">
                            Ditugaskan tetap
                        </span>
                    @endif

                    @if($aset->kondisi === 'Rusak')
                        <span class="rounded border border-red-400 bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">
                            Rusak
                        </span>
                    @endif
                </div>

                <div class="mt-2 space-y-0.5 text-xs text-gray-600 dark:text-gray-400">
                    <div><span class="font-semibold">Ruangan:</span> {{ $aset->dataRuangan->nama_ruangan ?? '-' }}</div>
                    <div><span class="font-semibold">PIC:</span> {{ $aset->dataPic->name ?? '-' }}</div>
                    @if($peminjam)
                        <div class="text-amber-700 dark:text-amber-500">
                            Dipegang {{ $peminjam->peminjamanAset->dataUser->name ?? 'orang lain' }}
                            sejak {{ $peminjam->peminjamanAset->tgl_pinjam ?? '-' }}
                        </div>
                    @elseif($aset->ditugaskan_tetap)
                        <div class="text-sky-700 dark:text-sky-400">
                            Ditugaskan tetap ke {{ $aset->dataPic->name ?? '-' }}.
                            Kalau dipinjam, PIC & ruangannya berpindah dan tidak kembali otomatis
                            setelah dikembalikan.
                        </div>
                    @endif
                </div>

                {{-- mt-auto mendorong tombol ke dasar kartu, apa pun panjang nama asetnya --}}
                <div class="mt-auto pt-4">
                    {{-- Aset yang masih dipegang orang lain tetap boleh diajukan (bisa saja
                         sudah kembali sebelum tanggal pinjam), tapi lewat konfirmasi dulu
                         supaya pengaju sadar pengajuannya berisiko ditolak GA/HRD. --}}
                    <button type="button"
                        @disabled($sudahDipilih)
                        @if($peminjam)
                            x-on:click="konfirmasi = {
                                id: {{ $aset->id }},
                                nama: @js(($aset->barangAset->nama_barang ?? 'Aset') . ' (' . $aset->nomor_aset . ')'),
                                peminjam: @js($peminjam->peminjamanAset->dataUser->name ?? 'orang lain'),
                                sejak: @js($peminjam->peminjamanAset->tgl_pinjam ?? '-'),
                            }"
                        @else
                            wire:click="$dispatch('asetSelected', { asetId: {{ $aset->id }} })"
                        @endif
                        @class([
                            'w-full rounded-md px-3 py-2 text-xs font-semibold transition',
                            'bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-700' => $sudahDipilih,
                            'bg-amber-600 text-white hover:bg-amber-500' => !$sudahDipilih && $peminjam,
                            'bg-indigo-600 text-white hover:bg-indigo-500' => !$sudahDipilih && !$peminjam,
                        ])>
                        @if($sudahDipilih)
                            Sudah dipilih
                        @elseif($peminjam)
                            Ajukan Walau Dipinjam
                        @else
                            Pilih Aset
                        @endif
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-lg border border-dashed border-gray-300 py-10 text-center dark:border-gray-600">
                <svg class="mx-auto h-10 w-10 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Aset tidak ditemukan</h3>
                <p class="mt-1 text-sm text-gray-500">Coba ubah kata pencarian atau matikan filter ketersediaan.</p>
            </div>
        @endforelse
    </div>

    @if($asetList->hasPages())
        <div class="mt-4">
            {{ $asetList->links() }}
        </div>
    @endif

    {{-- Dialog konfirmasi aset yang masih dipegang orang lain --}}
    <div x-show="konfirmasi" x-cloak
        class="fixed inset-0 z-50 flex h-full w-full items-center justify-center"
        style="background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px);"
        x-on:keydown.escape.window="konfirmasi = null"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100">

        <div class="relative max-h-full w-full max-w-md p-4" x-on:click.outside="konfirmasi = null">
            <div class="relative rounded-lg bg-white shadow dark:bg-gray-700">
                <div class="flex items-center justify-between rounded-t border-b p-4 md:p-5 dark:border-gray-600">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Aset Sedang Dipinjam</h3>
                    <button type="button" x-on:click="konfirmasi = null"
                        class="ms-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-transparent text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                        <span class="sr-only">Tutup</span>
                    </button>
                </div>

                <div class="space-y-4 p-5">
                    <div class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-600 dark:bg-amber-900/30 dark:text-amber-200">
                        <div class="font-semibold" x-text="konfirmasi?.nama"></div>
                        <div class="mt-1">
                            Masih dipegang <span class="font-semibold" x-text="konfirmasi?.peminjam"></span>
                            sejak <span class="font-semibold" x-text="konfirmasi?.sejak"></span>
                            dan belum dikembalikan.
                        </div>
                    </div>

                    <p class="text-sm text-gray-500 dark:text-gray-300">
                        Anda tetap boleh mengajukannya kalau aset diperkirakan sudah kembali sebelum tanggal pinjam.
                        Tapi kalau saat General Affair atau HRD memproses aset ini masih dipinjam,
                        pengajuannya akan otomatis ditolak.
                    </p>

                    <div class="flex gap-3">
                        <button type="button" x-on:click="konfirmasi = null"
                            class="w-full rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-500 dark:text-gray-200 dark:hover:bg-gray-600">
                            Batal
                        </button>
                        <button type="button"
                            x-on:click="$wire.dispatch('asetSelected', { asetId: konfirmasi.id }); konfirmasi = null"
                            class="w-full rounded-lg bg-amber-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-300">
                            Ya, Tetap Ajukan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
