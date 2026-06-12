@section('title', 'Tambah Projek RnD | BE INVENTORY')
<x-app-layout>
    @props(['variant' => ''])
    <x-app.secondary-header :variant="$attributes['headerVariant']">
        <div class="flex">
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
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
        </div>

        <!-- Header: Right side -->
        <div class="flex items-center space-x-3">
            <div class="p-1 flex items-center justify-end gap-x-2">
                <a href="{{ route('projek-rnd.index') }}" type="button" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">Kembali</a>
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
                {{-- Left: Product List + Search --}}
                <div class="w-full bg-white border rounded-lg p-6 shadow">
                    <h2 class="text-xl font-bold mb-4">Daftar Bahan</h2>
                    <livewire:search-bahan-produk-sample/>
                </div>

                {{-- Right: Cart --}}
                <div class="w-full bg-white border rounded-lg p-6 shadow">
                    <form action="{{ route('projek-rnd.store') }}" method="POST" enctype="multipart/form-data" id="projekRndForm">
                        @csrf
                        <div class="space-y-6">
                            <div>
                                <div class="border-b border-gray-900/10 pb-2 mb-2">
                                    <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-1">
                                        <div class="flex items-center">
                                            <label for="kode_projek_rnd" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Kode Proyek</label>
                                            <input type="text" id="kode_projek_rnd" disabled placeholder="PR - " class="block rounded-md w-3/4 border-gray-300 bg-gray-100 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                        </div>

                                        <div class="flex items-center">
                                            <label for="nama_projek_rnd" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Nama Produk/Riset
                                                <sup class="text-red-500 text-base">*</sup>
                                            </label>
                                            <input type="text" id="nama_projek_rnd" name="nama_projek_rnd" class=" w-3/4 block rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                        </div>

                                        <div class="flex items-center">
                                            <label for="datepicker-autohide" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Mulai Proyek<sup class="text-red-500 text-base">*</sup></label>
                                            <div class="relative w-3/4">
                                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                    <svg class="w-3 h-3 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                                    </svg>
                                                </div>
                                                <input type="text" name="mulai_projek_rnd" id="datetimepicker" placeholder="Pilih tanggal dan waktu" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500 block w-full py-1.5 pl-10 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                                            </div>
                                        </div>

                                        <div class="flex items-center">
                                            <label for="keterangan" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                                                Keterangan <sup class="text-red-500 text-base">*</sup>
                                            </label>
                                            <textarea id="keterangan" name="keterangan" class="w-3/4 block rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">{{ old('keterangan') }}</textarea>
                                        </div>

                                        <div class="flex items-start">
                                            <label for="is_riset_lapangan" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                                                Riset Lapangan?
                                            </label>
                                            <div class="w-3/4">
                                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" id="is_riset_lapangan" name="is_riset_lapangan" value="1"
                                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                                        @checked(old('is_riset_lapangan'))>
                                                    Ya, riset dilakukan ke lapangan
                                                </label>
                                                <p class="mt-1 text-xs text-gray-500">Jika dicentang, proposal dan surat tugas riset wajib diupload.</p>
                                                @error('is_riset_lapangan')
                                                    <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>

                                        <div id="dokumen-riset-lapangan" class="hidden space-y-3">
                                            <div class="flex items-center">
                                                <label for="file_proposal_riset" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                                                    Proposal Riset <sup class="text-red-500 text-base">*</sup>
                                                </label>
                                                <div class="w-3/4">
                                                    <input type="file" name="file_proposal_riset" id="file_proposal_riset" accept=".pdf,.xlsx,.xls,.doc,.docx,.jpg,.jpeg,.png"
                                                        class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none file:mr-3 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-600 file:text-white hover:file:bg-indigo-700">
                                                    <p class="mt-1 text-xs text-gray-400">Format: PDF, Excel, Word, JPG, PNG. Maks. 10 MB.</p>
                                                    @error('file_proposal_riset')
                                                        <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="flex items-center">
                                                <label for="file_surat_tugas_riset" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                                                    Surat Tugas Riset <sup class="text-red-500 text-base">*</sup>
                                                </label>
                                                <div class="w-3/4">
                                                    <input type="file" name="file_surat_tugas_riset" id="file_surat_tugas_riset" accept=".pdf,.xlsx,.xls,.doc,.docx,.jpg,.jpeg,.png"
                                                        class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none file:mr-3 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-600 file:text-white hover:file:bg-indigo-700">
                                                    <p class="mt-1 text-xs text-gray-400">Format: PDF, Excel, Word, JPG, PNG. Maks. 10 MB.</p>
                                                    @error('file_surat_tugas_riset')
                                                        <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center">
                                            <label for="text" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4"></label>
                                            <div class="relative w-3/4 mr-2">
                                                <div class="flex items-center me-4">
                                                    <p class="text-red-500 text-sm"><sup>*</sup>) Wajib diisi</p>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                {{-- <livewire:search-bahan-produksi/> --}}
                                <livewire:bahan-projek-resource-cart/>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('saveButton').addEventListener('click', function() {
            document.getElementById('projekRndForm').submit();
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr("#datetimepicker", {
                enableTime: true,
                dateFormat: "Y-m-d H:i:S",
                time_24hr: true,
                disableMobile: true,
                defaultDate: document.querySelector('#datetimepicker').value || null,
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const risetLapanganCheckbox = document.getElementById('is_riset_lapangan');
            const dokumenRisetLapangan = document.getElementById('dokumen-riset-lapangan');
            const fileProposalRiset = document.getElementById('file_proposal_riset');
            const fileSuratTugasRiset = document.getElementById('file_surat_tugas_riset');

            function toggleDokumenRisetLapangan() {
                const isLapangan = risetLapanganCheckbox.checked;
                dokumenRisetLapangan.classList.toggle('hidden', !isLapangan);
                fileProposalRiset.required = isLapangan;
                fileSuratTugasRiset.required = isLapangan;
            }

            risetLapanganCheckbox.addEventListener('change', toggleDokumenRisetLapangan);
            toggleDokumenRisetLapangan();
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
