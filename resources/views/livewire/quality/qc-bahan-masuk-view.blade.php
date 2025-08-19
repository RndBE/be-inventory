<div>

    <div class="pos intro-y grid grid-cols-12 gap-5 mt-5">
        <!-- Konten yang akan dicetak -->
        <div class="intro-y col-span-12 lg:col-span-12 text-right">
            <button onclick="printDiv('print-section')" class="px-4 py-2 bg-theme-1 text-white rounded hover:bg-theme-1/90">
                Print / Preview PDF
            </button>
        </div>
        <div class="intro-y col-span-12 lg:col-span-12">
            <div id="print-section">
                <!-- Header Laporan -->
                <div class="bg-white shadow rounded-lg p-6 border mb-6">
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
                                <span>{{ $qc->kode_qc }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-40 font-semibold">Petugas QC</span>
                                <span class="pr-1">:</span>
                                <span>{{ $qc->petugasQc->name }}</span>
                            </div>
                            <div class="flex">
                                <span class="w-40 font-semibold">Total Item</span>
                                <span class="pr-1">:</span>
                                <span>2</span>
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
                                $totalPengajuan = collect($qc->details)->sum(function ($bahan) {
                                    return (float) ($bahan['jumlah_pengajuan'] ?? 0) * (float) ($bahan['unit_price'] ?? 0);
                                });
                                $totalDiterima = collect($qc->details)->sum(function ($bahan) {
                                    return (float) ($bahan['jumlah_diterima'] ?? 0) * (float) ($bahan['unit_price'] ?? 0);
                                });
                                $totalBaik = collect($qc->details)->sum(function ($bahan) {
                                    return (float) ($bahan['fisik_baik'] ?? 0) * (float) ($bahan['unit_price'] ?? 0);
                                });
                                $totalRusak = collect($qc->details)->sum(function ($bahan) {
                                    return (float) ($bahan['fisik_rusak'] ?? 0) * (float) ($bahan['unit_price'] ?? 0);
                                });
                                $totalRetur = collect($qc->details)->sum(function ($bahan) {
                                    return (float) ($bahan['fisik_retur'] ?? 0) * (float) ($bahan['unit_price'] ?? 0);
                                });
                            @endphp
                            <tbody>
                                @foreach ($qc->details as $index => $detail)
                                    <tr>
                                        <td class="border px-2 py-1 text-center">{{ $index + 1 }}</td>
                                        <td class="border px-2 py-1">{{ $detail->bahan->nama_bahan ?? '-' }}</td>
                                        <td class="border px-2 py-1">{{ $detail->supplier->nama ?? '-' }}</td>
                                        <td class="border px-2 py-1">{{ $detail->no_invoice ?? '-' }}</td>
                                        <td class="border px-2 py-1 text-right">{{ number_format((float) $detail->stok_lama ?? 0, 2, ',', '.') }}</td>
                                        <td class="border px-2 py-1 text-right">{{ number_format((float) $detail->jumlah_pengajuan ?? 0, 2, ',', '.') }}</td>
                                        <td class="border px-2 py-1 text-right">{{ number_format((float) $detail->jumlah_diterima ?? 0, 2, ',', '.') }}</td>
                                        <td class="border px-2 py-1 text-right">{{ number_format((float) $detail->fisik_baik ?? 0, 2, ',', '.') }}</td>
                                        <td class="border px-2 py-1 text-right">{{ number_format((float) $detail->fisik_rusak ?? 0, 2, ',', '.') }}</td>
                                        <td class="border px-2 py-1 text-right">{{ number_format((float) $detail->fisik_retur ?? 0, 2, ',', '.') }}</td>

                                        <td class="border px-2 py-1 text-right">{{ number_format($detail->unit_price, 2, ',', '.') }}</td>
                                        <td class="border px-2 py-1 text-right">{{ number_format($detail->sub_total, 2, ',', '.') }}</td>
                                        <td class="border px-2 py-1">{{ $detail->status }}</td>
                                        <td class="border px-2 py-1">{{ $detail->notes }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="font-semibold bg-gray-100">
                                    <td class="border px-2 py-1 text-center" colspan="5">Total Harga</td>
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
                        $bahanAdaDokumentasi = collect($selectedBahanList)->filter(function($bahan) use ($gambarPerBahan) {
                            $bahanId = $bahan['bahan_id'];
                            $files   = $gambarPerBahan[$bahanId] ?? [];
                            return count($files) > 0; // hanya ambil bahan yang benar-benar ada file
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
                        <img src="{{ asset('storage/'.$file->gambar) }}"
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
                <div class="bg-white shadow rounded-lg p-6 border mt-6">
                    <div class="mb-4">
                        <label class="inline-flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                class="form-checkbox h-5 w-5 text-black-600"
                                @if($is_verified == 1) checked @endif
                                @if($is_verified != 0) disabled @endif
                            >
                            <span class="ml-2 font-semibold text-black-700">
                                Saya menyatakan bahwa semua data QC bahan masuk telah diperiksa dan valid.
                            </span>
                        </label>
                    </div>

                    <div class="mb-4">
                        <label for="keterangan_qc" class="block font-medium mb-1">Catatan Tambahan (opsional)</label>
                        <textarea id="keterangan_qc" rows="3"
                            wire:model.defer="keterangan_qc"
                            class="form-control w-full resize-none border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
                            placeholder="Tulis jika ada catatan tambahan..." disabled></textarea>
                    </div>

                    <!-- Tanda Tangan -->
                    <div class="bg-white shadow rounded-lg p-6 border mt-6">
                        <div class="grid grid-cols-2 gap-6 mt-6">
                            <div class="text-center">
                                <p class="font-semibold mb-6">Petugas Input</p>
                                @if($petugas_input_qc_ttd)
                                    <img src="{{ asset('storage/' . $petugas_input_qc_ttd) }}" alt="Tanda Tangan Petugas Input" class="mx-auto h-24 object-contain" />
                                @else
                                    <div class="h-24 flex items-center justify-center text-gray-400 italic">Belum ada tanda tangan</div>
                                @endif
                                <p>{{ $petugas_input_qc_nama }}</p>
                            </div>

                            <div class="text-center">
                                <p class="font-semibold mb-6">Petugas QC</p>
                                @if ($petugas_qc_ttd)
                                    <img src="{{ asset('storage/' . $petugas_qc_ttd) }}" alt="Tanda Tangan Petugas QC" class="mx-auto h-24 object-contain" />
                                @else
                                    <div class="h-24 flex items-center justify-center text-gray-400 italic">Belum ada tanda tangan</div>
                                @endif
                                <p>{{ $petugas_qc_nama }}</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- <div class="col-span-12 lg:col-span-4">
            <div class="intro-y box p-5">
                <div class="flex justify-between items-center mb-3">
                    <label class="font-semibold">Tabel List Bahan</label>
                    <button class="px-4 py-2 bg-theme-1 text-white rounded">
                        Simpan Bahan Masuk
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="table table-bordered w-full">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-4 py-2">No</th>
                                <th class="px-4 py-2">Nama Bahan</th>
                                <th class="px-4 py-2">Unit Price</th>
                                <th class="px-4 py-2">Qty</th>
                                <th class="px-4 py-2">Sub Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($selectedBahanListFisikBaik as $index => $bahan)
                                <tr>
                                    <td class="px-4 py-2 text-center">{{ $index + 1 }}</td>
                                    <td class="px-4 py-2">{{ $bahan['nama_bahan'] }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format($bahan['unit_price'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format($bahan['fisik_baik'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format($bahan['sub_total'], 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-4 py-2 text-center text-gray-500">
                                        Tidak ada data bahan
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div> --}}

    </div>

    <!-- Script Print -->
    <script>
        function printDiv(divId) {
            const printContents = document.getElementById(divId).innerHTML;

            // Buka window baru
            const printWindow = window.open('', '_blank', 'width=1100,height=600');

            // Ambil semua CSS <link> dari halaman utama
            const styles = Array.from(document.querySelectorAll('link[rel="stylesheet"], style'))
                .map(link => link.outerHTML)
                .join('');

            // Ambil semua script JS jika perlu (opsional, biasanya untuk feather icons dll)
            const scripts = Array.from(document.querySelectorAll('script[src]'))
                .map(script => script.outerHTML)
                .join('');

            // Tulis HTML ke window baru
            printWindow.document.write(`
                <html>
                <head>
                    <title>Print Preview</title>
                    ${styles}
                </head>
                <body>
                    ${printContents}
                    ${scripts}
                </body>
                </html>
            `);

            printWindow.document.close();

            // Tunggu sampai semua CSS & JS siap
            printWindow.onload = function () {
                printWindow.focus();
                printWindow.print();
            };
        }
    </script>
    <!-- CSS untuk Print (opsional) -->
    <style>
    @media print {
        button {
            display: none;
        }
    }
    </style>
</div>
