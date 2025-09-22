<div>
    <div class="relative">
        <div class="card mb-0 border-0 shadow-sm bg-white">
            <div class="card-body">
                <div class="form-group mb-0">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text bg-gray-100">
                                <i class="bi bi-search text-primary"></i>
                            </div>
                        </div>
                        <input
                            type="text"
                            wire:model.live='query'
                            class="block w-full rounded-md border-gray-300 py-1.5 pr-14 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                            placeholder="Tuliskan nama bahan atau kode bahan....">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-4 mt-6">
        <!-- List Bahan Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-6">
    @foreach($bahanList as $bahan)
        <div class="bg-white border rounded-lg p-4 shadow-md hover:shadow-lg transition-shadow cursor-pointer"
            wire:click="selectBahan({{ $bahan['id'] }}, '{{ $bahan['type'] }}')">

            {{-- Gambar bahan --}}
            @if($bahan['gambar'])
                <img src="{{ asset('storage/' . $bahan['gambar']) }}"
                    alt="{{ $bahan['nama'] }}"
                    class="w-full h-40 object-cover rounded mb-4">
            @else
                <div class="w-full h-40 bg-gray-200 flex items-center justify-center rounded mb-4">
                    <span class="text-gray-500 text-sm">Tidak ada gambar</span>
                </div>
            @endif

            {{-- Nama & kode --}}
            <h4 class="font-bold text-sm">{{ $bahan['nama'] }}</h4>
            @if($bahan['type'] === 'setengahjadi')
                <p class="text-sm text-gray-600">PN: {{ $bahan['kode'] }}</p>
                <p class="text-sm text-gray-600">SN: {{ $bahan['serial_number'] }}</p>
            @elseif($bahan['type'] === 'jadi')
                <p class="text-sm text-gray-600">PN: {{ $bahan['kode'] }}</p>
                <p class="text-sm text-gray-600">SN: {{ $bahan['serial_number'] }}</p>
            @else
                <p class="text-sm text-gray-600">PN: {{ $bahan['kode'] }}</p>
            @endif

            {{-- Stok & info tambahan --}}
            <div class="flex justify-between items-start mt-4">
                <div>
                    @php
                        $stokClass = match($bahan['type']) {
                            'setengahjadi' => 'bg-blue-100 text-blue-800 border-blue-400',
                            'jadi'   => 'bg-orange-100 text-orange-800 border-orange-400',
                            default        => 'bg-green-100 text-green-800 border-green-400',
                        };
                    @endphp

                    @php
                        $stokFormatted = rtrim(rtrim(number_format($bahan['stok'], 2, '.', ''), '0'), '.');
                    @endphp

                    <span class="text-sm font-medium px-2.5 py-0.5 rounded border
                        {{ $bahan['type'] === 'setengahjadi'
                            ? 'bg-blue-100 text-blue-800 border-blue-400'
                            : ($bahan['type'] === 'jadi'
                                ? 'bg-orange-100 text-orange-800 border-orange-400'
                                : 'bg-green-100 text-green-800 border-green-400') }}
                    ">
                        {{ $stokFormatted }} {{ $bahan['unit'] }}
                    </span>

                    <div class="text-xs text-gray-600 mt-1">
                        <div><strong>Penempatan:</strong> {{ $bahan['penempatan'] ?? '-' }}</div>
                        <div><strong>Supplier:</strong> {{ $bahan['supplier'] ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>


        <!-- Pagination links -->
        <div class="mt-6">
            {{ $bahanList->links() }}
        </div>
    </div>
</div>
