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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($bahanList as $bahan)
                <div class="bg-white border rounded-lg p-4 shadow-md hover:shadow-lg transition-shadow">
                    @if($bahan['gambar'])
                        <img src="{{ asset('storage/' . $bahan['gambar']) }}"
                            alt="{{ $bahan['nama'] }}"
                            class="w-[200px] h-[200px] object-cover rounded mb-4 mx-auto">
                    @else
                        <div class="w-[200px] h-[200px] bg-gray-200 flex items-center justify-center rounded mb-4 mx-auto">
                            <span class="text-gray-500 text-sm">Tidak ada gambar</span>
                        </div>
                    @endif



                    <h4 class="font-bold text-lg">{{ $bahan['nama'] }}</h4>
                    {{-- Jika tipe setengahjadi, tampilkan serial number --}}
                    @if($bahan['type'] === 'setengahjadi')
                        <p class="text-sm text-gray-600">Serial: {{ $bahan['kode'] }}</p>
                    @else
                        <p class="text-sm text-gray-600">{{ $bahan['kode'] }}</p>
                    @endif
                    <div class="flex justify-between items-center mt-4">
                        <span class="text-xs font-medium px-2.5 py-0.5 rounded border
                            {{ $bahan['type'] === 'setengahjadi' ? 'bg-blue-100 text-blue-800 border-blue-400' : 'bg-green-100 text-green-800 border-green-400' }}
                        ">
                            {{ $bahan['stok'] }} {{ $bahan['unit'] }}
                        </span>

                        <button class=" text-white py-2 px-4 rounded-full" style="background-color: #2E2E4D;" wire:click="selectBahan({{ $bahan['id'] }}, '{{ $bahan['type'] }}')"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-shopping-cart-plus"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 19a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M12.5 17h-6.5v-14h-2" /><path d="M6 5l14 1l-.86 6.017m-2.64 .983h-10.5" /><path d="M16 19h6" /><path d="M19 16v6" /></svg></button>
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
