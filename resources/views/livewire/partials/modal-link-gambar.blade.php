{{--
    Modal isi/ubah tautan foto produk. Dipakai tabel Produk Jadi dan Produk
    Setengah Jadi lewat trait MengelolaLinkGambarProduk, jadi nama properti dan
    aksinya sama di kedua tempat.
--}}
@can('edit-link-gambar-produk')
    <div x-data="{ isOpen: @entangle('modalLinkGambarTerbuka') }"
        x-show="isOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center w-full h-full"
        style="background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px);"
        @keydown.escape.window="$wire.tutupModalLinkGambar()">

        <div class="relative w-full max-w-lg p-4 bg-white rounded-lg shadow dark:bg-gray-700"
            @click.outside="$wire.tutupModalLinkGambar()">

            <div class="flex items-center justify-between pb-3 border-b dark:border-gray-600">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tautan Foto Produk</h3>
                <button type="button" wire:click="tutupModalLinkGambar"
                    class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Tutup</span>
                </button>
            </div>

            <div class="py-4">
                <label for="linkGambar" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                    Tautan foto produk
                </label>
                <input type="text" id="linkGambar" wire:model="linkGambar"
                    placeholder="https://drive.google.com/file/d/..."
                    class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm dark:text-gray-200 dark:bg-gray-800 dark:ring-gray-600">

                @error('linkGambar')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Tautan Google Drive akan tampil sebagai gambar kecil di tabel.
                    Tautan lain tetap tersimpan, hanya ditampilkan sebagai tautan biasa.
                    Kosongkan untuk menghapus.
                </p>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t dark:border-gray-600">
                <button type="button" wire:click="tutupModalLinkGambar"
                    class="px-3 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                    Batal
                </button>
                <button type="button" wire:click="simpanLinkGambar" wire:loading.attr="disabled"
                    class="px-3 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-md shadow-sm hover:bg-indigo-500 disabled:opacity-50">
                    Simpan
                </button>
            </div>
        </div>
    </div>
@endcan
