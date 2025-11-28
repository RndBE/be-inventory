@if ($errors->any())
    <div class="text-red-500 mb-4">
        <ul>
            @foreach ($errors->all() as $error)
                <li>- {{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<div class="bg-gray-100 p-6">
    <!-- Header Laporan -->
    <div class="bg-white shadow rounded-lg p-6 border mb-6">
        <!-- Header -->
        <div class="flex flex-wrap justify-between items-center border-b pb-3 gap-3">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Laporan QC Bahan Masuk</h1>
                <p class="text-sm text-gray-600">
                    Tanggal: {{ now()->setTimezone('Asia/Jakarta')->format('d/m/Y H:i:s') }}
                </p>
            </div>
            <img src="{{ asset('images/logo_be2.png') }}" alt="Logo Be" class="h-10 w-auto">
        </div>

        <!-- Informasi Laporan -->
        <div class="mt-4 text-sm grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-1">
            <div class="space-y-1 text-sm">
                <div class="flex">
                    <span class="w-40 font-semibold">Nomor Laporan</span>
                    <span class="pr-1">:</span>
                    <span>QC-XXXXXXXXXX-BM</span>
                </div>
                <div class="flex">
                    <span class="w-40 font-semibold">Petugas QC</span>
                    <span class="pr-1">:</span>
                    <span>{{ optional($petugasList->firstWhere('id', $selected_petugas_id))->name ?? '-' }}</span>
                </div>
                <div class="flex">
                    <span class="w-40 font-semibold">Total Item</span>
                    <span class="pr-1">:</span>
                    <span>{{ count($selectedBahanList) }}</span>
                </div>
            </div>
        </div>
    </div>


    <!-- Tabel Preview -->
    <div class="bg-white shadow rounded-lg p-6 border">
        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-200 text-gray-700">
                        <th class="border px-2 py-1">No</th>
                        <th class="border px-2 py-1">Nama Bahan</th>
                        <th class="border px-2 py-1">Supplier</th>
                        <th class="border px-2 py-1">No Invoice</th>
                        <th class="border px-2 py-1">Stok Lama</th>
                        <th class="border px-2 py-1">Jml. Pengajuan</th>
                        <th class="border px-2 py-1">Jml. Pembelian</th>
                        <th class="border px-2 py-1">Jml. Diterima</th>
                        <th class="border px-2 py-1">Baik</th>
                        <th class="border px-2 py-1">Rusak</th>
                        <th class="border px-2 py-1">Retur</th>
                        <th class="border px-2 py-1">Harga / Unit</th>
                        <th class="border px-2 py-1">Total Harga</th>
                        <th class="border px-2 py-1">Status QC</th>
                        <th class="border px-2 py-1">Catatan</th>
                    </tr>
                </thead>
                @php
                    $totalPengajuan = collect($selectedBahanList)->where('is_selected', true)->sum(function ($bahan) {
                        return (float) ($bahan['jumlah_pembelian'] ?? 0) * (float) ($bahan['unit_price'] ?? 0);
                    });
                    $totalDiterima = collect($selectedBahanList)->where('is_selected', true)->sum(function ($bahan) {
                        return (float) ($bahan['jumlah_diterima'] ?? 0) * (float) ($bahan['unit_price'] ?? 0);
                    });
                    $totalBaik = collect($selectedBahanList)->where('is_selected', true)->sum(function ($bahan) {
                        return (float) ($bahan['fisik_baik'] ?? 0) * (float) ($bahan['unit_price'] ?? 0);
                    });
                    $totalRusak = collect($selectedBahanList)->where('is_selected', true)->sum(function ($bahan) {
                        return (float) ($bahan['fisik_rusak'] ?? 0) * (float) ($bahan['unit_price'] ?? 0);
                    });
                    $totalRetur = collect($selectedBahanList)->where('is_selected', true)->sum(function ($bahan) {
                        return (float) ($bahan['fisik_retur'] ?? 0) * (float) ($bahan['unit_price'] ?? 0);
                    });
                @endphp
                <tbody>
                    @foreach (collect($selectedBahanList)->where('is_selected', true)->values() as $index => $bahan)
                        <tr>
                            <td class="border px-2 py-1 text-center">{{ $index + 1 }}</td>
                            <td class="border px-2 py-1">{{ $bahan['nama_bahan'] }}</td>
                            <td class="border px-2 py-1">
                                {{ optional($supplierList->firstWhere('id', $bahan['supplier_id']))->nama }}
                            </td>
                            <td class="border px-2 py-1">{{ $bahan['no_invoice'] }}</td>
                            <td class="border px-2 py-1 text-right">{{ number_format((float) ($bahan['stok_lama'] ?? 0), 2, ',', '.') }}</td>
                            <td class="border px-2 py-1 text-right">{{ number_format((float) ($bahan['jumlah_pengajuan'] ?? 0), 2, ',', '.') }}</td>
                            <td class="border px-2 py-1 text-right">{{ number_format((float) ($bahan['jumlah_pembelian'] ?? 0), 2, ',', '.') }}</td>
                            <td class="border px-2 py-1 text-right">{{ number_format((float) ($bahan['jumlah_diterima'] ?? 0), 2, ',', '.') }}</td>
                            <td class="border px-2 py-1 text-right">{{ number_format((float) ($bahan['fisik_baik'] ?? 0), 2, ',', '.') }}</td>
                            <td class="border px-2 py-1 text-right">{{ number_format((float) ($bahan['fisik_rusak'] ?? 0), 2, ',', '.') }}</td>
                            <td class="border px-2 py-1 text-right">{{ number_format((float) ($bahan['fisik_retur'] ?? 0), 2, ',', '.') }}</td>
                            <td class="border px-2 py-1 text-right">{{ number_format((float) ($bahan['unit_price'] ?? 0), 2, ',', '.') }}</td>
                            {{-- Total harga per baris --}}
                            <td class="border px-2 py-1 text-right">
                                @php
                                    $totalHarga = (float) ($bahan['fisik_baik'] ?? 0) * (float) ($bahan['unit_price'] ?? 0);
                                @endphp
                                Rp {{ number_format($totalHarga, 2, ',', '.') }}
                            </td>

                            <td class="border px-2 py-1 text-center">
                                {{ ucfirst(str_replace('_', ' ', $bahan['statusQc'])) }}
                            </td>
                            <td class="border px-2 py-1">{{ $bahan['notes'] }}</td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr class="font-semibold bg-gray-100">
                        <td class="border px-2 py-1 text-center" colspan="6">Total Harga</td>
                        <td class="border px-2 py-1 text-right">{{ number_format($totalPengajuan, 2, ',', '.') }}</td>
                        <td class="border px-2 py-1 text-right">{{ number_format($totalDiterima, 2, ',', '.') }}</td>
                        <td class="border px-2 py-1 text-right">{{ number_format($totalBaik, 2, ',', '.') }}</td>
                        <td class="border px-2 py-1 text-right">{{ number_format($totalRusak, 2, ',', '.') }}</td>
                        <td class="border px-2 py-1 text-right">{{ number_format($totalRetur, 2, ',', '.') }}</td>
                        <td class="border px-2 py-1 text-right"></td>
                        <td class="border px-2 py-1 text-right"></td>
                        <td class="border px-2 py-1 text-right"></td>
                        <td class="border px-2 py-1 text-right"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Dokumentasi Foto -->
    <div class="bg-white shadow rounded-lg p-6 border mt-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Dokumentasi / Foto Bahan</h3>

        @php
            // Filter bahan yang ada dokumentasi
            $bahanAdaDokumentasi = collect($selectedBahanList)->filter(function($bahan) use ($gambarPerBahan) {
                $bahanId = $bahan['bahan_id'];
                return !empty($gambarPerBahan[$bahanId] ?? []);
            });
        @endphp

        @if ($bahanAdaDokumentasi->isEmpty())
            <p class="text-gray-500 italic">Tidak ada dokumentasi bahan.</p>
        @else
            @foreach ($bahanAdaDokumentasi as $bahan)
                @php
                    $bahanId = $bahan['bahan_id'];
                    $files   = $gambarPerBahan[$bahanId] ?? [];
                @endphp

                <div class="mb-8">
                    <p class="font-medium text-gray-700 mb-3">{{ $bahan['nama_bahan'] }}</p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        @foreach ($files as $file)
                            <div class="border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition">
                                <img src="{{ $file->temporaryUrl() }}"
                                    alt="Foto {{ $bahan['nama_bahan'] }}"
                                    class="w-full h-100 object-cover">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <!-- Ringkasan -->
    <div class="bg-white shadow rounded-lg p-6 border mt-6"
    x-data="{ isVerified: @entangle('is_verified').defer, showConfirm: false }">
        <div class="mb-4">
            <label class="inline-flex items-center cursor-pointer">
                <input type="checkbox"
                    wire:model.defer="is_verified"
                    class="form-checkbox h-5 w-5 text-black-600"
                    x-model="isVerified">
                <span class="ml-2 font-semibold text-black-700">
                    Saya menyatakan bahwa semua data QC bahan masuk telah diperiksa dan valid.
                </span>
            </label>
            @error('is_verified')
                <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="keterangan_qc" class="block font-medium mb-1">Catatan Tambahan (opsional)</label>
            <textarea wire:model.defer="keterangan_qc" id="keterangan_qc" rows="3"
                class="form-control w-full resize-none border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
                placeholder="Tulis jika ada catatan tambahan..."></textarea>
            @error('keterangan_qc')
                <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- Tanda Tangan -->
        <div class="bg-white shadow rounded-lg p-6 border mt-6" x-show="isVerified" x-cloak>
            <div class="grid grid-cols-2 gap-6 mt-6">
                <div class="text-center">
                    <p class="font-semibold mb-6">Petugas Input</p>
                    @php
                        $user = auth()->user();
                        $ttdInput = $user->tanda_tangan ?? null;
                    @endphp
                    @if ($ttdInput)
                        <img src="{{ asset('storage/' . $ttdInput) }}" alt="Tanda Tangan Petugas Input" class="mx-auto h-24 object-contain" />
                    @else
                        <div class="h-24 flex items-center justify-center text-gray-400 italic">Belum ada tanda tangan</div>
                    @endif
                    <p>{{ $user->name ?? '-' }}</p>
                </div>

                <div class="text-center">
                    <p class="font-semibold mb-6">Petugas QC</p>
                    @php
                        $petugasQC = optional($petugasList->firstWhere('id', $selected_petugas_id));
                        $ttdQC = $petugasQC->tanda_tangan ?? null;
                    @endphp
                    @if ($ttdQC)
                        <img src="{{ asset('storage/' . $ttdQC) }}" alt="Tanda Tangan Petugas QC" class="mx-auto h-24 object-contain" />
                    @else
                        <div class="h-24 flex items-center justify-center text-gray-400 italic">Belum ada tanda tangan</div>
                    @endif
                    <p>{{ $petugasQC->name ?? '-' }}</p>
                </div>
            </div>
        </div>

        <!-- Tombol Simpan -->
        <div class="mt-4 text-right">
            <button
                @click="if (isVerified) showConfirm = true"
                :class="isVerified ? 'bg-theme-1 hover:bg-theme-1/90' : 'bg-gray-400 cursor-not-allowed'"
                class="px-4 py-2 text-white rounded"
                :disabled="!isVerified"
            >
                Simpan
            </button>
        </div>

        <!-- Modal Konfirmasi -->
       <div
        x-show="showConfirm"
        @click.self="showConfirm = false"
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
        x-cloak
    >
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
                <div class="text-center">
                    <i data-feather="alert-triangle" class="w-16 h-16 text-yellow-500 mx-auto"></i>
                    <div class="text-2xl font-semibold mt-4">Yakin simpan data?</div>

                    <!-- Peringatan utama -->
                    <div class="text-red-600 font-semibold mt-2">
                        ⚠️ Data yang disimpan <u>tidak dapat diubah kembali</u>.
                    </div>

                    <!-- Peringatan tambahan -->
                    <div class="text-gray-600 mt-2">
                        Pastikan semua data sudah benar dan semua lampiran telah diunggah.
                    </div>

                    <!-- Peringatan konfirmasi -->
                    <div class="text-gray-600 italic mt-1">
                        Proses ini bersifat final dan tidak bisa dibatalkan.
                    </div>
                </div>
                <div class="mt-6 flex justify-center space-x-3">
                    <button
                        @click="showConfirm = false"
                        class="px-4 py-2 border rounded text-gray-700 hover:bg-gray-100"
                    >
                        Batal
                    </button>
                    <button
                        @click="$wire.saveQCBahanMasuk(); showConfirm = false"
                        class="px-4 py-2 bg-theme-1 text-white rounded hover:bg-theme-1/90"
                    >
                        Ya, Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
