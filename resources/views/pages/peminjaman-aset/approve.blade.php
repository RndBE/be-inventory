@php
    $labelTahap = [
        'leader' => 'Leader',
        'manager' => 'Manager',
        'ga' => 'General Affair',
        'hrd' => 'HRD (Mengetahui)',
    ][$tahapApprove] ?? 'Approval';
@endphp
{{-- mengirim: mengunci tombol Simpan sejak form dikirim. Tanpa ini, klik kedua
     mengirim keputusan approval dua kali. --}}
<div x-data="{ isOpen: @entangle('isApproveModalOpen'), keputusan: '', mengirim: false }"
    x-show="isOpen"
    class="fixed inset-0 flex items-center justify-center z-50 w-full h-full"
    style="background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px);"
    @keydown.escape.window="isOpen = false; $wire.closeModal();"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100">

    <div class="relative p-4 w-full max-w-md max-h-full" x-show="isOpen"
        @click.outside="isOpen = false; $wire.closeModal();">
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Approval {{ $labelTahap }}
                </h3>
                <button wire:click="closeModal" type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>

            <div class="pt-0 p-5">
                <p class="mt-4 mb-4 text-sm text-gray-500 dark:text-gray-300">
                    Pengajuan <span class="font-semibold text-gray-900 dark:text-white">{{ $kode_peminjaman }}</span>
                </p>

                {{-- Dipasang pada submit, bukan click: event ini tidak menyala kalau
                     validasi HTML gagal, jadi tombolnya tidak ikut mati saat form
                     belum lengkap. --}}
                <form class="space-y-6" method="POST" x-on:submit="mengirim = true"
                    action="{{ route('peminjaman-aset.approval', ['tahap' => $tahapApprove, 'id' => (int)$id_peminjaman]) }}">
                    @csrf
                    <div>
                        <label for="status" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Keputusan</label>
                        <select name="status" id="status" required x-model="keputusan"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                            <option value="">-- Pilih Keputusan --</option>
                            <option value="Belum disetujui">Belum disetujui (hanya catat kendala)</option>
                            <option value="Disetujui">Disetujui</option>
                            <option value="Ditolak">Ditolak</option>
                        </select>
                    </div>

                    <div x-cloak x-show="keputusan === 'Belum disetujui' || keputusan === 'Ditolak'" class="min-h-[8.25rem]">
                        <div x-show="keputusan === 'Belum disetujui'">
                            <label for="kendala" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Kendala</label>
                            <textarea name="kendala" id="kendala" rows="3" maxlength="500"
                                :disabled="keputusan !== 'Belum disetujui'"
                                :required="keputusan === 'Belum disetujui'"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white"
                                placeholder="Contoh: menunggu konfirmasi kepala proyek, aset masih dipakai tim lain sampai minggu depan">{{ $kendalaTahap }}</textarea>
                        </div>

                        <div x-show="keputusan === 'Ditolak'">
                            <label for="catatan" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Catatan</label>
                            <textarea name="catatan" id="catatan" rows="3"
                                :disabled="keputusan !== 'Ditolak'"
                                :required="keputusan === 'Ditolak'"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white"
                                placeholder="Wajib diisi kalau ditolak"></textarea>
                        </div>
                    </div>

                    <button type="submit" x-bind:disabled="mengirim"
                        class="w-full text-white bg-indigo-600 hover:bg-indigo-800 focus:ring-4 focus:outline-none focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500">
                        <span x-show="!mengirim">Simpan</span>
                        <span x-show="mengirim" x-cloak>Menyimpan…</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
