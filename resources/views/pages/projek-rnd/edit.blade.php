@section('title', 'Update Projek Rnd | BE INVENTORY')
<x-app-layout>
    @props(['variant' => ''])
    <x-app.secondary-header :variant="$attributes['headerVariant']">
        <div class="flex">
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <div class="w-full md:block md:w-auto">
                    <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
                        <li class="flex items-center {{ $projek_rnd->status === 'Konfirmasi' ? 'text-blue-600 dark:text-blue-500' : '' }}">
                            <span class="flex items-center justify-center w-4 h-4 me-2 text-xs border {{ $projek_rnd->status === 'Konfirmasi' ? 'border-blue-600 dark:border-blue-500' : 'border-gray-500 dark:border-gray-400' }} rounded-full shrink-0">
                                1
                            </span>
                            <span class="text-xs">Konfirmasi</span>
                            <svg class="w-3 h-3 ms-2 sm:ms-4 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 12 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m7 9 4-4-4-4M1 9l4-4-4-4"/>
                            </svg>
                        </li>
                        <li class="flex items-center {{ $projek_rnd->status === 'Dalam Proses' ? 'text-blue-600 dark:text-blue-500' : '' }}">
                            <span class="flex items-center justify-center w-4 h-4 me-2 text-xs border {{ $projek_rnd->status === 'Dalam Proses' ? 'border-blue-600 dark:border-blue-500' : 'border-gray-500 dark:border-gray-400' }} rounded-full shrink-0">
                                2
                            </span>
                            <span class="text-xs">Dalam Proses</span>
                            <svg class="w-3 h-3 ms-2 sm:ms-4 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 12 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m7 9 4-4-4-4M1 9l4-4-4-4"/>
                            </svg>
                        </li>
                        <li class="flex items-center {{ $projek_rnd->status === 'Selesai' ? 'text-blue-600 dark:text-blue-500' : '' }}">
                            <span class="flex items-center justify-center w-4 h-4 me-2 text-xs border {{ $projek_rnd->status === 'Selesai' ? 'border-blue-600 dark:border-blue-500' : 'border-gray-500 dark:border-gray-400' }} rounded-full shrink-0">
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
                @if($projek_rnd->status !== 'Selesai' && $projek_rnd->status !== 'Tidak dilanjutkan'  && $projek_rnd->status !== 'Selesai Belum Laku')
                    <a href="{{ route('projek-rnd.index') }}" type="button" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Kembali</a>
                    @can('selesai-projek-rnd')
                        <button data-modal-target="upload-laporan-modal" data-modal-toggle="upload-laporan-modal" type="button" class="rounded-md bg-yellow-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500">Upload Laporan</button>
                        <button id="saveButton" type="button" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Simpan</button>
                        <button id="simpanSelesaikanBtn" type="button"
                            class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 flex items-center gap-1.5">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                            </svg>
                            Selesaikan
                        </button>
                    @else
                        <button id="saveButton" type="button" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Simpan</button>
                    @endcan
                @elseif ($projek_rnd->status === 'Selesai')
                    <a href="{{ route('projek-rnd.index') }}" type="button" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Kembali</a>
                    <button data-modal-target="upload-laporan-modal" data-modal-toggle="upload-laporan-modal" type="button" class="rounded-md bg-yellow-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500">Upload Laporan</button>
                @else
                    <a href="{{ route('projek-rnd.index') }}" type="button" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Kembali</a>
                    <button data-modal-target="upload-laporan-modal" data-modal-toggle="upload-laporan-modal" type="button" class="rounded-md bg-yellow-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500">Upload Laporan</button>
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

        {{-- @if ($errors->any())
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
        @endif --}}

        <div class="sm:flex sm:justify-between sm:items-center mb-2">
        </div>

        <div class="w-full max-w-9xl mx-auto">
            <div class="flex flex-col items-start gap-6">
                <div class="w-full bg-white border rounded-lg p-6 shadow">
                    <h2 class="text-xl font-bold mb-4">Daftar Bahan</h2>
                    @if ($projek_rnd->status !== 'Selesai' && $projek_rnd->status !== 'Tidak dilanjutkan')
                        <livewire:search-bahan-produk-sample/>
                    @endif
                </div>

                <div class="w-full bg-white border rounded-lg p-6 shadow">
                    <form action="{{ route('projek-rnd.update', $projek_rnd->id) }}" method="POST" enctype="multipart/form-data" id="projekRndForm">
                        @csrf
                        @method('PUT') <!-- Use PUT method for updating -->
                        <div class="space-y-6">
                            <div>
                                <div class="border-b border-gray-900/10 pb-2 mb-2">
                                    <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-1">
                                        <div class="flex items-center">
                                            <label for="kode_projek_rnd" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Kode Proyek</label>
                                            <input type="text" id="kode_projek_rnd" value="{{ $projek_rnd->kode_projek_rnd }}" disabled placeholder="PR - " class="block rounded-md w-3/4 border-gray-300 bg-gray-100 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" readonly>
                                        </div>

                                        <div class="flex items-center">
                                            <label for="nama_projek_rnd" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Nama Produk/Riset
                                                <sup class="text-red-500 text-base">*</sup>
                                            </label>
                                            <input type="text" name="nama_projek_rnd" value="{{ $projek_rnd->nama_projek_rnd ?? '' }}" id="nama_projek_rnd"
                                            placeholder="" class="block rounded-md border-0 py-1.5 w-3/4 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" {{ $projek_rnd->status === 'Selesai' ? 'disabled' : '' }} required>
                                            @error('nama_projek_rnd')
                                                <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="flex items-center">
                                            <label for="mulai_projek_rnd" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Mulai Proyek<sup class="text-red-500 text-base">*</sup></label>
                                            <div class="relative w-3/4">
                                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                    <svg class="w-3 h-3 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                                    </svg>
                                                </div>
                                                <input type="text" value="{{ $projek_rnd->mulai_projek_rnd }}" name="mulai_projek_rnd" id="datetimepicker" placeholder="Pilih tanggal dan waktu" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500 block w-full py-1.5 pl-10 sm:text-sm sm:leading-6 cursor-default pointer-events-none" {{ $projek_rnd->status === 'Selesai' || $projek_rnd->status === 'Tidak dilanjutkan'  ? 'disabled' : '' }} readonly required>
                                            </div>
                                            @error('mulai_projek_rnd')
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                        </div>

                                        <div class="flex items-center">
                                            <label for="keterangan" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                                                Keterangan <sup class="text-red-500 text-base">*</sup>
                                            </label>
                                            <textarea id="keterangan" name="keterangan" class="w-3/4 block rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" {{ $projek_rnd->status === 'Selesai' || $projek_rnd->status === 'Tidak dilanjutkan' ? 'disabled' : '' }}>{{ old('keterangan', $projek_rnd->keterangan) }}</textarea>
                                        </div>

                                        <div class="flex items-center">
                                            <label for="serial_number" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Serial Number

                                            </label>
                                            <input type="text" name="serial_number" value="{{ $projek_rnd->serial_number ?? '' }}" id="serial_number"
                                            placeholder="" class="block rounded-md border-0 py-1.5 w-3/4 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" {{ $projek_rnd->status === 'Selesai' || $projek_rnd->status === 'Tidak dilanjutkan' ? 'disabled' : '' }}>
                                            @error('serial_number')
                                                <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                            @enderror
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
                                {{-- @if ($projek_rnd->status !== 'Selesai' && $projek_rnd->status !== 'Tidak dilanjutkan')
                                    <livewire:search-bahan-produksi/>
                                @endif --}}
                                <livewire:edit-bahan-projek-rnd-cart :projekId="$projekId" />
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @include('pages.projek-rnd.selesai')

    {{-- Modal Upload Laporan --}}
    <div id="upload-laporan-modal" tabindex="-1" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-full max-h-full" style="background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px);">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-xl shadow-lg dark:bg-gray-700">
                <button type="button" class="absolute top-3 end-3 text-gray-400 bg-transparent hover:bg-gray-100 hover:text-gray-900 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center" data-modal-hide="upload-laporan-modal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
                <div class="p-6">
                    <div class="flex items-center justify-center w-14 h-14 mx-auto mb-4 rounded-full bg-yellow-100">
                        <svg class="w-7 h-7 text-yellow-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                        </svg>
                    </div>
                    <h3 class="mb-1 text-base font-semibold text-gray-800 text-center">Upload Laporan</h3>
                    <p class="mb-4 text-sm text-gray-500 text-center">Upload file laporan Projek RnD <br><span class="font-medium text-gray-700">{{ $projek_rnd->kode_projek_rnd }}</span></p>

                    @if($projek_rnd->file_laporan)
                    <div class="mb-4 flex items-center gap-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <svg class="w-5 h-5 text-green-600 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-green-700">File laporan tersedia</p>
                            <a href="{{ route('projek-rnd.downloadLaporan', $projek_rnd->id) }}" class="text-xs text-green-600 hover:underline truncate block">Download file saat ini</a>
                        </div>
                    </div>
                    @endif

                    <form action="{{ route('projek-rnd.uploadLaporan', $projek_rnd->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pilih File <sup class="text-red-500">*</sup></label>
                            <input type="file" name="file_laporan" id="file_laporan" accept=".pdf,.xlsx,.xls,.doc,.docx,.jpg,.jpeg,.png"
                                class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none file:mr-3 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-medium file:bg-yellow-600 file:text-white hover:file:bg-yellow-700">
                            <p class="mt-1 text-xs text-gray-400">Format: PDF, Excel, Word, JPG, PNG. Maks. 10 MB.</p>
                            @error('file_laporan')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" data-modal-hide="upload-laporan-modal"
                                class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Batal
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-yellow-600 rounded-lg hover:bg-yellow-700">
                                Upload
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Tombol Simpan biasa (untuk user tanpa permission selesai-projek-rnd)
        const saveButtonOnly = document.getElementById('saveButton');
        if (saveButtonOnly) {
            saveButtonOnly.addEventListener('click', function() {
                document.getElementById('projekRndForm').submit();
            });
        }

        // Tombol Simpan & Selesaikan — buka modal pilihan status
        const simpanSelesaikanBtn = document.getElementById('simpanSelesaikanBtn');
        if (simpanSelesaikanBtn) {
            simpanSelesaikanBtn.addEventListener('click', function() {
                const modal = document.getElementById('pilihan-status-modal');
                if (modal) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }
            });
        }

        // Fungsi tutup modal pilihan status
        function tutupPilihanStatusModal() {
            const modal = document.getElementById('pilihan-status-modal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        // Pilihan tab status di dalam modal
        const pilihanBtns = document.querySelectorAll('.pilihan-status-btn');
        const pilihanPanels = document.querySelectorAll('.pilihan-status-panel');

        pilihanBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const target = btn.dataset.target;

                // Reset semua tombol
                pilihanBtns.forEach(function(b) {
                    b.classList.remove('border-indigo-600', 'text-indigo-600', 'bg-indigo-50');
                    b.classList.add('border-gray-200', 'text-gray-500', 'bg-white');
                });

                // Aktifkan tombol yang dipilih
                btn.classList.add('border-indigo-600', 'text-indigo-600', 'bg-indigo-50');
                btn.classList.remove('border-gray-200', 'text-gray-500', 'bg-white');

                // Sembunyikan semua panel
                pilihanPanels.forEach(function(panel) {
                    panel.classList.add('hidden');
                });

                // Tampilkan panel yang sesuai
                const activePanel = document.getElementById('panel-' + target);
                if (activePanel) activePanel.classList.remove('hidden');
            });
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
