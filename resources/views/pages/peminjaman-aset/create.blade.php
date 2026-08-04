@section('title', 'Ajukan Peminjaman | BE INVENTORY')
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
                <a href="{{ route('peminjaman-aset.index') }}" type="button" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">Kembali</a>
                {{-- type="submit" + form="peminjamanForm", bukan tombol biasa yang
                     di-submit lewat JS: atribut form= menghubungkannya ke <form> di
                     badan halaman secara native, jadi tidak lagi bergantung skrip. --}}
                <button id="saveButton" type="submit" form="peminjamanForm" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed disabled:bg-gray-400">Simpan</button>
            </div>
        </div>
    </x-app.secondary-header>

    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        @if ($errors->any())
            {{-- Tidak hilang sendiri: pesan validasi perlu dibaca sambil membetulkan
                 isian, dan sebelumnya alert ini lenyap setelah lima detik. --}}
            <x-app.alert type="error">
                @foreach ($errors->all() as $error)
                    <span class="font-medium">{{ $error }}</span><br>
                @endforeach
            </x-app.alert>
        @endif

        <div class="sm:flex sm:justify-between sm:items-center mb-2">
        </div>

        <div class="w-full bg-white border border-gray-200 rounded-lg p-4 shadow sm:p-8 dark:bg-gray-800 dark:border-gray-700">
            <form action="{{ route('peminjaman-aset.store') }}" method="POST" id="peminjamanForm">
                @csrf

                {{-- Data pengajuan --}}
                <div class="border-b border-gray-900/10 pb-2 mb-2">
                    <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
                        <div class="flex items-center">
                            <label for="kode_peminjaman" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white mr-2 w-1/4">Kode
                                Peminjaman</label>
                            <input type="text" id="kode_peminjaman" disabled placeholder="PJA - "
                                class="block rounded-md w-3/4 border-gray-300 bg-gray-100 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 sm:text-sm sm:leading-6">
                        </div>

                        <div class="flex items-center">
                            <label for="pengaju" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white mr-2 w-1/4">Pengaju</label>
                            <input type="text" id="pengaju" disabled value="{{ Auth::user()->name }}"
                                class="block rounded-md w-3/4 border-gray-300 bg-gray-100 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 sm:text-sm sm:leading-6">
                        </div>

                        <div class="flex items-center">
                            <label for="divisi" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white mr-2 w-1/4">
                                Divisi <sup class="text-red-500 text-base">*</sup>
                            </label>
                            <select name="divisi" id="divisi" required
                                class="dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 block rounded-md border-0 py-1.5 w-3/4 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                <option value="">-- Pilih Divisi --</option>
                                @foreach(['Administrasi','General Affair','HRD','HSE','Marketing','OP','Produksi','Publikasi','Purchasing','RnD','Sekretaris','Software','Teknisi'] as $divisi)
                                    <option value="{{ $divisi }}" {{ old('divisi') == $divisi ? 'selected' : '' }}>{{ $divisi }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center">
                            <label for="ruangan_id" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white mr-2 w-1/4">
                                Ruangan Tujuan <sup class="text-red-500 text-base">*</sup>
                            </label>
                            <select name="ruangan_id" id="ruangan_id" required
                                class="dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 block rounded-md border-0 py-1.5 w-3/4 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                <option value="">-- Pilih Ruangan Tujuan --</option>
                                @foreach($dataRuangan as $ruangan)
                                    <option value="{{ $ruangan->id }}" {{ old('ruangan_id') == $ruangan->id ? 'selected' : '' }}>
                                        {{ $ruangan->nama_ruangan }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center">
                            <label for="tgl_pinjam" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white mr-2 w-1/4">Tgl Pinjam
                                <sup class="text-red-500 text-base">*</sup>
                            </label>
                            <div class="relative w-3/4">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-3 h-3 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                    </svg>
                                </div>
                                <input type="text" name="tgl_pinjam" id="tgl_pinjam" value="{{ old('tgl_pinjam') }}" placeholder="Pilih tanggal" required
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500 block w-full py-1.5 pl-10 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                            </div>
                        </div>

                        <div class="flex items-center">
                            <label for="keperluan" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white mr-2 w-1/4">
                                Keperluan <sup class="text-red-500 text-base">*</sup>
                            </label>
                            <textarea id="keperluan" name="keperluan" required
                                class="w-3/4 block rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600"
                                placeholder="Contoh: dipakai untuk instalasi di lokasi proyek">{{ old('keperluan') }}</textarea>
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

                {{-- Daftar aset di kiri, keranjang di kanan --}}
                <div class="w-full max-w-9xl mx-auto">
                    <div class="flex flex-col lg:flex-row items-start gap-6">
                        {{-- Left: Asset List + Search --}}
                        <div class="w-full lg:w-2/4 bg-white border rounded-lg p-6 shadow dark:bg-gray-800 dark:border-gray-700">
                            <h2 class="text-xl font-bold mb-4 dark:text-white">Daftar Aset</h2>
                            <livewire:search-aset-peminjaman />
                        </div>

                        {{-- Right: Cart --}}
                        <div class="w-full lg:w-3/4 bg-white border rounded-lg p-6 shadow dark:bg-gray-800 dark:border-gray-700">
                            <livewire:aset-peminjaman-cart />
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
<x-app.kirim-sekali form="peminjamanForm" />

<script>
    document.addEventListener('DOMContentLoaded', function() {
        flatpickr("#tgl_pinjam", { dateFormat: "Y-m-d" });
    });
</script>
