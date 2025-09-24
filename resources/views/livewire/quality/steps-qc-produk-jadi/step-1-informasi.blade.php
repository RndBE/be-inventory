<div>
    <div class="p-5 grid grid-cols-12 gap-4 row-gap-3">
        <!-- Kode produksi Bahan -->
        <div class="col-span-12 relative"
            x-data="{
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
                        $wire.set('selected_produksi_produk_jadi_id', this.items[this.activeIndex]);
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
            <label class="text-black" for="search_produksi">Pilih Kode Produksi</label>

            {{-- Trigger dropdown --}}
            <div @click="openDropdown"
                class="input w-full border mt-2 cursor-pointer flex justify-between items-center">
                <span>
                    {{ $selected_produksi_produk_jadi_id
                        ? optional($produksiProdukjadiList->firstWhere('id', $selected_produksi_produk_jadi_id))->kode_produksi
                        : 'Pilih Kode Produksi' }}
                </span>
                <svg class="w-4 h-4 transform transition-transform duration-200"
                    :class="{ 'rotate-180': open }"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
            @error('selected_produksi_produk_jadi_id')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror

            {{-- Dropdown content --}}
            <div x-show="open"
                class="absolute z-10 w-full mt-1 bg-white border rounded shadow"
                x-cloak>
                {{-- Search input --}}
                <input type="text"
                    x-ref="input"
                    placeholder="Cari kode produksi..."
                    @input="$wire.set('searchProduksi', $event.target.value); activeIndex = 0"
                    class="w-full px-3 py-2 border-b border-gray-200 focus:outline-none">

                {{-- Sync Alpine items with Livewire --}}
                <div x-effect="items = @js($this->filteredProduksiJadiList->pluck('id')->values());
                            if (activeIndex >= items.length) activeIndex = 0;"></div>

                {{-- List options --}}
                <ul style="max-height: 240px; overflow-y: auto; padding: 0; margin: 0;" x-ref="listContainer">
                    @forelse ($this->filteredProduksiJadiList as $index => $produksi)
                        <li
                            x-ref="item{{ $index }}"
                            :class="{
                                'bg-red-100 text-red-700': activeIndex === {{ $index }},
                                'hover:bg-gray-100': activeIndex !== {{ $index }}
                            }"
                            style="padding: 10px 12px; cursor: pointer; font-size: 1.1rem;"
                            @mouseenter="activeIndex = {{ $index }}"
                            @mouseleave="activeIndex = -1"
                            wire:click="$set('selected_produksi_produk_jadi_id', {{ $produksi->id }})" @click="open = false"
                            x-effect="if (activeIndex === {{ $index }}) $refs['item{{ $index }}']?.scrollIntoView({ block: 'nearest' })"
                        >
                            {{ $produksi->kode_produksi }}
                        </li>
                    @empty
                        <li style="padding: 10px 12px; color: #999; font-size: 1.1rem;">Tidak ditemukan</li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- <div class="col-span-12 relative">
            <label class="text-black">Diproduksi Oleh</label>
            <select wire:model="selected_petugas_id" class="input border w-full mt-1 text-sm">
                <option value="">Pilih Tim Produksi</option>
                <option value="RASYID PRIYO NUGROHO">RASYID PRIYO NUGROHO</option>
                <option value="ENDARTO NUGROHO">ENDARTO NUGROHO</option>
            </select>
            @error('selected_petugas_id')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div> --}}

    </div>
</div>
@push('scripts')
    <script>
        function initSelect2() {
            $('.select2').select2({
                width: '100%'
            }).on('change', function (e) {
                const model = $(this).attr('wire:model');
                if (model) {
                    @this.set(model, $(this).val());
                }
            });
        }

        document.addEventListener("livewire:load", function () {
            initSelect2();

            Livewire.hook('message.processed', (message, component) => {
                initSelect2(); // Re-init Select2 after Livewire DOM updates
            });
        });
    </script>
@endpush
