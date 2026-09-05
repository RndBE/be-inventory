<div>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <div class="sm:flex sm:justify-between sm:items-center mb-2">
            <div class="mb-4 sm:mb-0">
                <h6 class="text-2xl text-gray-800 dark:text-gray-100 font-bold">Audit Perubahan Data</h6>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Setiap koreksi data yang dijalankan lewat sistem, per kolom. Baris di sini tidak bisa diubah maupun dihapus.
                </p>
            </div>

            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <ul class="flex flex-wrap -m-1">
                    <li class="m-1">
                        @include('livewire.searchdata', ['debounceMs' => 400])
                    </li>
                    <li class="m-1">
                        @include('livewire.dataperpage')
                    </li>
                </ul>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
            <div>
                <label for="filterJenis" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jenis pengajuan</label>
                <select wire:model.live="filterJenis" id="filterJenis"
                    class="mt-1 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm dark:text-gray-400 dark:bg-gray-800">
                    <option value="">Semua jenis</option>
                    @foreach ($daftarJenis as $jenis)
                        <option value="{{ $jenis }}">{{ $jenis }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="dariTanggal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dari tanggal</label>
                <input type="date" wire:model.live="dariTanggal" id="dariTanggal"
                    class="mt-1 block w-full rounded-md border-0 py-1.5 px-3 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm dark:text-gray-400 dark:bg-gray-800">
            </div>
            <div>
                <label for="sampaiTanggal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sampai tanggal</label>
                <input type="date" wire:model.live="sampaiTanggal" id="sampaiTanggal"
                    class="mt-1 block w-full rounded-md border-0 py-1.5 px-3 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm dark:text-gray-400 dark:bg-gray-800">
            </div>
        </div>

        <div class="relative overflow-x-auto pt-2">
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">Perubahan</th>
                            <th scope="col" class="px-6 py-3">Lama &rarr; Baru</th>
                            <th scope="col" class="px-6 py-3">Alasan</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{--
                            Tiga kolom, dengan waktu, tiket, dan pelakunya diangkat ke
                            baris kepala tiap kelompok.

                            Sebelumnya keempatnya jadi kolom sendiri yang digabung
                            rowspan. Benar, tapi kelompok berisi tiga baris menyisakan
                            dua sel kosong tinggi di kiri dan kanan — mata membaca ruang
                            kosong itu sebagai "tidak ada isinya", padahal isinya ada di
                            sel yang sudah lewat. Diangkat jadi baris kepala, keterangan
                            yang berlaku untuk satu kelompok berdiri di atas kelompoknya,
                            dan kolom yang tersisa dapat lebar penuh.

                            Penggabungannya murni tampilan. Tiap perubahan tetap satu
                            baris audit tersendiri di database.
                        --}}
                        @php $kunciSebelumnya = null; @endphp

                        @forelse ($auditList as $audit)
                            @php
                                $kunci = $kunciKelompok[$audit->id] ?? 'baris-' . $audit->id;
                                $awalKelompok = $kunci !== $kunciSebelumnya;
                                $tinggi = $tinggiKelompok[$kunci] ?? 1;
                                $kunciSebelumnya = $kunci;

                                $alasan = $runAlasan[$audit->id] ?? ['awal' => true, 'tinggi' => 1];
                            @endphp

                            @if ($awalKelompok)
                                <tr class="bg-gray-100/80 dark:bg-gray-700/60 border-t-2 border-gray-200 dark:border-gray-600">
                                    <td colspan="3" class="px-6 py-2.5">
                                        <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-1">
                                            <div class="flex flex-wrap items-center gap-x-2 text-sm">
                                                <span class="font-medium text-gray-800 dark:text-gray-200">
                                                    {{ optional($audit->created_at)->format('d/m/Y H:i') ?? '-' }}
                                                </span>

                                                @if ($audit->perbaikanData)
                                                    <span class="text-gray-300" aria-hidden="true">&middot;</span>
                                                    <span class="text-indigo-600">Tiket {{ $audit->perbaikanData->kode_pengajuan }}</span>
                                                @else
                                                    <span class="text-gray-300" aria-hidden="true">&middot;</span>
                                                    <span class="text-amber-700">Tanpa tiket</span>
                                                @endif

                                                @if ($tinggi > 1)
                                                    <span class="text-gray-300" aria-hidden="true">&middot;</span>
                                                    <span class="text-gray-500">{{ $tinggi }} perubahan</span>
                                                @endif
                                            </div>

                                            {{--
                                                Satu orang ditulis sekali. Nama yang sama dengan dua
                                                label berbeda membuat blok yang terbaca seragam di
                                                seluruh halaman, dan menenggelamkan hal yang justru
                                                paling perlu terlihat saat diperiksa.

                                                Dipicu `disetujui_sendiri` yang lahir dari
                                                perbandingan id, bukan dari nama yang kebetulan
                                                sama: dua karyawan bisa bernama sama, dan
                                                menyatakan mereka satu orang adalah tuduhan yang
                                                datanya tidak menyediakan.
                                            --}}
                                            <div class="flex flex-wrap items-center gap-x-2 text-sm">
                                                @if ($audit->disetujui_sendiri)
                                                    <span class="text-gray-800 dark:text-gray-200">
                                                        {{ $audit->namaPengaju() ?? $audit->approver->name ?? '-' }}
                                                    </span>
                                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-200">
                                                        Mengajukan &amp; menyetujui sendiri
                                                    </span>
                                                @else
                                                    <span class="text-xs uppercase tracking-wide text-gray-400">Diajukan</span>
                                                    <span class="text-gray-800 dark:text-gray-200">{{ $audit->namaPengaju() ?? '-' }}</span>
                                                    <span class="text-gray-300" aria-hidden="true">&middot;</span>
                                                    <span class="text-xs uppercase tracking-wide text-gray-400">Disetujui</span>
                                                    <span class="text-gray-800 dark:text-gray-200">{{ $audit->approver->name ?? '-' }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif

                            <tr class="bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700 hover:bg-indigo-50/40 dark:hover:bg-gray-600">
                                <td class="px-6 py-4 align-top">
                                    <div class="font-medium text-gray-800 dark:text-gray-200">
                                        {{ $audit->labelModul() }} #{{ $audit->baris_target_id ?? $audit->modul_id }}
                                    </div>
                                    <div class="text-gray-700 dark:text-gray-300">{{ $audit->labelField() }}</div>
                                    {{-- Nama teknisnya tetap ada: yang mengerjakan koreksinya
                                         di database mencari tabel dan kolomnya, bukan
                                         labelnya. --}}
                                    <div class="mt-0.5 text-xs text-gray-500">
                                        {{ $audit->tabel_target }} &middot; {{ $audit->field }}
                                    </div>
                                </td>

                                {{--
                                    Nilai ditampilkan mentah, tanpa number_format. Kalau
                                    salah ketiknya soal titik atau nol berlebih, memformat
                                    ulang justru membuat dua angka berbeda tampil sama.
                                --}}
                                <td class="px-6 py-4 align-top whitespace-nowrap">
                                    <div class="flex flex-wrap items-baseline gap-1.5">
                                        <span class="text-red-700 line-through break-all">{{ $audit->nilai_lama ?? '(kosong)' }}</span>
                                        <span class="text-gray-400" aria-hidden="true">&rarr;</span>
                                        <span class="text-green-700 font-medium break-all">{{ $audit->nilai_baru ?? '(kosong)' }}</span>
                                    </div>
                                </td>

                                @if ($alasan['awal'])
                                    <td rowspan="{{ $alasan['tinggi'] }}" class="px-6 py-4 align-top max-w-sm">
                                        <div class="whitespace-pre-line break-words">{{ $audit->alasan }}</div>

                                        @if ($alasan['tinggi'] > 1)
                                            <div class="mt-1 text-xs text-gray-400">
                                                Berlaku untuk {{ $alasan['tinggi'] }} perubahan di atas
                                            </div>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center px-6 py-4">Belum ada perubahan data yang tercatat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $auditList->links() }}
            </div>
        </div>
    </div>
</div>
