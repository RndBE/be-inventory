{{-- Modal Konfirmasi Selesai --}}
<div id="selesai-modal" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-full max-h-full" style="background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px);">
    <div class="relative p-4 w-full max-w-sm max-h-full">
        <div class="relative bg-white rounded-xl shadow-lg dark:bg-gray-700">
            {{-- Tombol X tutup --}}
            <button type="button" class="absolute top-3 end-3 text-gray-400 bg-transparent hover:bg-gray-100 hover:text-gray-900 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="selesai-modal">
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                </svg>
                <span class="sr-only">Close modal</span>
            </button>

            <form action="{{ route('projek-rnd.updateStatus', $projek_rnd->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="p-6 text-center">
                    {{-- Icon lingkaran hijau --}}
                    <div class="flex items-center justify-center w-14 h-14 mx-auto mb-4 rounded-full bg-green-100 dark:bg-green-900">
                        <svg class="w-7 h-7 text-green-600 dark:text-green-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </div>

                    <h3 class="mb-1 text-base font-semibold text-gray-800 dark:text-white">Selesaikan Proyek RnD?</h3>
                    <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">
                        Pilih <strong>Ya</strong> jika ingin menyelesaikan proyek RnD,<br>
                        Pilih <strong>Tidak</strong> untuk membatalkan aksi ini.
                    </p>

                    <div class="flex justify-center gap-3">
                        <button type="submit" name="status" value="selesai"
                            class="px-5 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 dark:focus:ring-green-800">
                            Ya
                        </button>
                        <button type="button" data-modal-hide="selesai-modal"
                            class="px-5 py-2 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-red-600 focus:ring-4 focus:outline-none focus:ring-red-200 dark:focus:ring-red-800">
                            Tidak
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi Hentikan --}}
<div id="hentikan-modal" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-full max-h-full" style="background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px);">
    <div class="relative p-4 w-full max-w-sm max-h-full">
        <div class="relative bg-white rounded-xl shadow-lg dark:bg-gray-700">
            {{-- Tombol X tutup --}}
            <button type="button" class="absolute top-3 end-3 text-gray-400 bg-transparent hover:bg-gray-100 hover:text-gray-900 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="hentikan-modal">
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                </svg>
                <span class="sr-only">Close modal</span>
            </button>

            <form action="{{ route('projek-rnd.updateStatus', $projek_rnd->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="p-6 text-center">
                    {{-- Icon lingkaran merah --}}
                    <div class="flex items-center justify-center w-14 h-14 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900">
                        <svg class="w-7 h-7 text-red-600 dark:text-red-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </div>

                    <h3 class="mb-1 text-base font-semibold text-gray-800 dark:text-white">Hentikan Proyek RnD?</h3>
                    <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">
                        Pilih <strong>Ya</strong> jika ingin menghentikan proyek RnD,<br>
                        Status akan berubah menjadi <strong class="text-red-600">Tidak Dilanjutkan</strong>.
                    </p>

                    <div class="flex justify-center gap-3">
                        <button type="submit" name="status" value="batal"
                            class="px-5 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 dark:focus:ring-green-800">
                            Ya
                        </button>
                        <button type="button" data-modal-hide="hentikan-modal"
                            class="px-5 py-2 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-red-600 focus:ring-4 focus:outline-none focus:ring-red-200 dark:focus:ring-red-800">
                            Tidak
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
