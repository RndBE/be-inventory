@php
    $badge = function ($status) {
        return match ($status) {
            'Disetujui' => 'bg-green-100 text-green-800',
            'Ditolak' => 'bg-red-100 text-red-800',
            default => 'bg-yellow-100 text-yellow-800',
        };
    };
@endphp
<div x-data="{
        isOpen: false,
        close() {
            this.isOpen = false;
            setTimeout(() => $wire.closeModal(), 220);
        }
    }"
    x-init="$nextTick(() => isOpen = true)"
    @keydown.escape.window="close()">

    <div x-show="isOpen"
        @click="close()"
        x-transition:enter="transition-opacity ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-40 bg-black bg-opacity-30"
        style="display: none"></div>

    <aside x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed top-0 right-0 z-50 h-full w-full max-w-3xl overflow-y-auto bg-white shadow-xl dark:bg-gray-700"
        style="display: none"
        @click.outside="close()">
            <div class="sticky top-0 z-10 flex items-center justify-between border-b bg-white p-4 md:p-5 dark:border-gray-600 dark:bg-gray-700">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Detail Peminjaman {{ $detailPeminjaman->kode_peminjaman }}
                </h3>
                <button @click="close()" type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>

            <div class="p-5 space-y-5">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="text-xs uppercase text-gray-400">Pengaju</div>
                        <div class="font-medium text-gray-900 dark:text-white">
                            {{ $detailPeminjaman->dataUser->name ?? '-' }}
                            <span class="text-gray-500 dark:text-gray-300">({{ $detailPeminjaman->dataUser->dataJobPosition->nama ?? '-' }})</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-xs uppercase text-gray-400">Divisi</div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ $detailPeminjaman->divisi ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase text-gray-400">Ruangan Tujuan</div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ $detailPeminjaman->dataRuangan->nama_ruangan ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase text-gray-400">Tanggal Pinjam</div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ $detailPeminjaman->tgl_pinjam ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase text-gray-400">Status Pengembalian</div>
                        <div class="font-medium text-gray-900 dark:text-white">
                            {{ $detailPeminjaman->status_pengembalian }}
                            @if($detailPeminjaman->lama_dipinjam !== null)
                                <span class="text-gray-500 dark:text-gray-300">(sudah {{ $detailPeminjaman->lama_dipinjam }} hari)</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-span-2">
                        <div class="text-xs uppercase text-gray-400">Keperluan</div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ $detailPeminjaman->keperluan ?? '-' }}</div>
                    </div>
                    @if($detailPeminjaman->catatan)
                        <div class="col-span-2">
                            <div class="text-xs uppercase text-gray-400">Catatan Approval</div>
                            <div class="font-medium text-gray-900 dark:text-white">{{ $detailPeminjaman->catatan }}</div>
                        </div>
                    @endif
                </div>

                <div>
                    <div class="text-xs uppercase text-gray-400 mb-2">Riwayat Approval</div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        @foreach ([
                            'Leader' => ['status' => $detailPeminjaman->status_leader, 'tgl' => $detailPeminjaman->tgl_approve_leader],
                            'Manager' => ['status' => $detailPeminjaman->status_manager, 'tgl' => $detailPeminjaman->tgl_approve_manager],
                            'General Affair' => ['status' => $detailPeminjaman->status, 'tgl' => $detailPeminjaman->tgl_approve_ga],
                            'HRD (Mengetahui)' => ['status' => $detailPeminjaman->status_hrd, 'tgl' => $detailPeminjaman->tgl_approve_hrd],
                        ] as $label => $tahap)
                            @php
                                $kendala = $detailPeminjaman->kendalaApproval($label);
                            @endphp
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-600">
                                <div class="text-xs text-gray-500 dark:text-gray-300">{{ $label }}</div>
                                <span class="mt-1 inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $badge($tahap['status']) }}">
                                    {{ $tahap['status'] ?? 'Belum disetujui' }}
                                </span>
                                <div class="mt-1 text-xs text-gray-400">{{ $tahap['tgl'] ?? '-' }}</div>
                                @if ($kendala)
                                    <div class="mt-1 text-xs text-amber-700 dark:text-amber-500">
                                        Kendala: {{ $kendala }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="text-xs uppercase text-gray-400 mb-2">Aset yang Dipinjam</div>
                    <div class="relative overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-600">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-2">Nomor Aset</th>
                                    <th class="px-4 py-2">Nama Barang</th>
                                    <th class="px-4 py-2">Ruangan Saat Ini</th>
                                    <th class="px-4 py-2">Status</th>
                                    <th class="px-4 py-2">Kembali</th>
                                    <th class="px-4 py-2">Bukti Foto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detailPeminjaman->peminjamanAsetDetails as $detail)
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="px-4 py-2">{{ $detail->dataAset->nomor_aset ?? '-' }}</td>
                                        <td class="px-4 py-2">{{ $detail->dataAset->barangAset->nama_barang ?? '-' }}</td>
                                        <td class="px-4 py-2">{{ $detail->dataAset->dataRuangan->nama_ruangan ?? '-' }}</td>
                                        <td class="px-4 py-2">
                                            @if($detail->status_pengembalian === 'Dikembalikan')
                                                <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">Dikembalikan</span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">Belum kembali</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2">
                                            @if($detail->status_pengembalian === 'Dikembalikan')
                                                {{ $detail->tgl_kembali }} &middot; {{ $detail->kondisi_kembali }}
                                                @if($detail->catatan_pengembalian)
                                                    <div class="text-xs text-gray-400">{{ $detail->catatan_pengembalian }}</div>
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-4 py-2">
                                            @forelse($detail->buktiFoto as $bukti)
                                                <a href="{{ $bukti->url }}" target="_blank"
                                                    class="mb-1 me-1 inline-block h-16 w-16 overflow-hidden rounded-lg border border-gray-200 align-top hover:border-indigo-400"
                                                    title="Klik untuk lihat foto penuh">
                                                    <img src="{{ $bukti->url }}" alt="Bukti pengembalian"
                                                        class="h-full w-full object-cover">
                                                </a>
                                            @empty
                                                <span class="text-xs text-gray-400">-</span>
                                            @endforelse
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
    </aside>
</div>
