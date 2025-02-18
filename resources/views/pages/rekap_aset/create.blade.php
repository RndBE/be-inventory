@section('title', 'Tambah Bahan | BE INVENTORY')
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
                                <a href="{{ route('rekap-aset.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Rekap Aset</a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                </svg>
                                <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Tambah Rekap Aset</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Header: Right side -->
        <div class="flex items-center space-x-3">
            <div class="p-1 flex items-center justify-end gap-x-2">
                <a href="{{ route('rekap-aset.index') }}" type="button" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">Kembali</a>
                <button id="saveButton" type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Simpan</button>
            </div>
        </div>
    </x-app.secondary-header>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <div class="w-full bg-white border border-gray-200 rounded-lg shadow sm:p-8 dark:bg-gray-800 dark:border-gray-700">
            <form action="{{ route('rekap-aset.store') }}" method="POST" enctype="multipart/form-data" id="asetForm">
                @csrf
                <div class="space-y-12">
                    <div class="border-b border-gray-900/10 pb-12">
                        <div class="p-4 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">

                            <div class="sm:col-span-2 sm:col-start-1">
                            <label for="nomor_aset" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">Nomor Aset</label>
                            <div class="mt-2">
                                <input value="{{ old('nomor_aset') }}" type="text" name="nomor_aset" id="nomor_aset" autocomplete="address-level2" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" placeholder="INV/ATC/CHAIR-001/DIR/2025">
                                @error('nomor_aset')
                                    <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                @enderror
                            </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="barang_aset_id" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">Nama Barang</label>
                                <div class="mt-2">
                                    <select id="barang_aset_id" name="barang_aset_id" autocomplete="country-name" class="block w-full mt-2 rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
                                        <option value="" disabled selected>Pilih Barang</option>
                                        @foreach($barangAset as $barang)
                                            <option value="{{ $barang->id }}" {{ old('barang_aset_id') == $barang->id ? 'selected' : '' }}>{{ $barang->nama_barang }}</option>
                                        @endforeach
                                    </select>
                                    @error('barang_aset_id')
                                        <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>


                            <div class="sm:col-span-2">
                                <label for="link_gambar" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">Link Gambar</label>
                                <div class="mt-2">
                                    <input value="{{ old('link_gambar') }}" type="text" name="link_gambar" id="link_gambar" autocomplete="street-address" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
                                    @error('link_gambar')
                                        <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="datepicker-autohide" class="block text-sm font-medium leading-6 text-gray-900">Tanggal Perolehan</label>
                                <div class="mt-2">
                                    <div class="relative max-w-lg">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <svg class="w-3 h-3 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                            </svg>
                                        </div>
                                        <input type="text" name="tgl_perolehan" id="datetimepicker" value="{{ old('tgl_perolehan') }}" placeholder="Pilih tanggal" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 py-1.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                                    </div>
                                    @error('tgl_perolehan')
                                        <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="jumlah_aset" class="block text-sm font-medium leading-6 text-gray-900">Jumlah Aset</label>
                                <div class="mt-2">
                                    <input value="{{ old('jumlah_aset') }}" type="number" name="jumlah_aset" id="jumlah_aset" autocomplete="address-level2" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                    @error('jumlah_aset')
                                        <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="harga_perolehan" class="block text-sm font-medium leading-6 text-gray-900">Harga Perolehan</label>
                                <div class="mt-2">
                                    <input value="{{ old('harga_perolehan') }}" type="text" name="harga_perolehan" id="harga_perolehan" autocomplete="address-level2" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                    @error('harga_perolehan')
                                        <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>


                            <div class="sm:col-span-2">
                                <label for="kondisi" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">Kondisi Aset</label>
                                <div class="mt-2">
                                    <select id="kondisi" name="kondisi" autocomplete="country-name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
                                        <option value="" disabled selected>Pilih Kondisi</option>
                                        <option value="Baik" {{ old('kondisi') == 'Baik' ? 'selected' : '' }}>Baik</option>
                                        <option value="Rusak" {{ old('kondisi') == 'Rusak' ? 'selected' : '' }}>Rusak</option>
                                    </select>
                                    @error('kondisi')
                                        <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="jumlah_aset" class="block text-sm font-medium leading-6 text-gray-900">Keterangan</label>
                                <div class="mt-2">
                                    <textarea id="keterangan" name="keterangan" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">{{ old('keterangan') }}</textarea>
                                    @error('keterangan')
                                        <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="user_id" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">Penanggung Jawab</label>
                                <div class="mt-2">
                                    <select id="user_id" name="user_id" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
                                        <option value="" disabled selected>Pilih Penanggung Jawab</option>
                                        @foreach($dataUser as $user)
                                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>


    </div>
</x-app-layout>
<script>
    setTimeout(function() {
        const errorMessages = document.querySelectorAll('.error-message');
        errorMessages.forEach(function(message) {
            message.style.display = 'none';
        });
    }, 3000);
</script>
<script>
    document.getElementById('saveButton').addEventListener('click', function() {
        document.getElementById('asetForm').submit();
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        flatpickr("#datetimepicker", {
            dateFormat: "Y-m-d",
        });
    });
</script>
