<div class="relative overflow-x-auto shadow-md sm:rounded-lg">
    <div class="flex flex-wrap sm:flex-nowrap items-center justify-between space-y-3">

        <div class="flex flex-wrap items-center space-x-3 w-full">
            @include('livewire.searchdata')

            @include('livewire.dataperpage')
        </div>
        <a href="{{ route('bahan.create') }}" class="inline-flex rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            Tambah
        </a>
    </div>



    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="p-4">
                    No
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
                    Stok Awal
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
                    <td class="px-6 py-4">
                        <img src="{{ $row->gambar ? asset('images/' . $row->gambar) : asset('images/image-4@2x.jpg') }}" alt="Gambar {{ $row->nama_bahan }}" class="h-auto w-24 rounded-lg">
                        {{-- @php
                            dd(asset('images/' . $row->gambar));
                        @endphp --}}

                    </td>
                    <td class="px-6 py-3">{{ $row->kode_bahan }}</td>
                    <td class="px-6 py-3">{{ $row->nama_bahan }}</td>
                    <td class="px-6 py-3">{{ $row->jenisBahan->nama ?? 'N/A' }}</td>
                    <td class="px-6 py-4"><span class="bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-green-400 border border-green-400">{{ $row->stok_awal }} {{ $row->dataUnit->nama ?? 'N/A' }}</span></td>
                    <td class="px-6 py-4">
                        <span class="bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-green-400 border border-green-400">
                            {{ $row->total_stok }} {{ $row->dataUnit->nama ?? 'N/A' }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="row flex space-x-2">
                            <!-- Detail Button -->
                            <button wire:click="showBahan({{$row->id}})" data-modal-target="showbahan-modal" data-modal-toggle="showbahan-modal" class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-blue-600 hover:border-blue-600 focus:text-white focus:bg-blue-600 focus:border-blue-600" type="button">
                                <svg class="w-[16px] h-[16px] text-gray-800 dark:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-width="2" d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z"/><path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            </button>

                            <!-- Edit Button -->
                            <a href="{{ route('bahan.edit', $row->id) }}" class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-yellow-600 hover:border-yellow-600 focus:text-white focus:bg-yellow-600 focus:border-yellow-600 active:border-yellow-600 active:text-white active:bg-yellow-600 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                                <svg class="w-[16px] h-[16px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
                                </svg>
                            </a>

                            <button wire:click="deleteBahan({{$row->id}})" data-modal-target="deletebahan-modal" data-modal-toggle="deletebahan-modal" class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-red-600 hover:border-red-600 focus:text-white focus:bg-red-600 focus:border-red-600 active:border-red-600 active:text-white active:bg-red-600 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none" type="button">
                                <svg class="w-[16px] h-[16px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                    <td colspan="8" class="px-6 py-4 text-center">
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

    <div class="px-6 py-4">
        {{ $bahans->links() }}
    </div>
    {{-- MODAL --}}
    @include('pages.bahan.show')
    @include('pages.bahan.remove')
</div>
