<div x-data="{ isOpen: @entangle('showModal') }"
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

        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <button wire:click="closeModal" type="button" @click="isOpen = false; $wire.closeModal();"
                class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900
                rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600
                dark:hover:text-white">
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                </svg>
                <span class="sr-only">Close modal</span>
            </button>
            <div class="p-4 md:p-5 text-center">
                <svg class="mx-auto mb-4 text-gray-400 w-12 h-12 dark:text-gray-200" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
                    <b>Perhatian!</b> Hanya pengajuan yang notifikasinya masuk ke finance sebelum pukul <b>11:45</b> yang akan diproses ke direktur.
                </h3>
                <div class="flex justify-center gap-4">
                    <button wire:click="closeModal" @click="isOpen = false"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg">Mengerti</button>
                </div>
            </div>
        </div>
    </div>
</div>
