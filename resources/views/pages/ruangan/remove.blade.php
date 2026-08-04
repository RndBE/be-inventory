{{-- duration-300, bukan duration-900: nilai itu bukan class Tailwind yang sah
     (75/100/150/200/300/500/700/1000), jadi tidak pernah ada di bundle dan
     transisinya diam-diam jatuh ke durasi default.

     mengirim: mengunci tombol sejak form dikirim, seperti modal aset lainnya. --}}
<div x-data="{ isOpen: @entangle('isDeleteModalOpen'), mengirim: false }"
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
    <div class="relative p-4 w-full max-w-md max-h-full" x-show="isOpen"
        @click.outside="isOpen = false; $wire.closeModal();"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-300 transform"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95">

        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            {{-- Cukup wire:click; @click yang juga memanggil $wire.closeModal()
                 membuatnya terpanggil dua kali, dan isOpen sudah ikut lewat @entangle. --}}
            <button wire:click="closeModal" type="button" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                </svg>
                <span class="sr-only">Close modal</span>
            </button>

            <div class="p-5 text-center">
                <svg class="mx-auto mb-4 text-gray-400 w-12 h-12 dark:text-gray-200" aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">Apakah Anda yakin ingin menghapus <span class="font-semibold text-gray-900 dark:text-white">{{ $nama_ruangan }}</span>?</h3>

                {{-- Batal di kiri, aksi destruktif di kanan — urutannya tadinya
                     terbalik dari seluruh modal lain di modul aset (selesaikan,
                     pengembalian, pengembalian-manajemen). Tombol destruktif yang
                     berpindah tempat antar layar adalah sumber salah klik. --}}
                <div class="flex justify-center gap-4">
                    <button wire:click="closeModal" type="button" x-bind:disabled="mengirim"
                        class="py-2.5 px-5 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                        Batal
                    </button>
                    <form action="{{ route('ruangan.destroy', (int)$id_ruangan) }}" method="POST" class="inline-flex"
                        x-on:submit="mengirim = true">
                        @csrf
                        @method('DELETE')
                        <button type="submit" x-bind:disabled="mengirim"
                            class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500 dark:focus:ring-red-800 font-medium rounded-lg text-sm px-5 py-2.5">
                            <span x-show="!mengirim">Yakin</span>
                            <span x-show="mengirim" x-cloak>Menghapus…</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
