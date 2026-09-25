@section('title', 'Tambah Projek | BE INVENTORY')
<x-app-layout>
    @props(['variant' => ''])
    <x-app.secondary-header :variant="$attributes['headerVariant']">
        <div class="flex">
            {{-- <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
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
            </div> --}}
        </div>

        <!-- Header: Right side -->
        <div class="flex items-center space-x-3">
            <div class="p-1 flex items-center justify-end gap-x-2">
                <a href="{{ route('perbaikan-data.index') }}" type="button" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">Kembali</a>
                <button id="saveButton" type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">{{ $isEdit ? 'Update' : 'Simpan' }}</button>
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
                {{-- Right: Cart --}}
                <div class="w-full bg-white border rounded-lg p-6 shadow">
                    <form
                        action="{{ $isEdit ? route('perbaikan-data.update', $perbaikanData->id) : route('perbaikan-data.store') }}"
                        method="POST"
                        enctype="multipart/form-data"
                        id="perbaikanDataForm"
                    >
                        @csrf
                        @if($isEdit)
                            @method('PUT')
                        @endif

                        <div class="space-y-6">
                            <div class="border-b border-gray-900/10 pb-2 mb-2">
                                <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-1">

                                    {{-- Kode Pengajuan --}}
                                    <div class="flex items-center">
                                        <label for="kode_pengajuan" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Kode Pengajuan</label>
                                        <input type="text"
                                            id="kode_pengajuan"
                                            name="kode_pengajuan"
                                            value="{{ $perbaikanData->kode_pengajuan ?? 'PD - ' }}"
                                            {{ $isEdit ? 'readonly' : 'disabled' }}
                                            class="block rounded-md w-3/4 border-gray-300 bg-gray-100 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                    </div>

                                    {{-- Jenis Pengajuan --}}
                                    <div class="flex items-start">
                                        <label class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                                            Jenis Pengajuan <span class="text-red-600">*</span>
                                            <span class="block text-xs font-normal text-gray-500 mt-1">
                                                Jenis yang dicentang menentukan kode transaksi mana yang muncul di bawah.
                                            </span>
                                        </label>
                                        <div class="grid grid-cols-2 gap-2 w-3/4" id="daftarJenis">
                                            @php
                                                // Daftarnya dari config/perbaikan_data.php, bukan ditulis di sini.
                                                // Label yang sama dipakai kunci `jenis` tiap modul untuk menyaring
                                                // pilihan kode transaksi; kalau daftarnya ditulis dua kali,
                                                // penyaringannya putus tanpa error apa pun.
                                                $jenisTerpilih = old('jenis', $isEdit ? explode(', ', (string) $perbaikanData->jenis) : []);
                                            @endphp

                                            @foreach($daftarJenis as $jenis)
                                                <label class="inline-flex items-center">
                                                    <input type="checkbox"
                                                        name="jenis[]"
                                                        value="{{ $jenis }}"
                                                        data-jenis
                                                        {{ in_array($jenis, (array) $jenisTerpilih) ? 'checked' : '' }}
                                                        class="rounded text-indigo-600 focus:ring-indigo-500">
                                                    <span class="ml-2 text-gray-700 text-sm">{{ $jenis }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{--
                                        Baris perubahan yang diminta.

                                        Muncul di form tambah maupun edit. Yang dibatasi bukan
                                        tampilannya melainkan boleh-tidaknya diubah: begitu
                                        tiketnya masuk tahap persetujuan atau ada barisnya yang
                                        sudah diterapkan, daftarnya jadi baca saja. Approver
                                        menyetujui satu daftar tertentu, dan kalau daftarnya masih
                                        bisa berubah sesudah itu, yang dicatat bisa bukan yang
                                        disetujui. Menyembunyikannya sama sekali juga tidak benar
                                        — pengaju kehilangan cara memastikan apa yang diajukannya.
                                    --}}
                                    <div class="flex items-start border-t border-gray-900/10 pt-4 mt-2">
                                        <label class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                                            Data yang Ingin Diubah
                                            <span class="block text-xs font-normal text-gray-500 mt-1">
                                                @if($targetBisaDiubah)
                                                    Centang jenis pengajuannya dulu di atas, lalu kode transaksinya
                                                    muncul di sini. Kosongkan kalau pengajuannya hanya berupa dokumen.
                                                @else
                                                    Baca saja — tiket ini sudah masuk tahap persetujuan.
                                                @endif
                                            </span>
                                        </label>
                                        <div class="w-3/4">
                                            @if($targetBisaDiubah)
                                                {{--
                                                    Daftarnya menggulir sendiri, bukan ikut halaman: dua puluh
                                                    baris perubahan membuat tombol Simpan dan checkbox jenis di
                                                    atasnya terdorong jauh dari pandangan, padahal keduanya
                                                    dipakai bergantian dengan daftar ini.

                                                    Konsekuensinya panel dropdown kodenya tidak bisa lagi
                                                    `absolute` — di dalam kotak ber-overflow panelnya akan
                                                    terpotong. Panelnya dipindah ke `fixed` dan koordinatnya
                                                    dihitung di posisikanPanel().
                                                --}}
                                                <div id="barisPerubahan" class="space-y-2 max-h-[60vh] overflow-y-auto"></div>
                                                <div class="mt-3 flex items-center gap-3">
                                                    <button type="button" id="tambahBaris"
                                                        class="rounded-md border border-indigo-600 px-3 py-1.5 text-sm font-semibold text-indigo-600 hover:bg-indigo-50">
                                                        + Tambah baris perubahan
                                                    </button>
                                                    {{-- Jumlahnya disebut di luar daftar karena barisnya bisa
                                                         terlipat: tanpa angka ini, daftar berisi lima ringkasan
                                                         dan satu baris setengah terisi tidak bisa dibedakan dari
                                                         daftar berisi enam baris siap kirim. --}}
                                                    <span id="jumlahBaris" class="text-xs text-gray-500"></span>
                                                </div>
                                                <input type="hidden" name="perubahan" id="perubahanJson">
                                                <p id="pesanJenis" class="text-xs text-amber-700 mt-2"></p>
                                                <p class="text-xs text-gray-500 mt-2">
                                                    Nilai lama diambil langsung dari database dan dibekukan saat pengajuan disimpan.
                                                    Kalau nilainya berubah sebelum disetujui, eksekusinya akan ditolak — bukan menimpa.
                                                </p>
                                            @else
                                                {{-- Baca saja. Tidak ada input `perubahan` yang dikirim,
                                                     jadi update() tidak menulis ulang barisnya. --}}
                                                @forelse($perbaikanData->target as $target)
                                                    <div class="rounded-md border border-gray-200 bg-gray-50 p-3 text-sm mb-2">
                                                        <div class="flex flex-wrap items-baseline gap-x-2">
                                                            <span class="font-medium text-gray-800">{{ $target->labelModul() }}</span>
                                                            <span class="text-xs text-gray-500">#{{ $target->modul_id }}</span>
                                                            <span class="text-gray-700">&middot; {{ $target->labelField() }}</span>
                                                        </div>
                                                        <div class="text-red-700 line-through break-all">{{ $target->nilai_lama ?? '(kosong)' }}</div>
                                                        <div class="text-green-700 font-medium break-all">{{ $target->nilai_baru ?? '(kosong)' }}</div>
                                                        @if($target->alasan)
                                                            <div class="text-xs text-gray-600 mt-1 whitespace-pre-line">Alasan: {{ $target->alasan }}</div>
                                                        @endif
                                                    </div>
                                                @empty
                                                    <p class="text-sm text-gray-500">
                                                        Pengajuan ini tidak mencantumkan perubahan terstruktur — isinya hanya dokumen lampiran.
                                                    </p>
                                                @endforelse
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Upload Form Pengajuan --}}
                                    <div class="flex items-center mb-3">
                                        <label for="form_pengajuan" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                                            Upload Form Pengajuan
                                            @if(!$isEdit)
                                                <span class="text-red-600">*</span>
                                                <span class="block text-xs font-normal text-gray-500 mt-1">
                                                    Wajib. Pakai berkas dari tombol "Download Format Surat" di halaman daftar,
                                                    unggah dalam bentuk PDF.
                                                </span>
                                            @endif
                                        </label>
                                        <div class="w-3/4">
                                            <input
                                                type="file"
                                                id="form_pengajuan"
                                                name="form_pengajuan"
                                                accept=".pdf"
                                                {{ $isEdit ? '' : 'required' }}
                                                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-md cursor-pointer
                                                    bg-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-600
                                                    file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                                    file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700
                                                    hover:file:bg-indigo-100"
                                            >
                                            <ul id="lampiran-list" class="mt-2 text-sm text-gray-600 list-disc list-inside"></ul>
                                            <div class="mt-3">
                                                @if($isEdit && $perbaikanData->form_pengajuan)
                                                    <a href="{{ asset('storage/' . $perbaikanData->form_pengajuan) }}"
                                                    target="_blank"
                                                    class="ml-3 text-sm text-indigo-600 hover:underline">Lihat Form Pengajuan</a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Lampiran --}}
                                    <div class="flex items-start">
                                        <label for="lampiran" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4 mt-1">
                                            Lampiran
                                        </label>
                                        <div class="w-3/4">
                                            <input
                                                type="file"
                                                name="lampiran[]"
                                                id="lampiran"
                                                multiple
                                                accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                                                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-md cursor-pointer
                                                    bg-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-600
                                                    file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                                    file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700
                                                    hover:file:bg-indigo-100"
                                                onchange="previewLampiran(this)"
                                            >

                                            <ul id="lampiran-list" class="mt-2 text-sm text-gray-600 list-disc list-inside"></ul>

                                            {{-- tampilkan file lama --}}
                                            @if($isEdit && $perbaikanData->lampiran->count() > 0)
                                                <div class="mt-3">
                                                    <span class="text-xs text-gray-500">Lampiran:</span>
                                                    <ul class="list-disc list-inside text-sm text-indigo-600">
                                                        @foreach($perbaikanData->lampiran as $lampiran)
                                                            <li>
                                                                <a href="{{ asset('storage/'.$lampiran->lampiran) }}" target="_blank">{{ basename($lampiran->lampiran) }}</a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @if($targetBisaDiubah)
    <script>
        // Repeater baris perubahan. Ditulis tanpa Livewire karena form ini
        // mengunggah berkas lewat POST biasa; mencampur keduanya berarti dua
        // mekanisme kirim dalam satu form. Datanya dititipkan sebagai JSON di
        // satu input tersembunyi, cara yang sama dipakai keranjang bahan di
        // modul transaksi lain.
        //
        // Tiga langkah per baris, urutannya sengaja: centang jenis di atas,
        // pilih kolom yang salah, lalu cari kode transaksinya. Kode datang
        // terakhir karena tabel yang dicari ditentukan oleh kolom yang dipilih.
        (function () {
            // Modul mana yang muncul untuk setiap jenis pengajuan. Tanpa peta ini
            // checkbox Jenis Pengajuan dan pilihan kolom jadi dua daftar yang
            // tidak saling kenal.
            const modulPerJenis = @json($modulPerJenis);
            // Modul dan kolom digabung jadi satu pilihan, mis.
            // "Harga Lot Bahan Masuk — Harga per Unit". Kata "modul" tidak lagi
            // muncul di layar; modulnya disimpulkan dari kolom yang dipilih dan
            // tetap tersimpan terpisah di database.
            const daftarKolom = @json($daftarKolom);
            // Baris yang sudah tersimpan, dipakai form edit untuk mengisi ulang
            // repeaternya. Kosong saat menambah pengajuan baru.
            const barisAwal = @json($barisAwal);
            const urlOpsi = "{{ route('perbaikan-data.opsi-record') }}";
            const urlBahan = "{{ route('perbaikan-data.opsi-bahan') }}";
            // Bentuk baku nilai Tambah Bahan dari server: "<nama> [<kode>] × <qty> <satuan>".
            const polaTambah = /^(.*) \[([^\[\]]+)\] × (\d+(?:\.\d+)?)(?: (.*))?$/;
            let nomorDaftarBahan = 0;
            const wadah = document.getElementById('barisPerubahan');
            const tersembunyi = document.getElementById('perubahanJson');
            const tombolTambah = document.getElementById('tambahBaris');
            const pesanJenis = document.getElementById('pesanJenis');
            const jumlahBaris = document.getElementById('jumlahBaris');

            function jenisTercentang() {
                return Array.prototype.slice
                    .call(document.querySelectorAll('[data-jenis]:checked'))
                    .map(function (kotak) { return kotak.value; });
            }

            // Gabungan modul dari semua jenis yang dicentang, bukan irisan:
            // mencentang dua jenis berarti pengajuannya menyentuh dua-duanya.
            function modulDiizinkan() {
                const hasil = [];

                jenisTercentang().forEach(function (jenis) {
                    (modulPerJenis[jenis] || []).forEach(function (slug) {
                        if (hasil.indexOf(slug) === -1) {
                            hasil.push(slug);
                        }
                    });
                });

                return hasil;
            }

            function kolomDiizinkan() {
                const izin = modulDiizinkan();

                return daftarKolom.filter(function (kolom) {
                    return izin.indexOf(kolom.modul) !== -1;
                });
            }

            function cariKolom(nilai) {
                for (let i = 0; i < daftarKolom.length; i++) {
                    if (daftarKolom[i].nilai === nilai) {
                        return daftarKolom[i];
                    }
                }

                return null;
            }

            function kolomBaris(baris) {
                return cariKolom(baris.querySelector('[data-kolom]').value);
            }

            // Baris "Tambah Bahan": bahan yang lupa diajukan belum punya baris,
            // jadi kotak nilai baru diganti pemilih bahan + jumlah. Kotak
            // [data-nilai-baru] tetap dipakai, tersembunyi, berisi JSON kiriman.
            function modeTambah(baris) {
                const kolom = kolomBaris(baris);
                return !! kolom && kolom.tipe === 'tambah_bahan';
            }

            function aturMode(baris) {
                const mode = modeTambah(baris) ? 'tambah' : 'biasa';

                if (baris.dataset.mode === mode) return;

                // Berganti mode membuang isian mode sebelumnya: JSON bahan tidak
                // boleh terkirim sebagai nilai kolom biasa, dan sebaliknya.
                if (baris.dataset.mode) {
                    baris.querySelector('[data-nilai-baru]').value = '';
                    baris.querySelector('[data-bahan-cari]').value = '';
                    baris.querySelector('[data-bahan-qty]').value = '';
                    baris.dataset.tampilBaru = '';
                    tampilkanPesan(baris, '');
                }

                baris.dataset.mode = mode;
                baris.querySelector('[data-wadah-baru]').classList.toggle('hidden', mode === 'tambah');
                baris.querySelector('[data-tambah]').classList.toggle('hidden', mode !== 'tambah');
                baris.querySelector('[data-nilai-lama]').placeholder = mode === 'tambah' ? '— (belum ada, bahan baru)' : '';
            }

            function susunTambah(baris) {
                const teks = baris.querySelector('[data-bahan-cari]').value.trim();
                const qty = baris.querySelector('[data-bahan-qty]').value.trim().replace(',', '.');
                const bahan = (baris._bahan || {})[teks];
                const qtyBenar = qty !== '' && ! isNaN(qty) && parseFloat(qty) > 0;
                const kotak = baris.querySelector('[data-nilai-baru]');

                if (bahan && qtyBenar) {
                    kotak.value = JSON.stringify(bahan.id ? { bahan_id: bahan.id, qty: qty } : { kode_bahan: bahan.kode, qty: qty });
                    baris.dataset.tampilBaru = teks + ' × ' + qty + (bahan.satuan ? ' ' + bahan.satuan : '');
                    tampilkanPesan(baris, '');
                    return;
                }

                kotak.value = '';
                baris.dataset.tampilBaru = '';
                tampilkanPesan(baris, teks && ! bahan ? 'Pilih salah satu bahan dari daftar yang muncul.' : '');
            }

            async function muatBahan(baris) {
                const teks = baris.querySelector('[data-bahan-cari]').value.trim();

                // Teks yang sudah persis salah satu pilihan tidak perlu dicari lagi.
                if ((baris._bahan || {})[teks]) return;

                try {
                    const jawab = await fetch(urlBahan + '?q=' + encodeURIComponent(teks), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });

                    if (! jawab.ok) return;

                    const data = await jawab.json();
                    const daftar = baris.querySelector('[data-bahan-daftar]');
                    daftar.innerHTML = '';
                    // Hasil lama tidak dibuang: begitu satu pilihan diklik, isi
                    // kotaknya berubah jadi label itu dan pencarian berikutnya
                    // tidak lagi mengembalikannya.
                    baris._bahan = baris._bahan || {};

                    (data.opsi || []).forEach(function (item) {
                        baris._bahan[item.label] = { id: item.id, satuan: item.satuan };
                        const opsi = document.createElement('option');
                        opsi.value = item.label;
                        daftar.appendChild(opsi);
                    });

                    susunTambah(baris);
                    sinkron();
                } catch (e) {
                    tampilkanPesan(baris, 'Daftar bahan tidak bisa dimuat.', 'error');
                }
            }

            const tundaBahan = new WeakMap();

            function cariBahanTertunda(baris) {
                clearTimeout(tundaBahan.get(baris));
                tundaBahan.set(baris, setTimeout(function () {
                    muatBahan(baris);
                }, 300));
            }

            // Kolom di luar daftar izin tetap diberi opsi, dengan penanda.
            // Dipakai dua kali: saat mengisi ulang baris lama di form edit, dan
            // saat centang jenis bergeser setelah barisnya diisi.
            function tambahOpsiLuar(pilih, nilai) {
                const kolom = cariKolom(nilai);
                const opsi = document.createElement('option');
                opsi.value = nilai;
                opsi.textContent = (kolom ? kolom.label : nilai) + ' (jenisnya belum dicentang)';
                pilih.appendChild(opsi);
            }

            function isiPilihanKolom(baris) {
                const pilih = baris.querySelector('[data-kolom]');
                const sebelumnya = pilih.value;
                const tabelRecord = baris.dataset.tabelRecord || '';

                // Sebelum kode transaksinya dipilih, belum diketahui record mana
                // yang dikoreksi — dan satu kode bisa menunjuk baris induknya
                // maupun baris detail di bawahnya, yang kolomnya berbeda. Jadi
                // dropdown ini menunggu, bukan menawarkan gabungan semuanya lalu
                // membatalkan pilihannya sendiri begitu recordnya masuk.
                if (!tabelRecord) {
                    pilih.innerHTML = '<option value="">Pilih kode transaksinya dulu</option>';
                    pilih.disabled = true;
                    return;
                }

                // Disaring per TABEL, bukan per modul. Tiga modul Pembelian
                // Bahan menunjuk baris yang sama persis — dipisah hanya untuk
                // mengelompokkan kolom biaya impor supaya labelnya terbaca.
                // Menyaring per modul akan menyembunyikan dua pertiga kolom
                // yang sebenarnya milik baris yang sama.
                const izin = kolomDiizinkan().filter(function (kolom) {
                    return kolom.tabel === tabelRecord;
                });

                pilih.disabled = false;
                pilih.innerHTML = '<option value="">Pilih kolom yang dikoreksi</option>';

                izin.forEach(function (kolom) {
                    const opsi = document.createElement('option');
                    opsi.value = kolom.nilai;
                    opsi.textContent = kolom.label;
                    pilih.appendChild(opsi);
                });

                const masihBoleh = izin.some(function (kolom) {
                    return kolom.nilai === sebelumnya;
                });

                if (masihBoleh) {
                    pilih.value = sebelumnya;
                    return;
                }

                // Baris yang sudah menunjuk kolom di luar daftar izin
                // dipertahankan apa adanya. Membuangnya diam-diam akan
                // menghilangkan baris perubahan yang sudah diisi — atau yang
                // sudah tersimpan — hanya karena satu centang jenis dilepas.
                if (sebelumnya !== '') {
                    tambahOpsiLuar(pilih, sebelumnya);
                    pilih.value = sebelumnya;
                    return;
                }

                // Cuma satu kolom yang cocok: tidak ada yang perlu dipilih.
                if (izin.length === 1) {
                    pilih.value = izin[0].nilai;
                }
            }

            // Hidden input disegarkan setiap ada perubahan, bukan saat submit:
            // tombol Simpan memanggil requestSubmit() secara programatik, dan
            // penyusunan JSON-nya tidak boleh bergantung pada event submit.
            function sinkron() {
                const hasil = [];

                wadah.querySelectorAll('[data-baris]').forEach(function (baris) {
                    aturMode(baris);

                    const kolom = kolomBaris(baris);
                    const modulId = baris.querySelector('[data-modul-id]').value;
                    const nilaiBaru = baris.querySelector('[data-nilai-baru]').value;
                    const alasan = baris.querySelector('[data-alasan]').value;

                    // Bentuk kirimannya tidak berubah: modul dan field tetap
                    // dua kunci terpisah, jadi controller dan servicenya tidak
                    // perlu tahu bahwa keduanya dipilih lewat satu dropdown.
                    if (kolom && modulId) {
                        hasil.push({
                            modul: kolom.modul,
                            modul_id: modulId,
                            field: kolom.field,
                            nilai_baru: nilaiBaru,
                            alasan: alasan
                        });
                    }
                });

                tersembunyi.value = hasil.length ? JSON.stringify(hasil) : '';

                wadah.querySelectorAll('[data-baris]').forEach(perbaruiRingkas);
                jumlahBaris.textContent = ringkasanJumlah(hasil.length);
            }

            // Kalimat di sebelah tombol tambah. Baris yang belum lengkap disebut
            // terpisah karena hanya baris lengkap yang ikut terkirim, dan tanpa
            // penyebutan itu barisnya yang tertinggal setengah isi tidak terlihat
            // sebagai masalah sampai pengajuannya ditolak server.
            function ringkasanJumlah(siap) {
                const semua = wadah.querySelectorAll('[data-baris]').length;

                if (!semua) {
                    return '';
                }

                const kurang = semua - siap;

                return kurang
                    ? siap + ' baris siap, ' + kurang + ' baris belum lengkap'
                    : siap + ' baris perubahan';
            }

            // Lengkap = cukup untuk dikirim DAN cukup untuk diringkas dalam satu
            // baris. Alasan ikut disyaratkan karena servernya mewajibkannya;
            // melipat baris tanpa alasan akan menyembunyikan kotak yang justru
            // membuat pengajuannya ditolak.
            function lengkap(baris) {
                return !! kolomBaris(baris)
                    && !! baris.querySelector('[data-modul-id]').value
                    && baris.querySelector('[data-nilai-baru]').value.trim() !== ''
                    && baris.querySelector('[data-alasan]').value.trim() !== '';
            }

            function nomorUlang() {
                let i = 0;

                wadah.querySelectorAll('[data-baris]').forEach(function (baris) {
                    i++;

                    baris.querySelectorAll('[data-nomor]').forEach(function (kotak) {
                        kotak.textContent = 'Perubahan ' + i;
                    });
                });
            }

            function ringkasanTeks(baris) {
                const kolom = kolomBaris(baris);
                const tambah = modeTambah(baris);
                const lama = tambah ? '' : baris.querySelector('[data-nilai-lama]').value;
                const baru = tambah ? (baris.dataset.tampilBaru || '') : baris.querySelector('[data-nilai-baru]').value;

                return baris.querySelector('[data-terpilih]').textContent.trim()
                    + '  ·  ' + (kolom ? kolom.label : '')
                    + '  ·  ' + (lama === '' ? '(kosong)' : lama)
                    + '  →  ' + (baru === '' ? '(kosong)' : baru);
            }

            function perbaruiRingkas(baris) {
                const bisa = lengkap(baris);

                baris.querySelector('[data-lipat]').classList.toggle('hidden', ! bisa);

                if (bisa) {
                    baris.querySelectorAll('[data-bentang]')[0].textContent = ringkasanTeks(baris);
                } else if (baris.dataset.terlipat === '1') {
                    // Baris terlipat yang isinya jadi tidak lengkap lagi — mis.
                    // centang jenisnya dilepas sehingga kolomnya tidak lagi
                    // terbaca — dibentangkan kembali, bukan dibiarkan terlipat
                    // menampilkan ringkasan yang sudah tidak berlaku.
                    bentang(baris, false);
                }
            }

            function lipat(baris) {
                if (! lengkap(baris)) return;

                tutupPanel(baris);
                // Bukan `dataset.lipat`: itu menulis atribut data-lipat ke
                // barisnya sendiri, sehingga closest('[data-lipat]') di handler
                // klik cocok untuk klik di mana pun dalam baris — dan baris
                // yang dibuka lewat "Ubah" langsung terlipat lagi begitu salah
                // satu kotaknya diklik.
                baris.dataset.terlipat = '1';
                baris.querySelector('[data-ringkas]').classList.remove('hidden');
                baris.querySelector('[data-isi]').classList.add('hidden');
                baris.classList.add('hover:bg-gray-100');
            }

            // `fokus` false dipakai pembuka yang bukan perbuatan langsung pengaju
            // — baris baru yang belum ada apa-apanya, dan baris yang terpaksa
            // dibentangkan karena centang jenisnya bergeser. Keduanya tidak boleh
            // merampas fokus dari tempat pengaju sedang bekerja.
            function bentang(baris, fokus) {
                // Satu baris terbuka sekaligus. Dua baris terbuka berarti
                // panjangnya kembali seperti sebelum dilipat, dan pengaju
                // kehilangan gambaran daftarnya secara keseluruhan.
                wadah.querySelectorAll('[data-baris]').forEach(function (lain) {
                    if (lain !== baris) {
                        lipat(lain);
                    }
                });

                baris.dataset.terlipat = '';
                baris.querySelector('[data-isi]').classList.remove('hidden');
                baris.querySelector('[data-ringkas]').classList.add('hidden');
                baris.classList.remove('hover:bg-gray-100');

                // 'nearest': yang digulir cukup kotak daftarnya, seminimal
                // mungkin. 'center' akan menggeser halaman juga, sehingga
                // membuka satu baris memindahkan seluruh form di layar.
                baris.scrollIntoView({ block: 'nearest' });

                if (fokus === false) return;

                // Tombol "Ubah" ikut tersembunyi bersama ringkasannya, jadi
                // fokusnya lepas ke <body> dan handler focusout di bawah
                // menyimpulkan pengaju sudah meninggalkan barisnya — lalu
                // melipatnya kembali seketika. Dari layar, tombolnya tampak
                // tidak berfungsi. Fokus dipindahkan ke dalam baris supaya
                // pemeriksaan itu lolos, sekaligus menaruh kursor di kotak yang
                // paling sering jadi alasan baris ini dibuka lagi.
                const sasaran = baris.querySelector(modeTambah(baris) ? '[data-bahan-cari]' : '[data-nilai-baru]');

                if (sasaran) {
                    sasaran.focus();
                    sasaran.select();
                }
            }

            function lipatSemuaLengkap() {
                wadah.querySelectorAll('[data-baris]').forEach(lipat);
            }

            function tampilkanPesan(baris, teks, jenis) {
                const kotak = baris.querySelector('[data-pesan]');
                kotak.textContent = teks || '';
                kotak.className = 'text-xs mt-1 ' + (jenis === 'error' ? 'text-red-600' : 'text-gray-500');
            }

            function tampilkanNilaiLama(baris) {
                const kolom = kolomBaris(baris);
                const nilai = JSON.parse(baris.dataset.nilai || '{}');
                const kotak = baris.querySelector('[data-nilai-lama]');

                kotak.value = kolom && nilai[kolom.field] !== undefined && nilai[kolom.field] !== null
                    ? nilai[kolom.field]
                    : '';
            }

            function kosongkanRecord(baris) {
                baris.querySelector('[data-modul-id]').value = '';
                baris.querySelector('[data-kode]').value = '';
                baris.querySelector('[data-nilai-lama]').value = '';
                baris.dataset.nilai = '{}';
                // Modul asal recordnya ikut dilupakan, dan dropdown kolomnya
                // terkunci lagi: tanpa record, kolom mana yang masuk akal
                // ditawarkan belum bisa diputuskan.
                baris.dataset.tabelRecord = '';
                isiPilihanKolom(baris);
                setLabel(baris, '');
                tutupPanel(baris);
            }

            // Dropdown kode dengan pencarian. Pencariannya dikerjakan server dan
            // hasilnya dibatasi, bukan seluruh tabel dikirim ke browser lalu
            // difilter di sini: tabel bahan masuk sendiri puluhan ribu baris.
            //
            // Ditulis tangan, bukan dengan select2 yang sudah ada di proyek ini.
            // select2 butuh diinisialisasi ulang setiap kali barisnya ditambah,
            // dan setiap baris punya sumber data berbeda tergantung kolomnya —
            // satu <select> statis per baris tidak cukup.
            function tutupPanel(baris) {
                baris.querySelector('[data-panel]').classList.add('hidden');
            }

            function bukaPanel(baris) {
                // Panel baris lain ikut ditutup: dua panel terbuka sekaligus
                // saling menimpa karena keduanya melayang di atas baris
                // berikutnya.
                wadah.querySelectorAll('[data-baris]').forEach(function (lain) {
                    if (lain !== baris) {
                        tutupPanel(lain);
                    }
                });

                baris.querySelector('[data-panel]').classList.remove('hidden');
                posisikanPanel(baris);
                baris.querySelector('[data-kode]').focus();
            }

            // Panelnya melayang relatif viewport, jadi koordinatnya harus
            // dihitung sendiri — tidak ada lagi `top-full` yang mengurusnya.
            // Dibuka ke atas kalau ruang di bawah comboboxnya tidak cukup:
            // baris terakhir di daftar yang menggulir hampir selalu berada di
            // tepi bawah kotaknya.
            function posisikanPanel(baris) {
                const panel = baris.querySelector('[data-panel]');

                if (panel.classList.contains('hidden')) return;

                const kotak = baris.querySelector('[data-combo]').getBoundingClientRect();
                const tinggi = panel.offsetHeight;
                const ruangBawah = window.innerHeight - kotak.bottom;
                const keAtas = ruangBawah < tinggi + 8 && kotak.top > tinggi + 8;

                panel.style.width = kotak.width + 'px';
                panel.style.left = kotak.left + 'px';
                panel.style.top = (keAtas ? kotak.top - tinggi - 4 : kotak.bottom + 4) + 'px';
            }

            function posisikanPanelTerbuka() {
                wadah.querySelectorAll('[data-baris]').forEach(posisikanPanel);
            }

            // Capture, dan di window: event scroll tidak menggelembung, jadi
            // gulir di dalam #barisPerubahan maupun gulir halaman hanya
            // tertangkap semuanya lewat fase capture di window.
            window.addEventListener('scroll', posisikanPanelTerbuka, true);
            window.addEventListener('resize', posisikanPanelTerbuka);

            // Label tombol pemicu. Abu-abu selama belum ada yang dipilih, hitam
            // begitu terpilih — mengikuti bentuk pemilih Supplier di form Bahan.
            function setLabel(baris, teks) {
                const label = baris.querySelector('[data-terpilih]');

                label.textContent = teks || 'Pilih kode transaksi';
                label.classList.toggle('text-gray-400', ! teks);
                label.classList.toggle('text-gray-900', !! teks);
            }

            function gambarOpsi(baris, opsi, pesanKosong, terpotong) {
                const daftar = baris.querySelector('[data-opsi]');
                daftar.innerHTML = '';

                if (!opsi.length) {
                    const kosong = document.createElement('li');
                    kosong.className = 'relative cursor-default select-none py-2 pl-3 pr-9 text-gray-500';
                    kosong.textContent = pesanKosong || 'Tidak ada yang cocok.';
                    daftar.appendChild(kosong);
                } else {
                    opsi.forEach(function (item) {
                        const li = document.createElement('li');
                        li.className = 'relative cursor-pointer select-none py-2 pl-3 pr-9 text-gray-900 hover:bg-indigo-600 hover:text-white';
                        li.setAttribute('data-opsi-item', '');
                        li.dataset.id = item.modul_id;
                        // Modul ikut dibawa: satu kode transaksi bisa menunjuk
                        // baris induk maupun baris detailnya, dan yang menentukan
                        // kolom apa saja yang ditawarkan berikutnya adalah modul
                        // dari pilihan ini, bukan jenis pengajuannya.
                        li.dataset.modul = item.modul || '';
                        li.dataset.tabel = item.tabel || '';
                        li.dataset.kode = item.kode || '';
                        li.dataset.label = item.label;
                        li.dataset.nilai = JSON.stringify(item.nilai || {});
                        li.textContent = item.label;
                        daftar.appendChild(li);
                    });
                }

                // Daftar yang dipotong harus mengatakannya. Tanpa baris ini
                // barisnya yang tidak terkirim tidak bisa dibedakan dari barisnya
                // yang memang tidak ada, dan pengaju berhenti mencari.
                if (terpotong) {
                    const sisa = document.createElement('li');
                    sisa.className = 'relative cursor-default select-none border-t py-2 pl-3 pr-9 text-xs text-gray-500';
                    sisa.textContent = 'Masih ada yang belum ditampilkan. Persempit pencarian, mis. ketik nama bahannya.';
                    daftar.appendChild(sisa);
                }

                // Tinggi panelnya baru diketahui setelah daftarnya terisi, dan
                // keputusan buka-ke-atas bergantung pada tinggi itu.
                posisikanPanel(baris);
            }

            async function muatOpsi(baris) {
                const jenis = jenisTercentang();
                const kata = baris.querySelector('[data-kode]').value.trim();
                const daftar = baris.querySelector('[data-opsi]');

                if (!jenis.length) {
                    gambarOpsi(baris, [], 'Centang jenis pengajuannya dulu di atas.');
                    return;
                }

                daftar.innerHTML = '<li class="relative select-none py-2 pl-3 pr-9 text-gray-500">Mencari ...</li>';

                // Dicari di semua modul milik jenis yang dicentang sekaligus.
                // Pada tahap ini kolomnya memang belum dipilih — itu
                // pertanyaan berikutnya, bukan syarat pencarian ini.
                const kunci = jenis.map(function (j) {
                    return 'jenis[]=' + encodeURIComponent(j);
                }).join('&');

                try {
                    const jawab = await fetch(
                        urlOpsi + '?' + kunci + '&q=' + encodeURIComponent(kata),
                        { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }
                    );
                    const data = await jawab.json();

                    if (!jawab.ok) {
                        gambarOpsi(baris, [], data.pesan || 'Daftar kode tidak bisa dimuat.');
                        return;
                    }

                    gambarOpsi(
                        baris,
                        data.opsi || [],
                        kata ? 'Tidak ada kode yang cocok dengan "' + kata + '".' : 'Belum ada data pada jenis ini.',
                        !! data.terpotong
                    );
                } catch (e) {
                    gambarOpsi(baris, [], 'Gagal menghubungi server.');
                }
            }

            function pilihOpsi(baris, item) {
                baris.querySelector('[data-modul-id]').value = item.dataset.id;
                baris.dataset.nilai = item.dataset.nilai || '{}';
                // Modulnya ditentukan oleh record yang dipilih, bukan sebaliknya.
                // Dari sinilah daftar kolom di sebelahnya disusun.
                baris.dataset.tabelRecord = item.dataset.tabel || '';

                isiPilihanKolom(baris);
                setLabel(baris, item.dataset.label);
                // Kotak carinya dikosongkan supaya pembukaan berikutnya mulai
                // dari daftar penuh, bukan dari sisa kata pencarian sebelumnya.
                baris.querySelector('[data-kode]').value = '';

                tampilkanPesan(baris, '');
                tampilkanNilaiLama(baris);
                tutupPanel(baris);
                sinkron();
            }

            // Satu timer per baris. Timer bersama akan membuat baris yang
            // diketik belakangan membatalkan pencarian baris sebelumnya.
            const tundaCari = new WeakMap();

            function cariTertunda(baris) {
                clearTimeout(tundaCari.get(baris));
                tundaCari.set(baris, setTimeout(function () {
                    muatOpsi(baris);
                }, 300));
            }

            // Dipanggil setiap centang jenis berubah: pilihan kolom tiap baris
            // disusun ulang, dan tombol tambah dimatikan kalau jenis yang
            // dicentang tidak punya kolom yang boleh dikoreksi sama sekali.
            function segarkanJenis() {
                const izin = kolomDiizinkan();
                const adaJenis = jenisTercentang().length > 0;

                tombolTambah.disabled = izin.length === 0;
                tombolTambah.classList.toggle('opacity-50', izin.length === 0);
                tombolTambah.classList.toggle('cursor-not-allowed', izin.length === 0);

                if (!adaJenis) {
                    pesanJenis.textContent = 'Centang jenis pengajuannya dulu di atas.';
                } else if (izin.length === 0) {
                    pesanJenis.textContent = 'Jenis yang dicentang tidak punya kolom yang boleh dikoreksi lewat sistem. '
                        + 'Pengajuannya tetap bisa disimpan sebagai dokumen: cukup form pengajuan dan lampiran.';
                } else {
                    pesanJenis.textContent = '';
                }

                wadah.querySelectorAll('[data-baris]').forEach(isiPilihanKolom);

                sinkron();
            }

            function buatBaris() {
                const baris = document.createElement('div');
                baris.className = 'border border-gray-200 rounded-md p-3 bg-gray-50';
                baris.setAttribute('data-baris', '');
                baris.dataset.nilai = '{}';
                // id datalist harus unik per baris.
                const nomorBahan = ++nomorDaftarBahan;

                baris.innerHTML =
                    // Ringkasan satu baris, dipakai saat barisnya terlipat.
                    // Isinya sama dengan yang akan tersimpan — kode, kolom, dan
                    // pergeseran nilainya — supaya melipat tidak berarti
                    // kehilangan cara memeriksa apa yang diajukan.
                    '<div data-ringkas class="hidden">' +
                        '<div class="flex items-center gap-2">' +
                            '<span class="shrink-0 text-xs font-semibold text-gray-500" data-nomor></span>' +
                            '<button type="button" data-bentang class="min-w-0 flex-1 truncate text-left text-sm text-gray-800 hover:text-indigo-700"></button>' +
                            '<button type="button" data-bentang class="shrink-0 text-xs text-indigo-600 hover:underline">Ubah</button>' +
                            '<button type="button" data-hapus class="shrink-0 text-xs text-red-600 hover:underline">Hapus</button>' +
                        '</div>' +
                    '</div>' +
                    '<div data-isi>' +
                    '<div class="mb-2 flex items-center justify-between">' +
                        '<span class="text-xs font-semibold text-gray-600" data-nomor></span>' +
                        // Hanya muncul kalau barisnya sudah lengkap: melipat baris
                        // yang masih bolong akan menyembunyikan justru kotak yang
                        // belum diisi.
                        '<button type="button" data-lipat class="hidden text-xs text-indigo-600 hover:underline">Tutup baris</button>' +
                    '</div>' +
                    '<div class="grid grid-cols-1 sm:grid-cols-2 gap-2">' +
                        '<div>' +
                            '<label class="block text-xs text-gray-600">Kode transaksi <span class="text-red-600">*</span></label>' +
                            // Bentuknya mengikuti pemilih Supplier di form Bahan:
                            // tombol yang tampak seperti select, panel terbuka di
                            // atas baris berikutnya, dan kotak pencariannya ada DI
                            // DALAM panel. Yang menampilkan pilihan adalah label
                            // tombolnya, bukan kotak carinya — jadi teks pencarian
                            // yang ditinggalkan tanpa memilih apa pun tidak bisa
                            // lagi terbaca seolah sudah terpilih.
                            '<div class="relative" data-combo>' +
                                '<button type="button" data-pemicu class="relative w-full cursor-pointer rounded-md bg-white border-0 py-1.5 pl-3 pr-10 text-left text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-600">' +
                                    '<span class="block truncate text-gray-400" data-terpilih>Pilih kode transaksi</span>' +
                                    '<span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">' +
                                        '<svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">' +
                                            '<path fill-rule="evenodd" d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z" clip-rule="evenodd" />' +
                                        '</svg>' +
                                    '</span>' +
                                '</button>' +
                                '<div data-panel class="hidden fixed z-50 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5">' +
                                    '<div class="p-2 border-b border-gray-200">' +
                                        '<input type="text" data-kode autocomplete="off" placeholder="Cari kode transaksi atau nama bahan..." class="block w-full rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">' +
                                    '</div>' +
                                    '<ul data-opsi class="py-1 text-sm" style="max-height: 15rem; overflow-y: auto;"></ul>' +
                                '</div>' +
                            '</div>' +
                            '<input type="hidden" data-modul-id>' +
                        '</div>' +
                        '<div>' +
                            '<label class="block text-xs text-gray-600">Kolom yang dikoreksi <span class="text-red-600">*</span></label>' +
                            // Terkunci sampai kode transaksinya dipilih. Sebelum
                            // itu belum diketahui record mana yang dikoreksi,
                            // jadi belum bisa diputuskan kolom mana yang masuk
                            // akal ditawarkan.
                            '<select data-kolom disabled class="block w-full rounded-md border-gray-300 py-1.5 text-sm ring-1 ring-inset ring-gray-300 disabled:bg-gray-100 disabled:text-gray-500"></select>' +
                        '</div>' +
                        // Disandingkan, bukan ditumpuk: keduanya dibaca sebagai
                        // satu pasangan "dari — ke", dan menumpuknya membuat tiap
                        // baris dua kali lebih tinggi tanpa menambah informasi.
                        '<div>' +
                            '<label class="block text-xs text-gray-600">Nilai lama (dari database)</label>' +
                            '<input type="text" data-nilai-lama readonly class="block w-full rounded-md border-gray-300 bg-gray-100 py-1.5 text-sm ring-1 ring-inset ring-gray-300">' +
                        '</div>' +
                        '<div data-wadah-baru>' +
                            '<label class="block text-xs text-gray-600">Nilai baru</label>' +
                            '<input type="text" data-nilai-baru class="block w-full rounded-md border-gray-300 py-1.5 text-sm ring-1 ring-inset ring-gray-300">' +
                        '</div>' +
                        // Pengganti "Nilai baru" untuk kolom Tambah Bahan.
                        '<div data-tambah class="hidden grid grid-cols-3 gap-2">' +
                            '<div class="col-span-2">' +
                                '<label class="block text-xs text-gray-600">Bahan yang ditambahkan <span class="text-red-600">*</span></label>' +
                                '<input type="text" data-bahan-cari list="daftarBahan' + nomorBahan + '" autocomplete="off" placeholder="Ketik nama atau kode bahan..." class="block w-full rounded-md border-gray-300 py-1.5 text-sm ring-1 ring-inset ring-gray-300">' +
                                '<datalist id="daftarBahan' + nomorBahan + '" data-bahan-daftar></datalist>' +
                            '</div>' +
                            '<div>' +
                                '<label class="block text-xs text-gray-600">Jumlah <span class="text-red-600">*</span></label>' +
                                '<input type="text" inputmode="decimal" data-bahan-qty placeholder="mis. 5" class="block w-full rounded-md border-gray-300 py-1.5 text-sm ring-1 ring-inset ring-gray-300">' +
                            '</div>' +
                        '</div>' +
                        // Alasan per baris, bukan per tiket: satu pengajuan bisa
                        // mengoreksi beberapa kolom dengan sebab yang berbeda, dan
                        // inilah yang tersimpan di kolom alasan halaman audit.
                        // Textarea, bukan input satu baris. Alasan yang berguna
                        // sering butuh beberapa kalimat — apa yang salah, kenapa
                        // salah, dan apa yang sudah dikerjakan di luar koreksi
                        // ini. Sel alasan di halaman audit sudah
                        // `whitespace-pre-line`, jadi barisnya tetap terbaca
                        // seperti yang ditulis.
                        '<div class="sm:col-span-2">' +
                            '<label class="block text-xs text-gray-600">Alasan <span class="text-red-600">*</span></label>' +
                            '<textarea data-alasan rows="3" placeholder="mis. salah ketik nominal, seharusnya sesuai invoice" class="block w-full rounded-md border-gray-300 py-1.5 text-sm ring-1 ring-inset ring-gray-300"></textarea>' +
                        '</div>' +
                    '</div>' +
                    '<div class="flex items-center justify-between mt-2">' +
                        '<span data-pesan class="text-xs mt-1 text-gray-500"></span>' +
                        '<button type="button" data-hapus class="text-xs text-red-600 hover:underline">Hapus baris</button>' +
                    '</div>' +
                    '</div>';

                wadah.appendChild(baris);
                isiPilihanKolom(baris);
                nomorUlang();
                return baris;
            }

            tombolTambah.addEventListener('click', function () {
                // Baris yang sudah lengkap dilipat dulu: kalau tidak, baris baru
                // muncul di bawah tumpukan baris terbuka dan pengaju harus
                // menggulir mencarinya.
                lipatSemuaLengkap();
                // Tanpa fokus: baris baru masih kosong, dan langkah pertamanya
                // memilih kode transaksi — bukan mengisi nilai baru.
                bentang(buatBaris(), false);
            });

            document.querySelectorAll('[data-jenis]').forEach(function (kotak) {
                kotak.addEventListener('change', segarkanJenis);
            });

            // mousedown, bukan click: klik pada pilihan didahului blur pada
            // inputnya, dan kalau daftarnya sudah ditutup oleh blur, click-nya
            // mendarat di tempat yang sudah tidak ada.
            wadah.addEventListener('mousedown', function (e) {
                const item = e.target.closest('[data-opsi-item]');
                if (!item) return;

                e.preventDefault();
                pilihOpsi(item.closest('[data-baris]'), item);
            });

            wadah.addEventListener('click', function (e) {
                const baris = e.target.closest('[data-baris]');
                if (!baris) return;

                if (e.target.closest('[data-pemicu]')) {
                    const panel = baris.querySelector('[data-panel]');

                    if (panel.classList.contains('hidden')) {
                        bukaPanel(baris);
                        muatOpsi(baris);
                    } else {
                        tutupPanel(baris);
                    }

                    return;
                }

                if (e.target.closest('[data-hapus]')) {
                    baris.remove();
                    nomorUlang();
                    sinkron();
                    return;
                }

                if (e.target.closest('[data-bentang]')) {
                    bentang(baris);
                    return;
                }

                if (e.target.closest('[data-lipat]')) {
                    lipat(baris);
                }
            });

            // Baris dilipat begitu fokusnya benar-benar keluar dari baris itu.
            // Diperiksa setelah fokusnya pindah, bukan pada saat blur: saat blur
            // berjalan, tujuan fokusnya belum tentu sudah ditetapkan, dan baris
            // yang fokusnya cuma bergeser antar kotaknya sendiri akan ikut
            // terlipat di tengah pengisian.
            wadah.addEventListener('focusout', function (e) {
                const baris = e.target.closest('[data-baris]');
                if (!baris) return;

                setTimeout(function () {
                    if (! baris.isConnected) return;
                    if (baris.contains(document.activeElement)) return;

                    lipat(baris);
                }, 0);
            });

            // Klik di luar comboboxnya menutup panelnya. Dipasang di document
            // karena barisnya dibuat belakangan, jadi tidak ada satu elemen
            // pembungkus yang sudah ada sejak awal untuk semua panel.
            document.addEventListener('click', function (e) {
                wadah.querySelectorAll('[data-baris]').forEach(function (baris) {
                    if (! baris.querySelector('[data-combo]').contains(e.target)) {
                        tutupPanel(baris);
                    }
                });
            });

            wadah.addEventListener('change', function (e) {
                const baris = e.target.closest('[data-baris]');
                if (!baris) return;

                if (e.target.matches('[data-kolom]')) {
                    // Pilihan kolomnya sudah dibatasi ke modul record yang
                    // terpilih, jadi berganti kolom tidak pernah lagi berarti
                    // berganti tabel — recordnya tetap berlaku. Nilai lama
                    // untuk seluruh kolom modul ini sudah ikut terkirim saat
                    // kodenya dipilih, jadi cukup ditampilkan.
                    tampilkanNilaiLama(baris);
                }

                sinkron();
            });

            wadah.addEventListener('input', function (e) {
                const baris = e.target.closest('[data-baris]');

                if (baris && e.target.matches('[data-kode]')) {
                    // Kotak ini sekarang murni pencarian — yang menampilkan
                    // pilihan adalah label tombolnya. Jadi mengetik di sini tidak
                    // lagi perlu membatalkan record yang sudah terpilih; pilihan
                    // baru hanya berlaku kalau salah satu hasilnya benar-benar
                    // diklik.
                    cariTertunda(baris);
                }

                if (baris && e.target.matches('[data-bahan-cari]')) {
                    cariBahanTertunda(baris);
                }

                if (baris && e.target.matches('[data-bahan-cari], [data-bahan-qty]')) {
                    susunTambah(baris);
                }

                sinkron();
            });

            // Isi ulang baris yang sudah tersimpan (form edit).
            barisAwal.forEach(function (awal) {
                const baris = buatBaris();

                // Modul recordnya diketahui dari baris tersimpan, jadi disetel
                // lebih dulu: daftar kolom disusun dari situ, dan tanpa ini
                // dropdown-nya terkunci dengan pesan "pilih kode transaksinya
                // dulu" padahal kodenya justru sudah ada.
                baris.dataset.tabelRecord = awal.tabel || '';
                isiPilihanKolom(baris);

                const pilihKolom = baris.querySelector('[data-kolom]');
                const nilaiKolom = awal.modul + '::' + awal.field;

                // Kolomnya mungkin tidak ada di daftar izin kalau centang jenisnya
                // sudah berubah sejak pengajuan dibuat. Opsinya ditambahkan supaya
                // baris lamanya tetap terlihat apa adanya, bukan hilang.
                if (!pilihKolom.querySelector('option[value="' + nilaiKolom + '"]')) {
                    tambahOpsiLuar(pilihKolom, nilaiKolom);
                }

                pilihKolom.value = nilaiKolom;

                baris.querySelector('[data-modul-id]').value = awal.modul_id;
                setLabel(baris, awal.label);
                baris.querySelector('[data-nilai-lama]').value = awal.nilai_lama === null ? '' : awal.nilai_lama;
                baris.querySelector('[data-nilai-baru]').value = awal.nilai_baru === null ? '' : awal.nilai_baru;
                baris.querySelector('[data-alasan]').value = awal.alasan === null ? '' : awal.alasan;

                const nilai = {};
                nilai[awal.field] = awal.nilai_lama;
                baris.dataset.nilai = JSON.stringify(nilai);

                // Baris Tambah Bahan tersimpan dalam bentuk baku; pecah lagi ke
                // kotak bahan dan jumlah. Server menerima bentuk baku itu apa
                // adanya kalau barisnya tidak disentuh.
                const cocok = modeTambah(baris) && awal.nilai_baru ? polaTambah.exec(awal.nilai_baru) : null;

                if (cocok) {
                    const label = cocok[1] + ' [' + cocok[2] + ']';
                    baris._bahan = {};
                    baris._bahan[label] = { id: null, kode: cocok[2], satuan: cocok[4] || null };
                    baris.querySelector('[data-bahan-cari]').value = label;
                    baris.querySelector('[data-bahan-qty]').value = cocok[3];
                    baris.dataset.tampilBaru = awal.nilai_baru;
                }
            });

            segarkanJenis();
            nomorUlang();
            // Form edit dibuka dalam keadaan terlipat semua. Barisnya sudah pernah
            // diisi, jadi yang dibutuhkan pertama adalah melihat daftarnya utuh —
            // bukan kotak isian baris pertama.
            lipatSemuaLengkap();
        })();
    </script>
    @endif

    <script>
        function previewLampiran(input) {
            const list = document.getElementById('lampiran-list');
            list.innerHTML = '';

            if (input.files.length > 0) {
                for (const file of input.files) {
                    const li = document.createElement('li');
                    li.textContent = file.name;
                    list.appendChild(li);
                }
            } else {
                list.innerHTML = '<li>Tidak ada file dipilih</li>';
            }
        }
    </script>
    <script>
        document.getElementById('saveButton').addEventListener('click', function() {
            const form = document.getElementById('perbaikanDataForm');

            // requestSubmit(), bukan submit(): submit() melewati seluruh
            // validasi HTML5, sehingga input `required` — form pengajuan yang
            // sekarang wajib — lolos ke server dan baru ditolak di sana. Yang
            // dilihat pengaju hanya halaman yang memuat ulang tanpa petunjuk
            // kotak mana yang belum diisi.
            if (form.requestSubmit) {
                form.requestSubmit();
            } else {
                form.submit();
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
