@php
    $detailPengembalian = $peminjamanPengembalian->peminjamanAsetDetails;
    $idSemua = $detailPengembalian->pluck('id')->values();
    // Aset yang belum pernah dicatat: jadi centangan awal, sekaligus penentu wajib/tidaknya foto.
    $idBelumKembali = $detailPengembalian->where('status_pengembalian', '!=', 'Dikembalikan')->pluck('id')->values();
@endphp

<div x-data="{
        isOpen: @entangle('isPengembalianModalOpen'),
        semua: @js($idSemua),
        belum: @js($idBelumKembali),
        terpilih: @js($idBelumKembali),
        jumlahFoto: 0,
        {{-- Mengunci tombol Simpan sejak form dikirim. Paling penting di sini:
             unggahan foto berjalan lama, jadi jeda tanpa penanda apa pun paling
             mengundang klik kedua — dan klik kedua mencatat pengembalian dua kali. --}}
        mengirim: false,
        {{-- Foto wajib begitu ada aset yang baru pertama kali dicatat. Kalau semua
             centangan hanya meralat catatan lama, fotonya boleh dikosongkan. --}}
        get wajibFoto() { return this.belum.some(id => this.terpilih.includes(id)) },
    }"
    x-show="isOpen"
    class="fixed inset-0 z-50 flex items-end justify-center sm:items-center"
    style="background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px);"
    @keydown.escape.window="isOpen = false; $wire.closeModal();"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100">

    {{-- Di ponsel modal menempel ke dasar layar (lebih mudah dijangkau ibu jari),
         mulai sm ke atas melayang di tengah seperti biasa. --}}
    <div class="relative w-full max-w-2xl p-0 sm:p-4" x-show="isOpen"
        @click.outside="isOpen = false; $wire.closeModal();">
        {{-- Tinggi dibatasi relatif layar + flex-col: kepala dan tombol tetap
             terlihat, hanya isian di tengah yang bergulir. Tanpa ini isi modal
             tumbuh melewati layar dan tombol Simpan-nya terpotong di bawah.
             Satuan dvh, bukan vh, supaya bilah alamat browser ponsel yang
             muncul-hilang saat menggulir ikut diperhitungkan. --}}
        <div class="relative flex max-h-[95dvh] flex-col bg-white rounded-t-2xl shadow dark:bg-gray-700 sm:max-h-[90dvh] sm:rounded-lg">
            <div class="flex shrink-0 items-start justify-between gap-2 border-b p-4 md:p-5 rounded-t-2xl sm:rounded-t-lg dark:border-gray-600">
                <div class="min-w-0">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white sm:text-xl">Catat Pengembalian Aset</h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-300">
                        {{ $peminjamanPengembalian->kode_peminjaman }} &middot;
                        {{ $peminjamanPengembalian->dataUser->name ?? '-' }} &middot;
                        dipinjam sejak {{ $peminjamanPengembalian->tgl_pinjam ?? '-' }}
                    </p>
                </div>
                <button wire:click="closeModal" type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>

            {{-- min-h-0 wajib: tanpa itu anak flex menolak menyusut dan overflow
                 di dalamnya tidak pernah aktif. --}}
            {{-- Dipasang pada submit, bukan click: event ini tidak menyala kalau
                 validasi HTML gagal, jadi tombolnya tidak ikut mati saat form
                 belum lengkap. --}}
            <form class="flex min-h-0 flex-1 flex-col" method="POST" enctype="multipart/form-data"
                x-on:submit="mengirim = true"
                action="{{ route('peminjaman-aset.pengembalian', (int) $peminjamanPengembalian->id) }}">
                @csrf

                {{-- overscroll-contain: menggulir di dalam modal tidak ikut
                     menggeser halaman di belakangnya, terutama di layar sentuh. --}}
                <div class="flex-1 space-y-5 overflow-y-auto overscroll-contain p-4 pt-0 sm:p-5 sm:pt-0">

                <div class="mt-4">
                    <div class="mb-2 flex items-center justify-between">
                        <label class="block text-sm font-medium text-gray-900 dark:text-white">
                            Aset yang Dikembalikan <sup class="text-red-500 text-base">*</sup>
                        </label>
                        <button type="button"
                            x-on:click="terpilih = terpilih.length === semua.length ? [] : [...semua]"
                            class="text-xs font-medium text-indigo-600 hover:underline dark:text-indigo-400">
                            <span x-text="terpilih.length === semua.length ? 'Kosongkan semua' : 'Pilih semua'"></span>
                        </button>
                    </div>

                    {{-- Tinggi daftar mengikuti layar, bukan angka tetap: di layar
                         pendek tidak mendesak form ke bawah, di layar tinggi tidak
                         menyisakan ruang kosong. --}}
                    <div class="max-h-[30dvh] overflow-y-auto overscroll-contain rounded-lg border border-gray-200 dark:border-gray-600 sm:max-h-64">
                        @forelse($detailPengembalian as $detail)
                            @php $sudahKembali = $detail->status_pengembalian === 'Dikembalikan'; @endphp
                            <label class="flex cursor-pointer items-start gap-3 border-b border-gray-100 p-3 last:border-b-0 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-600">
                                <input type="checkbox" name="detail_ids[]" value="{{ $detail->id }}"
                                    x-model.number="terpilih"
                                    class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">

                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $detail->dataAset->barangAset->nama_barang ?? '-' }}
                                    </span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                                        {{ $detail->dataAset->nomor_aset ?? '-' }}
                                    </span>

                                    @if($sudahKembali)
                                        <span class="mt-1 block text-xs text-amber-700 dark:text-amber-500">
                                            Sudah dicatat {{ \Carbon\Carbon::parse($detail->tgl_kembali)->format('d/m/Y') }}
                                            &middot; {{ $detail->kondisi_kembali }}
                                            &mdash; mencentangnya akan menimpa catatan ini.
                                        </span>
                                    @endif
                                </span>

                                <span class="shrink-0">
                                    @if($sudahKembali)
                                        <span class="rounded border border-green-400 bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">Kembali</span>
                                    @else
                                        <span class="rounded border border-amber-400 bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Belum</span>
                                    @endif
                                </span>
                            </label>
                        @empty
                            <p class="p-3 text-sm text-gray-500">Pengajuan ini tidak punya rincian aset.</p>
                        @endforelse
                    </div>

                    @error('detail_ids')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="tgl_kembali" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tanggal Kembali</label>
                        <input type="date" name="tgl_kembali" id="tgl_kembali" value="{{ now()->format('Y-m-d') }}" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">Boleh mundur dari hari ini.</p>
                    </div>

                    <div>
                        <label for="kondisi_kembali" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Kondisi Saat Kembali</label>
                        <select name="kondisi_kembali" id="kondisi_kembali" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                            <option value="Baik">Baik</option>
                            <option value="Rusak">Rusak</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                            Berlaku untuk semua aset yang dicentang.
                        </p>
                    </div>
                </div>

                <div>
                    <label for="bukti_foto" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                        Bukti Foto Pengembalian
                        <sup class="text-red-500 text-base" x-show="wajibFoto">*</sup>
                    </label>
                    <input type="file" name="bukti_foto[]" id="bukti_foto" accept="image/*" multiple
                        x-bind:required="wajibFoto"
                        x-on:change="jumlahFoto = $event.target.files.length"
                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none dark:text-gray-400 dark:bg-gray-600 dark:border-gray-500">

                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                        Boleh pilih beberapa foto sekaligus — semuanya dipakai untuk seluruh aset yang dicentang.
                        Format JPG, PNG, atau WEBP, maksimal 4 MB per foto dan 10 foto sekali unggah.
                        <span x-show="!wajibFoto">Boleh dikosongkan kalau hanya meralat catatan lama.</span>
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
                    <label for="catatan_pengembalian" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Catatan</label>
                    <textarea name="catatan_pengembalian" id="catatan_pengembalian" rows="2"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white"
                        placeholder="Opsional"></textarea>
                </div>

                </div>{{-- akhir area bergulir --}}

                {{-- Tombol dipatok di luar area gulir supaya selalu terlihat,
                     berapa pun panjang daftar asetnya. --}}
                {{-- pb ekstra di ponsel memberi ruang untuk area gestur/home indicator. --}}
                <div class="flex shrink-0 gap-3 border-t border-gray-200 p-4 pb-6 dark:border-gray-600 sm:p-5">
                    <button wire:click="closeModal" type="button" x-bind:disabled="mengirim"
                        class="w-full rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-500 dark:text-gray-200 dark:hover:bg-gray-600">
                        Batal
                    </button>
                    <button type="submit" x-bind:disabled="terpilih.length === 0 || mengirim"
                        class="w-full rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-800 focus:ring-4 focus:outline-none focus:ring-indigo-300 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500">
                        <span x-show="!mengirim">
                            Simpan <span x-show="terpilih.length > 0">(<span x-text="terpilih.length"></span> aset)</span>
                        </span>
                        <span x-show="mengirim" x-cloak>Mengunggah…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
