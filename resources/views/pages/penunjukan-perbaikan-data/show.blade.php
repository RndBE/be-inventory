@section('title', 'Detail Penunjukan Perbaikan Data | BE INVENTORY')
<x-app-layout>
    <x-app.secondary-header :variant="$attributes['headerVariant'] ?? ''">
        <div class="flex"></div>
        <div class="flex items-center space-x-3">
            <div class="p-1 flex items-center justify-end gap-x-2">
                {{-- Unduhan berkas, bukan halaman: target="_blank" dilepas
                     supaya tab kosong tidak tertinggal setiap kali diunduh. --}}
                <a href="{{ route('penunjukan-perbaikan-data.pdf', $penunjukan->id) }}"
                    class="rounded-md bg-slate-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-600">
                    Unduh Surat (Word)
                </a>
                {{-- Muncul pada status apa pun. Unggahan surat bertanda tangan
                     lewat form yang sama, dan kertas sering baru kembali dari
                     meja tanda tangan setelah pelaksanaannya diisi — kalau
                     tombolnya hilang di titik itu, berkasnya tidak akan pernah
                     bisa masuk. --}}
                @can('edit-penunjukan-perbaikan-data')
                    <a href="{{ route('penunjukan-perbaikan-data.edit', $penunjukan->id) }}"
                        class="rounded-md bg-yellow-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500">
                        Ubah Surat
                    </a>
                @endcan
                <a href="{{ route('perbaikan-data.index', ['tab' => 'penunjukan']) }}"
                    class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Kembali</a>
            </div>
        </div>
    </x-app.secondary-header>

    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        @if (session('success'))
            <div class="p-4 mb-4 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50">
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50">
                @foreach ($errors->all() as $error)
                    <span class="font-medium">{{ $error }}</span><br>
                @endforeach
            </div>
        @endif

        {{-- Identitas surat --}}
        <div class="bg-white border rounded-lg p-6 shadow mb-6">
            <div class="flex flex-wrap items-baseline justify-between gap-2 mb-4">
                <div>
                    <h6 class="text-xl font-bold text-gray-800">{{ $penunjukan->nomorSuratCetak() }}</h6>
                    <p class="text-xs text-gray-400">Kode internal: {{ $penunjukan->kode_penunjukan }}</p>
                </div>
                <span class="text-sm text-gray-500">Status: <strong class="text-gray-800">{{ $penunjukan->status }}</strong></span>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                <div class="flex">
                    <dt class="w-40 text-gray-500">Pelaksana Ditunjuk</dt>
                    <dd class="text-gray-800 font-medium">{{ optional($penunjukan->pelaksana)->name ?? '-' }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Ditunjuk Oleh</dt>
                    <dd class="text-gray-800">{{ optional($penunjukan->penunjuk)->name ?? '-' }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Tgl Penunjukan</dt>
                    <dd class="text-gray-800">{{ optional($penunjukan->tgl_penunjukan)->format('d/m/Y') ?? '-' }}</dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Form Penunjukan</dt>
                    <dd class="text-gray-800">
                        @if($penunjukan->form_penunjukan)
                            <a href="{{ asset('storage/' . $penunjukan->form_penunjukan) }}" target="_blank"
                                class="text-indigo-600 hover:underline">Lihat berkas</a>
                        @else
                            <span class="text-amber-700">Belum diunggah</span>
                        @endif
                    </dd>
                </div>
                <div class="flex">
                    <dt class="w-40 text-gray-500">Tim Pemohon</dt>
                    <dd class="text-gray-800">{{ $penunjukan->timPemohon() }}</dd>
                </div>
                <div class="flex sm:col-span-2">
                    <dt class="w-40 text-gray-500">Pokok Perubahan</dt>
                    <dd class="text-gray-800">{{ $penunjukan->perihal_perubahan ?: '(dirangkum otomatis di surat)' }}</dd>
                </div>
                <div class="flex sm:col-span-2">
                    <dt class="w-40 text-gray-500">Catatan Penunjukan</dt>
                    <dd class="text-gray-800">{{ $penunjukan->catatan_penunjukan ?: '-' }}</dd>
                </div>
            </dl>
        </div>

        {{-- Isi pengajuan yang ditunjuk --}}
        <div class="bg-white border rounded-lg p-6 shadow mb-6">
            <div class="flex items-center justify-between mb-3">
                <h6 class="text-lg font-bold text-gray-800">Pengajuan yang Ditunjuk</h6>
                @if($penunjukan->perbaikanData)
                    <a href="{{ route('perbaikan-data.show', $penunjukan->perbaikan_data_id) }}"
                        class="text-sm text-indigo-600 hover:underline">Buka halaman pengajuan</a>
                @endif
            </div>

            @if($penunjukan->perbaikanData)
                @include('pages.penunjukan-perbaikan-data.partials.ringkasan', [
                    'pengajuan' => $penunjukan->perbaikanData,
                ])

                {{-- Perubahan datanya tetap diterapkan lewat tombol Eksekusi di
                     halaman pengajuan, bukan dari sini. Satu-satunya jalan tulis
                     adalah PerbaikanDataService, jadi jejaknya selalu masuk Audit
                     Perubahan Data — surat ini yang menjelaskan siapa yang diberi
                     wewenang menekan tombolnya. --}}
                <p class="mt-3 text-xs text-gray-500">
                    Perubahan datanya diterapkan lewat tombol <strong>Eksekusi Perubahan</strong> di halaman
                    pengajuan, dan setiap penerapan tercatat di Audit Perubahan Data.
                </p>
            @else
                <p class="text-sm text-gray-500">Pengajuannya tidak ditemukan.</p>
            @endif
        </div>

        {{-- Bagian pelaksanaan --}}
        <div class="bg-white border rounded-lg p-6 shadow">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div>
                    <h6 class="text-lg font-bold text-gray-800 mb-1">Pelaksanaan</h6>
                    <p class="text-xs text-gray-500">
                        Diisi pelaksana yang ditunjuk di surat ini setelah pekerjaannya dilakukan.
                    </p>
                </div>

                {{-- Lembar konfirmasi saja, tanpa halaman instruksinya. Muncul
                     setelah pelaksananya menjawab — sebelum itu isinya kosong
                     dan tidak ada yang perlu ditandatangani.

                     Ditaruh di kartu ini, bukan di deretan tombol atas: yang
                     dicetak isi kartu inilah, dan yang mencetaknya orang yang
                     baru saja mengisinya. --}}
                @if($penunjukan->sudahDilaksanakan())
                    <a href="{{ route('penunjukan-perbaikan-data.konfirmasi', $penunjukan->id) }}"
                        class="inline-flex flex-none items-center gap-2 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                        </svg>
                        Unduh Lembar Konfirmasi
                    </a>
                @endif
            </div>

            @if($penunjukan->sudahDilaksanakan())
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm mb-4">
                    <div class="flex">
                        <dt class="w-40 text-gray-500">Tgl Pelaksanaan</dt>
                        <dd class="text-gray-800">{{ $penunjukan->tgl_pelaksanaan->format('d/m/Y') }}</dd>
                    </div>
                    <div class="flex">
                        <dt class="w-40 text-gray-500">Status</dt>
                        <dd class="text-gray-800 font-medium">{{ $penunjukan->status }}</dd>
                    </div>
                    {{-- Dibaca dari penunjukannya, bukan disimpan terpisah di sini:
                         satu orang yang sama tidak boleh punya dua jawaban. Diubah
                         lewat Ubah Surat. --}}
                    <div class="flex">
                        <dt class="w-40 text-gray-500">Petugas</dt>
                        <dd class="text-gray-800">{{ optional($penunjukan->pelaksana)->name ?? '-' }}</dd>
                    </div>
                    <div class="flex">
                        <dt class="w-40 text-gray-500">Diisi Oleh</dt>
                        <dd class="text-gray-800">{{ optional($penunjukan->pengisiPelaksanaan)->name ?? '-' }}</dd>
                    </div>
                    <div class="flex sm:col-span-2">
                        <dt class="w-40 text-gray-500">Keterangan</dt>
                        <dd class="text-gray-800">{{ $penunjukan->keterangan ?: '-' }}</dd>
                    </div>
                </dl>
            @endif

            @if($penunjukan->bolehDiisiOleh(auth()->user()))
                <form method="POST" action="{{ route('penunjukan-perbaikan-data.pelaksanaan', $penunjukan->id) }}"
                    class="border-t border-gray-900/10 pt-4 grid grid-cols-1 gap-y-4">
                    @csrf
                    @method('PUT')

                    <div class="flex items-center">
                        <label for="tgl_pelaksanaan" class="block text-sm font-medium text-gray-900 mr-2 w-1/4">
                            Tanggal Pelaksanaan <span class="text-red-600">*</span>
                        </label>
                        <div class="w-3/4">
                            <input type="date" name="tgl_pelaksanaan" id="tgl_pelaksanaan" required
                                value="{{ old('tgl_pelaksanaan', optional($penunjukan->tgl_pelaksanaan)->format('Y-m-d') ?? now()->setTimezone('Asia/Jakarta')->format('Y-m-d')) }}"
                                class="block w-full rounded-md border-gray-300 py-1.5 text-sm ring-1 ring-inset ring-gray-300">
                        </div>
                    </div>

                    {{-- Tampil supaya pengisinya tahu atas nama siapa lembar
                         konfirmasi ini akan tercetak, tapi tidak bisa diketik:
                         nama DAN nomor induknya sama-sama diambil dari baris
                         `users` orang ini. Kolom ketikan bebas dulu ada di sini
                         dan tidak membawa nomor induk apa pun, jadi blok tanda
                         tangannya tercetak tanpa ID. Tanpa `name`, jadi tidak
                         ikut terkirim dan tidak bisa dipalsukan lewat form. --}}
                    <div class="flex items-center">
                        <label for="petugas" class="block text-sm font-medium text-gray-900 mr-2 w-1/4">
                            Petugas
                            <span class="block text-xs font-normal text-gray-500">
                                Ikut pelaksana di surat. Ubah lewat Ubah Surat.
                            </span>
                        </label>
                        <div class="w-3/4">
                            <input type="text" id="petugas" disabled
                                value="{{ optional($penunjukan->pelaksana)->name ?? 'Belum ditunjuk' }}"
                                class="block w-full rounded-md border-gray-300 bg-gray-100 py-1.5 text-sm text-gray-600 ring-1 ring-inset ring-gray-300">
                        </div>
                    </div>

                    <div class="flex items-center">
                        <label for="status" class="block text-sm font-medium text-gray-900 mr-2 w-1/4">
                            Status <span class="text-red-600">*</span>
                        </label>
                        <div class="w-3/4">
                            @php
                                $statusTerpilih = old('status', $penunjukan->status);
                                // Daftarnya dari config, sumber yang sama dengan
                                // kotak centang di PDF-nya dan dengan validasi di
                                // controller. Menulis ulang di sini akan membuat
                                // pilihan di layar dan kotak di surat berbeda.
                                $pilihanStatus = \App\Models\PenunjukanPerbaikanData::pilihanStatus();
                            @endphp
                            <select name="status" id="status" required
                                class="block w-full rounded-md border-gray-300 py-1.5 text-sm ring-1 ring-inset ring-gray-300">
                                <option value="">Pilih status</option>
                                @foreach($pilihanStatus as $pilihan)
                                    <option value="{{ $pilihan }}" {{ $statusTerpilih === $pilihan ? 'selected' : '' }}>
                                        {{ $pilihan }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <label for="keterangan" class="block text-sm font-medium text-gray-900 mr-2 w-1/4">
                            Keterangan
                            <span class="block text-xs font-normal text-gray-500 mt-1">
                                Wajib kalau statusnya bukan "Selesai & Sesuai".
                            </span>
                        </label>
                        <div class="w-3/4">
                            <textarea name="keterangan" id="keterangan" rows="3" maxlength="2000"
                                class="block w-full rounded-md border-gray-300 py-1.5 text-sm ring-1 ring-inset ring-gray-300">{{ old('keterangan', $penunjukan->keterangan ?? '') }}</textarea>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            {{ $penunjukan->sudahDilaksanakan() ? 'Perbarui Pelaksanaan' : 'Simpan Pelaksanaan' }}
                        </button>
                    </div>
                </form>
            @elseif(! $penunjukan->sudahDilaksanakan())
                <p class="text-sm text-gray-500">
                    Bagian ini menunggu diisi oleh {{ optional($penunjukan->pelaksana)->name ?? 'pelaksana yang ditunjuk' }}.
                </p>
            @endif
        </div>
    </div>
</x-app-layout>
