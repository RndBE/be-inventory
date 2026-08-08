{{--
    Pratinjau foto produk. Dipakai tabel Produk Jadi dan Produk Setengah Jadi lewat
    trait MengelolaLinkGambarProduk, jadi nama properti dan aksinya sama di keduanya.

    Tidak dibungkus @can: yang boleh membuka halaman ini sudah lolos pemeriksaan di
    level route, dan fotonya tidak lebih rahasia daripada baris yang menampilkannya.
--}}
<div x-data="{ isOpen: @entangle('modalPreviewGambarTerbuka') }"
    x-show="isOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center w-full h-full"
    style="background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px);"
    @keydown.escape.window="$wire.tutupPreviewGambar()"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0">

    <div class="relative w-full max-w-2xl max-h-full p-4"
        @click.outside="$wire.tutupPreviewGambar()">

        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <div class="flex items-center justify-between p-4 border-b rounded-t md:p-5 dark:border-gray-600">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Foto Produk
                    @if(filled($judulPreviewGambar))
                        <span class="block text-xs font-normal text-gray-500 dark:text-gray-300">
                            {{ $judulPreviewGambar }}
                        </span>
                    @endif
                </h3>
                <button wire:click="tutupPreviewGambar" type="button"
                    class="absolute inline-flex items-center justify-center w-8 h-8 text-sm text-gray-400 bg-transparent rounded-lg top-3 end-2.5 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Tutup</span>
                </button>
            </div>

            {{-- max-h + overflow supaya modalnya tidak melewati layar ponsel. --}}
            <div class="max-h-[75dvh] overflow-y-auto overscroll-contain p-5 pt-0">
                @php
                    $preview = \App\Helpers\GoogleDriveHelper::preview($linkPreviewGambar);
                @endphp

                @if($preview)
                    {{-- Tinggi mengikuti layar, bukan angka piksel tetap. --}}
                    <iframe src="{{ $preview }}" title="Pratinjau foto produk"
                        class="w-full mt-4 border-0 rounded-md h-[60dvh] sm:h-[65dvh]"></iframe>
                @else
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-300">
                        @if(filled($linkPreviewGambar))
                            Tautannya bukan berkas Google Drive, jadi tidak bisa ditampilkan di sini.
                            <a href="{{ $linkPreviewGambar }}" target="_blank" rel="noopener noreferrer"
                                class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">Buka di tab baru</a>.
                        @else
                            Produk ini belum punya tautan gambar.
                        @endif
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
