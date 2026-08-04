@section('title', 'Ubah Pengajuan Peminjaman | BE INVENTORY')
<x-app-layout>
    @props(['variant' => ''])
    <x-app.secondary-header :variant="$attributes['headerVariant']">
        <div class="flex">
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <div class="w-full md:block md:w-auto">
                    <h1 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        Ubah Pengajuan {{ $peminjaman->kode_peminjaman }}
                    </h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Isi masih bisa diubah karena belum ada approver yang memutuskan.
                    </p>
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
                <button id="saveButton" type="submit" form="peminjamanForm" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed disabled:bg-gray-400">Simpan Perubahan</button>
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
            <form action="{{ route('peminjaman-aset.update', $peminjaman->id) }}" method="POST" id="peminjamanForm">
                @csrf
                @method('PUT')

                {{-- Data pengajuan --}}
                <div class="border-b border-gray-900/10 pb-2 mb-2">
                    <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
                        <div class="flex items-center">
                            <label for="kode_peminjaman" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white mr-2 w-1/4">Kode
                                Peminjaman</label>
                            <input type="text" id="kode_peminjaman" disabled value="{{ $peminjaman->kode_peminjaman }}"
                                class="block rounded-md w-3/4 border-gray-300 bg-gray-100 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 sm:text-sm sm:leading-6">
                        </div>

                        <div class="flex items-center">
                            <label for="pengaju" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white mr-2 w-1/4">Pengaju</label>
                            <input type="text" id="pengaju" disabled value="{{ $peminjaman->dataUser->name ?? '-' }}"
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
                                    <option value="{{ $divisi }}" @selected(old('divisi', $peminjaman->divisi) === $divisi)>{{ $divisi }}</option>
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
                                    <option value="{{ $ruangan->id }}" @selected((int) old('ruangan_id', $peminjaman->ruangan_id) === (int) $ruangan->id)>
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
                                <input type="text" name="tgl_pinjam" id="tgl_pinjam" value="{{ old('tgl_pinjam', $peminjaman->tgl_pinjam) }}" placeholder="Pilih tanggal" required
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500 block w-full py-1.5 pl-10 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                            </div>
                        </div>

                        <div class="flex items-center">
                            <label for="keperluan" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white mr-2 w-1/4">
                                Keperluan <sup class="text-red-500 text-base">*</sup>
                            </label>
                            <textarea id="keperluan" name="keperluan" required
                                class="w-3/4 block rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600"
                                placeholder="Contoh: dipakai untuk instalasi di lokasi proyek">{{ old('keperluan', $peminjaman->keperluan) }}</textarea>
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

                {{-- Daftar aset di kiri, keranjang di kanan. Keranjang dimuati isi
                     pengajuan lewat :peminjamanId, aset bisa ditambah maupun dibuang. --}}
                <div class="w-full max-w-9xl mx-auto">
                    <div class="flex flex-col lg:flex-row items-start gap-6">
                        <div class="w-full lg:w-2/4 bg-white border rounded-lg p-6 shadow dark:bg-gray-800 dark:border-gray-700">
                            <h2 class="text-xl font-bold mb-4 dark:text-white">Daftar Aset</h2>
                            <livewire:search-aset-peminjaman />
                        </div>

                        <div class="w-full lg:w-3/4 bg-white border rounded-lg p-6 shadow dark:bg-gray-800 dark:border-gray-700">
                            <livewire:aset-peminjaman-cart :peminjamanId="$peminjaman->id" />
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
<x-app.kirim-sekali form="peminjamanForm" label="Menyimpan…" />

<script>
    document.addEventListener('DOMContentLoaded', function() {
        flatpickr("#tgl_pinjam", { dateFormat: "Y-m-d" });
    });
</script>
