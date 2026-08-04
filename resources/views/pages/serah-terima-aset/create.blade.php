@section('title', 'Buat BAST | BE INVENTORY')
<x-app-layout>
    @props(['variant' => ''])
    <x-app.secondary-header :variant="$attributes['headerVariant']">
        <div class="flex">
            <div>
                <h1 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Buat Berita Acara Serah Terima Aset</h1>
                <p class="text-xs text-gray-500 dark:text-gray-400">Daftar aset ditarik otomatis dari data karyawan</p>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('serah-terima-aset.index') }}"
                class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Kembali</a>
            @if($terpilih)
                <button type="submit" form="bastForm"
                    class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-gray-400">Simpan</button>
            @endif
        </div>
    </x-app.secondary-header>

    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        @if ($errors->any())
            <x-app.alert type="error">
                @foreach ($errors->all() as $error)
                    <div class="font-medium">{{ $error }}</div>
                @endforeach
            </x-app.alert>
        @endif

        {{-- Langkah 1: pilih karyawan. Halaman dimuat ulang dengan ?karyawan_id=
             supaya daftar asetnya bisa ditampilkan sebelum BAST dibuat. --}}
        <div class="mb-6 w-full rounded-lg border border-gray-200 bg-white p-6 shadow dark:border-gray-700 dark:bg-gray-800">
            {{-- Memilih karyawan langsung memuat ulang halaman dengan ?karyawan_id=.
                 Tombolnya tetap ada sebagai cadangan kalau JavaScript mati. --}}
            <form method="GET" action="{{ route('serah-terima-aset.create') }}" id="pilihKaryawanForm"
                class="flex flex-wrap items-end gap-3">
                <div class="min-w-64 flex-1">
                    <label for="karyawan_id" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                        Karyawan yang Keluar <sup class="text-red-500 text-base">*</sup>
                    </label>
                    <select name="karyawan_id" id="karyawan_id" required
                        onchange="if (this.value) { this.form.submit(); }"
                        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 sm:text-sm dark:bg-gray-700 dark:text-gray-300">
                        <option value="">-- Pilih Karyawan --</option>
                        @foreach($karyawan as $k)
                            <option value="{{ $k->id }}" @selected($terpilih && $terpilih->id === $k->id)>
                                {{ $k->name }} @if($k->status !== 'Aktif') (Non-Aktif) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                {{-- py-1.5 menyamai tinggi select di sebelahnya supaya benar-benar sejajar,
                     bukan sekadar rata bawah dengan tinggi yang berbeda. --}}
                <button type="submit" class="shrink-0 rounded-md bg-slate-700 px-4 py-1.5 text-sm font-semibold text-white hover:bg-slate-600">
                    Tampilkan Aset
                </button>
            </form>

            @if($terpilih)
                <p class="mt-3 border-t border-gray-200 pt-3 text-sm text-gray-600 dark:border-gray-600 dark:text-gray-300">
                    Menampilkan data untuk <strong class="text-gray-900 dark:text-white">{{ $terpilih->name }}</strong>.
                    Lengkapi form di bawah lalu tekan <strong>Simpan</strong> di kanan atas.
                </p>
            @endif
        </div>

        @if($terpilih)
            <form id="bastForm" method="POST" action="{{ route('serah-terima-aset.store') }}"
                class="w-full rounded-lg border border-gray-200 bg-white p-6 shadow dark:border-gray-700 dark:bg-gray-800">
                @csrf
                <input type="hidden" name="karyawan_id" value="{{ $terpilih->id }}">

                <div class="grid grid-cols-1 gap-4 border-b border-gray-900/10 pb-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Karyawan</label>
                        <input type="text" disabled value="{{ $terpilih->name }} — {{ $terpilih->dataJobPosition->nama ?? '-' }}"
                            class="block w-full rounded-md border-gray-300 bg-gray-100 py-1.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Atasan (otomatis)</label>
                        <input type="text" disabled value="{{ $terpilih->atasanLevel3->name ?? $terpilih->atasanLevel2->name ?? 'Tidak ada — tahap Atasan dilewati' }}"
                            class="block w-full rounded-md border-gray-300 bg-gray-100 py-1.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300">
                    </div>

                    <div>
                        <label for="alasan_keluar" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                            Alasan Berakhir <sup class="text-red-500 text-base">*</sup>
                        </label>
                        <select name="alasan_keluar" id="alasan_keluar" required
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 sm:text-sm dark:bg-gray-700 dark:text-gray-300">
                            @foreach(['Resign', 'Habis Kontrak', 'Pensiun', 'Mutasi', 'Pemutusan Hubungan Kerja', 'Lainnya'] as $alasan)
                                <option value="{{ $alasan }}" @selected(old('alasan_keluar') === $alasan)>{{ $alasan }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="tgl_efektif" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                            Tanggal Efektif <sup class="text-red-500 text-base">*</sup>
                        </label>
                        <input type="date" name="tgl_efektif" id="tgl_efektif" required
                            value="{{ old('tgl_efektif', now()->format('Y-m-d')) }}"
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 sm:text-sm dark:bg-gray-700 dark:text-gray-300">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="keterangan" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Catatan</label>
                        <textarea name="keterangan" id="keterangan" rows="2" placeholder="Opsional"
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 sm:text-sm dark:bg-gray-700 dark:text-gray-300">{{ old('keterangan') }}</textarea>
                    </div>
                </div>

                @php
                    $masihDipegang = $aset->where('status_pegang', 'Dipegang');
                    $sudahKembali = $aset->where('status_pegang', 'Sudah kembali');
                @endphp

                <h2 class="mb-3 mt-5 text-lg font-bold dark:text-white">
                    Aset yang Tercatat
                    <span class="ml-1 align-middle text-sm font-normal text-gray-500 dark:text-gray-400">
                        {{ $masihDipegang->count() }} masih dipegang
                        @if($sudahKembali->isNotEmpty())
                            &middot; {{ $sudahKembali->count() }} sudah kembali
                        @endif
                    </span>
                </h2>

                @if($masihDipegang->isEmpty())
                    <div class="mb-4 rounded-lg border border-green-300 bg-green-50 p-4 text-sm text-green-800 dark:border-green-700 dark:bg-green-900/20 dark:text-green-300">
                        Tidak ada aset yang masih dipegang karyawan ini.
                        BAST tetap dapat diterbitkan dan akan berlaku sebagai <strong>Surat Keterangan Bebas Aset</strong>.
                    </div>
                @endif

                @if($aset->isNotEmpty())
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-600">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="px-4 py-2">Nama Aset</th>
                                    <th class="px-4 py-2">Nomor Aset</th>
                                    <th class="px-4 py-2">Ruangan</th>
                                    <th class="px-4 py-2">Sumber</th>
                                    <th class="px-4 py-2">Status</th>
                                    <th class="px-4 py-2">Kondisi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($aset as $baris)
                                    @php $kembali = $baris['status_pegang'] === 'Sudah kembali'; @endphp
                                    {{-- Baris yang sudah kembali diredupkan: ikut tercantum sebagai
                                         keterangan, tapi bukan yang diserahkan hari ini. --}}
                                    <tr class="border-b dark:border-gray-600 {{ $kembali ? 'bg-gray-50 text-gray-500 dark:bg-gray-900/30' : '' }}">
                                        <td class="px-4 py-2 {{ $kembali ? '' : 'font-medium text-gray-900 dark:text-white' }}">{{ $baris['nama_barang'] }}</td>
                                        <td class="px-4 py-2">{{ $baris['nomor_aset'] }}</td>
                                        <td class="px-4 py-2">{{ $baris['ruangan'] }}</td>
                                        <td class="px-4 py-2">
                                            <span class="rounded border px-2 py-0.5 text-xs {{ $baris['sumber'] === 'PIC' ? 'border-slate-400 bg-slate-100 text-slate-700' : 'border-blue-400 bg-blue-100 text-blue-800' }}">
                                                {{ $baris['sumber'] === 'PIC' ? 'Tanggung jawab tetap' : 'Pinjaman' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            @if($kembali)
                                                <span class="rounded border border-green-400 bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">
                                                    Sudah kembali
                                                </span>
                                                @if($baris['tgl_kembali'])
                                                    <div class="mt-0.5 text-xs text-gray-500">
                                                        {{ \Carbon\Carbon::parse($baris['tgl_kembali'])->format('d/m/y') }}
                                                    </div>
                                                @endif
                                            @else
                                                <span class="rounded border border-amber-400 bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
                                                    Masih dipegang
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2">{{ $baris['kondisi'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Daftar ini dibekukan saat BAST disimpan. Yang berstatus <strong>Sudah kembali</strong> hanya
                        dicantumkan sebagai keterangan — tidak diproses ulang saat dokumen tuntas.
                    </p>
                @endif
            </form>
        @endif
    </div>

    {{-- Menerbitkan BAST tidak boleh terkirim dua kali: tiap kiriman membuat satu
         berita acara baru dengan nomor sendiri untuk karyawan yang sama. --}}
    <x-app.kirim-sekali form="bastForm" />
</x-app-layout>
