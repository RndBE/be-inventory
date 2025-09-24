<div>
    @if(count($selectedProdukJadiList) > 0)
        <div class="overflow-x-auto">
            <div class="overflow-x-auto bg-white rounded-lg mt-4">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-2 gap-6">
                    @foreach($selectedProdukJadiList as $index => $produk)
                        <div wire:key="bahan-card-{{ $produk['produk_jadi_id'] }}-{{ $produk['nomor'] }}"
                            class="bg-white shadow-sm rounded-lg p-4 border space-y-3
                            {{ $produk['is_disabled'] ? 'opacity-50 pointer-events-none' : '' }}"
                            x-data="{
                                isSelected: @entangle('selectedProdukJadiList.' . $index . '.is_selected'),

                            }">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-lg text-gray-800">
                                    {{ $produk['nama_produk'] }}
                                </h3>
                                <div class="flex items-center gap-2">
                                    <span class="text-lg text-black">#{{ $produk['nomor'] }}</span>
                                    <!-- Toggle Switch -->
                                    <div x-data="{ on: @entangle('selectedProdukJadiList.' . $index . '.is_selected') }">
                                        <button
                                            type="button"
                                            @click="on = !on"
                                            :class="on ? 'bg-theme-1' : 'bg-gray-300'"
                                            class="relative inline-flex h-5 w-10 items-center rounded-full transition-colors focus:outline-none"
                                            {{ $produk['is_disabled'] ? 'disabled' : '' }}
                                        >
                                            <span
                                                :class="on ? 'translate-x-5' : 'translate-x-1'"
                                                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                            ></span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div :class="!isSelected ? 'opacity-50 pointer-events-none' : ''">
                                <!-- Sub Info -->
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <label class="block text-gray-600 font-medium">Kode Produksi</label>
                                        <div class="mt-1 text-gray-900 font-semibold">
                                            {{-- {{ $produk['kode_produksi'] }} --}}
                                            {{ $produk['kode_list'] }}
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-gray-600 font-medium">Product Number</label>
                                        <div class="mt-1 text-gray-900 font-semibold">
                                            {{ $produk['nama_produk'] }}
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-gray-600 font-medium">Qty</label>
                                        <div class="mt-1 text-gray-900 font-semibold">
                                            {{ $produk['qty'] }}
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-gray-600 font-medium">Unit Price</label>
                                        <div class="mt-1 text-gray-900 font-semibold">
                                            Rp {{ number_format($produk['unit_price'], 0, ',', '.') }}
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-gray-600 font-medium">Sub Total</label>
                                        <div class="mt-1 text-gray-900 font-semibold">
                                            Rp {{ number_format($produk['sub_total'], 0, ',', '.') }}
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-gray-600 font-medium">Mulai Produksi</label>
                                        <div class="mt-1 text-gray-900 font-semibold">
                                            {{ $produk['mulai_produksi'] }}
                                        </div>
                                    </div>

                                    <div>
                                        <label for="id_logger" class="text-gray-600">ID Logger Produk</label>
                                        <div class="mt-1">
                                            <input
                                                type="number"
                                                id="id_logger_{{ $index }}"
                                                name="id_logger"
                                                wire:model.lazy="selectedProdukJadiList.{{ $index }}.id_logger"
                                                min="0"
                                                max="99999"
                                                class="mt-2 block w-full rounded-md border-gray-300 shadow-sm
                                                    focus:border-theme-1 focus:ring focus:ring-theme-1 focus:ring-opacity-50
                                                    [appearance:textfield]"
                                                placeholder="Masukkan ID Logger"
                                                oninput="if(this.value.length > 5) this.value = this.value.slice(0,5)"
                                            >
                                        </div>
                                        @error("selectedProdukJadiList.$index.id_logger")
                                            <span class="text-red-600 text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>

                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <p class="text-black">Belum ada produk dipilih.</p>
    @endif
</div>
