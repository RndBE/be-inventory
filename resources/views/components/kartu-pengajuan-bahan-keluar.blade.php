@props(['keluar', 'judul' => 'Pengajuan Bahan', 'konteks' => 'pengajuan ini'])

{{--
    Satu kartu untuk satu pengajuan bahan keluar: statusnya, sudah sampai mana
    approvalnya, dan bahan apa saja yang diajukan.

    Menggantikan kotak merah yang dulu ditulis ulang di sembilan keranjang.
    Merahnya dipertahankan — kotak ini memang penanda "ada pengajuan yang belum
    tuntas", dan warnanya sudah dikenali orang yang memakai halaman ini setiap
    hari. Yang diperbaiki isinya:

    1. Statusnya dulu ditempel ke judul — "Informasi Pengajuan Bahan Produksi
       Belum disetujui" — sehingga judulnya berubah-ubah dan terbaca sebagai
       kalimat yang belum selesai. Sekarang judul tetap, status jadi pil.
    2. Kode transaksi dan tanggal pengajuannya tidak pernah ditampilkan, padahal
       itu yang dipakai menyebut transaksinya saat menagih approval.
    3. Daftar bahannya sebaris teks dipisah koma, jadi tiga puluh bahan terbaca
       sebagai satu paragraf panjang tanpa jumlah yang bisa ditelusuri.
--}}

@php
    $status = $keluar->status ?: 'Belum disetujui';
    $bahan = $keluar->bahanKeluarDetails ?? collect();
@endphp

<div {{ $attributes->merge([
    'class' => 'rounded-lg border border-red-300 bg-red-50 p-4 text-red-800 dark:border-red-800 dark:bg-gray-800 dark:text-red-400',
]) }} role="alert">
    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
        <svg class="h-4 w-4 flex-shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
        </svg>

        <h3 class="text-base font-semibold text-red-900 dark:text-red-200">{{ $judul }}</h3>

        <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 ring-1 ring-inset ring-red-200 dark:bg-red-900/40 dark:text-red-300 dark:ring-red-800">
            {{ $status }}
        </span>

        @if ($keluar->kode_transaksi)
            <span class="text-xs text-red-800/70 dark:text-red-300/70">&middot; {{ $keluar->kode_transaksi }}</span>
        @endif

        @if ($keluar->tgl_pengajuan)
            <span class="text-xs text-red-800/70 dark:text-red-300/70">
                &middot; diajukan {{ \Carbon\Carbon::parse($keluar->tgl_pengajuan)->format('d/m/Y') }}
            </span>
        @endif
    </div>

    <x-progres-approval-bahan-keluar :keluar="$keluar" />

    <div class="mt-3 border-t border-red-200 pt-3 dark:border-red-800/60">
        <div class="text-xs font-semibold uppercase tracking-wide text-red-700/70 dark:text-red-400/70">
            Bahan yang diajukan untuk {{ $konteks }}
        </div>

        {{--
            Satu keping per bahan, bukan satu baris dipisah koma. Jumlahnya yang
            dicari pembaca, dan angka yang menempel di tengah kalimat panjang
            harus dihitung sendiri satu per satu.
        --}}
        <div class="mt-2 flex flex-wrap gap-1.5">
            @forelse ($bahan as $detail)
                <span class="inline-flex items-baseline gap-1.5 rounded-md bg-white/70 px-2 py-1 text-sm ring-1 ring-inset ring-red-200 dark:bg-gray-900/40 dark:ring-red-800/60">
                    <span class="text-red-900 dark:text-red-200">{{ $detail->dataBahan->nama_bahan ?? 'Bahan tidak ditemukan' }}</span>
                    <span class="font-semibold text-red-900 dark:text-red-100">
                        {{ method_exists($detail, 'qtyTampil') && $detail->dataBahan?->panjang_standar
                            ? $detail->qtyTampil()
                            : rtrim(rtrim(number_format((float) $detail->qty, 2, ',', '.'), '0'), ',') }}
                    </span>
                </span>
            @empty
                <span class="text-sm text-red-800/70 dark:text-red-300/70">Tidak ada rincian bahan pada pengajuan ini.</span>
            @endforelse
        </div>
    </div>
</div>
