@section('title', 'Tambah Projek | BE INVENTORY')
<x-app-layout>
    @props(['variant' => ''])
    <x-app.secondary-header :variant="$attributes['headerVariant']">
        <div class="flex">
            {{-- <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
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
            </div> --}}
        </div>

        <!-- Header: Right side -->
        <div class="flex items-center space-x-3">
            <div class="p-1 flex items-center justify-end gap-x-2">
                <a href="{{ route('perbaikan-data.index') }}" type="button" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">Kembali</a>
                <button id="saveButton" type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">{{ $isEdit ? 'Update' : 'Simpan' }}</button>
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

        @if ($errors->any())
            <div id="errorAlert" class="flex items-center p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800" role="alert">
                <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <span class="sr-only">Info</span>
                <div>
                    <strong class="font-bold">Error!</strong>
                    <span class="font-medium">{{ $errors->first('error') }}</span>
                    @foreach ($errors->all() as $error)
                        <span class="font-medium">{{ $error }}</span><br>
                    @endforeach
                </div>
            </div>
        @endif
        <div class="sm:flex sm:justify-between sm:items-center mb-2">
        </div>

        <div class="w-full max-w-9xl mx-auto">
            {{-- Layout --}}
            <div class="flex flex-col items-start gap-6">
                {{-- Right: Cart --}}
                <div class="w-full bg-white border rounded-lg p-6 shadow">
                    <form
                        action="{{ $isEdit ? route('perbaikan-data.update', $perbaikanData->id) : route('perbaikan-data.store') }}"
                        method="POST"
                        enctype="multipart/form-data"
                        id="perbaikanDataForm"
                    >
                        @csrf
                        @if($isEdit)
                            @method('PUT')
                        @endif

                        <div class="space-y-6">
                            <div class="border-b border-gray-900/10 pb-2 mb-2">
                                <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-1">

                                    {{-- Kode Pengajuan --}}
                                    <div class="flex items-center">
                                        <label for="kode_pengajuan" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Kode Pengajuan</label>
                                        <input type="text"
                                            id="kode_pengajuan"
                                            name="kode_pengajuan"
                                            value="{{ $perbaikanData->kode_pengajuan ?? 'PD - ' }}"
                                            {{ $isEdit ? 'readonly' : 'disabled' }}
                                            class="block rounded-md w-3/4 border-gray-300 bg-gray-100 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                    </div>

                                    {{-- Jenis Pengajuan --}}
                                    <div class="flex items-start">
                                        <label class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                                            Jenis Pengajuan
                                        </label>
                                        <div class="grid grid-cols-2 gap-2 w-3/4">
                                            @php
                                                $jenisList = [
                                                    'Transaksi - Bahan Masuk',
                                                    'Transaksi - Bahan Keluar',
                                                    'Transaksi - Pembelian Bahan',
                                                    'Bahan Rusak',
                                                    'Bahan Retur',
                                                    'Stock Opname',
                                                    'Pengambilan Bahan Non Proyek/Produksi',
                                                    'Produksi Produk Setengah Jadi',
                                                    'Produk Setengah Jadi',
                                                    'Produksi Produk Jadi',
                                                    'Produk Jadi',
                                                    'Proyek',
                                                    'Produk Sample',
                                                    'Garansi Proyek',
                                                    'Proyek RnD',
                                                    'QC Bahan Masuk',
                                                    'QC Produk Setengah Jadi',
                                                    'QC Produk Jadi',
                                                ];
                                                $selectedJenis = $isEdit ? explode(', ', $perbaikanData->jenis) : [];
                                            @endphp

                                            @foreach($jenisList as $jenis)
                                                <label class="inline-flex items-center">
                                                    <input type="checkbox"
                                                        name="jenis[]"
                                                        value="{{ $jenis }}"
                                                        {{ in_array($jenis, $selectedJenis) ? 'checked' : '' }}
                                                        class="rounded text-indigo-600 focus:ring-indigo-500">
                                                    <span class="ml-2 text-gray-700 text-sm">{{ $jenis }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Upload Form Pengajuan --}}
                                    <div class="flex items-center mb-3">
                                        <label for="form_pengajuan" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                                            Upload Form Pengajuan
                                        </label>
                                        <div class="w-3/4">
                                            <input
                                                type="file"
                                                id="form_pengajuan"
                                                name="form_pengajuan"
                                                accept=".pdf"
                                                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-md cursor-pointer
                                                    bg-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-600
                                                    file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                                    file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700
                                                    hover:file:bg-indigo-100"
                                            >
                                            <ul id="lampiran-list" class="mt-2 text-sm text-gray-600 list-disc list-inside"></ul>
                                            <div class="mt-3">
                                                @if($isEdit && $perbaikanData->form_pengajuan)
                                                    <a href="{{ asset('storage/' . $perbaikanData->form_pengajuan) }}"
                                                    target="_blank"
                                                    class="ml-3 text-sm text-indigo-600 hover:underline">Lihat Form Pengajuan</a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Lampiran --}}
                                    <div class="flex items-start">
                                        <label for="lampiran" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4 mt-1">
                                            Lampiran
                                        </label>
                                        <div class="w-3/4">
                                            <input
                                                type="file"
                                                name="lampiran[]"
                                                id="lampiran"
                                                multiple
                                                accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                                                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-md cursor-pointer
                                                    bg-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-600
                                                    file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                                    file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700
                                                    hover:file:bg-indigo-100"
                                                onchange="previewLampiran(this)"
                                            >

                                            <ul id="lampiran-list" class="mt-2 text-sm text-gray-600 list-disc list-inside"></ul>

                                            {{-- tampilkan file lama --}}
                                            @if($isEdit && $perbaikanData->lampiran->count() > 0)
                                                <div class="mt-3">
                                                    <span class="text-xs text-gray-500">Lampiran:</span>
                                                    <ul class="list-disc list-inside text-sm text-indigo-600">
                                                        @foreach($perbaikanData->lampiran as $lampiran)
                                                            <li>
                                                                <a href="{{ asset('storage/'.$lampiran->lampiran) }}" target="_blank">{{ basename($lampiran->lampiran) }}</a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        function previewLampiran(input) {
            const list = document.getElementById('lampiran-list');
            list.innerHTML = '';

            if (input.files.length > 0) {
                for (const file of input.files) {
                    const li = document.createElement('li');
                    li.textContent = file.name;
                    list.appendChild(li);
                }
            } else {
                list.innerHTML = '<li>Tidak ada file dipilih</li>';
            }
        }
    </script>
    <script>
        document.getElementById('saveButton').addEventListener('click', function() {
            document.getElementById('perbaikanDataForm').submit();
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

</x-app-layout>
