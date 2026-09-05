@props(['keluar'])

{{--
    Sampai mana approval sebuah pengajuan bahan keluar berjalan.

    Judul kotaknya dulu cuma menempelkan status mentah — "Informasi Pengajuan
    Bahan Produksi Belum disetujui". Satu kata itu tidak menjawab pertanyaan
    yang dibawa pengaju saat membuka halamannya: menunggu siapa sekarang.
    Pengaju yang tidak tahu jawabannya menagih lewat WhatsApp ke orang yang
    mungkin bukan gilirannya.

    Dua tahap, bukan satu:

    1. Leader atau Manager, tergantung jenis pengajuannya. Projek RnD dan Produk
       Sample kategori RnD naik langsung ke Manager; sisanya ke Leader. Aturannya
       dibaca dari BahanKeluar::approvalAwalRole() supaya layar dan controller
       tidak bisa berselisih.
    2. Purchasing. Persetujuannya yang memotong stok dan mengisi tgl_keluar,
       jadi tahap inilah yang menentukan bahannya benar-benar keluar.

    Warnanya menyatu dengan kartu merah induknya: tanpa kotak putih di dalam
    kotak, hanya garis pemisah dan lingkaran bernomor. Lingkaran hijau dipakai
    khusus untuk tahap yang sudah disetujui — di dalam kartu yang seluruhnya
    merah, "sudah lewat" dan "ditolak" harus bisa dibedakan sekali lihat, dan
    dua nada merah tidak cukup untuk itu.
--}}

@php
    $labelTahapAwal = $keluar->approvalAwalRole();
    $approverAwal = $keluar->approverLeader();

    $statusAwal = $keluar->status_leader ?: 'Belum disetujui';
    $statusAkhir = $keluar->status ?: 'Belum disetujui';

    // Tahap kedua belum jadi giliran siapa pun selama tahap pertama belum
    // disetujui. Menyebutnya "menunggu Purchasing" sejak awal membuat pengaju
    // menagih orang yang memang belum boleh memutuskan.
    $tahapAwalBeres = $statusAwal === 'Disetujui';

    $tahap = [
        [
            'label' => $labelTahapAwal,
            'orang' => $approverAwal->name ?? null,
            'status' => $statusAwal,
            'giliran' => ! $tahapAwalBeres && $statusAwal !== 'Ditolak',
        ],
        [
            'label' => 'Purchasing',
            'orang' => null,
            'status' => $tahapAwalBeres ? $statusAkhir : 'Menunggu ' . $labelTahapAwal,
            'giliran' => $tahapAwalBeres && ! in_array($statusAkhir, ['Disetujui', 'Ditolak'], true),
        ],
    ];
@endphp

<div {{ $attributes->merge(['class' => 'mt-3 border-t border-red-200 pt-3 dark:border-red-800/60']) }}>
    <div class="text-xs font-semibold uppercase tracking-wide text-red-700/70 dark:text-red-400/70">
        Approval sampai mana
    </div>

    <ol class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-0">
        @foreach ($tahap as $satu)
            @php
                $selesai = $satu['status'] === 'Disetujui';
                $ditolak = $satu['status'] === 'Ditolak';
            @endphp

            <li class="flex items-start gap-2 sm:flex-1">
                <span @class([
                    'mt-0.5 flex h-6 w-6 flex-none items-center justify-center rounded-full text-xs font-semibold',
                    'bg-emerald-600 text-white' => $selesai,
                    'bg-red-700 text-white' => $ditolak,
                    'bg-white text-red-700 ring-2 ring-red-400' => $satu['giliran'],
                    'bg-red-100 text-red-400 ring-1 ring-red-200' => ! $selesai && ! $ditolak && ! $satu['giliran'],
                ])>
                    @if ($selesai)
                        &check;
                    @elseif ($ditolak)
                        &times;
                    @else
                        {{ $loop->iteration }}
                    @endif
                </span>

                <div class="min-w-0">
                    <div class="text-sm font-medium text-red-900 dark:text-red-200">
                        {{ $satu['label'] }}
                        @if ($satu['orang'])
                            <span class="font-normal text-red-800/70 dark:text-red-300/70">&middot; {{ $satu['orang'] }}</span>
                        @endif
                    </div>

                    <div class="text-xs text-red-800/80 dark:text-red-300/80">
                        {{ $satu['status'] }}
                        @if ($satu['giliran'])
                            {{-- Penanda giliran terpisah dari statusnya: "Belum
                                 disetujui" bisa muncul di dua tahap sekaligus, dan
                                 tanpa penanda ini pembacanya tidak tahu mana yang
                                 sedang ditunggu. --}}
                            <span class="ml-1 font-semibold">&mdash; menunggu di sini</span>
                        @endif
                    </div>
                </div>
            </li>

            @if (! $loop->last)
                {{-- Konektor hanya muncul di layar lebar. Ditumpuk ke bawah pada
                     layar sempit, garis mendatar justru memutus urutan bacanya. --}}
                <li class="hidden h-px flex-1 bg-red-200 sm:block dark:bg-red-800/60" aria-hidden="true"></li>
            @endif
        @endforeach
    </ol>

    @if ($keluar->status === 'Disetujui')
        <div class="mt-2 text-xs text-red-800/70 dark:text-red-300/70">
            Bahan keluar {{ $keluar->tgl_keluar ? 'pada ' . \Carbon\Carbon::parse($keluar->tgl_keluar)->format('d/m/Y H:i') : 'sudah disetujui' }}
            &middot; pengambilan: {{ $keluar->status_pengambilan ?? '-' }}
        </div>
    @endif
</div>
