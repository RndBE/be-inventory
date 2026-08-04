{{--
    Konfirmasi menandai BAST selesai.

    Menggantikan confirm() bawaan peramban: selain tampilannya tidak mengikuti
    tema, dialog bawaan tidak bisa merinci apa yang akan terjadi. Padahal ini
    tindakan tak terbalikkan — aset dilepas dari karyawan dan akunnya
    dinonaktifkan — jadi akibatnya perlu disebutkan satu per satu sebelum ditekan.
--}}
{{-- mengirim: mengunci tombol sejak form dikirim. Di modal inilah pengunci itu
     paling penting — aksinya melepas aset, menutup peminjaman, dan menonaktifkan
     akun, dan tidak bisa dibatalkan. Klik kedua pada tindakan seperti itu tidak
     boleh sampai terkirim. --}}
<div x-data="{ isOpen: @entangle('isSelesaiModalOpen'), mengirim: false }"
    x-show="isOpen"
    class="fixed inset-0 z-50 flex h-full w-full items-center justify-center"
    style="background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px);"
    @keydown.escape.window="isOpen = false; $wire.closeModal();"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100">

    <div class="relative max-h-full w-full max-w-lg p-4" x-show="isOpen"
        @click.outside="isOpen = false; $wire.closeModal();"
        x-transition:enter="transition ease-out duration-200 transform"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100">

        <div class="relative rounded-lg bg-white shadow dark:bg-gray-700">
            <div class="flex items-start justify-between rounded-t border-b p-4 md:p-5 dark:border-gray-600">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tandai Selesai</h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-300">
                        {{ $bastSelesai->kode_bast }} &middot; {{ $bastSelesai->dataKaryawan->name ?? '-' }}
                    </p>
                </div>
                <button wire:click="closeModal" type="button"
                    class="ms-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-transparent text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Tutup</span>
                </button>
            </div>

            <div class="space-y-4 p-4 md:p-5">
                @php
                    $jumlahDipegang = $bastSelesai->aset_diserahkan->count();
                @endphp

                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Setelah ditandai selesai, hal berikut akan terjadi:
                </p>

                <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-200">
                    <li class="flex gap-2">
                        <span class="text-gray-400">&bull;</span>
                        <span>
                            @if($jumlahDipegang > 0)
                                <strong>{{ $jumlahDipegang }} aset</strong> dilepas dari
                                {{ $bastSelesai->dataKaryawan->name ?? 'karyawan' }} — PIC dan ruangannya
                                dikosongkan, artinya aset kembali ke tangan manajemen.
                            @else
                                Tidak ada aset yang perlu dilepas — karyawan ini sudah bebas aset.
                            @endif
                        </span>
                    </li>
                    <li class="flex gap-2">
                        <span class="text-gray-400">&bull;</span>
                        <span>Peminjaman yang masih menggantung ikut ditutup dengan tanggal kembali
                            {{ $bastSelesai->tgl_efektif }}.</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="text-gray-400">&bull;</span>
                        <span>Akun <strong>{{ $bastSelesai->dataKaryawan->name ?? '-' }}</strong> diubah
                            menjadi <strong>Non-Aktif</strong>.</span>
                    </li>
                </ul>

                <div class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-600 dark:bg-amber-900/30 dark:text-amber-200">
                    Tindakan ini <strong>tidak dapat dibatalkan</strong>. Pastikan asetnya benar-benar
                    sudah diterima sebelum menandai selesai — kalau ditandai lebih dulu, sistem mencatat
                    aset sudah kembali padahal barangnya masih di tangan orang.
                </div>
            </div>

            <div class="flex gap-3 rounded-b border-t border-gray-200 p-4 md:p-5 dark:border-gray-600">
                <button wire:click="closeModal" type="button" x-bind:disabled="mengirim"
                    class="w-full rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-500 dark:text-gray-200 dark:hover:bg-gray-600">
                    Batal
                </button>
                <form method="POST" x-on:submit="mengirim = true" class="w-full"
                    action="{{ route('serah-terima-aset.selesaikan', $bastSelesai->id) }}">
                    @csrf
                    <button type="submit" x-bind:disabled="mengirim"
                        class="w-full rounded-lg bg-green-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-300 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500">
                        <span x-show="!mengirim">Ya, Tandai Selesai</span>
                        <span x-show="mengirim" x-cloak>Memproses…</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
