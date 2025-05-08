@section('title', 'Edit Stock Opname | BE INVENTORY')
<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Dashboard actions -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Edit Stock Opname</h1>
            </div>

            <!-- Right: Actions -->
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
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
                            <a href="{{ route('stock-opname.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Stock Opname</a>
                        </div>
                    </li>
                    <li aria-current="page">
                    <div class="flex items-center">
                        <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                        </svg>
                        <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Edit Stock Opname</span>
                    </div>
                    </li>
                </ol>
                </nav>
            </div>
        </div>
        {{-- <div class="w-full bg-white border border-gray-200 rounded-lg shadow sm:p-2 dark:bg-gray-800 dark:border-gray-700 mb-4">
            <livewire:search-bahan/>
        </div> --}}
        <div class="w-full bg-white border border-gray-200 rounded-lg p-4 shadow sm:p-8 dark:bg-gray-800 dark:border-gray-700">
            <form action="{{ route('stock-opname.update', $stockOpname->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="space-y-6">
                    <div class="border-b border-gray-900/10 pb-2 mb-2">
                        <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
                        <div class="flex items-center">
                            <label for="nomor_referensi" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Ref</label>
                            <input type="text" id="nomor_referensi" value="{{ $stockOpname->nomor_referensi }}" class="block w-full rounded-md border-gray-50 bg-gray-50 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" disabled>
                        </div>

                        <div class="flex items-center">
                        </div>

                        <div class="flex items-center">
                            <label for="divisi" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                                Tanggal <sup class="text-red-500 text-base">*</sup>
                            </label>
                            <div class="relative mt-2 w-full"> <!-- Tambahkan w-full dan relative pada div pembungkus -->
                                <!-- Ikon di kiri input -->
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z" />
                                    </svg>
                                </div>
                                <!-- Input field -->
                                <input type="text" value="{{ $stockOpname->tgl_pengajuan }}" name="tgl_pengajuan" id="datetimepicker" value="{{ old('tgl_pengajuan') }}" placeholder="Pilih tanggal"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 py-1.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required @if($stockOpname->status_selesai === 'Selesai') disabled @endif>
                            </div>
                            @error('tgl_pengajuan')
                                <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center">
                        </div>

                        <div class="flex items-center">
                            <label for="keterangan" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                                Keterangan <sup class="text-red-500 text-base">*</sup>
                            </label>
                            <textarea id="keterangan" name="keterangan" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" @if($stockOpname->status_selesai === 'Selesai') disabled @endif>{{ $stockOpname->keterangan }}</textarea>
                        </div>

                        <div class="flex items-center">
                        </div>
                    </div>

                    </div>
                    <livewire:search-bahan/>
                    <livewire:edit-bahan-stock-opname-cart :stockOpnameId="$stockOpnameId"/>
                </div>

                <div class="mt-2 flex items-center justify-end gap-x-2">
                    <a href="{{ route('stock-opname.index') }}" type="button" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">Kembali</a>

                    {{-- @if($stockOpname->status_selesai !== 'Selesai') --}}
                        <a href="{{ route('stock-opname.downloadPdf', $stockOpname->id) }}" target="__blank" type="button" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-[20px] h-[20px] icon icon-tabler icons-tabler-outline icon-tabler-file-type-pdf">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4" />
                                <path d="M5 18h1.5a1.5 1.5 0 0 0 0 -3h-1.5v6" /><path d="M17 18h2" /><path d="M20 15h-3v6" /><path d="M11 15v6h1a2 2 0 0 0 2 -2v-2a2 2 0 0 0 -2 -2h-1z" />
                            </svg>
                        </a>
                    {{-- @endif --}}
                    @can('selesai-stock-opname')
                        @if($stockOpname->status_direktur === 'Disetujui' && $stockOpname->status_selesai !== 'Selesai')
                            <button class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600" type="button" data-modal-target="statustockopnameselesai-modal"
                            data-modal-toggle="statustockopnameselesai-modal">Selesai
                            </button>
                        @endif
                    @endcan

                    @can('approve-stock-opname-finance')
                        @if($stockOpname->status_finance !== 'Disetujui' && $stockOpname->status_finance !== 'Ditolak')
                            <button class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600" type="button" data-modal-target="statustockopnamefianance-modal"
                            data-modal-toggle="statustockopnamefianance-modal">Approve Finance
                            </button>
                        @endif
                    @endcan

                    @can('approve-stock-opname-direktur')
                        @if($stockOpname->status_direktur !== 'Disetujui' && $stockOpname->status_direktur !== 'Ditolak')
                            <button class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600" type="button" data-modal-target="statustockopnamedirektur-modal"
                            data-modal-toggle="statustockopnamedirektur-modal">Approve Direktur
                            </button>
                        @endif
                    @endcan

                    @can('update-stock-opname')
                        @if($stockOpname->status_selesai !== 'Selesai')
                            <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Simpan</button>
                        @endif
                    @endcan
                </div>
            </form>
        </div>
        @include('pages.stock-opname.approval-finance')
        @include('pages.stock-opname.approval-direktur')
        @include('pages.stock-opname.selesai')
    </div>
    <script>
        // Fungsi untuk menghilangkan pesan error setelah 5 detik
        setTimeout(function() {
            const errorMessages = document.querySelectorAll('.error-message');
            errorMessages.forEach(function(message) {
                message.style.display = 'none';
            });
        }, 3000); // 5000 ms = 5 detik
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr("#datetimepicker", {
                dateFormat: "Y-m-d",
            });
        });
    </script>
</x-app-layout>
