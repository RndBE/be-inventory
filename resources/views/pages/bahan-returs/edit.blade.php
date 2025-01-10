<!-- Main modal -->
{{-- @if($isEditModalOpen) --}}
<div x-data="{ isOpen: @entangle('isEditModalOpen') }"
    x-show="isOpen"
    class="fixed inset-0 flex items-center justify-center z-50 w-full h-full"
    style="background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px);"
    @keydown.escape.window="isOpen = false; $wire.closeModal();"
    x-transition:enter="transition ease-out duration-900"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-900"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0">

    <div class="relative p-4 w-full max-w-md max-h-full"
        x-show="isOpen"
        @click.outside="isOpen = false; $wire.closeModal();"
        x-transition:enter="transition ease-out duration-900 transform"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-900 transform"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Edit Status
                </h3>
                <button wire:click="closeModal" type="button" @click="isOpen = false; $wire.closeModal();" class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <!-- Modal body -->
            <div class="pt-0 p-5">
                <form class="formeditdata space-y-6" method="post" action="{{ route('bahan-returs.update', (int)$id_bahan_returs) }}">
                    @csrf
                    {{ method_field('PUT') }}
                    <div>
                        <label for="status" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Status</label>
                        <select wire:model="status" name="status" id="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white" required>
                            <option value="" disabled>Pilih Status</option>
                            <option value="Belum disetujui" {{ $status === 'Belum disetujui' ? 'selected' : '' }}>Belum disetujui</option>
                            <option value="Disetujui" {{ $status === 'Disetujui' ? 'selected' : '' }}>Disetujui</option>
                            <option value="Ditolak" {{ $status === 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
                            <!-- Tambahkan opsi lain sesuai kebutuhan -->
                        </select>
                    </div>

                    <button type="submit" class="w-full text-white bg-indigo-600 hover:bg-indigo-800 focus:ring-4 focus:outline-none focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800">Simpan</button>
                </form>
            </div>
        </div>
    </div>
</div>
{{-- @endif --}}
