<div>
    @if(count($selectedProdukList) > 0)
        <div class="overflow-x-auto">
            <div class="overflow-x-auto bg-white rounded-lg mt-4">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-2 gap-6">
                    @foreach($selectedProdukList as $index => $produk)
                        <div wire:key="bahan-card-{{ $produk['bahan_id'] }}-{{ $produk['nomor'] }}"
                            class="bg-white shadow-sm rounded-lg p-4 border space-y-3
                            {{ $produk['is_disabled'] ? 'opacity-50 pointer-events-none' : '' }}"
                            x-data="{
                                isSelected: @entangle('selectedProdukList.' . $index . '.is_selected'),

                            }">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-lg text-gray-800">
                                    {{ $produk['nama_bahan'] }}
                                </h3>
                                <div class="flex items-center gap-2">
                                    <span class="text-lg text-black">#{{ $produk['nomor'] }}</span>
                                    <!-- Toggle Switch -->
                                    <div x-data="{ on: @entangle('selectedProdukList.' . $index . '.is_selected') }">
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
                                            {{ $produk['kode_list'] }}
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-gray-600 font-medium">Product Number</label>
                                        <div class="mt-1 text-gray-900 font-semibold">
                                            {{ $produk['nama_bahan'] }}
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

                                    {{-- ID Bluetooth hanya tampil jika bukan Wiring & bukan Vendor --}}
                                    @if($selected_jenis_sn !== 'Wiring' && $selected_jenis_sn !== 'Vendor')
                                        <div>
                                            <label class="block text-gray-600 font-medium">ID Bluetooth</label>
                                            <div class="space-y-2">
                                                {{-- Opsi Tidak Ada --}}
                                                <label class="inline-flex items-center">
                                                    <input
                                                        type="radio"
                                                        wire:model.lazy="selectedProdukList.{{ $index }}.id_bluetooth_option"
                                                        value="000"
                                                        class="text-theme-1 focus:ring-theme-1"
                                                        {{ $produk['is_disabled'] ? 'disabled' : '' }}
                                                    >
                                                    <span class="ml-2">Tidak Ada</span>
                                                </label>

                                                {{-- Opsi Ada --}}
                                                <label class="inline-flex items-center">
                                                    <input
                                                        type="radio"
                                                        wire:model.lazy="selectedProdukList.{{ $index }}.id_bluetooth_option"
                                                        value="custom"
                                                        class="text-theme-1 focus:ring-theme-1"
                                                        {{ $produk['is_disabled'] ? 'disabled' : '' }}
                                                    >
                                                    <span class="ml-2">Ada</span>
                                                </label>

                                                {{-- Jika pilih "Ada" → tampilkan input --}}
                                                @if(($selectedProdukList[$index]['id_bluetooth_option'] ?? null) === 'custom')
                                                    <input
                                                        type="text"
                                                        wire:model.lazy="selectedProdukList.{{ $index }}.id_bluetooth"
                                                        placeholder="Masukkan ID Bluetooth"
                                                        maxlength="3"
                                                        pattern="[0-9]{1,3}"
                                                        class="mt-2 block w-full rounded-md border-gray-300 shadow-sm
                                                            focus:border-theme-1 focus:ring focus:ring-theme-1 focus:ring-opacity-50"
                                                        {{ $produk['is_disabled'] ? 'disabled' : '' }}
                                                    >
                                                @else
                                                    <input type="hidden" wire:model.lazy="selectedProdukList.{{ $index }}.id_bluetooth" value="000">
                                                @endif
                                            </div>

                                            @error("selectedProdukList.$index.id_bluetooth")
                                                <span class="text-red-600 text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    @endif


                                    {{-- Kode Jenis Unit Produk tampil hanya jika Non-Wiring --}}
                                    @if($selected_jenis_sn === 'Non-Wiring' || $selected_jenis_sn === 'Wiring')
                                        <div>
                                            <label class="block text-gray-600 font-medium">Kode Jenis Unit Produk</label>
                                            <select
                                                wire:model.defer="selectedProdukList.{{ $index }}.kode_jenis_unit"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-theme-1 focus:ring focus:ring-theme-1 focus:ring-opacity-50"
                                                {{ $produk['is_disabled'] ? 'disabled' : '' }}
                                            >
                                                <option value="">-- Pilih Kode --</option>
                                                <option value="01">01 - BL 1100 V2</option>
                                                <option value="02">02 - BL110 V2</option>
                                                <option value="03">03 - BL 1100</option>
                                                <option value="04">04 - BL 110</option>
                                                <option value="05">05 - BL11</option>
                                                <option value="06">06 - BL 1</option>
                                                <option value="07">07 - GCM Master</option>
                                                <option value="08">08 - EWS Master</option>
                                                <option value="09">09 - Multiplexer 8 channel</option>
                                                <option value="10">10 - Serial 8 channel</option>
                                                <option value="11">11 - Serial 4 channel</option>
                                                <option value="12">12 - Multiconverter</option>
                                                <option value="13">13 - Serial Converter RS 232 to RS 485</option>
                                                <option value="14">14 - Watchdog</option>
                                                <option value="15">15 - Modul sensor tipping bucket pronamik</option>
                                                <option value="16">16 - Filter sensor tipping bucket pronamik</option>
                                            </select>

                                            @error("selectedProdukList.$index.kode_jenis_unit")
                                                <span class="text-red-600 text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    @endif


                                    {{-- Kode Jenis Unit Produk Wiring tampil hanya jika Wiring --}}
                                    @if($selected_jenis_sn === 'Wiring')
                                        <div>
                                            <label class="block text-gray-600 font-medium">Kode Jenis Unit Produk Wiring</label>
                                            <select
                                                wire:model.defer="selectedProdukList.{{ $index }}.kode_wiring_unit"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-theme-1 focus:ring focus:ring-theme-1 focus:ring-opacity-50"
                                                {{ $produk['is_disabled'] ? 'disabled' : '' }}
                                            >
                                                <option value="">-- Pilih Kode Wiring --</option>
                                                <option value="001">001 - Wiring ARR</option>
                                                <option value="002">002 - Wiring AWR</option>
                                                <option value="003">003 - Wiring AQR</option>
                                                <option value="004">004 - Wiring AWLR</option>
                                                <option value="005">005 - Wiring AWQR</option>
                                                <option value="006">006 - Wiring AFMR</option>
                                                <option value="007">007 - Wiring AWGC</option>
                                                <option value="008">008 - Wiring GCM</option>
                                                <option value="009">009 - Wiring AVWR</option>
                                                <option value="010">010 - Wiring VWR</option>
                                                <option value="011">011 - Wiring ASQR</option>
                                                <option value="012">012 - Wiring ADR</option>
                                                <option value="013">013 - Wiring APS</option>
                                                <option value="014">014 - Wiring EWS</option>
                                                <option value="015">015 - Wiring Radio</option>
                                                <option value="016">016 - Wiring Camera</option>
                                            </select>

                                            @error("selectedProdukList.$index.kode_wiring_unit")
                                                <span class="text-red-600 text-sm">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    @endif
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
