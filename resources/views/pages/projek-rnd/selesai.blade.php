{{-- Modal Pilihan Status Akhir Proyek --}}
<div id="pilihan-status-modal" tabindex="-1"
    class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-full max-h-full"
    style="background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px);">
    <div class="relative p-4 w-full max-w-lg max-h-full">
        <div class="relative bg-white rounded-2xl shadow-xl">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-indigo-100">
                        <svg class="w-5 h-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-800">Simpan & Selesaikan Proyek</h3>
                        <p class="text-xs text-gray-500">{{ $projek_rnd->kode_projek_rnd }} — Pilih status akhir proyek</p>
                    </div>
                </div>
                <button type="button" onclick="tutupPilihanStatusModal()"
                    class="text-gray-400 bg-transparent hover:bg-gray-100 hover:text-gray-900 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center">
                    <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Tutup</span>
                </button>
            </div>

            <div class="px-6 py-5">
                {{-- Step: Pilih Status --}}
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Pilih Status Akhir</p>
                <div class="grid grid-cols-3 gap-2 mb-5">
                    {{-- Tombol: Selesai --}}
                    <button type="button"
                        class="pilihan-status-btn border-2 border-indigo-600 text-indigo-600 bg-indigo-50 rounded-xl px-3 py-3 text-sm font-medium flex flex-col items-center gap-2 transition-all duration-150 hover:shadow-md"
                        data-target="selesai">
                        <span class="w-8 h-8 flex items-center justify-center rounded-full bg-green-100">
                            <svg class="w-4 h-4 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                        Selesai
                    </button>

                    {{-- Tombol: Selesai Belum Laku --}}
                    <button type="button"
                        class="pilihan-status-btn border-2 border-gray-200 text-gray-500 bg-white rounded-xl px-3 py-3 text-sm font-medium flex flex-col items-center gap-2 transition-all duration-150 hover:border-yellow-400 hover:text-yellow-600 hover:bg-yellow-50 hover:shadow-md"
                        data-target="tidak-laku">
                        <span class="w-8 h-8 flex items-center justify-center rounded-full bg-yellow-100">
                            <svg class="w-4 h-4 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                        </span>
                        <span class="text-center leading-tight">Selesai Tapi<br>Belum Laku</span>
                    </button>

                    {{-- Tombol: Dihentikan --}}
                    <button type="button"
                        class="pilihan-status-btn border-2 border-gray-200 text-gray-500 bg-white rounded-xl px-3 py-3 text-sm font-medium flex flex-col items-center gap-2 transition-all duration-150 hover:border-red-400 hover:text-red-600 hover:bg-red-50 hover:shadow-md"
                        data-target="dihentikan">
                        <span class="w-8 h-8 flex items-center justify-center rounded-full bg-red-100">
                            <svg class="w-4 h-4 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                        </span>
                        Dihentikan
                    </button>
                </div>

                {{-- Panel: Selesai --}}
                <div id="panel-selesai" class="pilihan-status-panel">
                    <form action="{{ route('projek-rnd.updateStatus', $projek_rnd->id) }}" method="POST" id="form-simpan-selesai">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="selesai">
                        <input type="hidden" name="simpan_form" value="1">
                        <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                            <div class="flex items-center gap-2 mb-3">
                                <svg class="w-4 h-4 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <p class="text-sm font-semibold text-green-700">Proyek Selesai</p>
                            </div>
                            <p class="text-xs text-green-600 mb-3">Proyek dinyatakan selesai dan berhasil. Data akan disimpan dan status diperbarui.</p>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Deskripsi / Keterangan <sup class="text-red-500">*</sup>
                            </label>
                            <textarea name="keterangan_status" rows="3" required
                                placeholder="Tuliskan catatan atau keterangan penyelesaian proyek..."
                                class="w-full rounded-lg border border-green-300 bg-white text-sm text-gray-800 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-400 placeholder:text-gray-400 resize-none"></textarea>
                        </div>
                        <div class="flex justify-end gap-2 mt-4">
                            <button type="button" onclick="tutupPilihanStatusModal()"
                                class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Batal
                            </button>
                            <button type="submit"
                                class="px-5 py-2 text-sm font-semibold text-white bg-green-600 rounded-lg hover:bg-green-700 flex items-center gap-1.5">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Konfirmasi Selesai
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Panel: Selesai Belum Laku --}}
                <div id="panel-tidak-laku" class="pilihan-status-panel hidden">
                    <form action="{{ route('projek-rnd.updateStatus', $projek_rnd->id) }}" method="POST" id="form-simpan-tidak-laku">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="selesai_tidak_laku">
                        <input type="hidden" name="simpan_form" value="1">
                        <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4">
                            <div class="flex items-center gap-2 mb-3">
                                <svg class="w-4 h-4 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                                <p class="text-sm font-semibold text-yellow-700">Selesai Tapi Belum Laku</p>
                            </div>
                            <p class="text-xs text-yellow-700 mb-3">Proyek selesai namun produk/riset tidak berhasil dipasarkan atau tidak dapat dilanjutkan ke produksi.</p>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Deskripsi / Keterangan <sup class="text-red-500">*</sup>
                            </label>
                            <textarea name="keterangan_status" rows="3" required
                                placeholder="Tuliskan alasan atau keterangan mengapa produk belum laku..."
                                class="w-full rounded-lg border border-yellow-300 bg-white text-sm text-gray-800 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400 placeholder:text-gray-400 resize-none"></textarea>
                        </div>
                        <div class="flex justify-end gap-2 mt-4">
                            <button type="button" onclick="tutupPilihanStatusModal()"
                                class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Batal
                            </button>
                            <button type="submit"
                                class="px-5 py-2 text-sm font-semibold text-white bg-yellow-600 rounded-lg hover:bg-yellow-700 flex items-center gap-1.5">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Konfirmasi
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Panel: Dihentikan --}}
                <div id="panel-dihentikan" class="pilihan-status-panel hidden">
                    <form action="{{ route('projek-rnd.updateStatus', $projek_rnd->id) }}" method="POST" id="form-simpan-dihentikan">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="batal">
                        <input type="hidden" name="simpan_form" value="1">
                        <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                            <div class="flex items-center gap-2 mb-3">
                                <svg class="w-4 h-4 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                                <p class="text-sm font-semibold text-red-700">Proyek Dihentikan</p>
                            </div>
                            <p class="text-xs text-red-600 mb-3">Proyek akan dihentikan dan status berubah menjadi <strong>Tidak Dilanjutkan</strong>. Aksi ini tidak dapat dibatalkan.</p>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Deskripsi / Keterangan <sup class="text-red-500">*</sup>
                            </label>
                            <textarea name="keterangan_status" rows="3" required
                                placeholder="Tuliskan alasan penghentian proyek..."
                                class="w-full rounded-lg border border-red-300 bg-white text-sm text-gray-800 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-400 placeholder:text-gray-400 resize-none"></textarea>
                        </div>
                        <div class="flex justify-end gap-2 mt-4">
                            <button type="button" onclick="tutupPilihanStatusModal()"
                                class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Batal
                            </button>
                            <button type="submit"
                                class="px-5 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 flex items-center gap-1.5">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                                Hentikan Proyek
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
