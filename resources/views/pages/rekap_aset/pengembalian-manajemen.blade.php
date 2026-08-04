{{--
    Pencatatan aset ber-PIC yang diserahkan kembali ke manajemen.

    Padanan modal "Catat Pengembalian" milik peminjaman, untuk aset yang PIC &
    ruangannya ditetapkan lewat rekap aset sehingga tidak punya pengajuan
    peminjaman. Isian dan aturannya sengaja dibuat sama supaya GA tidak perlu
    belajar dua cara mencatat hal yang sama.

    Dibuka per PIC: satu tanggal dan satu set bukti foto berlaku untuk semua aset
    yang dicentang.
--}}
@php
    $idSemua = $asetPengembalian->pluck('id')->values();
@endphp

<div x-data="{
        isOpen: @entangle('isPengembalianModalOpen'),
        semua: @js($idSemua),
        terpilih: @js($asetTerpilihAwal),
        jumlahFoto: 0,
        {{-- Mengunci tombol Simpan sejak form dikirim. Unggahan foto berjalan lama,
             jadi jeda tanpa penanda apa pun mengundang klik kedua — dan klik kedua
             mencatat serah terima dua kali. --}}
        mengirim: false,
    }"
    x-show="isOpen"
    class="fixed inset-0 z-50 flex items-end justify-center sm:items-center"
    style="background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px);"
    @keydown.escape.window="isOpen = false; $wire.closeModal();"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100">

    {{-- Di ponsel menempel ke dasar layar, mulai sm ke atas melayang di tengah. --}}
    <div class="relative w-full max-w-2xl p-0 sm:p-4" x-show="isOpen"
        @click.outside="isOpen = false; $wire.closeModal();">
        {{-- Tinggi dibatasi relatif layar + flex-col: kepala dan tombol tetap
             terlihat, hanya isian di tengah yang bergulir. Satuan dvh supaya
             bilah alamat browser ponsel ikut diperhitungkan. --}}
        <div class="relative flex max-h-[95dvh] flex-col rounded-t-2xl bg-white shadow dark:bg-gray-700 sm:max-h-[90dvh] sm:rounded-lg">
            <div class="flex shrink-0 items-start justify-between gap-2 rounded-t-2xl border-b p-4 sm:rounded-t-lg md:p-5 dark:border-gray-600">
                <div class="min-w-0">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white sm:text-xl">Kembalikan ke Manajemen</h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-300">
                        PIC: {{ $picPengembalian->name ?? '-' }} &middot;
                        {{ $asetPengembalian->count() }} aset dipegang
                    </p>
                </div>
                <button wire:click="closeModal" type="button" class="ms-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-transparent text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Tutup</span>
                </button>
            </div>

            @if($asetPengembalian->isEmpty())
                <div class="p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-300">
                        Semua aset {{ $picPengembalian->name ?? 'orang ini' }} sedang dipinjam lewat pengajuan.
                        Pengembaliannya dicatat dari menu Pengajuan Peminjaman, bukan dari sini —
                        supaya status pinjamnya ikut ditutup.
                    </p>
                </div>
            @else
                {{-- min-h-0 wajib: tanpa itu anak flex menolak menyusut dan
                     overflow di dalamnya tidak pernah aktif. --}}
                {{-- Dipasang pada submit, bukan click: event ini tidak menyala kalau
                     validasi HTML gagal, jadi tombolnya tidak ikut mati saat form
                     belum lengkap. --}}
                <form class="flex min-h-0 flex-1 flex-col" method="POST" enctype="multipart/form-data"
                    x-on:submit="mengirim = true"
                    action="{{ route('rekap-aset.pengembalian-manajemen') }}">
                    @csrf
                    <input type="hidden" name="pic_id" value="{{ $picPengembalian->id }}">

                    <div class="flex-1 space-y-5 overflow-y-auto overscroll-contain p-4 pt-0 sm:p-5 sm:pt-0">

                        <div class="mt-4">
                            <div class="mb-2 flex items-center justify-between">
                                <label class="block text-sm font-medium text-gray-900 dark:text-white">
                                    Aset yang Diserahkan <sup class="text-base text-red-500">*</sup>
                                </label>
                                <button type="button"
                                    x-on:click="terpilih = terpilih.length === semua.length ? [] : [...semua]"
                                    class="text-xs font-medium text-indigo-600 hover:underline dark:text-indigo-400">
                                    <span x-text="terpilih.length === semua.length ? 'Kosongkan semua' : 'Pilih semua'"></span>
                                </button>
                            </div>

                            <div class="max-h-[30dvh] overflow-y-auto overscroll-contain rounded-lg border border-gray-200 dark:border-gray-600 sm:max-h-64">
                                @foreach($asetPengembalian as $aset)
                                    <label class="flex cursor-pointer items-start gap-3 border-b border-gray-100 p-3 last:border-b-0 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-600">
                                        <input type="checkbox" name="rekap_aset_ids[]" value="{{ $aset->id }}"
                                            x-model.number="terpilih"
                                            class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">

                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ $aset->barangAset->nama_barang ?? '-' }}
                                            </span>
                                            <span class="block text-xs text-gray-500 dark:text-gray-400">
                                                {{ $aset->nomor_aset }}
                                                @if($aset->dataRuangan)
                                                    &middot; {{ $aset->dataRuangan->nama_ruangan }}
                                                @endif
                                            </span>
                                        </span>

                                        @if($aset->kondisi === 'Rusak')
                                            <span class="shrink-0 rounded border border-red-400 bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800">Rusak</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>

                            @error('rekap_aset_ids')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="tgl_kembali_manajemen" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Tanggal Diserahkan</label>
                                <input type="date" name="tgl_kembali" id="tgl_kembali_manajemen" value="{{ now()->format('Y-m-d') }}" required
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                                    Boleh mundur dari hari ini. Tanggal inilah yang dipakai di riwayat,
                                    bukan waktu pencatatan.
                                </p>
                            </div>

                            <div>
                                <label for="kondisi_manajemen" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Kondisi Saat Diserahkan</label>
                                <select name="kondisi" id="kondisi_manajemen" required
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white">
                                    <option value="Baik">Baik</option>
                                    <option value="Rusak">Rusak</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                                    Berlaku untuk semua aset yang dicentang, dan memperbarui kondisi di rekap aset.
                                </p>
                            </div>
                        </div>

                        <div>
                            <label for="bukti_foto_manajemen" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                                Bukti Foto Serah Terima <sup class="text-base text-red-500">*</sup>
                            </label>
                            <input type="file" name="bukti_foto[]" id="bukti_foto_manajemen" accept="image/*" multiple required
                                x-on:change="jumlahFoto = $event.target.files.length"
                                class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-gray-50 text-sm text-gray-900 focus:outline-none dark:border-gray-500 dark:bg-gray-600 dark:text-gray-400">

                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                                Boleh pilih beberapa foto sekaligus — semuanya dipakai untuk seluruh aset yang dicentang.
                                Format JPG, PNG, atau WEBP, maksimal 4 MB per foto dan 10 foto sekali unggah.
                            </p>

                            <p x-show="jumlahFoto > 0" x-cloak class="mt-1 text-xs font-medium text-indigo-600 dark:text-indigo-400">
                                <span x-text="jumlahFoto"></span> foto dipilih.
                                <span x-show="jumlahFoto > 10" class="text-red-600">Maksimal 10 — kurangi dulu.</span>
                            </p>

                            @error('bukti_foto')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                            @foreach($errors->get('bukti_foto.*') as $pesanFoto)
                                <p class="mt-1 text-sm text-red-500">{{ $pesanFoto[0] }}</p>
                            @endforeach
                        </div>

                        <div>
                            <label for="catatan_manajemen" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Catatan</label>
                            <textarea name="catatan" id="catatan_manajemen" rows="2"
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-500 dark:bg-gray-600 dark:text-white"
                                placeholder="Opsional, mis. diserahkan ke GA lengkap dengan charger"></textarea>
                        </div>

                        <div class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-600 dark:bg-amber-900/30 dark:text-amber-200">
                            PIC dan ruangan aset yang dicentang akan dikosongkan — artinya aset ada di tangan
                            manajemen dan belum ditugaskan ke siapa pun. Penugasan berikutnya diisi lagi lewat
                            form edit rekap aset.
                        </div>

                    </div>{{-- akhir area bergulir --}}

                    {{-- Tombol dipatok di luar area gulir supaya selalu terlihat.
                         pb ekstra di ponsel memberi ruang untuk area gestur. --}}
                    <div class="flex shrink-0 gap-3 border-t border-gray-200 p-4 pb-6 dark:border-gray-600 sm:p-5">
                        <button wire:click="closeModal" type="button" x-bind:disabled="mengirim"
                            class="w-full rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-500 dark:text-gray-200 dark:hover:bg-gray-600">
                            Batal
                        </button>
                        <button type="submit" x-bind:disabled="terpilih.length === 0 || mengirim"
                            class="w-full rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-800 focus:outline-none focus:ring-4 focus:ring-indigo-300 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500">
                            <span x-show="!mengirim">
                                Simpan <span x-show="terpilih.length > 0">(<span x-text="terpilih.length"></span> aset)</span>
                            </span>
                            <span x-show="mengirim" x-cloak>Mengunggah…</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
