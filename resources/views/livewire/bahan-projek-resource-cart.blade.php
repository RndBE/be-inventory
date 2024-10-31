
<div>
    <div class="border-b border-gray-900/10 pb-2">
        <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
            <div class="flex items-center">
                <label for="kode_projek_rnd" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Kode Projek</label>
                <input type="text" id="kode_projek_rnd" disabled placeholder="PR - " class="block rounded-md w-3/4 border-gray-300 bg-gray-100 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
            </div>

            <div class="flex items-center">
                <label for="nama_projek_rnd" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Nama Projek
                    <sup class="text-red-500 text-base">*</sup>
                </label>
                <input type="text" id="nama_projek_rnd" name="nama_projek_rnd" class=" w-3/4 block rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
            </div>



            <div class="flex items-center">
                <label for="datepicker-autohide" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Mulai Projek<sup class="text-red-500 text-base">*</sup></label>
                <div class="relative w-3/4">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-3 h-3 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                        </svg>
                    </div>
                    <input type="text" name="mulai_projek_rnd" id="datetimepicker" placeholder="Pilih tanggal dan waktu" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500 block w-full py-1.5 pl-10 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                </div>
            </div>

            <div class="flex items-center">
                {{-- <label for="jenis_produksi" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Jenis Produksi<sup class="text-red-500 text-base">*</sup></label>
                <div class="relative w-3/4 mr-2">
                    <div class="flex flex-wrap">
                        <div class="flex items-center me-4">
                            <input id="red-radio" type="radio" value="Produk Jadi" name="jenis_produksi" class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 focus:ring-purple-500 dark:focus:ring-purple-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="red-radio" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Produk Jadi</label>
                        </div>
                        <div class="flex items-center me-4">
                            <input id="green-radio" type="radio" value="Produk Setengah Jadi" name="jenis_produksi" class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 focus:ring-purple-500 dark:focus:ring-purple-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="green-radio" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Produk Setengah Jadi</label>
                        </div>
                    </div>
                </div> --}}
            </div>

            <div class="flex items-center">
                <label for="text" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4"></label>
                <div class="relative w-3/4 mr-2">
                    <div class="flex items-center me-4">
                        <p class="text-red-500 text-sm"><sup>*</sup>) Wajib diisi</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <div class="border-b border-gray-900/10 pt-2">
        <livewire:search-bahan-produksi/>
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg pt-2">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">Bahan</th>
                        <th scope="col" class="px-6 py-3 text-center">Qty</th>
                        <th scope="col" class="px-6 py-3">Sub Total</th>
                        <th scope="col" class="px-6 py-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cart as $item)
                        <input type="hidden" name="cartItems" value="{{ json_encode($this->getCartItemsForStorage()) }}">

                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ $item->nama_bahan }}</td>
                            <td class="px-6 py-4">
                                <div class="flex justify-center items-center">
                                    {{-- <button wire:click="decreaseQuantity({{ $item->id }})"
                                        class="inline-flex items-center justify-center p-1 text-sm font-medium h-6 w-6 text-gray-500 bg-white border border-gray-300 rounded-full focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
                                        type="button">
                                        <span class="sr-only">Decrease Quantity</span>
                                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 2"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h16"/></svg>
                                    </button> --}}

                                    <input value="{{ old('qty.'.$item->id, $qty[$item->id] ?? 0) }}"
                                        type="number"
                                        wire:model="qty.{{ $item->id }}"
                                        wire:keyup="updateQuantity({{ $item->id }})"
                                        class="bg-gray-50 w-20 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                        placeholder="0" min="0" required />

                                    {{-- <button wire:click="increaseQuantity({{ $item->id }})"
                                        class="inline-flex items-center justify-center h-6 w-6 p-1 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-full focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
                                        type="button">
                                        <span class="sr-only">Increase Quantity</span>
                                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16"/></svg>
                                    </button> --}}
                                </div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white"><span><strong>Rp.</strong> {{ number_format($subtotals[$item->id] ?? 0, 0, ',', '.') }}</span></td>
                            <td class="px-6 py-4">
                                <a href="#" class="font-medium text-red-600 dark:text-red-500 hover:underline" wire:click.prevent="removeItem({{ $item->id }})"><svg class="w-6 h-6 text-red-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm7.707-3.707a1 1 0 0 0-1.414 1.414L10.586 12l-2.293 2.293a1 1 0 1 0 1.414 1.414L12 13.414l2.293 2.293a1 1 0 0 0 1.414-1.414L13.414 12l2.293-2.293a1 1 0 0 0-1.414-1.414L12 10.586 9.707 8.293Z" clip-rule="evenodd"/>
                                </svg>
                                </a>
                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
{{-- <script>
    document.getElementById('bahan_id').addEventListener('change', function() {
        @this.call('bahanSelected', this.value);
    });
</script> --}}
