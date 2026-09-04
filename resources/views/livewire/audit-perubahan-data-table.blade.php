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
                            <th scope="col" class="px-6 py-3">Waktu &amp; Tiket</th>
                            <th scope="col" class="px-6 py-3">Perubahan</th>
                            <th scope="col" class="px-6 py-3">Lama &rarr; Baru</th>
                            <th scope="col" class="px-6 py-3">Alasan</th>
                            <th scope="col" class="px-6 py-3">Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{--
                            Baris yang lahir dari satu keputusan digabung: waktu, tiket,
                            dan pelakunya ditulis sekali dengan rowspan, dan yang berulang
                            ke bawah hanya yang memang berbeda. Alasan ikut digabung
                            selama seluruh kelompok beralasan sama persis.

                            Penggabungannya murni tampilan. Tiap perubahan tetap satu
                            baris audit tersendiri di database, dengan nilai lama dan
                            barunya masing-masing.

                            Lima kolom, bukan tujuh. Modul, nomor record, dan nama kolom
                            menjawab satu pertanyaan — apa yang dikoreksi — jadi ketiganya
                            satu sel. Begitu juga pengaju dan approver: masing-masing satu
                            nama, sering nama yang sama, dan justru kesamaan itu yang harus
                            terbaca sekaligus. Tabel yang lebih sempit dari layar bisa
                            disusuri ke bawah tanpa digeser mendatar, dan menyusuri satu
                            kolom ke bawah itulah cara halaman ini dipakai.
                        --}}
                        @php
                            $kunciSebelumnya = null;
                            $nomorKelompok = 0;
                        @endphp

                        @forelse ($auditList as $audit)
                            @php
                                $kunci = $kunciKelompok[$audit->id] ?? 'baris-' . $audit->id;
                                $awalKelompok = $kunci !== $kunciSebelumnya;
                                $tinggi = $tinggiKelompok[$kunci] ?? 1;
                                $alasanBersama = $alasanSeragam[$kunci] ?? null;
                                $kunciSebelumnya = $kunci;

                                // Naik hanya di awal kelompok, jadi seluruh baris satu
                                // kelompok berlatar sama. Warnanya yang membedakan
                                // kelompok bersebelahan; garis tipis saja tidak cukup
                                // begitu satu kelompok berisi tiga baris atau lebih.
                                $nomorKelompok += $awalKelompok ? 1 : 0;
                                $latar = $nomorKelompok % 2 === 0
                                    ? 'bg-gray-50 dark:bg-gray-800/60'
                                    : 'bg-white dark:bg-gray-800';
                            @endphp

                            <tr class="{{ $latar }} hover:bg-indigo-50/40 dark:hover:bg-gray-600 {{ $awalKelompok ? 'border-t-2 border-gray-200 dark:border-gray-600' : '' }}">
                                @if ($awalKelompok)
                                    <td rowspan="{{ $tinggi }}" class="px-6 py-4 align-top whitespace-nowrap">
                                        <div>{{ optional($audit->created_at)->format('d/m/Y H:i') ?? '-' }}</div>

                                        @if ($audit->perbaikanData)
                                            <div class="mt-1 text-xs text-indigo-600">
                                                Tiket {{ $audit->perbaikanData->kode_pengajuan }}
                                            </div>
                                        @else
                                            <div class="mt-1 text-xs text-amber-700">Tanpa tiket</div>
                                        @endif

                                        @if ($tinggi > 1)
                                            <div class="mt-1 text-xs text-gray-500">{{ $tinggi }} perubahan</div>
                                        @endif
                                    </td>
                                @endif

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
                                <td class="px-6 py-4 align-top">
                                    <div class="flex flex-wrap items-baseline gap-1.5">
                                        <span class="text-red-700 line-through break-all">{{ $audit->nilai_lama ?? '(kosong)' }}</span>
                                        <span class="text-gray-400" aria-hidden="true">&rarr;</span>
                                        <span class="text-green-700 font-medium break-all">{{ $audit->nilai_baru ?? '(kosong)' }}</span>
                                    </div>
                                </td>

                                @if ($alasanBersama === null)
                                    <td class="px-6 py-4 align-top max-w-xs">
                                        <div class="whitespace-pre-line break-words">{{ $audit->alasan }}</div>
                                    </td>
                                @elseif ($awalKelompok)
                                    <td rowspan="{{ $tinggi }}" class="px-6 py-4 align-top max-w-xs">
                                        <div class="whitespace-pre-line break-words">{{ $alasanBersama }}</div>
                                    </td>
                                @endif

                                @if ($awalKelompok)
                                    <td rowspan="{{ $tinggi }}" class="px-6 py-4 align-top">
                                        {{--
                                            Satu orang ditulis sekali. Menuliskan nama yang
                                            sama dua kali dengan label berbeda membuat sel
                                            ini jadi blok teks yang seragam di seluruh
                                            halaman, dan justru menenggelamkan hal yang
                                            paling perlu terlihat: bahwa pengaju dan
                                            approvernya memang orang yang sama.

                                            Penggabungan hanya dipicu `disetujui_sendiri`,
                                            yang lahir dari perbandingan id. Nama yang
                                            kebetulan sama tidak cukup — dua karyawan bisa
                                            bernama sama, dan menyatakan mereka satu orang
                                            adalah tuduhan yang tidak dibuat datanya.
                                        --}}
                                        @if ($audit->disetujui_sendiri)
                                            <div class="font-medium text-gray-800 dark:text-gray-200">
                                                {{ $audit->namaPengaju() ?? $audit->approver->name ?? '-' }}
                                            </div>
                                            <span class="mt-1 inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-200">
                                                Mengajukan &amp; menyetujui sendiri
                                            </span>
                                        @else
                                            <div class="text-xs uppercase tracking-wide text-gray-400">Diajukan</div>
                                            <div class="text-gray-800 dark:text-gray-200">{{ $audit->namaPengaju() ?? '-' }}</div>

                                            <div class="mt-2 text-xs uppercase tracking-wide text-gray-400">Disetujui</div>
                                            <div class="text-gray-800 dark:text-gray-200">{{ $audit->approver->name ?? '-' }}</div>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center px-6 py-4">Belum ada perubahan data yang tercatat.</td>
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
