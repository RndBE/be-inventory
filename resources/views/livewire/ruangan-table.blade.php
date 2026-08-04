<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
    <x-app.memuat />

    @if (session('success'))
        <x-app.alert type="success">
            <span class="font-medium">{{ session('success') }}</span>
        </x-app.alert>
    @endif

    @if (session('error'))
        <x-app.alert type="error">
            <span class="font-medium">{{ session('error') }}</span>
        </x-app.alert>
    @endif

    {{-- WAJIB ada di sini. Form tambah/ubah ruangan berada di dalam modal yang
         defaultnya hidden, sedangkan controller mengembalikan
         redirect()->back()->withErrors(). Blok @error di dalam modal tidak
         terlihat, dan halaman ini tadinya tidak merender $errors sama sekali —
         jadi kode ruangan yang bentrok gagal tanpa satu pun pesan: ruangannya
         cuma tidak terbuat. Isian yang diketik tetap tersimpan lewat old(),
         jadi cukup buka lagi modalnya. --}}
    @if ($errors->any())
        <x-app.alert type="error">
            @foreach ($errors->all() as $error)
                <span class="font-medium">{{ $error }}</span><br>
            @endforeach
        </x-app.alert>
    @endif
    <div class="sm:flex sm:justify-between sm:items-center mb-2">

        <div class="mb-4 sm:mb-0">
            <h6 class="text-2xl text-gray-800 dark:text-gray-100 font-bold">Ruangan Aset</h6>
        </div>

        <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
            <ul class="flex flex-wrap -m-1">
                <li class="m-1">
                    @include('livewire.searchdata', ['debounceMs' => 400])
                </li>
                <li class="m-1">
                    @include('livewire.dataperpage')
                </li>
                <li class="m-1">
                    @can('tambah-ruangan')
                        @include('pages.ruangan.create')
                    @endcan
                </li>
            </ul>
        </div>
    </div>

    <div class="relative overflow-x-auto pt-2">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            {{-- Diredupkan + dikunci klik selama permintaan berjalan. pointer-events-none
                 sengaja hanya di <table>, bukan di pembungkusnya, supaya kolom pencarian
                 dan filter di toolbar tetap bisa dipakai sambil menunggu. --}}
            <table wire:loading.class.delay="opacity-50 pointer-events-none"
                class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="p-4">
                            No
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Kode Ruangan
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Nama Ruangan
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Jumlah Aset
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Keterangan
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ruangans as $index => $row)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4"><div class="text-slate-800 dark:text-slate-100">{{ $ruangans->firstItem() + $index }}</div></td>
                            <td class="px-6 py-3">{{ $row->kode_ruangan }}</td>
                            <td class="px-6 py-3">{{ $row->nama_ruangan }}</td>
                            <td class="px-6 py-3 text-center">{{ $row->rekap_asets_count }}</td>
                            <td class="px-6 py-3">{{ $row->keterangan ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <div class="row flex space-x-2">
                                    {{-- <button>, bukan <a> tanpa href: anchor tanpa href tidak bisa
                                         difokus keyboard dan tidak punya focus ring, jadi aksinya cuma
                                         terjangkau lewat mouse. Ikonnya aria-hidden, jadi nama tombolnya
                                         datang dari sr-only — tanpa itu pembaca layar hanya mengumumkan
                                         "tombol" tanpa keterangan. --}}
                                    @can('edit-ruangan')
                                        <button wire:click="editRuangan({{$row->id}})" type="button" title="Ubah ruangan" class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-yellow-600 hover:border-yellow-600 focus:text-white focus:bg-yellow-600 focus:border-yellow-600 active:border-yellow-600 active:text-white active:bg-yellow-600 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                                            <svg class="w-[16px] h-[16px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
                                            </svg>
                                            <span class="sr-only">Ubah ruangan {{ $row->nama_ruangan }}</span>
                                        </button>
                                    @endcan

                                    @can('hapus-ruangan')
                                        <button wire:click="deleteRuangan({{$row->id}})" title="Hapus ruangan" class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-red-600 hover:border-red-600 focus:text-white focus:bg-red-600 focus:border-red-600 active:border-red-600 active:text-white active:bg-red-600 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none" type="button">
                                            <svg class="w-[16px] h-[16px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                                            </svg>
                                            <span class="sr-only">Hapus ruangan {{ $row->nama_ruangan }}</span>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td colspan="6" class="px-6 py-4 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Data Tidak Ditemukan!</h3>
                                <p class="mt-1 text-sm text-gray-500">Maaf, data yang Anda cari tidak ada</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4">
            {{ $ruangans->links() }}
        </div>
        {{-- MODAL --}}
        @if($isEditModalOpen)
            @include('pages.ruangan.edit')
        @endif

        @if($isDeleteModalOpen)
            @include('pages.ruangan.remove')
        @endif

    </div>
</div>
{{-- Timer alert sudah pindah ke <x-app.alert>: setTimeout di DOMContentLoaded
     tidak pernah jalan untuk alert yang muncul dari aksi Livewire. --}}
