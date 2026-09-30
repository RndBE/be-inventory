<div>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <div class="sm:flex sm:justify-between sm:items-center mb-2">
            <div class="mb-4 sm:mb-0">
                <h6 class="text-2xl text-gray-800 dark:text-gray-100 font-bold">Audit Perubahan Data</h6>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Setiap koreksi data yang dijalankan lewat sistem. Klik Detail untuk melihat nilai lama, nilai baru, dan alasannya. Baris di sini tidak bisa diubah maupun dihapus.
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
                            <th scope="col" class="px-6 py-3">Tanggal</th>
                            <th scope="col" class="px-6 py-3">Tiket</th>
                            <th scope="col" class="px-6 py-3">Diajukan</th>
                            <th scope="col" class="px-6 py-3">Disetujui</th>
                            <th scope="col" class="px-6 py-3 text-right">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{--
                            Satu baris = satu pencatatan koreksi. Penggabungannya murni
                            tampilan: tiap perubahan tetap satu baris audit tersendiri
                            di database, dan semuanya terlihat di popup detail.
                        --}}
                        @forelse ($kelompokList as $kelompok)
                            <tr wire:key="audit-{{ $kelompok->kunci }}"
                                wire:click="bukaDetail(@js($kelompok->kunci))"
                                class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-indigo-50/40 dark:hover:bg-gray-600 cursor-pointer">
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-800 dark:text-gray-200">
                                    {{ optional($kelompok->created_at)->format('d/m/Y H:i') ?? '-' }}
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($kelompok->perbaikanData)
                                        <span class="text-indigo-600">{{ $kelompok->perbaikanData->kode_pengajuan }}</span>
                                    @else
                                        <span class="text-amber-700">Tanpa tiket</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-gray-800 dark:text-gray-200">
                                    {{ $kelompok->namaPengaju() ?? '-' }}
                                </td>

                                {{--
                                    Penanda persetujuan sendiri dipicu `disetujui_sendiri`
                                    yang lahir dari perbandingan id, bukan dari nama yang
                                    kebetulan sama: dua karyawan bisa bernama sama, dan
                                    menyatakan mereka satu orang adalah tuduhan yang datanya
                                    tidak menyediakan.
                                --}}
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-gray-800 dark:text-gray-200">{{ $kelompok->approver->name ?? '-' }}</span>
                                        @if ($kelompok->disetujui_sendiri)
                                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-200">
                                                Menyetujui sendiri
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <button type="button"
                                        wire:click.stop="bukaDetail(@js($kelompok->kunci))"
                                        class="inline-flex items-center gap-1.5 rounded-md bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-200 hover:bg-indigo-100 dark:bg-gray-700 dark:text-indigo-300 dark:ring-gray-600">
                                        Lihat {{ $kelompok->jumlah }} perubahan
                                    </button>
                                </td>
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
                {{ $kelompokList->links() }}
            </div>
        </div>
    </div>

    {{-- POPUP DETAIL --}}
    @if ($detail->isNotEmpty())
        @php $kepala = $detail->first(); @endphp

        <div wire:key="detail-{{ $kunciDetail }}"
            x-data="{
                isOpen: false,
                close() {
                    this.isOpen = false;
                    setTimeout(() => $wire.tutupDetail(), 200);
                }
            }"
            x-init="$nextTick(() => isOpen = true)"
            @keydown.escape.window="close()">

            <div x-show="isOpen"
                x-transition:enter="transition-opacity ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-40 bg-black bg-opacity-30"
                style="display: none"></div>

            <div x-show="isOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-4"
                @click.self="close()"
                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:p-8"
                style="display: none">

                <div role="dialog" aria-modal="true" aria-labelledby="judul-detail-audit"
                    class="w-full max-w-5xl rounded-lg bg-white shadow-xl dark:bg-gray-800">

                    <div class="flex items-start justify-between gap-4 border-b p-4 md:p-5 dark:border-gray-600">
                        <div>
                            <h3 id="judul-detail-audit" class="text-lg font-semibold text-gray-900 dark:text-white">
                                Detail Perubahan
                                @if ($kepala->perbaikanData)
                                    &middot; {{ $kepala->perbaikanData->kode_pengajuan }}
                                @endif
                            </h3>
                            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                {{ $detail->count() }} perubahan dicatat pada {{ optional($kepala->created_at)->format('d/m/Y H:i') ?? '-' }}
                            </p>
                        </div>
                        <button @click="close()" type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Tutup</span>
                        </button>
                    </div>

                    <div class="p-4 md:p-5 space-y-5">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <div class="text-xs uppercase text-gray-400">Tanggal</div>
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ optional($kepala->created_at)->format('d/m/Y H:i') ?? '-' }}
                                </div>
                            </div>
                            <div>
                                <div class="text-xs uppercase text-gray-400">Tiket</div>
                                @if ($kepala->perbaikanData)
                                    <div class="font-medium text-indigo-600">{{ $kepala->perbaikanData->kode_pengajuan }}</div>
                                    @can('lihat-perbaikan-data')
                                        <a href="{{ route('perbaikan-data.show', $kepala->perbaikan_data_id) }}"
                                            class="text-xs text-indigo-500 hover:underline">Buka tiket &rarr;</a>
                                    @endcan
                                @else
                                    <div class="font-medium text-amber-700">Tanpa tiket</div>
                                @endif
                            </div>
                            <div>
                                <div class="text-xs uppercase text-gray-400">Diajukan</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $kepala->namaPengaju() ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="text-xs uppercase text-gray-400">Disetujui</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $kepala->approver->name ?? '-' }}</div>
                                @if ($kepala->disetujui_sendiri)
                                    <span class="mt-1 inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-200">
                                        Mengajukan &amp; menyetujui sendiri
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-lg ring-1 ring-gray-200 dark:ring-gray-600">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-4 py-3">Perubahan</th>
                                        <th scope="col" class="px-4 py-3">Lama &rarr; Baru</th>
                                        <th scope="col" class="px-4 py-3">Alasan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($detail as $audit)
                                        @php $alasan = $runAlasan[$audit->id] ?? ['awal' => true, 'tinggi' => 1]; @endphp

                                        <tr class="bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700">
                                            <td class="px-4 py-3 align-top">
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
                                            <td class="px-4 py-3 align-top">
                                                <div class="flex flex-wrap items-baseline gap-1.5">
                                                    <span class="text-red-700 line-through break-all">{{ $audit->nilai_lama ?? '(kosong)' }}</span>
                                                    <span class="text-gray-400" aria-hidden="true">&rarr;</span>
                                                    <span class="text-green-700 font-medium break-all">{{ $audit->nilai_baru ?? '(kosong)' }}</span>
                                                </div>
                                            </td>

                                            @if ($alasan['awal'])
                                                <td rowspan="{{ $alasan['tinggi'] }}" class="px-4 py-3 align-top max-w-sm border-l border-gray-100 dark:border-gray-700">
                                                    <div class="whitespace-pre-line break-words">{{ $audit->alasan }}</div>

                                                    @if ($alasan['tinggi'] > 1)
                                                        <div class="mt-1 text-xs text-gray-400">
                                                            Berlaku untuk {{ $alasan['tinggi'] }} perubahan
                                                        </div>
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="flex justify-end border-t p-4 dark:border-gray-600">
                        <button @click="close()" type="button"
                            class="rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:ring-gray-600">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
