<div>
    @if(session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 5000
            });
        </script>
    @endif

    @if(session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: '{{ session('error') }}',
                showConfirmButton: true,
                timer: 5000
            });
        </script>
    @endif

    <script>
        window.addEventListener('swal:error', event => {
            // console.log(event.detail);
            Swal.fire({
                icon: 'error',
                title: event.detail[0].title,
                text: event.detail[0].text,
                showConfirmButton: true,
                timer: 5000
            });
        });
    </script>

    <script>
        window.addEventListener('swal:success', event => {
            // console.log(event.detail);
            Swal.fire({
                icon: 'success',
                title: event.detail[0].title,
                text: event.detail[0].text,
                showConfirmButton: true,
                timer: 5000
            });
        });
    </script>

    <div class="intro-y col-span-12 flex flex-wrap sm:flex-no-wrap items-center mt-2">
        <a href="{{ route('quality-page.qc-produk-jadi.wizard') }}">
            <button class="button text-white bg-theme-primary shadow-md mr-2">
                Selesaikan Produksi
            </button>
        </a>
        <a href="{{ route('produksi-produk-jadi.index') }}">
            <button class="button text-white bg-indigo-600 hover:bg-indigo-500 shadow-md mr-2">
                Produksi Produk Jadi
            </button>
        </a>
        <div class="hidden md:block mx-auto text-gray-600">Menampilkan 1 sampai 1 dari 1 data</div>
        <div class="w-full sm:w-auto mt-3 sm:mt-0 sm:ml-auto md:ml-0">
            <input type="text" wire:model.live='search' class="input w-56 box pr-10 placeholder-theme-13" placeholder="Cari...">
            <i class="w-4 h-4 absolute my-auto inset-y-0 mr-3 right-0" data-feather="search"></i>
        </div>
    </div>

    <div>
        <table class="table table-report -mt-2">
            <thead>
                <tr>
                    <th rowspan="2">Kode Produksi/List</th>
                    <th rowspan="2" class="text-center">Waktu Produksi</th>
                    <th rowspan="2" class="text-center">PN/SN</th>
                    <th colspan="2" class="text-center">Hasil QC</th>
                    <th rowspan="2" class="text-center">Final Grade</th>
                    <th rowspan="2" class="text-center">Qty</th>
                    <th rowspan="2" class="text-center">Unit Price</th>
                    <th rowspan="2" class="text-center">Sub Total</th>
                    <th rowspan="2" class="text-center">Tanggal Masuk Gudang</th>
                    <th rowspan="2" class="text-center w-72">Aksi</th>
                </tr>
                <tr>
                    <th class="text-center">QC 1</th>
                    <th class="text-center">QC 2</th>
                </tr>
            </thead>

            <tbody>
                @forelse($qcList as $item)
                    <tr class="intro-x">
                        <!-- Kode Produksi/List -->
                        <td>
                            <a href="{{ route('quality-page.qc-produk-jadi.view', $item->id) }}" class="font-medium">
                                {{ $item->produksiProdukJadi->kode_produksi ?? '-' }}
                            </a>
                            <div class="text-gray-600 text-xs">
                                {{ $item->kode_list }}
                            </div>
                        </td>

                        <!-- Waktu Produksi -->
                        <td class="text-center">
                            @if($item->mulai_produksi && $item->selesai_produksi)
                                {{ \Carbon\Carbon::parse($item->mulai_produksi)->format('d-m-Y') }} /
                                {{ \Carbon\Carbon::parse($item->selesai_produksi)->format('d-m-Y') }}
                                <div class="text-xs text-gray-500">
                                    {{ \Carbon\Carbon::parse($item->mulai_produksi)->diffForHumans($item->selesai_produksi, true) }}
                                </div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>

                        <td>
                            <div class="text-black text-sm">
                                {{ $item->produksiProdukJadi->dataProdukJadi->nama_produk }}
                            </div>
                            <div class="text-gray-600 text-xs">
                                {{ $item->serial_number ?? '-' }}
                            </div>
                        </td>

                        <td class="text-center">
                            @if($item->qc1)
                                <div class="font-medium text-blue-700 cursor-pointer"
                                    wire:click="$dispatch('open-edit-qc-modal', { id: {{ $item->qc1->id }}, qc: 1 })">
                                    {{ $item->qc1->kode_qc }}
                                </div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>

                        <td class="text-center">
                            @if($item->qc2)
                                <div class="font-medium text-blue-700 cursor-pointer"
                                    wire:click="$dispatch('open-edit-qc-modal', { id: {{ $item->qc2->id }}, qc: 2 })">
                                    {{ $item->qc2->kode_qc }}
                                </div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>

                        <td class="text-center font-semibold">
                            @if($item->qc2)
                                <span><img src="{{ asset('images/grade B.png') }}" alt="Grade B" class="h-16"></span>
                            @elseif($item->qc1)
                                @if($item->qc1->grade == 'A')
                                    <img src="{{ asset('images/grade A.png') }}" alt="Grade A" class="h-16 inline">
                                @elseif($item->qc1->grade == 'B')
                                    <img src="{{ asset('images/grade B.png') }}" alt="Grade B" class="h-16 inline">
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>

                        <!-- Qty -->
                        <td class="text-center">{{ number_format($item->qty, 2) }}</td>

                        <!-- Unit Price -->
                        <td class="text-center">Rp. {{ number_format($item->unit_price, 2, ',', '.') }}</td>

                        <!-- Sub Total -->
                        <td class="text-center">Rp. {{ number_format($item->sub_total, 2, ',', '.') }}</td>

                        <td class="text-center">
                            {{ $item->tanggal_masuk_gudang ? $item->tanggal_masuk_gudang->format('Y-m-d H:i:s') : '-' }}
                        </td>

                        <!-- Aksi -->
                        <td class="table-report__action w-72">
                            <div class="flex justify-start items-start space-x-4">

                                {{-- <a wire:ignore class="flex items-center text-theme-1"
                                href="{{ route('quality-page.qc-produk-jadi.view', $item->id) }}">
                                    <i data-feather="check-square" class="w-4 h-4 mr-1"></i> View
                                </a> --}}

                                @if(!$item->qc1)
                                    <button wire:ignore
                                        wire:click="$dispatch('open-qc-modal', { id: {{ $item->id }}, qc: 1 })"
                                        class="flex items-center text-blue-600 cursor-pointer">
                                        <i data-feather="check-circle" class="w-4 h-4 mr-1"></i> QC 1
                                    </button>
                                @endif

                                {{-- Tombol QC 2 (hanya muncul jika QC1 sudah ada & QC2 belum ada) --}}
                                @if($item->qc1 && !$item->qc2 && empty($item->serial_number))
                                    <button wire:ignore
                                        wire:click="$dispatch('open-qc-modal', { id: {{ $item->id }}, qc: 2 })"
                                        class="flex items-center text-green-600 cursor-pointer">
                                        <i data-feather="check-circle" class="w-4 h-4 mr-1"></i> QC 2
                                    </button>
                                @endif

                                @if(!$item->tanggal_masuk_gudang && $item->qc1)
                                    <button
                                        x-data wire:ignore
                                        x-on:click="$dispatch('open-gudang-modal', { id: {{ $item->id }} })"
                                        class="flex items-center text-green-600">
                                        <i data-feather="box" class="w-4 h-4 mr-1"></i> Add to Gudang
                                    </button>
                                @endif

                                <!-- Tombol Hapus -->
                                @if(empty($item->serial_number))
                                    <a wire:ignore wire:click="confirmDelete({{ $item->id }})"
                                    class="flex items-center text-red-600 cursor-pointer">
                                        <i data-feather="trash-2" class="w-4 h-4 mr-1"></i> Hapus
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-gray-500">
                            Tidak ada data.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-5">
            {{ $qcList->links() }}
        </div>
    </div>

    <!-- Modal QC -->
    <div x-data="{ open: false, qc: null, id: null }"
        x-on:open-qc-modal.window="
            open = true;
            qc = $event.detail.qc;
            id = $event.detail.id;
            if(qc == 2){
                $wire.set('grade', 'B'); // otomatis set grade ke B
            } else {
                $wire.set('grade', ''); // reset jika QC1
            }
        "
        x-show="open"
        class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50"
        style="display: none;">

        <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6" @click.outside="open = false; $wire.resetForm()">
            <h2 class="text-lg font-bold mb-4">Input QC <span x-text="qc"></span></h2>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Grade</label>
                <select
                    wire:model="grade"
                    class="input border w-full mt-1 text-sm"
                    :disabled="qc == 2"
                >
                    <option value="">Pilih Grade</option>
                    <option value="A" x-show="qc == 1">Grade A</option>
                    <option value="B">Grade B</option>
                </select>
                @error('grade') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Laporan QC</label>
                <input type="file" wire:model="laporan_qc" accept="application/pdf"
                    class="mt-1 block w-full text-sm text-gray-700 border border-gray-300 rounded-md shadow-sm"/>
                @error('laporan_qc') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Catatan</label>
                <textarea wire:model="catatan"
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm"
                    rows="3"></textarea>
                @error('catatan') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Dokumentasi QC</label>
                <div
                    class="p-5 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 hover:bg-gray-100 transition cursor-pointer">

                    <input
                        type="file"
                        id="dokumentasi-upload"
                        multiple
                        accept="image/png, image/jpeg, image/jpg, image/webp"
                        wire:model="dokumentasi"
                        class="hidden"
                    >

                    <label for="dokumentasi-upload" class="flex flex-col items-center justify-center cursor-pointer">
                        <svg class="w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7 16V4a1 1 0 011-1h8a1 1 0 011 1v12m-4-4h.01M5 20h14a2 2 0 002-2v-5a2 2 0 00-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        <p class="text-gray-600">Klik atau drag & drop gambar</p>
                        <p class="text-lg text-gray-400">PNG, JPG, JPEG, WEBP</p>
                    </label>

                    @if ($dokumentasi)
                        <div class="grid grid-cols-3 gap-2 mt-4">
                            @foreach ($dokumentasi as $index => $file)
                                <div class="relative w-full h-24 border rounded overflow-hidden">
                                    <button
                                        type="button"
                                        wire:click="removeDokumentasi({{ $index }})"
                                        style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
                                            background-color: #8b0000; color: white; font-size: 20px; width: 32px; height: 32px;
                                            display: flex; align-items: center; justify-content: center; border: none; border-radius: 50%;
                                            cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">
                                        ×
                                    </button>

                                    <img src="{{ $file->temporaryUrl() }}" alt="Preview"
                                        class="w-full h-full object-cover">
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                @error('dokumentasi.*') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>


            <div class="flex justify-end space-x-2">
                <button @click="open=false; $wire.resetForm()"  @click="open=false" class="px-4 py-2 bg-gray-300 rounded-lg">Batal</button>
                <button wire:click="simpanQc(id, qc)" @click="open=false"
                    class="px-4 py-2 bg-theme-1 text-white rounded-lg">
                    Simpan
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Edit QC -->
    <div x-data="{ open: false, qc: null, id: null }"
        x-on:open-edit-qc-modal.window="
            open = true;
            qc = $event.detail.qc;
            id = $event.detail.id;
            $wire.loadQcData(id, qc);
            if(qc == 2){
                $wire.set('grade', 'B'); // otomatis set grade ke B
            } else {
                $wire.set('grade', ''); // reset jika QC1
            }"
        x-show="open" class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50" style="display: none;">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6" @click.outside="open = false; $wire.resetForm()">
            <h2 class="text-lg font-bold mb-4">Edit QC <span x-text="qc"></span></h2>

            <!-- Grade -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Grade</label>
                <select wire:model="grade" class="input border w-full mt-1 text-sm" :disabled="qc == 2">
                    <option value="">Pilih Grade</option>
                    <option value="A" x-show="qc == 1">Grade A</option>
                    <option value="B">Grade B</option>
                </select>
                @error('grade') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Laporan QC (file PDF) -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Laporan QC</label>

                @if($laporan_qc_old)
                    <p class="text-sm text-gray-600 mb-2">
                        File lama: <a href="{{ Storage::url($laporan_qc_old) }}" target="_blank" class="text-blue-600 underline">Lihat PDF</a>
                    </p>
                @endif

                <input type="file" wire:model="laporan_qc" accept="application/pdf"
                    class="mt-1 block w-full text-sm text-gray-700 border border-gray-300 rounded-md shadow-sm"/>
                @error('laporan_qc') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Catatan</label>
                <textarea wire:model="catatan"
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm"
                    rows="3"></textarea>
                @error('catatan') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Dokumentasi QC</label>

                <div class="p-5 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 hover:bg-gray-100 transition">
                    <input type="file" id="dokumentasi-upload-edit" multiple
                        accept="image/png, image/jpeg, image/jpg, image/webp"
                        wire:model="dokumentasi"
                        class="hidden">
                    <label for="dokumentasi-upload-edit" class="flex flex-col items-center justify-center cursor-pointer">
                        <svg class="w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7 16V4a1 1 0 011-1h8a1 1 0 011 1v12m-4-4h.01M5 20h14a2 2 0 002-2v-5a2 2 0 00-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        <p class="text-gray-600">Klik atau drag & drop gambar</p>
                        <p class="text-lg text-gray-400">PNG, JPG, JPEG, WEBP</p>
                    </label>

                    <div class="grid grid-cols-3 gap-2 mt-4">
                        @if($dokumentasi_lama && count($dokumentasi_lama) > 0)
                            @foreach($dokumentasi_lama as $doc)
                                <div class="relative w-full h-24 border rounded overflow-hidden group">
                                    <img src="{{ Storage::url($doc->file_path) }}" class="w-full h-full object-cover">
                                    <button type="button"
                                            wire:click="hapusDokumentasi({{ $doc->id }})"
                                            style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
                                                    background-color: #8b0000; color: white; font-size: 20px; width: 32px; height: 32px;
                                                    display: flex; align-items: center; justify-content: center; border: none; border-radius: 50%;
                                                    cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">
                                        ×
                                    </button>
                                </div>
                            @endforeach
                        @endif

                        @if ($dokumentasi && count($dokumentasi) > 0)
                            @foreach ($dokumentasi as $index => $file)
                                <div class="relative w-full h-24 border rounded overflow-hidden group">
                                    <img src="{{ $file->temporaryUrl() }}" alt="Preview" class="w-full h-full object-cover">
                                    <button type="button"
                                            wire:click="removeDokumentasi({{ $index }})"
                                            style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
                                                    background-color: #8b0000; color: white; font-size: 20px; width: 32px; height: 32px;
                                                    display: flex; align-items: center; justify-content: center; border: none; border-radius: 50%;
                                                    cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">
                                        ×
                                    </button>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex justify-end space-x-2">
                <button @click="open=false" class="px-4 py-2 bg-gray-300 rounded-lg">Batal</button>
                <button wire:click="updateQc(id, qc)" @click="open=false"
                    class="px-4 py-2 bg-theme-1 text-white rounded-lg">
                    Update
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Konfirmasi + Input Serial Number --}}
    <div x-data="{ open: false, id: null, serial: '' }"
        x-on:open-gudang-modal.window="open = true; id = $event.detail.id;"
        x-show="open"
        class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50"
        style="display: none;"
        >
        <div @click.away="open = false"
            class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">

            <h2 class="text-lg font-bold text-gray-700 mb-4">Konfirmasi</h2>
            <p class="text-gray-600 mb-4">
                Apakah Anda yakin ingin memproses produk ini ke Gudang?
            </p>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-1">
                    Serial Number
                </label>
                <input type="text"
                    x-model="serial"
                    x-init="$watch('id', value => { $wire.call('generateSerialNumberLive', value).then(s => serial = s) })"
                    placeholder="Masukkan Serial Number"
                    class="w-full border rounded-lg p-2 focus:ring focus:ring-green-300">
            </div>

            <div class="flex justify-end space-x-3">
                <button @click="open = false"
                    class="px-4 py-2 bg-gray-300 rounded-lg">
                    Batal
                </button>
                <button
                    @click="$wire.prosesKeGudang(id, serial); open = false;"
                    class="px-4 py-2 rounded-lg bg-theme-1 text-white">
                    Ya, Proses
                </button>
            </div>
        </div>
    </div>

    <div
        x-data="{ open: false, id: null }"
        x-on:open-delete-modal.window="open = true; id = $event.detail.id;"
        x-show="open"
        class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50"
        style="display: none;"
        >
        <div @click.away="open = false"
            class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">

            <h2 class="text-lg font-bold text-gray-700 mb-4">Konfirmasi Hapus</h2>
            <p class="text-gray-600 mb-4">
                Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak bisa dibatalkan.
            </p>

            <div class="flex justify-end space-x-3">
                <button @click="open = false"
                        class="px-4 py-2 bg-gray-300 rounded-lg">
                    Batal
                </button>
                <button
                    @click="$wire.deleteItem(id); open = false;"
                    class="px-4 py-2 rounded-lg bg-theme-1 text-white">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>

</div>
