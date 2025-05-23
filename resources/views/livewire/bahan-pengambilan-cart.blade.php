<div>
    <div class="border-b border-gray-900/10 pb-2 mb-2">
        <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-1">
            <div class="flex items-center">
                <label for="kode_pengajuan" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Kode Pengajuan</label>
                <input type="text" id="kode_pengajuan" disabled placeholder="PB - " class="block rounded-md w-3/4 border-gray-300 bg-gray-100 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
            </div>

            <div class="flex items-center">
                <label for="project" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">Project
                    <sup class="text-red-500 text-base">*</sup>
                </label>
                <input type="text" id="project" name="project" class=" w-3/4 block rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
            </div>

            <div class="flex items-center">
                <label for="divisi" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                    Divisi <sup class="text-red-500 text-base">*</sup>
                </label>
                <select name="divisi" id="divisi" class="dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 block rounded-md border-0 py-1.5 w-3/4 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" autofocus required>
                    <option value="">-- Pilih Divisi --</option>
                    <option value="Produksi" {{ old('divisi') == 'Produksi' ? 'selected' : '' }}>Produksi</option>
                    <option value="Teknisi" {{ old('divisi') == 'Teknisi' ? 'selected' : '' }}>Teknisi</option>
                    <option value="RnD" {{ old('divisi') == 'RnD' ? 'selected' : '' }}>RnD</option>
                    <option value="Publikasi" {{ old('divisi') == 'Publikasi' ? 'selected' : '' }}>Publikasi</option>
                    <option value="Software" {{ old('divisi') == 'Software' ? 'selected' : '' }}>Software</option>
                    <option value="Marketing" {{ old('divisi') == 'Marketing' ? 'selected' : '' }}>Marketing</option>
                    <option value="Purchasing" {{ old('divisi') == 'Purchasing' ? 'selected' : '' }}>Purchasing</option>
                    <option value="HSE" {{ old('divisi') == 'HSE' ? 'selected' : '' }}>HSE</option>
                    <option value="OP" {{ old('divisi') == 'OP' ? 'selected' : '' }}>OP</option>
                    <option value="Administrasi" {{ old('divisi') == 'Administrasi' ? 'selected' : '' }}>Administrasi</option>
                    <option value="Sekretaris" {{ old('divisi') == 'Sekretaris' ? 'selected' : '' }}>Sekretaris</option>
                </select>
            </div>

            <div class="flex items-center">
                <label for="keterangan" class="block text-sm font-medium leading-6 text-gray-900 mr-2 w-1/4">
                    Keterangan <sup class="text-red-500 text-base">*</sup>
                </label>
                <textarea id="keterangan" name="keterangan" class="w-3/4 block rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">{{ old('keterangan') }}</textarea>
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
    {{-- <livewire:search-bahan-produksi/> --}}
    {{-- <livewire:search-pengambilan-bahan/> --}}
    {{-- <livewire:search-bahan/> --}}
    <div class=" border-gray-900/10 pt-2">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg pt-0">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">Bahan</th>
                        {{-- <th scope="col" class="px-6 py-3 text-center">Kebutuhan</th> --}}
                        <th scope="col" class="px-6 py-3 text-center">QTY</th>
                        {{-- <th scope="col" class="px-6 py-3">Sub Total</th> --}}
                        <th scope="col" class="px-6 py-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cartItems as $item)
                        <input type="hidden" name="cartItems" value="{{ json_encode($this->getCartItemsForStorage()) }}">

                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ $item->nama_bahan }}</td>
                            {{-- <td class="px-6 py-4">
                                <div class="flex justify-center items-center">
                                    <input type="number" name="jml_bahan[{{ $item->id }}]" id="jml_bahan_{{ $item->id }}"
                                        wire:model="jml_bahan.{{ $item->id }}"
                                        class="bg-gray-50 w-32 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                        placeholder="0"
                                        min="0"
                                        required/>
                                </div>
                            </td> --}}
                            <td class="px-6 py-4">
                                <div class="flex justify-center items-center">
                                    <input value="{{ old('qty.'.$item->id, $qty[$item->id] ?? 0) }}"
                                        type="number"
                                        wire:model="qty.{{ $item->id }}"
                                        wire:keyup="updateQuantity({{ $item->id }})"
                                        class="bg-gray-50 w-20 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                        placeholder="0" min="0" required />
                                </div>
                            </td>
                            {{-- <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white"><span><strong>Rp.</strong> {{ number_format($subtotals[$item->id] ?? 0, 0, ',', '.') }}</span></td> --}}
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
