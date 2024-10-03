@section('title', 'Tambah Produksi | BE INVENTORY')
<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        @if (session('success'))
            <div id="successAlert" class="flex items-center p-4 mb-4 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800" role="alert">
                <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <span class="sr-only">Info</span>
                <div>
                    <strong class="font-bold">Success!</strong>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            </div>

        @endif

        @if (session('error'))
            <div id="errorAlert" class="flex items-center p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800" role="alert">
                <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <span class="sr-only">Info</span>
                <div>
                    <strong class="font-bold">Error!</strong>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
            </div>
        @endif
        <nav class="bg-white border border-gray-200 rounded-lg shadow sm:p-2 dark:bg-gray-800 dark:border-gray-700 mb-4">
            <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
                <a href="#" class="flex items-center space-x-3 rtl:space-x-reverse">

                </a>
                <div class="w-full md:block md:w-auto">
                    <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
                        <li class="flex items-center text-blue-600 dark:text-blue-500">
                            <span class="flex items-center justify-center w-4 h-4 me-2 text-xs border border-blue-600 rounded-full shrink-0 dark:border-blue-500">
                                1
                            </span>
                            <span class="text-xs">Konfirmasi</span>
                            <svg class="w-3 h-3 ms-2 sm:ms-4 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 12 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m7 9 4-4-4-4M1 9l4-4-4-4"/>
                            </svg>
                        </li>
                        <li class="flex items-center">
                            <span class="flex items-center justify-center w-4 h-4 me-2 text-xs border border-gray-500 rounded-full shrink-0 dark:border-gray-400">
                                2
                            </span>
                            <span class="text-xs">Dalam Proses</span>
                            <svg class="w-3 h-3 ms-2 sm:ms-4 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 12 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m7 9 4-4-4-4M1 9l4-4-4-4"/>
                            </svg>
                        </li>
                        <li class="flex items-center">
                            <span class="flex items-center justify-center w-4 h-4 me-2 text-xs border border-gray-500 rounded-full shrink-0 dark:border-gray-400">
                                3
                            </span>
                            <span class="text-xs">Selesai</span>
                        </li>
                    </ol>
                </div>
            </div>
        </nav>

        <!-- Dashboard actions -->
        <div class="sm:flex sm:justify-between sm:items-center mb-2">
        </div>

        <div class="w-full bg-white border border-gray-200 rounded-lg p-4 shadow sm:p-8 dark:bg-gray-800 dark:border-gray-700">
            <form action="{{ route('produksis.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="space-y-6">
                    <div class="border-b border-gray-900/10 pb-2">
                        <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
                            <div class="flex items-center">
                                <label for="kode_produksi" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Kode Produksi</label>
                                <input type="text" id="kode_produksi" disabled placeholder="PR - " class="block rounded-md w-3/4 border-gray-300 bg-gray-100 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            </div>

                            <div class="flex items-center">
                                <label for="nama_produk" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Nama Produk<sup class="text-red-500 text-base">*</sup></label>
                                <input type="text" name="nama_produk" id="nama_produk" placeholder="" class="block rounded-md border-0 py-1.5 w-3/4 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" required>
                                @error('nama_produk')
                                    <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex items-center">
                                <label for="jml_produksi" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Jumlah Produksi<sup class="text-red-500 text-base">*</sup></label>
                                <input type="number" name="jml_produksi"  id="jml_produksi" placeholder="" class="block rounded-md border-0 w-3/4 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" required>
                                @error('jml_produksi')
                                    <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex items-center">
                                <label for="datepicker-autohide" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Mulai Produksi<sup class="text-red-500 text-base">*</sup></label>
                                <div class="relative w-3/4 mr-2">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <svg class="w-3 h-3 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                        </svg>
                                    </div>
                                    <input type="text" name="mulai_produksi" id="datetimepicker" placeholder="Pilih tanggal dan waktu" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500 block w-full py-1.5 pl-10 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                                </div>
                                @error('mulai_produksi')
                                    <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex items-center">
                                <label for="datepicker-autohide" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Jenis Produksi<sup class="text-red-500 text-base">*</sup></label>
                                <div class="relative w-3/4 mr-2">
                                    <div class="flex flex-wrap">
                                        <div class="flex items-center me-4">
                                            <input id="red-radio" type="radio" value="Produk Jadi" name="jenis_produksi" class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 focus:ring-purple-500 dark:focus:ring-purple-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                            <label for="red-radio" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Produk Jadi</label>
                                        </div>
                                        <div class="flex items-center me-4">
                                            <input id="green-radio" type="radio" value="Produk Setengah Jadi" name="jenis_produksi" class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 focus:ring-purple-500 dark:focus:ring-purple-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                            <label for="green-radio" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Produk Setengah Jadi</label>
                                        </div>
                                    </div>
                                </div>
                                @error('jenis_produksi')
                                    <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex items-center">
                                <label for="datepicker-autohide" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4"></label>
                                <div class="relative w-3/4 mr-2">
                                    <div class="flex flex-wrap">
                                        <div class="flex items-center me-4">
                                            <p class="text-red-500 text-sm"><sup>*</sup>) Wajib diisi</p>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <livewire:search-bahan-masuk/>
                    <livewire:bahan-produksi-cart/>
                </div>
                <div class="mt-2 flex items-center justify-end gap-x-6">
                    <a href="{{ route('produksis.index') }}" type="button" class="text-sm font-semibold leading-6 text-gray-900">Batal</a>
                    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr("#datetimepicker", {
                enableTime: true,
                dateFormat: "Y-m-d H:i:S",
                time_24hr: true // Menggunakan format 24 jam
            });
        });
    </script>

    <script>
        // Fungsi untuk menghilangkan pesan error setelah 5 detik
        setTimeout(function() {
            const errorMessages = document.querySelectorAll('.error-message');
            errorMessages.forEach(function(message) {
                message.style.display = 'none';
            });
        }, 3000); // 3000 ms = 3 detik
    </script>
</x-app-layout>
