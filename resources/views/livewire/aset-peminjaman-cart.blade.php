<div>
    <input type="hidden" name="items" value="{{ $this->itemsJson }}">

    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold dark:text-white">
            Aset Dipinjam
            <span class="ml-1 align-middle inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-800">
                {{ count($items) }}
            </span>
        </h2>
        @error('items')
            <p class="text-sm text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <div class="relative overflow-x-auto shadow-md sm:rounded-lg pt-0">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">Nama Aset</th>
                    <th scope="col" class="px-6 py-3">Ruangan</th>
                    <th scope="col" class="px-6 py-3">Keterangan</th>
                    <th scope="col" class="px-6 py-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $id => $item)
                    {{-- Baris merah menandai aset yang saat dipilih masih dipegang orang lain,
                         supaya risikonya tetap terlihat sampai pengajuan dikirim. --}}
                    <tr wire:key="aset-peminjaman-cart-row-{{ $id }}"
                        @class([
                            'border-b dark:border-gray-700',
                            'bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/40' => $item['dipinjam_oleh'],
                            'bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-600' => !$item['dipinjam_oleh'],
                        ])>
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                            {{ $item['nama_barang'] }}
                            <div class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ $item['nomor_aset'] }}</div>
                            @if($item['kondisi'] === 'Rusak')
                                <span class="mt-1 inline-flex items-center rounded border border-red-400 bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-800">Rusak</span>
                            @endif
                            @if($item['dipinjam_oleh'])
                                <div class="mt-1 flex items-start gap-1 text-xs font-semibold text-red-700 dark:text-red-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-px h-3.5 w-3.5 shrink-0">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4" /><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" /><path d="M12 16h.01" />
                                    </svg>
                                    <span>
                                        Masih dipinjam {{ $item['dipinjam_oleh'] }}
                                        @if($item['dipinjam_sejak']) sejak {{ $item['dipinjam_sejak'] }} @endif
                                        &mdash; berisiko ditolak GA/HRD.
                                    </span>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">{{ $item['ruangan'] }}</td>
                        <td class="px-6 py-4">
                            <div class="flex justify-right items-right">
                                <textarea wire:model.blur="items.{{ $id }}.keterangan"
                                    class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block px-2.5 py-1 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                    placeholder="Opsional"></textarea>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <a href="#" class="font-medium text-red-600 dark:text-red-500 hover:underline"
                                wire:click.prevent="hapusAset({{ $id }})">
                                <svg class="w-6 h-6 text-red-800 dark:text-white" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd"
                                        d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm7.707-3.707a1 1 0 0 0-1.414 1.414L10.586 12l-2.293 2.293a1 1 0 1 0 1.414 1.414L12 13.414l2.293 2.293a1 1 0 0 0 1.414-1.414L13.414 12l2.293-2.293a1 1 0 0 0-1.414-1.414L12 10.586 9.707 8.293Z"
                                        clip-rule="evenodd" />
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr class="bg-white dark:bg-gray-800">
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
                            Belum ada aset dipilih. Klik <span class="font-semibold">Pilih Aset</span> pada daftar di sebelah kiri.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
