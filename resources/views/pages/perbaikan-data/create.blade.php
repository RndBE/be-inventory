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
                                                <div id="barisPerubahan" class="space-y-3"></div>
                                                <button type="button" id="tambahBaris"
                                                    class="mt-3 rounded-md border border-indigo-600 px-3 py-1.5 text-sm font-semibold text-indigo-600 hover:bg-indigo-50">
                                                    + Tambah baris perubahan
                                                </button>
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
                                                            <div class="text-xs text-gray-600 mt-1">Alasan: {{ $target->alasan }}</div>
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
            const wadah = document.getElementById('barisPerubahan');
            const tersembunyi = document.getElementById('perubahanJson');
            const tombolTambah = document.getElementById('tambahBaris');
            const pesanJenis = document.getElementById('pesanJenis');

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
                baris.querySelector('[data-kode]').focus();
            }

            // Label tombol pemicu. Abu-abu selama belum ada yang dipilih, hitam
            // begitu terpilih — mengikuti bentuk pemilih Supplier di form Bahan.
            function setLabel(baris, teks) {
                const label = baris.querySelector('[data-terpilih]');

                label.textContent = teks || 'Pilih kode transaksi';
                label.classList.toggle('text-gray-400', ! teks);
                label.classList.toggle('text-gray-900', !! teks);
            }

            function gambarOpsi(baris, opsi, pesanKosong) {
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
                        kata ? 'Tidak ada kode yang cocok dengan "' + kata + '".' : 'Belum ada data pada jenis ini.'
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

                baris.innerHTML =
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
                                '<div data-panel class="hidden absolute z-50 mt-1 top-full w-full rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5">' +
                                    '<div class="p-2 border-b border-gray-200">' +
                                        '<input type="text" data-kode autocomplete="off" placeholder="Cari kode transaksi..." class="block w-full rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">' +
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
                        '<div class="sm:col-span-2">' +
                            '<label class="block text-xs text-gray-600">Nilai lama (dari database)</label>' +
                            '<input type="text" data-nilai-lama readonly class="block w-full rounded-md border-gray-300 bg-gray-100 py-1.5 text-sm ring-1 ring-inset ring-gray-300">' +
                        '</div>' +
                        '<div class="sm:col-span-2">' +
                            '<label class="block text-xs text-gray-600">Nilai baru</label>' +
                            '<input type="text" data-nilai-baru class="block w-full rounded-md border-gray-300 py-1.5 text-sm ring-1 ring-inset ring-gray-300">' +
                        '</div>' +
                        // Alasan per baris, bukan per tiket: satu pengajuan bisa
                        // mengoreksi beberapa kolom dengan sebab yang berbeda, dan
                        // inilah yang tersimpan di kolom alasan halaman audit.
                        '<div class="sm:col-span-2">' +
                            '<label class="block text-xs text-gray-600">Alasan <span class="text-red-600">*</span></label>' +
                            '<input type="text" data-alasan placeholder="mis. salah ketik nominal, seharusnya sesuai invoice" class="block w-full rounded-md border-gray-300 py-1.5 text-sm ring-1 ring-inset ring-gray-300">' +
                        '</div>' +
                    '</div>' +
                    '<div class="flex items-center justify-between mt-2">' +
                        '<span data-pesan class="text-xs mt-1 text-gray-500"></span>' +
                        '<button type="button" data-hapus class="text-xs text-red-600 hover:underline">Hapus baris</button>' +
                    '</div>';

                wadah.appendChild(baris);
                isiPilihanKolom(baris);
                return baris;
            }

            tombolTambah.addEventListener('click', function () {
                buatBaris();
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

                if (e.target.matches('[data-hapus]')) {
                    baris.remove();
                    sinkron();
                }
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
            });

            segarkanJenis();
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
