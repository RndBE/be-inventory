<div>
    @if(count($selectedBahanList) > 0)
        <div class="overflow-x-auto">
            <div class="overflow-x-auto bg-white border shadow rounded-lg mt-4">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach ($selectedBahanList as $index => $bahan)
                        <div wire:key="bahan-card-{{ $bahan['bahan_id'] }}"
                            class="bg-white shadow-sm rounded-lg p-4 border space-y-3"
                            x-data="{
                                jumlahDiterima: '{{ $bahan['jumlah_diterima'] ?? '' }}',
                                fisikBaik: '{{ $bahan['fisik_baik'] ?? 0 }}',
                                fisikRusak: '{{ $bahan['fisik_rusak'] ?? 0 }}',
                                fisikRetur: '{{ $bahan['fisik_retur'] ?? 0 }}',
                                adjustFisik() {
                                    let diterima = parseFloat(this.jumlahDiterima || 0);
                                    let baik = parseFloat(this.fisikBaik || 0);
                                    let rusak = parseFloat(this.fisikRusak || 0);

                                    let retur = diterima - (baik + rusak);
                                    this.fisikRetur = retur >= 0 ? retur.toFixed(2) : 0;

                                    // Jika total lebih dari jumlah diterima, kurangi dari fisik baik
                                    if (baik + rusak + parseFloat(this.fisikRetur) > diterima) {
                                        this.fisikBaik = (diterima - rusak - this.fisikRetur).toFixed(2);
                                    }

                                    // Sinkron nilai ke Livewire, gunakan index dinamis lewat data Alpine (misal `bahanIndex`)
                                    @this.set(`selectedBahanList.{{ $index }}.fisik_retur`, this.fisikRetur);
                                    @this.set(`selectedBahanList.{{ $index }}.fisik_baik`, this.fisikBaik);
                                    @this.set(`selectedBahanList.{{ $index }}.fisik_rusak`, this.fisikRusak);
                                }
                            }"
                            >

                            <!-- Header -->
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-sm text-gray-800">
                                    {{ $bahan['nama_bahan'] }}
                                </h3>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-black">#{{ $index + 1 }}</span>
                                    <!-- Toggle Switch -->
                                    <div x-data="{ on: @entangle('selectedBahanList.' . $index . '.is_selected') }">
                                        <button
                                            type="button"
                                            @click="on = !on"
                                            :class="on ? 'bg-theme-1' : 'bg-gray-300'"
                                            class="relative inline-flex h-5 w-10 items-center rounded-full transition-colors focus:outline-none"
                                        >
                                            <span
                                                :class="on ? 'translate-x-5' : 'translate-x-1'"
                                                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                            ></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="text-black">No Invoice</label>
                                <input type="text" wire:model.defer="selectedBahanList.{{ $index }}.no_invoice"
                                    class="input border w-full mt-1" placeholder="INV-0001">
                                @error("selectedBahanList.$index.no_invoice")
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <!-- Sub Info -->
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <label class="text-black">Jumlah Pengajuan</label>
                                    <div class="mt-1 text-sm">{{ $bahan['jumlah_pengajuan'] }}</div>
                                </div>
                                <div>
                                    <label class="text-black">Stok Lama</label>
                                    <div class="mt-1 text-sm">{{ $bahan['stok_lama'] }}</div>
                                </div>

                                <div x-data="{
                                        open: false,
                                        activeIndex: 0,
                                        items: [],
                                        openDropdown() {
                                            this.open = true;
                                            this.activeIndex = 0;
                                            this.$nextTick(() => this.$refs.input.focus());
                                        },
                                        moveDown() {
                                            if (this.activeIndex < this.items.length - 1) this.activeIndex++;
                                        },
                                        moveUp() {
                                            if (this.activeIndex > 0) this.activeIndex--;
                                        },
                                        selectActiveItem() {
                                            if (this.activeIndex >= 0 && this.activeIndex < this.items.length) {
                                                $wire.set('selectedBahanList.{{ $index }}.supplier_id', this.items[this.activeIndex]);
                                                this.open = false;
                                            }
                                        }
                                    }"
                                    x-init="$watch('open', value => { if (value) activeIndex = 0 })"
                                    @click.away="open = false"
                                    @keydown.arrow-down.prevent="open = true; moveDown()"
                                    @keydown.arrow-up.prevent="moveUp()"
                                    @keydown.enter.prevent="selectActiveItem()"
                                >
                                    <label for="search_supplier">Pilih Supplier</label>

                                    <!-- Trigger -->
                                    <div @click="openDropdown"
                                        class="input border mt-1 cursor-pointer flex justify-between items-center"
                                        style="width: 100%; max-width: 100%; padding: 8px 12px;">
                                        <span>
                                            {{ $bahan['supplier_id']
                                                ? optional($supplierList->firstWhere('id', $bahan['supplier_id']))->nama
                                                : 'Pilih Supplier' }}
                                        </span>
                                        <svg class="w-4 h-4 transform transition-transform duration-200"
                                            :class="{ 'rotate-180': open }"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </div>
                                    @error("selectedBahanList.$index.supplier_id")
                                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                    @enderror


                                    <!-- Dropdown -->
                                    <div x-show="open"
                                        class="absolute z-10 bg-white border rounded shadow"
                                        style="width: 15%; max-width: 15%;"
                                        x-cloak>
                                        <!-- Search -->
                                        <input type="text"
                                            x-ref="input"
                                            placeholder="Cari supplier..."
                                            @input="$wire.set('searchSupplier', $event.target.value); activeIndex = 0"
                                            class="w-full px-3 py-2 border-b border-gray-200 focus:outline-none">

                                        <!-- Sync Alpine items -->
                                        <div x-effect="items = @js($this->filteredSupplierList->pluck('id')->values());
                                                    if (activeIndex >= items.length) activeIndex = 0;"></div>

                                        <!-- List -->
                                        <ul style="max-height: 240px; overflow-y: auto; padding: 0; margin: 0; width: 100%;">

                                            @forelse ($this->filteredSupplierList as $sIndex => $supplier)
                                                <li
                                                    x-ref="item{{ $sIndex }}"
                                                    :class="{
                                                        'bg-red-100 text-red-700': activeIndex === {{ $sIndex }},
                                                        'hover:bg-gray-100': activeIndex !== {{ $sIndex }}
                                                    }"
                                                    style="padding: 10px 12px; cursor: pointer; font-size: 1rem;"
                                                    @mouseenter="activeIndex = {{ $sIndex }}"
                                                    @mouseleave="activeIndex = -1"
                                                    @click="$wire.set('selectedBahanList.{{ $index }}.supplier_id', {{ $supplier->id }}); open = false"
                                                    x-effect="if (activeIndex === {{ $sIndex }}) $refs['item{{ $sIndex }}']?.scrollIntoView({ block: 'nearest' })"
                                                >
                                                    {{ $supplier->nama }}
                                                </li>
                                            @empty
                                                <li style="padding: 10px 12px; color: #999;">Tidak ditemukan</li>
                                            @endforelse
                                        </ul>
                                    </div>
                                </div>


                                <div>
                                    <label class="text-black">Jumlah Diterima</label>
                                    <input type="number" step="0.01"
                                        wire:model.defer="selectedBahanList.{{ $index }}.jumlah_diterima"
                                        x-model="jumlahDiterima"
                                        class="input border w-full mt-1 text-xs" placeholder="100">
                                    @error("selectedBahanList.$index.jumlah_diterima")
                                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                    @enderror

                                </div>

                                <div>
                                    <label class="text-black">Fisik Baik</label>
                                    <input type="number" step="0.01"
                                        wire:model.defer="selectedBahanList.{{ $index }}.fisik_baik"
                                        x-model="fisikBaik"
                                        :disabled="!jumlahDiterima"
                                        @input="adjustFisik()"
                                        class="input border w-full mt-1 text-xs" placeholder="98">
                                    @error("selectedBahanList.$index.fisik_baik")
                                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="text-black">Fisik Rusak</label>
                                    <input type="number" step="0.01"
                                        wire:model.defer="selectedBahanList.{{ $index }}.fisik_rusak"
                                        x-model="fisikRusak"
                                        :disabled="!jumlahDiterima"
                                        @input="adjustFisik()"
                                        class="input border w-full mt-1 text-xs" placeholder="2">
                                    @error("selectedBahanList.$index.fisik_rusak")
                                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="text-black">Fisik Retur</label>
                                    <input type="number" step="0.01"
                                        wire:model.lazy="selectedBahanList.{{ $index }}.fisik_retur"
                                        x-model="fisikRetur"
                                        :disabled="!jumlahDiterima"
                                        @input="adjustFisik();$dispatch('input');"
                                        class="input border w-full mt-1 text-xs" placeholder="2">
                                    @error("selectedBahanList.$index.fisik_retur")
                                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="text-black">Harga / Unit</label>
                                    <input type="number" step="0.01" wire:model.defer="selectedBahanList.{{ $index }}.unit_price"
                                        class="input border w-full mt-1 text-xs" placeholder="50000">
                                    @error("selectedBahanList.$index.unit_price")
                                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="col-span-2">
                                    <label class="text-black">Status QC</label>
                                    <select
                                        wire:model.defer="selectedBahanList.{{ $index }}.statusQc"
                                        class="input border w-full mt-1 text-xs"
                                    >
                                        <option disabled value="">Pilih</option>
                                        <option value="Belum Diterima">Belum Diterima</option>
                                        <option value="Diterima Semua">Diterima Semua</option>
                                        <option value="Diterima Sebagian">Diterima Sebagian</option>
                                        <option value="Ditolak">Ditolak</option>
                                    </select>
                                    @error("selectedBahanList.$index.statusQc")
                                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                    @enderror

                                </div>

                                <div class="col-span-2">
                                    <label class="text-black">Catatan</label>
                                    <textarea type="text" wire:model.defer="selectedBahanList.{{ $index }}.notes"
                                        class="input border w-full mt-1 text-xs" placeholder="Opsional..."></textarea>
                                </div>

                                @php
                                    $bahanId = $bahan['bahan_id'];
                                    $files   = $gambarPerBahan[$bahanId] ?? [];
                                @endphp

                                <div class="col-span-2">
                                    <div class="p-5 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 hover:bg-gray-100 transition cursor-pointer">

                                        <!-- Input Upload (ID unik per bahan) -->
                                        <input
                                            type="file"
                                            id="gambar-{{ $bahanId }}"
                                            multiple
                                            accept="image/png, image/jpeg, image/jpg, image/webp"
                                            wire:model="gambarPerBahan.{{ $bahanId }}"
                                            class="hidden"
                                        >

                                        <label for="gambar-{{ $bahanId }}" class="flex flex-col items-center justify-center cursor-pointer">
                                            <svg class="w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M7 16V4a1 1 0 011-1h8a1 1 0 011 1v12m-4-4h.01M5 20h14a2 2 0 002-2v-5a2 2 0 00-2-2H5a2 2 0 00-2 2z" />
                                            </svg>
                                            <p class="text-gray-600">Klik atau drag & drop gambar</p>
                                            <p class="text-xs text-gray-400">PNG, JPG, JPEG, WEBP</p>
                                        </label>

                                        <!-- Preview per bahan -->
                                        @if (!empty($files))
                                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 16px;">
                                                @foreach ($files as $fIndex => $file)
                                                    <div style="position: relative; width: 100%; height: 96px; border: 1px solid #ccc; border-radius: 6px; overflow: hidden;">
                                                        <button
                                                            type="button"
                                                            wire:click="removeImage({{ $bahanId }}, {{ $fIndex }})"
                                                            style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
                                                                background-color: #8b0000; color: white; font-size: 20px; width: 32px; height: 32px;
                                                                display: flex; align-items: center; justify-content: center; border: none; border-radius: 50%;
                                                                cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">
                                                            ×
                                                        </button>
                                                        <img src="{{ $file->temporaryUrl() }}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <p class="text-black">Belum ada bahan dipilih.</p>
    @endif
</div>

