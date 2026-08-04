{{-- Panel geser dari kanan, mengikuti pola detail peminjaman aset supaya kedua
     modul terasa sama. Struktur overlay, transisi, dan gaya labelnya sengaja
     disalin persis. --}}
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
            <div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Berita Acara {{ $detailBast->kode_bast }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-300">
                    @if($detailBast->selesai)
                        Selesai {{ \Carbon\Carbon::parse($detailBast->tgl_selesai)->format('d/m/Y H:i') }}
                        oleh {{ $detailBast->dataPenyelesai->name ?? '-' }}
                    @else
                        Draft &mdash; aset belum dilepas dari karyawan
                    @endif
                </p>
            </div>
            <button @click="close()" type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                </svg>
                <span class="sr-only">Tutup</span>
            </button>
        </div>

        <div class="p-5 space-y-5">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <div class="text-xs uppercase text-gray-400">Karyawan</div>
                    <div class="font-medium text-gray-900 dark:text-white">
                        {{ $detailBast->dataKaryawan->name ?? '-' }}
                        <span class="text-gray-500 dark:text-gray-300">({{ $detailBast->dataKaryawan->dataJobPosition->nama ?? '-' }})</span>
                    </div>
                </div>
                <div>
                    <div class="text-xs uppercase text-gray-400">Atasan Langsung</div>
                    <div class="font-medium text-gray-900 dark:text-white">{{ $detailBast->dataAtasan->name ?? 'Tidak ada' }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase text-gray-400">Alasan Berakhir</div>
                    <div class="font-medium text-gray-900 dark:text-white">{{ $detailBast->alasan_keluar ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase text-gray-400">Tanggal Efektif</div>
                    <div class="font-medium text-gray-900 dark:text-white">{{ $detailBast->tgl_efektif ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase text-gray-400">Status</div>
                    <div class="font-medium text-gray-900 dark:text-white">
                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $detailBast->selesai ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                            {{ $detailBast->status }}
                        </span>
                    </div>
                </div>
                <div>
                    <div class="text-xs uppercase text-gray-400">Dibuat Oleh</div>
                    <div class="font-medium text-gray-900 dark:text-white">{{ $detailBast->dataPengaju->name ?? '-' }}</div>
                </div>
                @if($detailBast->keterangan)
                    <div class="col-span-2">
                        <div class="text-xs uppercase text-gray-400">Catatan</div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ $detailBast->keterangan }}</div>
                    </div>
                @endif
            </div>

            <div>
                <div class="text-xs uppercase text-gray-400 mb-2">Ringkasan Aset</div>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-600">
                        <div class="text-xs text-gray-500 dark:text-gray-300">Diserahkan</div>
                        <span class="mt-1 inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ $detailBast->bebas_aset ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ $detailBast->bebas_aset ? 'Bebas aset' : $detailBast->aset_diserahkan->count() . ' aset' }}
                        </span>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-600">
                        <div class="text-xs text-gray-500 dark:text-gray-300">Sudah kembali sebelumnya</div>
                        <span class="mt-1 inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">
                            {{ $detailBast->aset_sudah_kembali->count() }} aset
                        </span>
                    </div>
                </div>
            </div>

            @if($detailBast->bebas_aset)
                <div class="rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-800 dark:border-green-700 dark:bg-green-900/20 dark:text-green-300">
                    Tidak ada aset yang perlu diserahkan &mdash; dokumen ini berlaku sebagai Surat Keterangan Bebas Aset.
                </div>
            @endif

            @if($detailBast->serahTerimaAsetDetails->isNotEmpty())
                <div>
                    <div class="text-xs uppercase text-gray-400 mb-2">Rincian Aset</div>
                    <div class="relative overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-600">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-2">Nomor Aset</th>
                                    <th class="px-4 py-2">Nama Barang</th>
                                    <th class="px-4 py-2">Sumber</th>
                                    <th class="px-4 py-2">Status</th>
                                    <th class="px-4 py-2">Kondisi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detailBast->serahTerimaAsetDetails as $detail)
                                    @php $kembali = $detail->status_pegang === 'Sudah kembali'; @endphp
                                    {{-- Baris yang sudah kembali diredupkan: tercantum sebagai
                                         keterangan, bukan yang diserahkan lewat BAST ini. --}}
                                    <tr class="border-b dark:border-gray-700 {{ $kembali ? 'bg-gray-50 dark:bg-gray-900/30' : 'bg-white dark:bg-gray-800' }}">
                                        <td class="px-4 py-2">{{ $detail->dataAset->nomor_aset ?? '-' }}</td>
                                        <td class="px-4 py-2 {{ $kembali ? '' : 'text-gray-900 dark:text-white' }}">
                                            {{ $detail->dataAset->barangAset->nama_barang ?? '-' }}
                                        </td>
                                        <td class="px-4 py-2">{{ $detail->sumber === 'PIC' ? 'Tanggung jawab tetap' : 'Pinjaman' }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            @if($kembali)
                                                <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">Sudah kembali</span>
                                                @if($detail->detailPeminjaman?->tgl_kembali)
                                                    <div class="mt-0.5 text-xs text-gray-400">
                                                        {{ \Carbon\Carbon::parse($detail->detailPeminjaman->tgl_kembali)->format('d/m/y') }}
                                                    </div>
                                                @endif
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">Diserahkan</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2">{{ $detail->kondisi_serah }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="border-t border-gray-200 pt-4 dark:border-gray-600">
                <a href="{{ route('serah-terima-aset.pdf', $detailBast->id) }}"
                    target="_blank" rel="noopener"
                    class="inline-flex items-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                    Lihat Dokumen BAST
                </a>

                @unless($detailBast->selesai)
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Cetak dokumennya, tandatangani keempat pihak, lalu tekan <strong>Tandai Selesai</strong>
                        di menu Opsi untuk melepas aset dari karyawan.
                    </p>
                @endunless
            </div>
        </div>
    </aside>
</div>
