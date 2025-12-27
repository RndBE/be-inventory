<div class="space-y-3 mt-3">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <div class="flex flex-col md:flex-row md:items-center gap-3 w-full">
            {{-- <input class="text-sm border rounded px-2 py-1" type="file" wire:model="excelFiles" multiple
            accept=".xlsx,.xls,.csv"> --}}
            <label for="excelFiles"
                class="inline-flex items-center gap-2 px-1 py-1.5 bg-indigo-600 text-white text-sm rounded cursor-pointer hover:bg-indigo-700 active:bg-indigo-800 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 16V4m0 0l-4 4m4-4l4 4M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
                </svg>
                Pilih File Excel
            </label>
            <input id="excelFiles" type="file" wire:model="excelFiles" multiple accept=".xlsx,.xls,.csv"
                class="hidden">

            @if ($excelFiles)
                <ul class="flex flex-wrap gap-2 text-xs md:text-sm">
                    @foreach ($excelFiles as $file)
                        <li class="px-3 py-1 bg-gray-100 border rounded">
                            📄 {{ $file->getClientOriginalName() }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="flex gap-2 justify-end md:justify-start">
            <button wire:click="readExcel" class="px-3 py-1.5 text-sm bg-indigo-600 text-white  rounded">
                Preview
            </button>

            @if (count($previewItems))
                <button wire:click="saveExcelResult" class="px-3 py-1.5 text-sm bg-green-600 text-white rounded w-full md:w-auto">
                    Submit Preview
                </button>
            @endif
        </div>
    </div>

    @if (count($previewItems))
        <table class="w-full text-sm border mt-3">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-3 py-2">Tanggal</th>
                    <th class="px-3 py-2">Nama</th>
                    <th class="px-3 py-2">Keterangan</th>
                    <th class="px-3 py-2 text-center">Qty</th>
                    <th class="px-3 py-2">Satuan</th>
                    <th class="px-3 py-2 text-right">Unit Price</th>
                    <th class="px-3 py-2 text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($previewItems as $row)
                    <tr>
                        <td>{{ $row['tanggal'] }}</td>
                        <td>{{ $row['nama_biaya_tambahan'] }}</td>
                        <td>{{ $row['keterangan'] }}</td>
                        <td class="text-center">{{ $row['qty'] }}</td>
                        <td>{{ $row['satuan'] }}</td>
                        <td class="text-right">{{ number_format($row['unit_price'], 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($row['total_biaya'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

</div>

<script>
    window.addEventListener('reload-tab2', () => {

        setTimeout(() => {

            // Simpan state tab ke sessionStorage
            sessionStorage.setItem('activeTab', 'tab2');

            // Reload halaman
            window.location.reload();

        }, 3000); // ⏱️ delay 3 detik
    });
</script>
