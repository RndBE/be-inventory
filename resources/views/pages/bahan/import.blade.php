<button data-modal-target="import-export-bahan-modal" data-modal-toggle="import-export-bahan-modal" type="button"
    title="Import / Export" aria-label="Import / Export"
    class="mt-2 block w-fit rounded-md bg-green-600 px-2 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600">
    <svg class="h-[22px] w-[18px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 10V4a1 1 0 0 0-1-1H9.914a1 1 0 0 0-.707.293L5.293 7.207A1 1 0 0 0 5 7.914V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2M10 3v4a1 1 0 0 1-1 1H5m5 6h9m0 0-2-2m2 2-2 2"/>
    </svg>
    <span class="sr-only">Import / Export</span>
</button>

<div id="import-export-bahan-modal" tabindex="-1" aria-hidden="true"
    class="hidden fixed inset-0 z-50 h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden"
    style="background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px);">
    <div class="relative max-h-full w-full max-w-lg p-4">
        <div x-data="{ tab: '{{ $canImportBahan ? 'import' : 'export' }}' }"
            class="relative rounded-lg bg-white shadow dark:bg-gray-700">
            <div class="flex items-center justify-between rounded-t border-b p-4 dark:border-gray-600">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Import / Export Data Bahan</h3>
                <button type="button" data-modal-hide="import-export-bahan-modal"
                    class="ms-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-transparent text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Tutup modal</span>
                </button>
            </div>

            <div class="border-b border-gray-200 px-5 pt-4 dark:border-gray-600">
                <div class="flex gap-2" role="tablist" aria-label="Pilihan import atau export bahan">
                    @if ($canImportBahan)
                        <button type="button" role="tab" x-on:click="tab = 'import'"
                            x-bind:aria-selected="tab === 'import'"
                            x-bind:class="tab === 'import' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                            class="border-b-2 px-4 py-2 text-sm font-semibold">
                            Import
                        </button>
                    @endif
                    @if ($canExportBahan)
                        <button type="button" role="tab" x-on:click="tab = 'export'"
                            x-bind:aria-selected="tab === 'export'"
                            x-bind:class="tab === 'export' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                            class="border-b-2 px-4 py-2 text-sm font-semibold">
                            Export
                        </button>
                    @endif
                </div>
            </div>

            @if ($canImportBahan)
                <form x-cloak x-show="tab === 'import'" action="{{ route('bahan.import') }}" method="POST"
                    enctype="multipart/form-data" class="p-5">
                    @csrf
                    <label for="file-import-bahan" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                        Pilih file Excel atau CSV
                    </label>
                    <input id="file-import-bahan" name="file" type="file" required accept=".xlsx,.xls,.csv"
                        class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-gray-50 text-sm text-gray-900 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400">
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-300">
                        Kode Bahan menjadi kunci pencocokan. Kode yang sudah ada diperbarui dan kode baru ditambahkan.
                        Jenis Bahan, Satuan Unit, dan Supplier harus sudah terdaftar. Maksimal 10 MB.
                    </p>

                    <button type="submit"
                        class="mt-5 w-full rounded-lg bg-indigo-600 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-300 dark:focus:ring-indigo-800">
                        Upload &amp; Import
                    </button>
                </form>
            @endif

            @if ($canExportBahan)
                <div x-cloak x-show="tab === 'export'" class="p-5">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Unduh seluruh data bahan dalam format Excel. File ini juga dapat diedit dan digunakan kembali pada tab Import.
                    </p>
                    <a href="{{ route('bahan.export') }}"
                        class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-300 dark:focus:ring-emerald-800">
                        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0 4-4m-4 4-4-4M5 14v5a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-5"/>
                        </svg>
                        Export Data Bahan
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
