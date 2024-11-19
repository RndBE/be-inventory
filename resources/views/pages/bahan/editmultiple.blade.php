@section('title', 'Edit Bahan | BE INVENTORY')
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
                                <a href="{{ route('bahan.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Bahan</a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                </svg>
                                <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Edit Bahan</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Header: Right side -->
        <div class="flex items-center space-x-3">
            <div class="p-1 flex items-center justify-end gap-x-2">
                <a href="{{ route('bahan.index') }}" type="button" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">Kembali</a>
                <button id="saveButton" type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Simpan</button>
            </div>
        </div>
    </x-app.secondary-header>
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
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="w-full bg-white border border-gray-200 rounded-lg shadow sm:p-8 dark:bg-gray-800 dark:border-gray-700">
            <form action="{{ route('bahan.update.multiple') }}" method="POST" enctype="multipart/form-data" id="bahanEditForm">
                @csrf
                @method('PUT')
                <div class="space-y-12">
                    <div class="border-b border-gray-900/10 pb-12">
                        @foreach ($bahans as $bahan)
                        <div class="p-4 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6 border border-gray-300 rounded-lg shadow-sm mb-4">
                            <input type="hidden" name="bahan[{{ $bahan->id }}][id]" value="{{ $bahan->id }}">

                            <!-- Kode Bahan -->
                            <div class="sm:col-span-2 sm:col-start-1">
                                <label for="kode_bahan_{{ $bahan->id }}" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">
                                    Kode Bahan
                                </label>
                                <div class="mt-2">
                                    <input type="text" name="bahan[{{ $bahan->id }}][kode_bahan]" id="kode_bahan_{{ $bahan->id }}"
                                        value="{{ old('bahan.' . $bahan->id . '.kode_bahan', $bahan->kode_bahan) }}"
                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:text-gray-400 dark:bg-gray-700">
                                    @error('bahan.' . $bahan->id . '.kode_bahan')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Nama Bahan -->
                            <div class="sm:col-span-2">
                                <label for="nama_bahan_{{ $bahan->id }}" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">
                                    Nama Bahan
                                </label>
                                <div class="mt-2">
                                    <input type="text" name="bahan[{{ $bahan->id }}][nama_bahan]" id="nama_bahan_{{ $bahan->id }}"
                                        value="{{ old('bahan.' . $bahan->id . '.nama_bahan', $bahan->nama_bahan) }}"
                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:text-gray-400 dark:bg-gray-700">
                                    @error('bahan.' . $bahan->id . '.nama_bahan')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Jenis Bahan -->
                            <div class="sm:col-span-2">
                                <label for="jenis_bahan_id_{{ $bahan->id }}" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">
                                    Jenis Bahan
                                </label>
                                <div class="mt-2">
                                    <select name="bahan[{{ $bahan->id }}][jenis_bahan_id]" id="jenis_bahan_id_{{ $bahan->id }}"
                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:text-gray-400 dark:bg-gray-700">
                                        <option value="" disabled>Pilih Jenis Bahan</option>
                                        @foreach($jenisBahan as $jenis)
                                            <option value="{{ $jenis->id }}"
                                                {{ old('bahan.' . $bahan->id . '.jenis_bahan_id', $bahan->jenis_bahan_id) == $jenis->id ? 'selected' : '' }}>
                                                {{ $jenis->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('bahan.' . $bahan->id . '.jenis_bahan_id')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Unit -->
                            <div class="sm:col-span-2">
                                <label for="unit_id_{{ $bahan->id }}" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">
                                    Unit
                                </label>
                                <div class="mt-2">
                                    <select name="bahan[{{ $bahan->id }}][unit_id]" id="unit_id_{{ $bahan->id }}"
                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:text-gray-400 dark:bg-gray-700">
                                        <option value="" disabled>Pilih Unit</option>
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}"
                                                {{ old('bahan.' . $bahan->id . '.unit_id', $bahan->unit_id) == $unit->id ? 'selected' : '' }}>
                                                {{ $unit->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('bahan.' . $bahan->id . '.unit_id')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Supplier -->
                            <div class="sm:col-span-2">
                                <label for="supplier_id_{{ $bahan->id }}" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">
                                    Supplier
                                </label>
                                <div class="mt-2">
                                    <select name="bahan[{{ $bahan->id }}][supplier_id]" id="supplier_id_{{ $bahan->id }}"
                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:text-gray-400 dark:bg-gray-700">
                                        <option value="" disabled>Pilih Supplier</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}"
                                                {{ old('bahan.' . $bahan->id . '.supplier_id', $bahan->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                                {{ $supplier->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('bahan.' . $bahan->id . '.supplier_id')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Penempatan -->
                            <div class="sm:col-span-2">
                                <label for="penempatan_{{ $bahan->id }}" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">
                                    Penempatan
                                </label>
                                <div class="mt-2">
                                    <input type="text" name="bahan[{{ $bahan->id }}][penempatan]" id="penempatan_{{ $bahan->id }}"
                                        value="{{ old('bahan.' . $bahan->id . '.penempatan', $bahan->penempatan) }}"
                                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:text-gray-400 dark:bg-gray-700">
                                    @error('bahan.' . $bahan->id . '.penempatan')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Gambar -->
                            <div class="sm:col-span-2 sm:col-start-1">
                                <label for="gambar_{{ $bahan->id }}" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">
                                    Gambar
                                </label>
                                <div class="mt-2">
                                    <input type="file" name="bahan[{{ $bahan->id }}][gambar]" id="gambar_{{ $bahan->id }}"
                                    class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 file:rounded-lg file:w-24 file:h-9">
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-300" id="file_input_help">PNG, JPG or JPEG (MAX. 2 MB).</p>
                                    @error('bahan.' . $bahan->id . '.gambar')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Display Current Image -->
                            <div class="sm:col-span-2 sm:col-start-1">
                                <div class="mt-2">
                                    @if($bahan->gambar)
                                        <img src="{{ asset('storage/' . $bahan->gambar) }}" alt="Gambar {{ $bahan->nama_bahan }}" class="h-auto w-24 rounded-lg">
                                    @else
                                        <p>Tidak ada gambar tersedia.</p>
                                    @endif
                                </div>
                            </div>

                        </div>
                        @endforeach
                    </div>
                </div>
            </form>
        </div>


    </div>
</x-app-layout>
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
        document.getElementById('bahanEditForm').submit();
    });
</script>
