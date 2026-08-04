<!-- Main modal -->
{{-- mengirim: mengunci tombol Simpan sejak form dikirim, seperti modal aset lainnya. --}}
<div x-data="{ isOpen: @entangle('isEditModalOpen'), mengirim: false }"
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

    <div class="relative p-4 w-full max-w-md max-h-full"
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
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Edit Ruangan
                </h3>
                {{-- Cukup wire:click; @click yang juga memanggil $wire.closeModal()
                     membuatnya terpanggil dua kali, dan isOpen sudah ikut lewat @entangle. --}}
                <button wire:click="closeModal" type="button" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <!-- Modal body -->
            <div class="pt-0 p-5">
                <form class="formeditdata space-y-6" method="post" x-on:submit="mengirim = true"
                    action="{{ route('ruangan.update',(int)$id_ruangan) }}">
                @csrf
                {{ method_field('PUT') }}
                    <div>
                        <label for="kode_ruangan" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Kode Ruangan</label>
                        {{-- autofocus di kolom PERTAMA. Sebelumnya dipasang di Nama
                             Ruangan, jadi kursornya melompati Kode Ruangan. --}}
                        <input autofocus wire:model="kode_ruangan" type="text" name="kode_ruangan" id="kode_ruangan" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white" placeholder="RG-DIR" required>
                    </div>
                    <div>
                        <label for="nama_ruangan" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nama Ruangan</label>
                        <input wire:model="nama_ruangan" type="text" name="nama_ruangan" id="nama_ruangan" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white" placeholder="Ruang Direksi" required>
                    </div>

                    <div>
                        <label for="keterangan" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Keterangan</label>
                        <input wire:model="keterangan" type="text" name="keterangan" id="keterangan" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white" placeholder="Opsional">
                    </div>

                    <button type="submit" x-bind:disabled="mengirim"
                        class="w-full text-white bg-indigo-600 hover:bg-indigo-800 focus:ring-4 focus:outline-none focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800">
                        <span x-show="!mengirim">Simpan</span>
                        <span x-show="mengirim" x-cloak>Menyimpan…</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
