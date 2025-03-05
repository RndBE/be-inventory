@section('title', 'Update Pengajuan Pengambilan Bahan | BE INVENTORY')
<x-app-layout>
    @props(['variant' => ''])
    <x-app.secondary-header :variant="$attributes['headerVariant']">
        <div class="flex">
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <div class="w-full md:block md:w-auto">
                    <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
                        <li class="flex items-center {{ $pengambilanBahan->status === 'Konfirmasi' ? 'text-blue-600 dark:text-blue-500' : '' }}">
                            <span class="flex items-center justify-center w-4 h-4 me-2 text-xs border {{ $pengambilanBahan->status === 'Konfirmasi' ? 'border-blue-600 dark:border-blue-500' : 'border-gray-500 dark:border-gray-400' }} rounded-full shrink-0">
                                1
                            </span>
                            <span class="text-xs">Konfirmasi</span>
                            <svg class="w-3 h-3 ms-2 sm:ms-4 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 12 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m7 9 4-4-4-4M1 9l4-4-4-4"/>
                            </svg>
                        </li>
                        <li class="flex items-center {{ $pengambilanBahan->status === 'Dalam Proses' ? 'text-blue-600 dark:text-blue-500' : '' }}">
                            <span class="flex items-center justify-center w-4 h-4 me-2 text-xs border {{ $pengambilanBahan->status === 'Dalam Proses' ? 'border-blue-600 dark:border-blue-500' : 'border-gray-500 dark:border-gray-400' }} rounded-full shrink-0">
                                2
                            </span>
                            <span class="text-xs">Dalam Proses</span>
                            <svg class="w-3 h-3 ms-2 sm:ms-4 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 12 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m7 9 4-4-4-4M1 9l4-4-4-4"/>
                            </svg>
                        </li>
                        <li class="flex items-center {{ $pengambilanBahan->status === 'Selesai' ? 'text-blue-600 dark:text-blue-500' : '' }}">
                            <span class="flex items-center justify-center w-4 h-4 me-2 text-xs border {{ $pengambilanBahan->status === 'Selesai' ? 'border-blue-600 dark:border-blue-500' : 'border-gray-500 dark:border-gray-400' }} rounded-full shrink-0">
                                3
                            </span>
                            <span class="text-xs">Selesai</span>
                        </li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Header: Right side -->
        <div class="flex items-center space-x-3">
            <div class="p-1 flex items-center justify-end gap-x-2">
                @if($pengambilanBahan->status !== 'Selesai')
                    <a href="{{ route('pengambilan-bahan.index') }}" type="button" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500" >Kembali</a>
                    @can('edit-pengambilan-bahan')
                        <button id="saveButton" type="button" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Simpan</button>
                    @endcan
                    @can('selesai-pengambilan')
                        @if($isComplete)
                            <button data-modal-target="selesai-modal" data-modal-toggle="selesai-modal" class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500" type="button">
                                Selesai
                            </button>
                        @endif
                    @endcan
                @elseif ($pengambilanBahan->status === 'Selesai')
                    <a href="{{ route('pengambilan-bahan.index') }}" type="button" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Kembali</a>
                @else
                    <a href="{{ route('pengambilan-bahan.index') }}" type="button" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Kembali</a>
                @endif
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

        <div class="w-full bg-white border border-gray-200 rounded-lg p-4 shadow sm:p-8 dark:bg-gray-800 dark:border-gray-700">
            <form action="{{ route('pengambilan-bahan.update', $pengambilanBahan->id) }}" method="POST" enctype="multipart/form-data" id="produksiForm">
                @csrf
                @method('PUT') <!-- Use PUT method for updating -->
                <div class="space-y-6">
                    <div>
                        <div class="border-b border-gray-900/10 pb-2 mb-2">
                            <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
                                <div class="flex items-center">
                                    <label for="kode_pengajuan" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Kode Pengajuan</label>
                                    <input type="text" id="kode_pengajuan" value="{{ $pengambilanBahan->kode_pengajuan }}" disabled placeholder="PR - " class="block rounded-md w-3/4 border-gray-300 bg-gray-100 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" readonly>
                                </div>

                                <div class="flex items-center">
                                    <label for="project" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                                        Project <sup class="text-red-500 text-base">*</sup>
                                    </label>
                                    <input
                                        type="text"
                                        id="project"
                                        name="project" disabled
                                        value="{{ old('project', $pengambilanBahan->project) }}"
                                        class="w-3/4 block rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>


                                <div class="flex items-center">
                                    <label for="divisi" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                                        Divisi <sup class="text-red-500 text-base">*</sup>
                                    </label>
                                    <select disabled name="divisi" id="divisi" class="dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 block rounded-md border-0 py-1.5 w-3/4 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" autofocus required>
                                        <option value="">-- Pilih Divisi --</option>
                                        <option value="Produksi" {{ (old('divisi') ?? $pengambilanBahan->divisi) == 'Produksi' ? 'selected' : '' }}>Produksi</option>
                                        <option value="Teknisi" {{ (old('divisi') ?? $pengambilanBahan->divisi) == 'Teknisi' ? 'selected' : '' }}>Teknisi</option>
                                        <option value="RnD" {{ (old('divisi') ?? $pengambilanBahan->divisi) == 'RnD' ? 'selected' : '' }}>RnD</option>
                                        <option value="Publikasi" {{ (old('divisi') ?? $pengambilanBahan->divisi) == 'Publikasi' ? 'selected' : '' }}>Publikasi</option>
                                        <option value="Software" {{ (old('divisi') ?? $pengambilanBahan->divisi) == 'Software' ? 'selected' : '' }}>Software</option>
                                        <option value="Marketing" {{ (old('divisi') ?? $pengambilanBahan->divisi) == 'Marketing' ? 'selected' : '' }}>Marketing</option>
                                        <option value="Purchasing" {{ (old('divisi') ?? $pengambilanBahan->divisi) == 'Purchasing' ? 'selected' : '' }}>Purchasing</option>
                                        <option value="HSE" {{ (old('divisi') ?? $pengambilanBahan->divisi) == 'HSE' ? 'selected' : '' }}>HSE</option>
                                        <option value="OP" {{ (old('divisi') ?? $pengambilanBahan->divisi) == 'OP' ? 'selected' : '' }}>OP</option>
                                        <option value="Administrasi" {{ (old('divisi') ?? $pengambilanBahan->divisi) == 'Administrasi' ? 'selected' : '' }}>Administrasi</option>
                                        <option value="Sekretaris" {{ (old('divisi') ?? $pengambilanBahan->divisi) == 'Sekretaris' ? 'selected' : '' }}>Sekretaris</option>
                                    </select>
                                </div>

                                {{-- <div class="flex items-center">
                                    <label for="mulai_pengajuan" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Mulai Pengajuan<sup class="text-red-500 text-base">*</sup></label>
                                    <div class="relative w-3/4">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <svg class="w-3 h-3 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                            </svg>
                                        </div>
                                        <input type="text" value="{{ $pengajuan->mulai_pengajuan }}" name="mulai_pengajuan" id="datetimepicker" placeholder="Pilih tanggal dan waktu" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500 block w-full py-1.5 pl-10 sm:text-sm sm:leading-6 cursor-default pointer-events-none" {{ $pengajuan->status === 'Selesai' ? 'disabled' : '' }} readonly required>
                                    </div>
                                    @error('mulai_pengajuan')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                                </div> --}}

                                <div class="flex items-center">
                                    <label for="keterangan" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                                        Keterangan <sup class="text-red-500 text-base">*</sup>
                                    </label>
                                    <textarea disabled id="keterangan" name="keterangan" class="w-3/4 block rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">{{ old('keterangan', $pengambilanBahan->keterangan) }}</textarea>
                                </div>

                                <div class="flex items-center">
                                    <label for="datepicker-autohide" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4"></label>
                                    <div class="relative w-3/4 mr-2">
                                        <div class="flex items-center me-4">
                                            <p class="text-red-500 text-sm"><sup>*</sup>) Wajib diisi</p>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                        @if ($pengambilanBahan->status !== 'Selesai')
                            {{-- <livewire:search-bahan-produksi/> --}}
                            <livewire:search-pengambilan-bahan/>
                        @endif
                        {{-- <livewire:bahan-pengajuan-cart :pengajuanId="$pengajuanId" /> --}}
                        <livewire:edit-bahan-pengambilan-bahan-cart :pengambilanBahanId="$pengambilanBahanId" />
                    </div>
                </div>
            </form>
        </div>
    </div>
    @include('pages.pengambilan.selesai')
    <script>
        document.getElementById('saveButton').addEventListener('click', function() {
            document.getElementById('produksiForm').submit();
        });
    </script>

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
        // Fungsi untuk menghilangkan pesan error setelah 5 detik
        setTimeout(function() {
            const errorMessages = document.querySelectorAll('.error-message');
            errorMessages.forEach(function(message) {
                message.style.display = 'none';
            });
        }, 3000); // 3000 ms = 3 detik
    </script>
</x-app-layout>
