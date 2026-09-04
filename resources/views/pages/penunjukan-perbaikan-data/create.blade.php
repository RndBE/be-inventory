@section('title', ($isEdit ? 'Ubah' : 'Terbitkan') . ' Penunjukan Perbaikan Data | BE INVENTORY')
<x-app-layout>
    @props(['variant' => ''])
    <x-app.secondary-header :variant="$attributes['headerVariant']">
        <div class="flex"></div>

        <div class="flex items-center space-x-3">
            <div class="p-1 flex items-center justify-end gap-x-2">
                <a href="{{ route('perbaikan-data.index', ['tab' => 'penunjukan']) }}"
                    class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Kembali</a>
                <button id="saveButton" type="submit"
                    class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    {{ $isEdit ? 'Update' : 'Simpan' }}
                </button>
            </div>
        </div>
    </x-app.secondary-header>

    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        @if (session('error'))
            <div id="errorAlert" class="p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div id="errorAlert" class="p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50">
                @foreach ($errors->all() as $error)
                    <span class="font-medium">{{ $error }}</span><br>
                @endforeach
            </div>
        @endif

        <div class="w-full bg-white border rounded-lg p-6 shadow">
            <form
                action="{{ $isEdit ? route('penunjukan-perbaikan-data.update', $penunjukan->id) : route('penunjukan-perbaikan-data.store') }}"
                method="POST"
                enctype="multipart/form-data"
                id="penunjukanForm"
            >
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <div class="grid grid-cols-1 gap-y-4">

                    {{--
                        Nomor surat. Dibuat sistem dan tidak bisa disunting: urutannya
                        per tahun, dan nomor yang sudah terbit sudah masuk arsip
                        Accounting. Nomornya juga tidak dihitung ulang kalau tanggal
                        suratnya digeser — dua dokumen bernomor berbeda untuk satu
                        penunjukan lebih buruk daripada nomor yang bulannya bergeser.
                    --}}
                    <div class="flex items-center">
                        <label class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Nomor Surat</label>
                        <input type="text"
                            value="{{ $penunjukan->nomor_surat ?? 'Dibuat otomatis saat disimpan' }}" disabled
                            class="block rounded-md w-3/4 border-gray-300 bg-gray-100 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 sm:text-sm">
                    </div>

                    {{--
                        Kode pengajuan. Saat mengubah surat, kodenya tidak bisa
                        dipindah ke pengajuan lain: surat yang subjeknya berganti
                        bukan lagi surat yang sama, dan bagian pelaksanaan yang
                        sudah diisi akan menempel pada pekerjaan yang tidak pernah
                        ditugaskan. Controller pun tidak menerima kolom ini di
                        update(), jadi tampilan ini tidak menyembunyikan apa pun.
                    --}}
                    <div class="flex items-start">
                        <label class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                            Kode Pengajuan <span class="text-red-600">*</span>
                            @if(!$isEdit)
                                <span class="block text-xs font-normal text-gray-500 mt-1">
                                    Ketik untuk mencari. Hanya pengajuan yang belum punya surat penunjukan yang muncul.
                                </span>
                            @endif
                        </label>
                        <div class="w-3/4">
                            @if($isEdit)
                                <input type="text" value="{{ optional($penunjukan->perbaikanData)->kode_pengajuan ?? '-' }}" disabled
                                    class="block w-full rounded-md border-gray-300 bg-gray-100 py-1.5 text-sm ring-1 ring-inset ring-gray-300">
                            @else
                                <div class="relative">
                                    <input type="text" id="cariPengajuan" autocomplete="off"
                                        value="{{ $pengajuanTerpilih->kode_pengajuan ?? '' }}"
                                        placeholder="Klik lalu ketik untuk mencari kode pengajuan"
                                        class="block w-full rounded-md border-gray-300 py-1.5 text-sm ring-1 ring-inset ring-gray-300">
                                    <ul id="opsiPengajuan"
                                        class="hidden absolute z-30 mt-1 max-h-56 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg"></ul>
                                </div>
                                {{-- Tanpa atribut `required`: input hidden
                                     dikecualikan dari validasi HTML5, jadi
                                     atributnya tidak berpengaruh apa-apa dan
                                     hanya menyesatkan yang membacanya. Yang
                                     memeriksanya tombol Simpan di bawah, dan
                                     server memeriksanya lagi. --}}
                                <input type="hidden" name="perbaikan_data_id" id="perbaikanDataId"
                                    value="{{ $pengajuanTerpilih->id ?? '' }}">
                                <p id="pesanPengajuan" class="text-xs text-gray-500 mt-1"></p>
                            @endif
                        </div>
                    </div>

                    {{--
                        Ringkasan pengajuan. Read-only dan tidak dikirim ke server:
                        isinya dibaca ulang lewat relasi setiap kali dibutuhkan,
                        termasuk saat mencetak PDF. Menyalinnya ke baris penunjukan
                        akan membuat surat yang tercetak berbeda dari pengajuan
                        aslinya begitu pengajuannya dikoreksi.
                    --}}
                    <div class="flex items-start border-t border-gray-900/10 pt-4">
                        <label class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                            Isi Pengajuan
                            <span class="block text-xs font-normal text-gray-500 mt-1">
                                Terisi sendiri dari kode di atas. Tidak bisa diubah dari sini.
                            </span>
                        </label>
                        <div class="w-3/4">
                            <div id="ringkasanPengajuan" class="rounded-md border border-gray-200 bg-gray-50 p-3 text-sm">
                                @if($pengajuanTerpilih)
                                    @include('pages.penunjukan-perbaikan-data.partials.ringkasan', [
                                        'pengajuan' => $pengajuanTerpilih,
                                    ])
                                @else
                                    <p class="text-xs text-gray-500">Belum ada pengajuan yang dipilih.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Pelaksana yang ditunjuk --}}
                    <div class="flex items-start border-t border-gray-900/10 pt-4">
                        <label for="ditunjuk_user_id" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                            Pelaksana Ditunjuk <span class="text-red-600">*</span>
                            <span class="block text-xs font-normal text-gray-500 mt-1">
                                Yang diberi wewenang mengubah datanya dan mengisi bagian pelaksanaan.
                            </span>
                        </label>
                        <div class="w-3/4">
                            @php
                                $pelaksanaTerpilih = old('ditunjuk_user_id', $penunjukan->ditunjuk_user_id ?? null);
                            @endphp
                            <select name="ditunjuk_user_id" id="ditunjuk_user_id" required
                                class="block w-full rounded-md border-gray-300 py-1.5 text-sm ring-1 ring-inset ring-gray-300">
                                <option value="">Pilih pelaksana</option>
                                @foreach($daftarPelaksana as $orang)
                                    {{-- Yang non-aktif hanya bisa muncul kalau dia
                                         pelaksana yang sekarang tertulis di surat ini.
                                         Ditandai supaya yang menyunting tahu, tanpa
                                         dipaksa menggantinya. --}}
                                    <option value="{{ $orang->id }}"
                                        {{ (int) $pelaksanaTerpilih === (int) $orang->id ? 'selected' : '' }}>
                                        {{ $orang->name }}@if(($orang->status ?? 'Aktif') !== 'Aktif') (non-aktif)@endif
                                    </option>
                                @endforeach
                            </select>
                            @if($daftarPelaksana->isEmpty())
                                <p class="text-xs text-red-600 mt-1">
                                    Belum ada kandidat pelaksana. Beri permission <strong>eksekusi-perbaikan-data</strong>
                                    ke role yang bersangkutan lewat halaman Role &amp; Permission dulu.
                                </p>
                            @endif
                        </div>
                    </div>

                    {{--
                        Dua kolom yang hanya dipakai surat cetaknya.

                        `tim_pemohon` mengisi kalimat "dari Tim Supply Chain".
                        Tidak bisa disimpulkan dari nama pengaju: kolom
                        `perbaikan_data.pengaju` berisi nama orang, dan tabel
                        users di server ini tidak punya kolom tim yang bisa
                        diandalkan.

                        `perihal_perubahan` mengisi "berkaitan dengan perubahan
                        harga barang dan biaya pengiriman". Kalau dikosongkan,
                        surat merangkumnya sendiri dari nama kolom yang
                        dikoreksi — benar secara teknis, tapi tidak terbaca
                        sebagai surat.
                    --}}
                    <div class="flex items-center">
                        <label for="tim_pemohon" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                            Tim Pemohon
                            <span class="block text-xs font-normal text-gray-500 mt-1">
                                Muncul di surat: "... dari <em>tim ini</em> mengenai permohonan perubahan data".
                            </span>
                        </label>
                        <div class="w-3/4">
                            <input type="text" name="tim_pemohon" id="tim_pemohon" maxlength="255"
                                placeholder="{{ config('surat_penunjukan.tim_pemohon_default') }}"
                                value="{{ old('tim_pemohon', $penunjukan->tim_pemohon ?? '') }}"
                                class="block w-full rounded-md border-gray-300 py-1.5 text-sm ring-1 ring-inset ring-gray-300">
                        </div>
                    </div>

                    <div class="flex items-start">
                        <label for="perihal_perubahan" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                            Pokok Perubahan
                            <span class="block text-xs font-normal text-gray-500 mt-1">
                                Muncul di surat: "... berkaitan dengan <em>pokok ini</em> untuk transaksi dengan kode ...".
                            </span>
                        </label>
                        <div class="w-3/4">
                            <textarea name="perihal_perubahan" id="perihal_perubahan" rows="2" maxlength="1000"
                                placeholder="mis. perubahan harga barang dan biaya pengiriman"
                                class="block w-full rounded-md border-gray-300 py-1.5 text-sm ring-1 ring-inset ring-gray-300">{{ old('perihal_perubahan', $penunjukan->perihal_perubahan ?? '') }}</textarea>
                        </div>
                    </div>

                    {{-- Tanggal penunjukan --}}
                    <div class="flex items-center">
                        <label for="tgl_penunjukan" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                            Tanggal Penunjukan <span class="text-red-600">*</span>
                        </label>
                        <div class="w-3/4">
                            <input type="date" name="tgl_penunjukan" id="tgl_penunjukan" required
                                value="{{ old('tgl_penunjukan', optional($penunjukan->tgl_penunjukan ?? null)->format('Y-m-d') ?? now()->setTimezone('Asia/Jakarta')->format('Y-m-d')) }}"
                                class="block w-full rounded-md border-gray-300 py-1.5 text-sm ring-1 ring-inset ring-gray-300">
                        </div>
                    </div>

                    {{-- Catatan penunjukan --}}
                    <div class="flex items-start">
                        <label for="catatan_penunjukan" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                            Catatan Penunjukan
                        </label>
                        <div class="w-3/4">
                            <textarea name="catatan_penunjukan" id="catatan_penunjukan" rows="3"
                                placeholder="mis. batas waktu pengerjaan, urutan yang harus diperbaiki lebih dulu"
                                class="block w-full rounded-md border-gray-300 py-1.5 text-sm ring-1 ring-inset ring-gray-300">{{ old('catatan_penunjukan', $penunjukan->catatan_penunjukan ?? '') }}</textarea>
                        </div>
                    </div>

                    {{-- Form penunjukan --}}
                    <div class="flex items-start border-t border-gray-900/10 pt-4">
                        <label for="form_penunjukan" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                            Upload Form Penunjukan
                            <span class="block text-xs font-normal text-gray-500 mt-1">
                                PDF. Cetak dulu surat ini lewat tombol Cetak di halaman detail, tanda tangani,
                                lalu unggah di sini.
                            </span>
                        </label>
                        <div class="w-3/4">
                            <input type="file" name="form_penunjukan" id="form_penunjukan" accept=".pdf"
                                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-md cursor-pointer
                                    bg-white file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0
                                    file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700
                                    hover:file:bg-indigo-100">
                            @if($isEdit && $penunjukan->form_penunjukan)
                                <a href="{{ asset('storage/' . $penunjukan->form_penunjukan) }}" target="_blank"
                                    class="mt-2 inline-block text-sm text-indigo-600 hover:underline">
                                    Lihat form penunjukan yang tersimpan
                                </a>
                            @endif
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    @if(!$isEdit)
    <script>
        // Dropdown pencarian kode pengajuan. Pencariannya di server dan hasilnya
        // dibatasi, sama seperti dropdown kode di form pengajuan: yang muncul
        // hanya pengajuan yang belum punya surat, jadi daftarnya tidak berisi
        // pilihan yang pasti ditolak saat disimpan.
        (function () {
            const urlOpsi = "{{ route('penunjukan-perbaikan-data.opsi-pengajuan') }}";
            const kotakCari = document.getElementById('cariPengajuan');
            const daftar = document.getElementById('opsiPengajuan');
            const idTersembunyi = document.getElementById('perbaikanDataId');
            const ringkasan = document.getElementById('ringkasanPengajuan');
            const pesan = document.getElementById('pesanPengajuan');
            let tunda;

            function lolos(teks) {
                const kotak = document.createElement('div');
                kotak.textContent = teks === null || teks === undefined || teks === '' ? '(kosong)' : teks;
                return kotak.innerHTML;
            }

            function gambarRingkasan(item) {
                let html = '<dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-sm">'
                    + '<div class="flex"><dt class="w-28 text-gray-500">Kode</dt><dd class="font-medium text-gray-800">' + lolos(item.kode) + '</dd></div>'
                    + '<div class="flex"><dt class="w-28 text-gray-500">Status</dt><dd class="text-gray-800">' + lolos(item.status) + '</dd></div>'
                    + '<div class="flex"><dt class="w-28 text-gray-500">Pengaju</dt><dd class="text-gray-800">' + lolos(item.pengaju) + '</dd></div>'
                    + '<div class="flex"><dt class="w-28 text-gray-500">Tgl Pengajuan</dt><dd class="text-gray-800">' + lolos(item.tgl_pengajuan) + '</dd></div>'
                    + '<div class="flex sm:col-span-2"><dt class="w-28 text-gray-500">Jenis</dt><dd class="text-gray-800">' + lolos(item.jenis) + '</dd></div>'
                    + '</dl>';

                if (item.perubahan && item.perubahan.length) {
                    html += '<div class="mt-3 border-t border-gray-200 pt-2">'
                        + '<p class="text-xs font-semibold text-gray-600 mb-1">Perubahan yang diminta</p>';

                    item.perubahan.forEach(function (baris) {
                        html += '<div class="py-1 border-b last:border-b-0 border-gray-100">'
                            + '<div class="text-xs text-gray-700">' + lolos(baris.modul) + ' &middot; ' + lolos(baris.field) + '</div>'
                            + '<div class="text-xs text-red-700 line-through break-all">' + lolos(baris.nilai_lama) + '</div>'
                            + '<div class="text-xs text-green-700 font-medium break-all">' + lolos(baris.nilai_baru) + '</div>'
                            + (baris.alasan ? '<div class="text-xs text-gray-500">Alasan: ' + lolos(baris.alasan) + '</div>' : '')
                            + '</div>';
                    });

                    html += '</div>';
                } else {
                    html += '<p class="mt-3 text-xs text-gray-500">'
                        + 'Pengajuan ini tidak mencantumkan perubahan terstruktur — isinya hanya dokumen lampiran.'
                        + '</p>';
                }

                ringkasan.innerHTML = html;
            }

            function gambarOpsi(opsi, pesanKosong) {
                daftar.innerHTML = '';

                if (!opsi.length) {
                    const kosong = document.createElement('li');
                    kosong.className = 'px-3 py-2 text-xs text-gray-500';
                    kosong.textContent = pesanKosong;
                    daftar.appendChild(kosong);
                } else {
                    opsi.forEach(function (item) {
                        const li = document.createElement('li');
                        li.className = 'cursor-pointer px-3 py-2 text-sm hover:bg-indigo-50';
                        li.setAttribute('data-opsi-item', '');
                        li.dataset.isi = JSON.stringify(item);
                        li.textContent = item.label;
                        daftar.appendChild(li);
                    });
                }

                daftar.classList.remove('hidden');
            }

            async function muatOpsi() {
                const kata = kotakCari.value.trim();

                daftar.innerHTML = '<li class="px-3 py-2 text-xs text-gray-500">Mencari ...</li>';
                daftar.classList.remove('hidden');

                try {
                    const jawab = await fetch(urlOpsi + '?q=' + encodeURIComponent(kata), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await jawab.json();

                    if (!jawab.ok) {
                        gambarOpsi([], data.pesan || 'Daftar pengajuan tidak bisa dimuat.');
                        return;
                    }

                    gambarOpsi(
                        data.opsi || [],
                        kata
                            ? 'Tidak ada pengajuan yang cocok dengan "' + kata + '".'
                            : 'Tidak ada pengajuan yang menunggu penunjukan.'
                    );
                } catch (e) {
                    gambarOpsi([], 'Gagal menghubungi server.');
                }
            }

            // mousedown, bukan click: klik pada pilihan didahului blur pada
            // inputnya, dan kalau daftarnya sudah ditutup oleh blur, click-nya
            // mendarat di tempat yang sudah tidak ada.
            daftar.addEventListener('mousedown', function (e) {
                const item = e.target.closest('[data-opsi-item]');
                if (!item) return;

                e.preventDefault();

                const isi = JSON.parse(item.dataset.isi);
                idTersembunyi.value = isi.id;
                kotakCari.value = isi.kode;
                pesan.textContent = 'Terpilih: ' + isi.kode + ' (' + isi.status + ')';
                pesan.className = 'text-xs text-gray-500 mt-1';
                gambarRingkasan(isi);
                daftar.classList.add('hidden');
            });

            kotakCari.addEventListener('focus', muatOpsi);

            kotakCari.addEventListener('input', function () {
                // Mengetik ulang membatalkan pilihan sebelumnya: tanpa ini, teks
                // yang tertinggal di kotak bisa berbeda dari pengajuan yang
                // benar-benar terkirim.
                idTersembunyi.value = '';
                ringkasan.innerHTML = '<p class="text-xs text-gray-500">Belum ada pengajuan yang dipilih.</p>';
                pesan.textContent = '';

                clearTimeout(tunda);
                tunda = setTimeout(muatOpsi, 300);
            });

            kotakCari.addEventListener('blur', function () {
                daftar.classList.add('hidden');

                if (!idTersembunyi.value) {
                    kotakCari.value = '';
                }
            });
        })();
    </script>
    @endif

    <script>
        document.getElementById('saveButton').addEventListener('click', function () {
            const form = document.getElementById('penunjukanForm');
            const idPengajuan = document.getElementById('perbaikanDataId');

            // Diperiksa di sini karena input hidden tidak ikut validasi HTML5.
            if (idPengajuan && !idPengajuan.value) {
                const kotak = document.getElementById('pesanPengajuan');
                if (kotak) {
                    kotak.textContent = 'Pilih kode pengajuannya dari daftar dulu.';
                    kotak.className = 'text-xs text-red-600 mt-1';
                }
                document.getElementById('cariPengajuan').focus();
                return;
            }

            // requestSubmit(), bukan submit(): submit() melewati seluruh validasi
            // HTML5, jadi kotak wajib yang belum diisi lolos ke server dan yang
            // dilihat pengguna hanya halaman yang memuat ulang tanpa petunjuk.
            if (form.requestSubmit) {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    </script>
</x-app-layout>
