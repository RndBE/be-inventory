<div>
    <div class="p-5 grid grid-cols-12 gap-4 row-gap-3">
        <!-- Kode Pembelian Bahan -->
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
                        $wire.set('selected_pembelian_id', this.items[this.activeIndex]);
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
            <label for="search_pembelian">Pilih Kode Pembelian</label>

            {{-- Trigger dropdown --}}
            <div @click="openDropdown"
                class="input w-full border mt-2 cursor-pointer flex justify-between items-center">
                <span>
                    {{ $selected_pembelian_id
                        ? optional($pembelianList->firstWhere('id', $selected_pembelian_id))->kode_transaksi
                        : 'Pilih Kode Pembelian' }}
                </span>
                <svg class="w-4 h-4 transform transition-transform duration-200"
                    :class="{ 'rotate-180': open }"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
            @error('selected_pembelian_id')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror

            {{-- Dropdown content --}}
            <div x-show="open"
                class="absolute z-10 w-full mt-1 bg-white border rounded shadow"
                x-cloak>
                {{-- Search input --}}
                <input type="text"
                    x-ref="input"
                    placeholder="Cari kode transaksi..."
                    @input="$wire.set('searchPembelian', $event.target.value); activeIndex = 0"
                    class="w-full px-3 py-2 border-b border-gray-200 focus:outline-none">

                {{-- Sync Alpine items with Livewire --}}
                <div x-effect="items = @js($this->filteredPembelianList->pluck('id')->values());
                            if (activeIndex >= items.length) activeIndex = 0;"></div>

                {{-- List options --}}
                <ul style="max-height: 240px; overflow-y: auto; padding: 0; margin: 0;" x-ref="listContainer">
                    @forelse ($this->filteredPembelianList as $index => $pembelian)
                        <li
                            x-ref="item{{ $index }}"
                            :class="{
                                'bg-red-100 text-red-700': activeIndex === {{ $index }},
                                'hover:bg-gray-100': activeIndex !== {{ $index }}
                            }"
                            style="padding: 10px 12px; cursor: pointer; font-size: 1.1rem;"
                            @mouseenter="activeIndex = {{ $index }}"
                            @mouseleave="activeIndex = -1"
                            @click="$wire.set('selected_pembelian_id', {{ $pembelian->id }}); open = false"
                            x-effect="if (activeIndex === {{ $index }}) $refs['item{{ $index }}']?.scrollIntoView({ block: 'nearest' })"
                        >
                            {{ $pembelian->kode_transaksi }}
                        </li>
                    @empty
                        <li style="padding: 10px 12px; color: #999; font-size: 1.1rem;">Tidak ditemukan</li>
                    @endforelse
                </ul>
            </div>
        </div>


        <!-- Petugas QC -->
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
                        $wire.set('selected_petugas_id', this.items[this.activeIndex]);
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
            <label for="search_pembelian">Pilih Petugas QC</label>

            {{-- Trigger dropdown --}}
            <div @click="openDropdown"
                class="input w-full border mt-2 cursor-pointer flex justify-between items-center">
                <span>
                    {{ $selected_petugas_id
                        ? optional($petugasList->firstWhere('id', $selected_petugas_id))->name
                        : 'Pilih Petugas QC' }}
                </span>
                <svg class="w-4 h-4 transform transition-transform duration-200"
                    :class="{ 'rotate-180': open }"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
            @error('selected_petugas_id')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror


            {{-- Dropdown content --}}
            <div x-show="open"
                class="absolute z-10 w-full mt-1 bg-white border rounded shadow"
                x-cloak>
                {{-- Search input --}}
                <input type="text"
                    x-ref="input"
                    placeholder="Cari petugas QC..."
                    @input="$wire.set('searchPetugas', $event.target.value); activeIndex = 0"
                    class="w-full px-3 py-2 border-b border-gray-200 focus:outline-none">

                {{-- Sync Alpine items with Livewire --}}
                <div x-effect="items = @js($this->filteredPetugasList->pluck('id')->values());
                            if (activeIndex >= items.length) activeIndex = 0;"></div>

                {{-- List options --}}
                <ul style="max-height: 240px; overflow-y: auto; padding: 0; margin: 0;" x-ref="listContainer">
                    @forelse ($this->filteredPetugasList as $index => $petugas)
                        <li
                            x-ref="item{{ $index }}"
                            :class="{
                                'bg-red-100 text-red-700': activeIndex === {{ $index }},
                                'hover:bg-gray-100': activeIndex !== {{ $index }}
                            }"
                            style="padding: 10px 12px; cursor: pointer; font-size: 1.1rem;"
                            @mouseenter="activeIndex = {{ $index }}"
                            @mouseleave="activeIndex = -1"
                            @click="$wire.set('selected_petugas_id', {{ $petugas->id }}); open = false"
                            x-effect="if (activeIndex === {{ $index }}) $refs['item{{ $index }}']?.scrollIntoView({ block: 'nearest' })"
                        >
                            {{ $petugas->name }}
                        </li>
                    @empty
                        <li style="padding: 10px 12px; color: #999; font-size: 1.1rem;">Tidak ditemukan</li>
                    @endforelse
                </ul>
            </div>
        </div>
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
