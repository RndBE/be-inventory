<!-- Main modal -->
<div x-data="{ isOpen: @entangle('isShowGambarModalOpen') }"
    x-show="isOpen"
    class="fixed inset-0 flex items-center justify-center z-50 w-full h-full"
    style="background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px);"
    @keydown.escape.window="isOpen = false; $wire.closeModal();"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0">

    <div class="relative p-4 w-full max-w-2xl max-h-full"
        x-show="isOpen"
        @click.outside="isOpen = false; $wire.closeModal();"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-300 transform"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                {{-- Sebelumnya berjudul "Invoice" — sisa salinan dari modul lain.
                     Modal ini menampilkan foto aset, dibuka dari kolom gambar di
                     tabel rekap aset. --}}
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Gambar Aset
                </h3>
                {{-- Cukup wire:click. Sebelumnya ada @click yang juga memanggil
                     $wire.closeModal(), jadi closeModal terpanggil dua kali;
                     isOpen sudah ikut lewat @entangle. --}}
                <button wire:click="closeModal" type="button" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <!-- Modal body -->
            {{-- max-h + overflow: tanpa ini modal melewati layar ponsel dan tidak
                 bisa digulir, karena tinggi iframe-nya dulu dipatok 600px. --}}
            <div class="max-h-[75dvh] overflow-y-auto overscroll-contain pt-0 p-5">
                @php
                    // Satu sumber penguraian untuk semua tempat. Versi sebelumnya di
                    // berkas ini memotong di '/view', jadi tautan '?usp=sharing' dan
                    // '/edit' menyisakan ekor dan preview-nya kosong — padahal
                    // thumbnail di tabel yang sama tampil normal.
                    $preview = \App\Helpers\GoogleDriveHelper::preview($link_gambar);
                @endphp
                @if ($preview)
                    {{-- Tinggi mengikuti layar, bukan 600px tetap. --}}
                    <iframe src="{{ $preview }}" title="Pratinjau gambar aset"
                        class="h-[60dvh] w-full rounded-md border-0 sm:h-[65dvh]"></iframe>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-300">
                        @if (filled($link_gambar))
                            Tautannya bukan berkas Google Drive, jadi tidak bisa ditampilkan di sini.
                            <a href="{{ $link_gambar }}" target="_blank" rel="noopener"
                                class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">Buka di tab baru</a>.
                        @else
                            Aset ini belum punya tautan gambar.
                        @endif
                    </p>
                @endif
            </div>

        </div>
    </div>
</div>
