@section('title', 'Edit Produk | BE INVENTORY')

<x-app-layout>
    @props(['variant' => ''])
    <x-app.secondary-header :variant="$attributes['headerVariant']">
        <div class="flex">
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <nav class="flex flex-wrap" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
                        <li class="inline-flex items-center">
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                                <svg class="w-3 h-3 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                                </svg>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                </svg>
                                <a href="{{ route('produk-produksis.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Produk Produksi</a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                </svg>
                                <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Edit Produk</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Header: Right side -->
        <div class="flex items-center space-x-3">
            <div class="p-1 flex items-center justify-end gap-x-2">
                <a href="{{ route('produk-produksis.index') }}" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Kembali</a>
                <button id="saveButton" type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Simpan</button>
            </div>
        </div>
    </x-app.secondary-header>

    <div class="px-4 sm:px-6 lg:px-8 py-2 w-full max-w-9xl mx-auto">
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
        <div class="w-full max-w-9xl mx-auto">
            <div class="flex flex-col lg:flex-row items-start gap-6">
                <div class="w-full lg:w-3/4 bg-white border rounded-lg p-6 shadow">
                    <h2 class="text-xl font-bold mb-4">Daftar Bahan</h2>
                    <livewire:search-bahan-pengambilan/>
                </div>

                <div class="w-full lg:w-2/4 bg-white border rounded-lg p-6 shadow">
                    <form action="{{ route('produk-produksis.update', $produkProduksis->id) }}" method="POST" enctype="multipart/form-data" id="produksiForm">
                        @csrf
                        @method('PUT')

                        <div class="space-y-12">
                            <div class="border-gray-900/10 pb-12">
                                <div class="p-4 grid grid-cols-2 gap-x-6 gap-y-2 sm:grid-cols-1">
                                    <div class="mt-2">
                                        <label for="bahan_id" class="block text-sm font-medium text-gray-700">Pilih Produk</label>
                                        <div class="mt-2">
                                            <select name="bahan_id" id="bahan_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                                                <option value="" disabled {{ !$produkProduksis->bahan_id ? 'selected' : '' }}>Pilih Produk</option>
                                                @foreach ($bahans as $bahan)
                                                    <option value="{{ $bahan->id }}" {{ $produkProduksis->bahan_id == $bahan->id ? 'selected' : '' }}>
                                                        {{ $bahan->nama_bahan }} ({{ $bahan->kode_bahan }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('bahan_id')
                                                <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        @error('bahan_id')
                                            <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- <livewire:search-bahan/> --}}

                        {{-- <livewire:search-bahan-pengambilan/> --}}
                        {{-- <div class="w-full lg:w-2/4 bg-white border rounded-lg p-6 shadow"> --}}
                        <livewire:bahan-cart :produkProduksisId="$produkProduksisId" />
                        {{-- </div> --}}
                    </form>
                </div>
            </div>
        </div>



    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Atur waktu delay dalam milidetik (contoh: 5000 = 5 detik)
            const delay = 5000;

            // Menghilangkan alert sukses
            const successAlert = document.getElementById('successAlert');
            if (successAlert) {
                setTimeout(() => {
                    successAlert.style.display = 'none';
                }, delay);
            }

            // Menghilangkan alert error
            const errorAlert = document.getElementById('errorAlert');
            if (errorAlert) {
                setTimeout(() => {
                    errorAlert.style.display = 'none';
                }, delay);
            }
        });
    </script>
    <script>
        document.getElementById('saveButton').addEventListener('click', function() {
            document.getElementById('produksiForm').submit();
        });
    </script>
</x-app-layout>
