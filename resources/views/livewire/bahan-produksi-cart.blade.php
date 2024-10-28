
<div>
    <div class="border-b border-gray-900/10 pb-2">
        <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
            <div class="flex items-center">
                <label for="kode_produksi" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Kode Produksi</label>
                <input type="text" id="kode_produksi" disabled placeholder="PR - " class="block rounded-md w-3/4 border-gray-300 bg-gray-100 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
            </div>

            <div class="flex items-center">
                <label for="bahan_id" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Nama Produk
                    <sup class="text-red-500 text-base">*</sup>
                </label>
                <select name="bahan_id" id="bahan_id" wire:model="selectedProdukId" wire:change="onProductSelected" class="block rounded-md border-0 py-1.5 w-3/4 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" autofocus required>
                    <option value="">-- Pilih Produk --</option>
                    @foreach($produkProduksi as $produk)
                        <option value="{{ $produk->dataBahan->id }}">{{ $produk->dataBahan->nama_bahan }}</option>
                    @endforeach
                </select>
            </div>



            <div class="flex items-center">
                <label for="datepicker-autohide" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Mulai Produksi<sup class="text-red-500 text-base">*</sup></label>
                <div class="relative w-3/4">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-3 h-3 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                        </svg>
                    </div>
                    <input type="text" name="mulai_produksi" id="datetimepicker" placeholder="Pilih tanggal dan waktu" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-md focus:ring-blue-500 focus:border-blue-500 block w-full py-1.5 pl-10 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                </div>
            </div>

            <div class="flex items-center">
                <label for="jenis_produksi" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Jenis Produksi<sup class="text-red-500 text-base">*</sup></label>
                <div class="relative w-3/4 mr-2">
                    <div class="flex flex-wrap">
                        {{-- <div class="flex items-center me-4">
                            <input id="red-radio" type="radio" value="Produk Jadi" name="jenis_produksi" class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 focus:ring-purple-500 dark:focus:ring-purple-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="red-radio" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Produk Jadi</label>
                        </div> --}}
                        <div class="flex items-center me-4">
                            <input id="green-radio" type="radio" value="Produk Setengah Jadi" name="jenis_produksi" class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 focus:ring-purple-500 dark:focus:ring-purple-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="green-radio" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Produk Setengah Jadi</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center">
                <label for="datepicker-autohide" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Jumlah Produksi<sup class="text-red-500 text-base">*</sup></label>
                <div class="relative w-3/4">
                    <div class="flex item-center">
                        <input type="number" name="jml_produksi" id="jml_produksi" placeholder="Jumlah Produksi"
                            class="block rounded-md border-0 w-full py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                            required
                            wire:model.lazy="jmlProduksi"
                            wire:input="updateJmlBahan">
                    </div>
                </div>
            </div>

            <div class="flex items-center">
                <label for="datepicker-autohide" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4"></label>
                <div class="relative w-3/4 mr-2">
                    <div class="flex items-center me-4">
                        <p class="text-red-500 text-sm"><sup>*</sup>) Wajib diisi</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <div class="border-b border-gray-900/10 pt-2">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg pt-0">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3 w-2/4">Bahan</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Kebutuhan</th>
                        <th scope="col" class="px-6 py-3 text-right w-1/4">Ketersediaan</th>
                        <th scope="col" class="px-6 py-3 text-right w-1/4">Sub Total</th>
                        <th scope="col" class="px-6 py-3 text-right w-0.5">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cartItems as $item)
                        <input type="hidden" name="cartItems" value="{{ json_encode($this->getCartItemsForStorage()) }}">

                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                {{ $item->nama ?? $item->nama_bahan }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                                {{ isset($jml_bahan[$item->id]) ? $jml_bahan[$item->id] : 0 }}
                            </td>
                            <td class="px-6 py-4 text-right flex justify-end">

                                <div class="flex items-center">
                                    @if(isset($warningMessage[$item->id]))
                                        <span class="text-red-500 text-sm pr-2">{{ $warningMessage[$item->id] }}</span> <!-- Display warning message -->
                                    @endif

                                    <input value="{{ old('qty.'.$item->id, $qty[$item->id] ?? 0) }}"
                                        type="number"
                                        wire:model="qty.{{ $item->id }}"
                                        wire:keyup="updateQuantity({{ $item->id }})"
                                        class="bg-gray-50 w-20 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 border-transparent"
                                        placeholder="0" min="0" required readonly />

                                </div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right"><span><strong></strong> {{ number_format($subtotals[$item->id] ?? 0, 0, ',', '.') }}</span></td>
                            <td class="px-6 py-4 text-right flex justify-end">
                                <a href="#" class="font-medium text-red-600 dark:text-red-500 hover:underline" wire:click.prevent="removeItem({{ $item->id }})"><svg class="w-6 h-6 text-red-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm7.707-3.707a1 1 0 0 0-1.414 1.414L10.586 12l-2.293 2.293a1 1 0 1 0 1.414 1.414L12 13.414l2.293 2.293a1 1 0 0 0 1.414-1.414L13.414 12l2.293-2.293a1 1 0 0 0-1.414-1.414L12 10.586 9.707 8.293Z" clip-rule="evenodd"/>
                                </svg>
                                </a>
                            </td>

                        </tr>
                    @endforeach
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white"></td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white"></td>
                        <td class="px-6 py-4 text-right text-black">
                            <strong>Total Harga</strong>
                        </td>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white text-right">
                            <span><strong>Rp.</strong> {{ number_format($totalharga, 0, ',', '.') }}</span>
                        </td>
                    </tr>
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
