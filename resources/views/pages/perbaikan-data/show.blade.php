@section('title', 'Detail Perbaikan Data | BE INVENTORY')
<x-app-layout>
    <x-app.secondary-header :variant="$attributes['headerVariant'] ?? ''">
        <div class="flex"></div>
        <div class="flex items-center space-x-3">
            <div class="p-1 flex items-center justify-end gap-x-2">
                <a href="{{ route('perbaikan-data.index') }}"
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

        <div class="bg-white border rounded-lg p-6 shadow mb-6">
            <h6 class="text-xl font-bold text-gray-800 mb-4">{{ $perbaikanData->kode_pengajuan }}</h6>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                <div class="flex"><dt class="w-40 text-gray-500">Jenis</dt><dd class="text-gray-800">{{ $perbaikanData->jenis ?: '-' }}</dd></div>
                <div class="flex"><dt class="w-40 text-gray-500">Pengaju</dt><dd class="text-gray-800">{{ $perbaikanData->pengaju ?: '-' }}</dd></div>
                <div class="flex"><dt class="w-40 text-gray-500">Tgl Pengajuan</dt><dd class="text-gray-800">{{ optional($perbaikanData->tgl_pengajuan)->format('d/m/Y H:i') ?? '-' }}</dd></div>
                <div class="flex"><dt class="w-40 text-gray-500">Status</dt><dd class="text-gray-800 font-medium">{{ $perbaikanData->status }}</dd></div>
                <div class="flex sm:col-span-2"><dt class="w-40 text-gray-500">Catatan</dt><dd class="text-gray-800">{{ $perbaikanData->catatan ?: '-' }}</dd></div>
            </dl>

            @if ($perbaikanData->dibatalkan_pada)
                <p class="mt-4 text-sm text-red-700 font-medium">
                    Pengajuan ini dibatalkan pada {{ $perbaikanData->dibatalkan_pada }}.
                </p>
            @endif
        </div>

        {{-- Penunjukan pelaksananya. Ditampilkan di sini supaya pertanyaan
             "siapa yang mengerjakan ini" terjawab di halaman yang sama dengan
             tombol eksekusinya. --}}
        <div class="bg-white border rounded-lg p-6 shadow mb-6">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <h6 class="text-lg font-bold text-gray-800">Penunjukan Pelaksana</h6>

                @if ($perbaikanData->penunjukan)
                    @can('lihat-penunjukan-perbaikan-data')
                        <a href="{{ route('penunjukan-perbaikan-data.show', $perbaikanData->penunjukan->id) }}"
                            class="text-sm text-indigo-600 hover:underline">Buka surat penunjukan</a>
                    @endcan
                @elseif (!$perbaikanData->dibatalkan_pada && !in_array($perbaikanData->status, ['Ditolak', 'Dibatalkan'], true))
                    @can('tambah-penunjukan-perbaikan-data')
                        <a href="{{ route('penunjukan-perbaikan-data.create', ['perbaikan_data_id' => $perbaikanData->id]) }}"
                            class="rounded-md bg-slate-700 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-600">
                            Terbitkan Penunjukan
                        </a>
                    @endcan
                @endif
            </div>

            @if ($perbaikanData->penunjukan)
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                    <div class="flex"><dt class="w-40 text-gray-500">Nomor Surat</dt><dd class="text-gray-800 font-medium">{{ $perbaikanData->penunjukan->nomorSuratCetak() }}</dd></div>
                    <div class="flex"><dt class="w-40 text-gray-500">Pelaksana</dt><dd class="text-gray-800">{{ optional($perbaikanData->penunjukan->pelaksana)->name ?? '-' }}</dd></div>
                    <div class="flex"><dt class="w-40 text-gray-500">Tgl Penunjukan</dt><dd class="text-gray-800">{{ optional($perbaikanData->penunjukan->tgl_penunjukan)->format('d/m/Y') ?? '-' }}</dd></div>
                    <div class="flex"><dt class="w-40 text-gray-500">Status Pelaksanaan</dt><dd class="text-gray-800 font-medium">{{ $perbaikanData->penunjukan->status }}</dd></div>
                </dl>
            @else
                <p class="text-sm text-gray-500">Belum ada surat penunjukan pelaksana untuk pengajuan ini.</p>
            @endif
        </div>

        <div class="bg-white border rounded-lg p-6 shadow mb-6">
            <div class="flex items-center justify-between mb-4">
                <h6 class="text-lg font-bold text-gray-800">Perubahan yang Diminta</h6>

                @php
                    $adaMenunggu = $perbaikanData->target->where('status', '!=', 'dicatat')->isNotEmpty();
                @endphp

                {{--
                    Tombol ini MENCATAT, tidak mengubah data. Perubahan datanya
                    dikerjakan tim software langsung di database; yang terjadi di
                    sini menulis jejaknya ke Audit Perubahan Data dan menutup
                    tiketnya. Teksnya harus jujur soal itu — modul yang ada demi
                    kejujuran catatan tidak boleh berbohong di tombolnya sendiri.

                    Muncul kalau ketiganya benar: pemegang permission, tiketnya
                    sudah disetujui (termasuk yang statusnya sudah disetel
                    Selesai manual), dan masih ada baris yang belum dicatat.
                    Menampilkannya di luar keadaan itu hanya memancing klik yang
                    pasti ditolak controller.

                    Status boleh disetel Selesai manual, jadi tiket bisa
                    ditandai selesai sebelum barisnya sempat dicatat. Tombolnya
                    sengaja tetap ada di keadaan itu — kalau hilang, baris-baris
                    tadi tidak akan pernah punya jejak di halaman audit.
                --}}
                @can('eksekusi-perbaikan-data')
                    @if ($perbaikanData->bolehDieksekusi() && $adaMenunggu)
                        @php
                            $akanDicatat = $perbaikanData->target->where('status', '!=', 'dicatat');
                        @endphp

                        {{-- Modal, bukan confirm() bawaan browser. Yang harus
                             dipahami sebelum menekan ada tiga lapis: apa yang
                             dicatat, apa yang TIDAK ikut berubah, dan apa yang
                             terjadi kalau sebagian gagal. Kotak abu-abu bawaan
                             browser memampatkan ketiganya jadi satu paragraf
                             tanpa penekanan, dan lapis kedua — penyangkalannya —
                             yang paling sering terlewat dibaca. --}}
                        <div x-data="{ buka: false }">
                            <button type="button" @click="buka = true"
                                class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.5 3.75V16.5L12 14.25 7.5 16.5V3.75m9 0H18A2.25 2.25 0 0 1 20.25 6v12A2.25 2.25 0 0 1 18 20.25H6A2.25 2.25 0 0 1 3.75 18V6A2.25 2.25 0 0 1 6 3.75h1.5m9 0h-9" />
                                </svg>
                                Catat &amp; Tutup Tiket
                            </button>

                            <div x-show="buka" x-cloak
                                class="fixed inset-0 z-50 overflow-y-auto"
                                @keydown.escape.window="buka = false"
                                role="dialog" aria-modal="true" aria-labelledby="judul-catat">

                                <div class="flex min-h-full items-center justify-center p-4">
                                    {{-- Latar gelap sebagai lapisan sendiri: klik di
                                         sini menutup modal, klik di kotaknya tidak. --}}
                                    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"
                                        @click="buka = false"
                                        x-transition:enter="ease-out duration-200"
                                        x-transition:enter-start="opacity-0"
                                        x-transition:enter-end="opacity-100"
                                        x-transition:leave="ease-in duration-150"
                                        x-transition:leave-start="opacity-100"
                                        x-transition:leave-end="opacity-0"></div>

                                    <div class="relative w-full max-w-xl overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-black/5"
                                        x-transition:enter="ease-out duration-200"
                                        x-transition:enter-start="opacity-0 translate-y-2 sm:scale-95"
                                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                        x-transition:leave="ease-in duration-150"
                                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                        x-transition:leave-end="opacity-0 translate-y-2 sm:scale-95">

                                        {{-- Kepala --}}
                                        <div class="flex items-start gap-4 border-b border-gray-200 px-6 py-5">
                                            <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-indigo-50 ring-1 ring-inset ring-indigo-100">
                                                <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M16.5 3.75V16.5L12 14.25 7.5 16.5V3.75m9 0H18A2.25 2.25 0 0 1 20.25 6v12A2.25 2.25 0 0 1 18 20.25H6A2.25 2.25 0 0 1 3.75 18V6A2.25 2.25 0 0 1 6 3.75h1.5m9 0h-9" />
                                                </svg>
                                            </span>

                                            <div class="min-w-0 flex-1">
                                                <h3 id="judul-catat" class="text-base font-semibold leading-6 text-gray-900">
                                                    Catat ke Audit Perubahan Data
                                                </h3>
                                                <p class="mt-1 text-sm text-gray-500">
                                                    {{ $akanDicatat->count() }} perubahan dicatat, lalu tiket
                                                    {{ $perbaikanData->kode_pengajuan }} ditutup.
                                                </p>
                                            </div>

                                            <button type="button" @click="buka = false"
                                                class="-m-1.5 flex-none rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                                                aria-label="Tutup">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        {{-- Isi --}}
                                        <div class="max-h-[60vh] overflow-y-auto px-6 py-5">
                                            {{-- Daftar barisnya ditampilkan, bukan cuma
                                                 jumlahnya. Yang ditandatangani orang di
                                                 halaman audit nanti baris-baris inilah,
                                                 dan satu-satunya kesempatan memeriksanya
                                                 sebelum tercatat permanen ada di sini:
                                                 audit menolak update dan delete. --}}
                                            <ul class="divide-y divide-gray-100 rounded-lg ring-1 ring-inset ring-gray-200">
                                                @foreach ($akanDicatat as $baris)
                                                    <li class="px-4 py-3">
                                                        <div class="flex flex-wrap items-baseline gap-x-2">
                                                            <span class="text-sm font-medium text-gray-900">{{ $baris->labelModul() }}</span>
                                                            <span class="text-sm text-gray-500">&middot; {{ $baris->labelField() }}</span>
                                                            <span class="text-xs text-gray-400">#{{ $baris->modul_id }}</span>
                                                        </div>

                                                        <div class="mt-1.5 flex flex-wrap items-center gap-2 text-sm">
                                                            <span class="rounded bg-red-50 px-1.5 py-0.5 text-red-700 line-through break-all">
                                                                {{ $baris->nilai_lama ?? '(kosong)' }}
                                                            </span>
                                                            <svg class="h-4 w-4 flex-none text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                                            </svg>
                                                            <span class="rounded bg-green-50 px-1.5 py-0.5 font-medium text-green-700 break-all">
                                                                {{ $baris->nilai_baru ?? '(kosong)' }}
                                                            </span>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>

                                            {{-- Penyangkalannya berdiri sendiri. Ini
                                                 satu-satunya bagian yang kalau salah
                                                 dipahami membuat orang mengira datanya
                                                 sudah berubah padahal belum. --}}
                                            <div class="mt-4 flex gap-3 rounded-lg bg-amber-50 p-3 ring-1 ring-inset ring-amber-200">
                                                <svg class="h-5 w-5 flex-none text-amber-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a1 1 0 0 0 0 2v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2v-3a1 1 0 0 0-1-1H9Z" clip-rule="evenodd" />
                                                </svg>
                                                <p class="text-sm text-amber-800">
                                                    Data aslinya <strong class="font-semibold">tidak ikut berubah</strong>.
                                                    Perubahan datanya dikerjakan langsung di database oleh tim software —
                                                    yang terjadi di sini hanya pencatatannya.
                                                </p>
                                            </div>

                                            <p class="mt-3 text-xs leading-5 text-gray-500">
                                                Baris yang nilai lamanya sudah tidak cocok dengan database ditandai gagal
                                                beserta sebabnya dan bisa dicoba lagi. Baris yang sudah tercatat tidak
                                                ditulis ulang.
                                            </p>
                                        </div>

                                        {{-- Kaki --}}
                                        <div class="flex flex-col-reverse gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-end">
                                            <button type="button" @click="buka = false"
                                                class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                                Batal
                                            </button>

                                            <form method="POST" action="{{ route('perbaikan-data.eksekusi', $perbaikanData->id) }}"
                                                x-data="{ kirim: false }" @submit="kirim = true">
                                                @csrf
                                                {{-- Dikunci begitu ditekan: pencatatannya menulis
                                                     satu baris audit per perubahan, dan klik ganda
                                                     pada koneksi lambat mengirim dua permintaan.
                                                     Yang kedua ditolak karena barisnya sudah
                                                     'dicatat', tapi yang menekan melihat pesan
                                                     gagal untuk pekerjaan yang berhasil. --}}
                                                <button type="submit" :disabled="kirim"
                                                    class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto">
                                                    <svg x-show="kirim" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path>
                                                    </svg>
                                                    {{-- Dua span, bukan x-text: labelnya memuat "&",
                                                         dan menaruhnya di dalam atribut berarti
                                                         bergantung pada penguraian entitas HTML di
                                                         dalam string JavaScript. Benar, tapi tidak
                                                         terbaca sebagai benar. --}}
                                                    <span x-show="!kirim">Catat &amp; Tutup Tiket</span>
                                                    <span x-show="kirim" x-cloak>Mencatat...</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif (!$perbaikanData->bolehDieksekusi())
                        <span class="text-sm text-gray-500">Menunggu status Disetujui sebelum barisnya bisa dicatat.</span>
                    @endif
                @endcan
            </div>

            @forelse ($perbaikanData->target as $target)
                <div class="border-b last:border-b-0 py-3">
                    <div class="flex flex-wrap items-baseline gap-x-3">
                        <span class="font-medium text-gray-800">{{ $target->labelModul() }}</span>
                        <span class="text-xs text-gray-500">#{{ $target->modul_id }}</span>
                        <span class="text-sm text-gray-700">&middot; {{ $target->labelField() }}</span>
                        @if ($target->status === 'dicatat')
                            <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-0.5 rounded">Sudah dicatat</span>
                        @elseif ($target->status === 'gagal')
                            <span class="bg-red-100 text-red-800 text-xs font-medium px-2 py-0.5 rounded">Gagal</span>
                        @else
                            <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2 py-0.5 rounded">Menunggu</span>
                        @endif
                    </div>

                    {{-- Nilai ditampilkan mentah: memformat ulang angka bisa membuat
                         dua nilai yang berbeda tampil sama, dan justru salah ketik
                         titik atau nol yang paling sering dikoreksi di sini. --}}
                    <div class="mt-2 text-sm">
                        <div class="text-red-700 line-through break-all">{{ $target->nilai_lama ?? '(kosong)' }}</div>
                        <div class="text-green-700 font-medium break-all">{{ $target->nilai_baru ?? '(kosong)' }}</div>
                    </div>

                    @if ($target->alasan)
                        <p class="mt-1 text-xs text-gray-600">Alasan: {{ $target->alasan }}</p>
                    @endif

                    @if ($target->catatan)
                        <p class="mt-1 text-xs text-red-700">{{ $target->catatan }}</p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500">
                    Pengajuan ini tidak mencantumkan perubahan terstruktur — isinya hanya dokumen lampiran,
                    jadi tidak ada baris yang bisa dicatat per kolom.
                </p>
            @endforelse
        </div>

        <div class="bg-white border rounded-lg p-6 shadow">
            <h6 class="text-lg font-bold text-gray-800 mb-3">Berkas</h6>
            <ul class="text-sm list-disc list-inside text-indigo-600">
                @if ($perbaikanData->form_pengajuan)
                    <li><a href="{{ asset('storage/' . $perbaikanData->form_pengajuan) }}" target="_blank">Form pengajuan</a></li>
                @endif
                @foreach ($perbaikanData->lampiran as $lampiran)
                    <li><a href="{{ asset('storage/' . $lampiran->lampiran) }}" target="_blank">{{ basename($lampiran->lampiran) }}</a></li>
                @endforeach
                @if (!$perbaikanData->form_pengajuan && $perbaikanData->lampiran->isEmpty())
                    <li class="list-none text-gray-500">Tidak ada berkas.</li>
                @endif
            </ul>
        </div>
    </div>
</x-app-layout>
