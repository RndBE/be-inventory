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
            <h6 class="text-2xl text-gray-800 dark:text-gray-100 font-bold">Bahan Keluar</h6>
        </div>

        <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">

        </div>
    </div>
    <div class="relative overflow-x-auto">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <div class="flex flex-column sm:flex-row flex-wrap space-y-4 sm:space-y-0 items-center justify-between pb-4">
                <div class="mb-4 sm:mb-0">
                    <ul class="flex flex-wrap -m-1">
                        <li class="m-1">
                            <button wire:click="setFilter('semua')" type="button"
                                class="group dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700 text-black dark:border-gray-500 bg-transparent border border-black font-medium rounded-full px-3 py-1 text-sm text-center transition duration-200 ease-in-out
                                hover:bg-black hover:text-white focus:bg-black focus:text-white group-focus:bg-black group-focus:text-white
                                focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-black {{ is_null($filter) ? 'dark:bg-gray-700 bg-black text-black' : '' }}">
                                Lihat Semua
                            </button>
                        </li>
                        <li class="m-1">
                            <button wire:click="setFilter('Belum disetujui')" type="button"
                                class="group dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700 text-black dark:border-gray-500 bg-transparent border border-black font-medium rounded-full px-3 py-1 text-sm text-center transition duration-200 ease-in-out
                                hover:bg-black hover:text-white focus:bg-black focus:text-white group-focus:bg-black group-focus:text-white
                                focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-black {{ $filter === 'Belum disetujui' ? 'dark:bg-gray-700 bg-black text-black' : '' }}">
                                Belum disetujui
                            </button>
                        </li>
                        <li class="m-1">
                            <button wire:click="setFilter('Disetujui')" type="button"
                                class="group dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700 text-black dark:border-gray-500 bg-transparent border border-black font-medium rounded-full px-3 py-1 text-sm text-center transition duration-200 ease-in-out
                                hover:bg-black hover:text-white focus:bg-black focus:text-white group-focus:bg-black group-focus:text-white
                                focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-black {{ $filter === 'Disetujui' ? 'dark:bg-gray-700 bg-black text-black' : '' }}">
                                Disetujui
                            </button>
                        </li>
                        <li class="m-1">
                            <button wire:click="setFilter('Ditolak')" type="button"
                                class="group dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700 text-black dark:border-gray-500 bg-transparent border border-black font-medium rounded-full px-3 py-1 text-sm text-center transition duration-200 ease-in-out
                                hover:bg-black hover:text-white focus:bg-black focus:text-white group-focus:bg-black group-focus:text-white
                                focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-black {{ $filter === 'Ditolak' ? 'dark:bg-gray-700 bg-black text-black' : '' }}">
                                Ditolak
                            </button>
                        </li>

                    </ul>
                </div>
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                @include('livewire.searchdata')
                @include('livewire.dataperpage')
            </div>
            </div>
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="p-4">No</th>
                        <th scope="col" class="px-6 py-3">Kode Transaksi</th>
                        <th scope="col" class="px-6 py-3">Tanggal Pengajuan</th>
                        <th scope="col" class="px-6 py-3">Tanggal Keluar</th>
                        <th scope="col" class="px-6 py-3">Tujuan</th>
                        <th scope="col" class="px-6 py-3">Pengaju</th>
                        <th scope="col" class="px-6 py-3">Total Item</th>
                        <th scope="col" class="px-6 py-3">Total Harga</th>
                        <th scope="col" class="px-6 py-3">Status Pengambilan</th>
                        <th scope="col" class="px-6 py-3">Status Pengajuan</th>
                        <th scope="col" class="px-6 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bahan_keluars as $index => $bahan_keluar)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4"><div class="text-slate-800 dark:text-slate-100">{{ $bahan_keluars->firstItem() + $index }}</div></td>
                            <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                <button class="text-blue-600 hover:underline" type="button" wire:click="showBahanKeluar({{$bahan_keluar->id}})">
                                    {{ $bahan_keluar->kode_transaksi }}
                                </button>
                            </th>
                            <td class="px-6 py-4">{{ $bahan_keluar->tgl_pengajuan }}</td>
                            <td class="px-6 py-4">{{ $bahan_keluar->tgl_keluar }}</td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $bahan_keluar->tujuan ?? null }}
                                </div>
                                <div class="text-xs text-gray-500">{{ $bahan_keluar->keterangan }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $bahan_keluar->dataUser?->name ?? '-' }}
                                </div>
                                <div class="text-xs text-gray-500">{{ $bahan_keluar->divisi }}</div>
                            </td>
                            <td class="px-6 py-4">{{ $bahan_keluar->bahanKeluarDetails->sum('qty') }}</td>
                            <td class="px-6 py-4">Rp {{ number_format($bahan_keluar->bahanKeluarDetails->sum('sub_total'), 2, ',', '.') }}</td>
                            <td class="px-6 py-4">
                                @if ($bahan_keluar->status_pengambilan == 'Belum Diambil')
                                    <span class="bg-blue-100 me-2 px-2.5 py-0.5 rounded-full text-blue-800 text-xs font-medium dark:bg-gray-700 dark:text-blue-400 border border-blue-400">{{ $bahan_keluar->status_pengambilan }}</span>
                                @elseif($bahan_keluar->status_pengambilan == 'Sudah Diambil')
                                    <span class="bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-gray-700 dark:text-green-400 border border-green-100">{{ $bahan_keluar->status_pengambilan }}</span>
                                @else
                                    <span class="bg-red-100 text-red-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-gray-700 dark:text-red-400 border border-red-100">{{ $bahan_keluar->status_pengambilan ?? 'Null' }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($bahan_keluar->status == 'Belum disetujui')
                                    <span class="bg-blue-100 me-2 px-2.5 py-0.5 rounded-full text-blue-800 text-xs font-medium dark:bg-gray-700 dark:text-blue-400 border border-blue-400">{{ $bahan_keluar->status }}</span>
                                @elseif($bahan_keluar->status == 'Disetujui')
                                    <span class="bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-gray-700 dark:text-green-400 border border-green-100">{{ $bahan_keluar->status }}</span>
                                @else
                                    <span class="bg-red-100 text-red-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-gray-700 dark:text-red-400 border border-red-100">{{ $bahan_keluar->status }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="row flex space-x-2">
                                    @if($bahan_keluar->status === 'Disetujui')
                                        <a href="{{ route('bahan-keluars.downloadPdf', $bahan_keluar->id) }}" target="__blank"
                                            class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-red-600 hover:border-red-600 focus:text-white focus:bg-red-600 focus:border-red-600 active:border-red-600 active:text-white active:bg-red-600 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none flex justify-center items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-[16px] h-[16px] icon icon-tabler icons-tabler-outline icon-tabler-file-type-pdf">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4" />
                                                <path d="M5 18h1.5a1.5 1.5 0 0 0 0 -3h-1.5v6" /><path d="M17 18h2" /><path d="M20 15h-3v6" /><path d="M11 15v6h1a2 2 0 0 0 2 -2v-2a2 2 0 0 0 -2 -2h-1z" />
                                            </svg>
                                        </a>
                                    @endif
                                    @if($bahan_keluar->status !== 'Disetujui' && $bahan_keluar->status !== 'Ditolak' && $bahan_keluar->status_leader === 'Disetujui')
                                        @can('edit-bahan-keluar')
                                            <a href="{{ route('bahan-keluars.edit', $bahan_keluar->id) }}"
                                                class="flex items-center justify-center rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-yellow-600 hover:border-yellow-600 focus:text-white focus:bg-yellow-600 focus:border-yellow-600 active:border-yellow-600 active:text-white active:bg-yellow-600 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none">
                                                <svg class="w-[16px] h-[16px] text-gray-800 dark:text-white"
                                                    aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
                                                </svg>
                                            </a>
                                        @endcan
                                    @endif
                                    @if($bahan_keluar->status_pengambilan === 'Belum Diambil' && $bahan_keluar->status === 'Disetujui')
                                        @can('edit-pengambilan')
                                            <button wire:click="editPengambilanBahanKeluar({{ $bahan_keluar->id }})" class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-yellow-600 hover:border-yellow-600 focus:text-white focus:bg-yellow-600 focus:border-yellow-600 active:border-yellow-600 active:text-white active:bg-yellow-600 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none" type="button">
                                                <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trolley"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M11 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M6 16l3 2" /><path d="M12 17l8 -12" /><path d="M17 10l2 1" /><path d="M9.592 4.695l3.306 2.104a1.3 1.3 0 0 1 .396 1.8l-3.094 4.811a1.3 1.3 0 0 1 -1.792 .394l-3.306 -2.104a1.3 1.3 0 0 1 -.396 -1.8l3.094 -4.81a1.3 1.3 0 0 1 1.792 -.394z" /></svg>
                                            </button>
                                        @endcan
                                    @endif
                                    @if($bahan_keluar->status === 'Disetujui')
                                        @can('edit-bahan-keluar')
                                            <form method="POST" action="{{ route('send.siap-ambil', $bahan_keluar->id) }}">
                                                @csrf
                                                <button class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-yellow-600 hover:border-yellow-600 focus:text-white focus:bg-yellow-600 focus:border-yellow-600 active:border-yellow-600 active:text-white active:bg-yellow-600 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none" type="submit">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="w-[16px] h-[16px] icon icon-tabler icons-tabler-outline icon-tabler-send text-gray-800 dark:text-white">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                        <path d="M10 14l11 -11"></path>
                                                        <path d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-3.5 -7l-7 -3.5a.55 .55 0 0 1 0 -1l18 -6.5"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endcan
                                    @endif
                                    @if($bahan_keluar->status !== 'Disetujui' && $bahan_keluar->status !== 'Ditolak')
                                        @can('edit-approve-leader')
                                            @if($bahan_keluar->status_leader !== 'Disetujui' && $bahan_keluar->status_leader !== 'Ditolak')
                                                <button wire:click="editBahanKeluar({{ $bahan_keluar->id }})" class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-yellow-600 hover:border-yellow-600 focus:text-white focus:bg-yellow-600 focus:border-yellow-600 active:border-yellow-600 active:text-white active:bg-yellow-600 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none" type="button">Approve Leader
                                                </button>
                                            @endif
                                        @endcan
                                        @can('hapus-bahan-keluar')
                                            @if($bahan_keluar->status !== 'Disetujui')
                                                <button wire:click="deleteBahanKeluars({{ $bahan_keluar->id }})" class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-red-600 hover:border-red-600 focus:text-white focus:bg-red-600 focus:border-red-600 active:border-red-600 active:text-white active:bg-red-600 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none" type="button">
                                                    <svg class="w-[16px] h-[16px] text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td colspan="11" class="px-6 py-4 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-500">Data Tidak Ditemukan!</h3>
                                <p class="mt-1 text-sm text-gray-500">Maaf, data yang Anda cari tidak ada</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>



        <!-- Table -->
        <div class="px-6 py-4">
            {{$bahan_keluars->links()}}
        </div>
        {{-- MODAL --}}
        @include('pages.bahan-keluars.test')
        @include('pages.bahan-keluars.approval-leader')
        @include('pages.bahan-keluars.edit-pengambilan')
        @include('pages.bahan-keluars.remove')
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
