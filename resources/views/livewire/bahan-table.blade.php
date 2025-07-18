<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
    @if (session('success'))
        <div id="successAlert" class="flex items-center p-4 mb-4 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800" role="alert">
            <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
            </svg>
            <span class="sr-only">Info</span>
            <div>
                <strong class="font-bold">Success!</strong>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div id="errorAlert" class="flex items-center p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800" role="alert">
            <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
            </svg>
            <span class="sr-only">Info</span>
            <div>
                <strong class="font-bold">Error!</strong>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
        </div>
    @endif
    <div class="sm:flex sm:justify-between sm:items-center mb-2">

        <div class="mb-4 sm:mb-0">
            <h6 class="text-4xl text-gray-800 dark:text-gray-100 font-bold">Bahan</h6>
        </div>

        <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
            <ul class="flex flex-wrap -m-1">
                <li class="m-1">
                    @include('livewire.searchdata')
                </li>
                <li class="m-1">
                    @include('livewire.dataperpage')
                </li>
                <li class="m-1">
                    @can('export-bahan')
                        <a href="{{ route('bahan.export') }}" class="mt-2 block w-fit rounded-md py-1.5 px-2 bg-green-600 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600">
                            <svg class="w-[18px] h-[22px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 10V4a1 1 0 0 0-1-1H9.914a1 1 0 0 0-.707.293L5.293 7.207A1 1 0 0 0 5 7.914V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2M10 3v4a1 1 0 0 1-1 1H5m5 6h9m0 0-2-2m2 2-2 2"/>
                            </svg>
                        </a>
                    @endcan
                </li>
                {{-- <li class="m-1">
                    @can('edit-bahan')
                        <button id="bulk-edit-button" wire:click="bulkEdit"
                            class="mt-2 block w-fit rounded-md py-1.5 px-3 bg-yellow-600 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500 disabled:bg-gray-300 disabled:cursor-not-allowed" disabled="selectedIds.length === 0">
                            Edit
                        </button>
                    @endcan
                </li> --}}
                <li class="m-1">
                    @can('tambah-bahan')
                        <a href="{{ route('bahan.create') }}" class="mt-2 block w-fit rounded-md py-1.5 px-3 bg-indigo-600 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            Tambah
                        </a>
                    @endcan
                </li>

            </ul>
        </div>
    </div>

    <ul class="flex flex-wrap -m-1">

    </ul>
    <div class="relative overflow-x-auto pt-2">
        {{-- <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="p-4">
                            No
                        </th>
                        <th scope="col" class="p-4">
                            <div class="flex items-center">

                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Gambar
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Kode Bahan
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Nama Bahan
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Jenis Bahan
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Supplier
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Total Stok
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bahans as $index => $row)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4"><div class="text-slate-800 dark:text-slate-100">{{ $bahans->firstItem() + $index }}</div></td>
                            <td class="w-4 p-4">
                                <div class="flex items-center">
                                    <input type="checkbox" wire:model="selectedIds" value="{{ $row->id }}" class="checkbox-row w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">

                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <img src="{{ $row->gambar ? asset('storage/' . $row->gambar) : asset('images/image-4@2x.jpg') }}" alt="Gambar {{ $row->nama_bahan }}" class="h-auto w-24 rounded-lg">
                            </td>
                            <td class="px-6 py-3">{{ $row->kode_bahan }}</td>
                            <td class="px-6 py-3">{{ $row->nama_bahan }}</td>
                            <td class="px-6 py-3">{{ $row->jenisBahan->nama ?? 'N/A' }}</td>
                            <td class="px-6 py-4">{{ $row->dataSupplier->nama ?? 'N/A' }}</td>
                            <td class="px-6 py-4">
                                <span class="bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-green-400 border border-green-400">
                                    {{ $row->total_stok }} {{ $row->dataUnit->nama ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="row flex space-x-2">
                                    @can('detail-bahan')
                                        <button wire:click="showBahan({{$row->id}})" class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-blue-600 hover:border-blue-600 focus:text-white focus:bg-blue-600 focus:border-blue-600" type="button">
                                            <svg class="w-[16px] h-[16px] text-gray-800 dark:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z"/><path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                        </button>
                                    @endcan

                                    @can('edit-bahan')
                                        <a href="{{ route('bahan.edit', $row->id) }}" class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-yellow-600 hover:border-yellow-600 focus:text-white focus:bg-yellow-600 focus:border-yellow-600 active:border-yellow-600 active:text-white active:bg-yellow-600 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                                            <svg class="w-[16px] h-[16px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
                                            </svg>
                                        </a>
                                    @endcan

                                    @can('hapus-bahan')
                                        <button wire:click="deleteBahan({{$row->id}})" class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-red-600 hover:border-red-600 focus:text-white focus:bg-red-600 focus:border-red-600 active:border-red-600 active:text-white active:bg-red-600 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none" type="button">
                                            <svg class="w-[16px] h-[16px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                                            </svg>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td colspan="9" class="px-6 py-4 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900">Data Tidak Ditemukan!</h3>
                                <p class="mt-1 text-sm text-gray-500">Maaf, data yang Anda cari tidak ada</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div> --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 p-4">
            @forelse($bahans as $row)
                <div
                    wire:click="showBahan({{ $row->id }})"
                    class="cursor-pointer group relative bg-white border border-gray-200 rounded-lg overflow-hidden shadow hover:shadow-md transition-all duration-300 min-h-[450px]"
                >
                    {{-- Dropdown Button --}}
                    <div class="absolute top-2 right-2 z-10">
                        <div x-data="{ open: false }" class="relative">
                            <button
                                @click.stop="open = !open"
                                class="text-gray-600 hover:text-gray-900 border border-gray-300 rounded-full p-1 bg-white shadow-sm"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12h.01M12 12h.01M18 12h.01"/>
                                </svg>
                            </button>

                            <div
                                x-cloak
                                x-show="open"
                                x-transition.origin.top.right.duration.150ms
                                @click.away="open = false"
                                class="absolute right-0 mt-2 w-32 bg-white border border-gray-200 rounded shadow text-sm z-20"
                            >
                                @can('edit-bahan')
                                    <a href="{{ route('bahan.edit', ['id' => $row->id, 'page' => $bahans->currentPage()]) }}" @click.stop class="block px-4 py-2 hover:bg-gray-100 text-gray-700" >Edit</a>
                                @endcan
                                @can('hapus-bahan')
                                    <button
                                        wire:click.stop="deleteBahan({{ $row->id }}, {{ $bahans->currentPage() }})"
                                        class="w-full text-left px-4 py-2 hover:bg-red-100 text-red-600"
                                    >Hapus</button>
                                @endcan
                            </div>
                        </div>
                    </div>


                    {{-- Gambar --}}
                    <img src="{{ $row->gambar ? asset('storage/' . $row->gambar) : asset('images/image-4@2x.jpg') }}"
                        alt="Gambar {{ $row->nama_bahan }}"
                        class="w-full h-64 object-cover rounded-t-lg"
                    >

                    {{-- Konten --}}
                    <div class="p-4 relative">
                        {{-- Nama Bahan --}}
                        <h3 class="text-xl font-bold text-slate-800 mb-2">{{ $row->nama_bahan }}</h3>

                        {{-- Stok di pojok kanan atas konten --}}
                        <div class="absolute top-12 right-4">
                            <span class="inline-flex items-center gap-1
                                text-sm font-medium px-2.5 py-0.5 rounded-xl border
                                {{ $row->total_stok == 0
                                    ? 'bg-red-100 text-red-800 border-red-400'
                                    : 'bg-green-100 text-green-800 border-green-400' }}">
                                <i class="fas fa-box"></i>
                                {{ $row->total_stok }} {{ $row->dataUnit->nama ?? 'N/A' }}
                            </span>
                        </div>

                        {{-- Detail Informasi --}}
                        <div class="mt-10 space-y-1 text-sm text-gray-600">
                            {{-- Kode Bahan --}}
                            <p class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z" />
                                </svg>
                                {{ $row->kode_bahan }}
                            </p>
                            {{-- Jenis Bahan --}}
                            <p class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                                </svg>
                                {{ $row->jenisBahan->nama ?? 'N/A' }}
                            </p>
                            <p class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.737 5.1a3.375 3.375 0 0 1 2.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 0 1 .9 2.7m0 0a3 3 0 0 1-3 3m0 3h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Zm-3 6h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Z" />
                                </svg>
                                {{ $row->penempatan ?? 'N/A' }}
                            </p>
                            {{-- Supplier --}}
                            <p class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                </svg>

                                {{ $row->dataSupplier->nama ?? 'N/A' }}
                            </p>
                        </div>

                    </div>

                </div>
            @empty
                <div class="col-span-full text-center py-10 text-gray-500">
                    <p class="text-sm">Data tidak ditemukan.</p>
                </div>
            @endforelse
        </div>

        <div class="px-6 py-4">
            {{ $bahans->links() }}
        </div>
        @if ($isDeleteModalOpen)
                @include('pages.bahan.remove')
        @endif
        @if ($isShowModalOpen)
                @include('pages.bahan.show')
        @endif
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Atur waktu delay dalam milidetik (contoh: 5000 = 5 detik)
        const delay = 5000;

        // Menghilangkan alert sukses
        const successAlert = document.getElementById('successAlert');
        if (successAlert) {
            setTimeout(() => {
                successAlert.style.display = 'none';
            }, delay);
        }

        // Menghilangkan alert error
        const errorAlert = document.getElementById('errorAlert');
        if (errorAlert) {
            setTimeout(() => {
                errorAlert.style.display = 'none';
            }, delay);
        }
    });
</script>

{{-- <script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAllCheckbox = document.getElementById('checkbox-all-search');
        const checkboxes = document.querySelectorAll('.checkbox-row');

        selectAllCheckbox.addEventListener('change', function () {
            const isChecked = this.checked;
            checkboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
        });

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                if (!this.checked) {
                    selectAllCheckbox.checked = false;
                } else if (Array.from(checkboxes).every(cb => cb.checked)) {
                    selectAllCheckbox.checked = true;
                }
            });
        });
    });
</script> --}}

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // const selectAllCheckbox = document.getElementById('checkbox-all-search');
        const checkboxes = document.querySelectorAll('.checkbox-row');
        const bulkEditButton = document.getElementById('bulk-edit-button');

        const updateButtonState = () => {
            const selectedCheckboxes = Array.from(checkboxes).filter(cb => cb.checked);
            bulkEditButton.disabled = selectedCheckboxes.length === 0;
        };

        // selectAllCheckbox.addEventListener('change', function () {
        //     const isChecked = this.checked;
        //     checkboxes.forEach(checkbox => {
        //         checkbox.checked = isChecked;
        //     });
        //     updateButtonState();
        // });

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                if (!this.checked) {
                    selectAllCheckbox.checked = false;
                } else if (Array.from(checkboxes).every(cb => cb.checked)) {
                    selectAllCheckbox.checked = true;
                }
                updateButtonState();
            });
        });
    });
</script>


