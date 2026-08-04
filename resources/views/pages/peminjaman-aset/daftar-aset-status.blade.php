{{--
    Daftar aset satu pengajuan beserta status kembalinya, per baris.

    Dipakai bersama tabel pemohon dan tabel approval supaya keduanya tidak
    pernah menampilkan versi yang berbeda.

    Kedua keadaan diberi label, bukan hanya yang sudah kembali. Penanda tunggal
    berupa centang membuat "belum kembali" tidak bisa dibedakan dari "tidak ada
    datanya", dan tidak terbaca sama sekali tanpa warna.
--}}
@php
    $semuaDetail = $peminjaman->peminjamanAsetDetails;
    $jumlahKembali = $semuaDetail->where('status_pengembalian', 'Dikembalikan')->count();
    $jumlahTotal = $semuaDetail->count();
    // Ringkasan hanya berguna saat pengembaliannya dicicil.
    $tampilkanRingkasan = $peminjaman->boleh_dikeluarkan && $jumlahKembali > 0 && $jumlahKembali < $jumlahTotal;
@endphp

@if($tampilkanRingkasan)
    <div class="mb-2 inline-flex items-center gap-1 rounded-md bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
        title="{{ $jumlahKembali }} dari {{ $jumlahTotal }} aset sudah dikembalikan">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3 shrink-0">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" />
        </svg>
        {{ $jumlahKembali }}/{{ $jumlahTotal }}
    </div>
@endif

<div class="space-y-1.5">
    @foreach($semuaDetail as $detail)
        @php $sudahKembali = $detail->status_pengembalian === 'Dikembalikan'; @endphp
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    {{ $detail->dataAset->barangAset->nama_barang ?? '-' }}
                </div>
                <div class="text-xs text-gray-500">{{ $detail->dataAset->nomor_aset ?? '-' }}</div>

                {{-- Peringatan, bukan penghalang: aset yang sudah ditugaskan tetap ke
                     seseorang tetap boleh dipinjam. Yang perlu diketahui approver
                     adalah penempatannya akan berpindah ke peminjam dan TIDAK pulih
                     sendiri setelah dikembalikan — harus ditugaskan ulang manual.

                     Hanya muncul sebelum aset keluar. Begitu peminjaman ini berjalan,
                     pic_id sudah jadi milik peminjam sehingga penandanya padam. --}}
                @if($detail->dataAset?->ditugaskan_tetap)
                    <div class="mt-0.5 inline-flex items-start gap-1 text-xs text-sky-700 dark:text-sky-400"
                        title="Penempatan tetap akan berpindah ke peminjam dan tidak pulih otomatis setelah dikembalikan">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-px h-3 w-3 shrink-0">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" />
                        </svg>
                        <span>Ditugaskan tetap ke {{ $detail->dataAset->dataPic->name ?? '-' }}</span>
                    </div>
                @endif
            </div>

            {{-- Status kembali hanya bermakna setelah aset benar-benar keluar.
                 Sebelum HRD mengetahui, tidak ada yang sedang dipinjam.

                 Ikon saja, tanpa teks label. Keterangannya lewat title= pada <span>
                 pembungkus — BUKAN pada <svg>-nya: peramban tidak memunculkan
                 tooltip untuk atribut title di elemen SVG, hanya untuk elemen
                 <title> di dalamnya. sr-only menjaga statusnya tetap terbaca
                 pembaca layar; tanpa keduanya status ini jadi informasi yang
                 hanya tersampaikan lewat warna. --}}
            @if($peminjaman->boleh_dikeluarkan)
                <div class="flex shrink-0 items-center gap-1.5">
                    @if($sudahKembali)
                        @php
                            $tglKembali = $detail->tgl_kembali
                                ? \Carbon\Carbon::parse($detail->tgl_kembali)->format('d/m/y')
                                : null;
                            $rusak = $detail->kondisi_kembali === 'Rusak';
                        @endphp

                        @if($tglKembali)
                            <span class="text-xs text-gray-500 whitespace-nowrap">{{ $tglKembali }}</span>
                        @endif

                        @if($rusak)
                            <span class="inline-flex" title="Dikembalikan dalam keadaan rusak{{ $tglKembali ? ' pada ' . $tglKembali : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    aria-hidden="true" class="h-4 w-4 shrink-0 text-red-600">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4" /><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" /><path d="M12 16h.01" />
                                </svg>
                                <span class="sr-only">Sudah dikembalikan, kondisi rusak</span>
                            </span>
                        @else
                            <span class="inline-flex" title="Sudah dikembalikan{{ $tglKembali ? ' pada ' . $tglKembali : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    aria-hidden="true" class="h-4 w-4 shrink-0 text-green-600">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12l2 2l4 -4" />
                                </svg>
                                <span class="sr-only">Sudah dikembalikan</span>
                            </span>
                        @endif
                    @else
                        <span class="inline-flex" title="Masih dipinjam, belum dikembalikan">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                aria-hidden="true" class="h-4 w-4 shrink-0 text-amber-500">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 7v5l2.5 2.5" />
                            </svg>
                            <span class="sr-only">Masih dipinjam</span>
                        </span>
                    @endif
                </div>
            @endif
        </div>
    @endforeach
</div>
