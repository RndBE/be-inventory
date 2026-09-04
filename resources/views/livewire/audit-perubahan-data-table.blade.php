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
                            <th scope="col" class="px-6 py-3">Waktu</th>
                            <th scope="col" class="px-6 py-3">Modul &amp; Record</th>
                            <th scope="col" class="px-6 py-3">Kolom</th>
                            <th scope="col" class="px-6 py-3">Lama &rarr; Baru</th>
                            <th scope="col" class="px-6 py-3">Alasan</th>
                            <th scope="col" class="px-6 py-3">Pengaju</th>
                            <th scope="col" class="px-6 py-3">Approver</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($auditList as $audit)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ optional($audit->created_at)->format('d/m/Y H:i') ?? '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-800 dark:text-gray-200">{{ $audit->labelModul() }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ $audit->tabel_target }} #{{ $audit->baris_target_id ?? $audit->modul_id }}
                                    </div>
                                    @if ($audit->perbaikanData)
                                        <div class="text-xs text-indigo-600">
                                            Tiket {{ $audit->perbaikanData->kode_pengajuan }}
                                        </div>
                                    @else
                                        <div class="text-xs text-amber-700">Tanpa tiket</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div>{{ $audit->labelField() }}</div>
                                    <div class="text-xs text-gray-500">{{ $audit->field }}</div>
                                </td>
                                {{--
                                    Nilai ditampilkan mentah, tanpa number_format. Kalau
                                    salah ketiknya soal titik atau nol berlebih, memformat
                                    ulang justru membuat dua angka berbeda tampil sama.
                                --}}
                                <td class="px-6 py-4">
                                    <div class="text-red-700 line-through break-all">{{ $audit->nilai_lama ?? '(kosong)' }}</div>
                                    <div class="text-green-700 font-medium break-all">{{ $audit->nilai_baru ?? '(kosong)' }}</div>
                                </td>
                                <td class="px-6 py-4 max-w-xs">
                                    <div class="whitespace-pre-line break-words">{{ $audit->alasan }}</div>
                                </td>
                                <td class="px-6 py-4">{{ $audit->pengaju->name ?? '-' }}</td>
                                <td class="px-6 py-4">
                                    {{ $audit->approver->name ?? '-' }}
                                    @if ($audit->disetujui_sendiri)
                                        {{-- Pengaju dan approver orang yang sama. Tidak
                                             dilarang, tapi harus terlihat saat diperiksa. --}}
                                        <span class="block mt-1 text-xs font-medium text-amber-700">
                                            Diajukan &amp; disetujui orang yang sama
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center px-6 py-4">Belum ada perubahan data yang tercatat.</td>
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
