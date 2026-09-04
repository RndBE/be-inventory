<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
    @if (session('success'))
        <div id="successAlert" class="flex items-center p-4 mb-4 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800" role="alert">
            <div>
                <strong class="font-bold">Success!</strong>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div id="errorAlert" class="flex items-center p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800" role="alert">
            <div>
                <strong class="font-bold">Error!</strong>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <div class="sm:flex sm:justify-between sm:items-center mb-2">
        <div class="mb-4 sm:mb-0">
            <h6 class="text-2xl text-gray-800 dark:text-gray-100 font-bold">Penunjukan Perbaikan Data</h6>
            <p class="text-sm text-gray-500 mt-1">
                Surat penunjukan pelaksana. Isinya diambil dari kode pengajuan yang dipilih, bukan diketik ulang.
            </p>
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
                    @can('tambah-penunjukan-perbaikan-data')
                        <a href="{{ route('penunjukan-perbaikan-data.create') }}"
                            class="mt-2 block w-fit rounded-md py-1.5 px-2 bg-indigo-600 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            Terbitkan Penunjukan
                        </a>
                    @endcan
                </li>
            </ul>
        </div>
    </div>

    <div class="relative overflow-x-auto pt-2">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="p-4">No</th>
                        <th scope="col" class="px-6 py-3">Nomor Surat</th>
                        <th scope="col" class="px-6 py-3">Kode Pengajuan</th>
                        <th scope="col" class="px-6 py-3">Pelaksana Ditunjuk</th>
                        <th scope="col" class="px-6 py-3">Tgl Penunjukan</th>
                        <th scope="col" class="px-6 py-3">Pelaksanaan</th>
                        <th scope="col" class="px-6 py-3">Status</th>
                        <th scope="col" class="px-6 py-3">Berkas</th>
                        <th scope="col" class="px-6 py-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($daftarPenunjukan as $index => $baris)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4">
                                <div class="text-slate-800 dark:text-slate-100">{{ $daftarPenunjukan->firstItem() + $index }}</div>
                            </td>
                            <td class="px-6 py-3">
                                <strong>{{ $baris->nomorSuratCetak() }}</strong>
                                {{-- Kode internal tetap ditampilkan kecil: itu yang
                                     dipakai nama berkas unggahan dan pesan log,
                                     sementara nomor surat yang dipakai arsip. --}}
                                <div class="text-xs text-gray-400">{{ $baris->kode_penunjukan }}</div>
                            </td>
                            <td class="px-6 py-3">
                                @if($baris->perbaikanData)
                                    <a href="{{ route('perbaikan-data.show', $baris->perbaikan_data_id) }}"
                                        class="text-indigo-600 hover:underline">{{ $baris->perbaikanData->kode_pengajuan }}</a>
                                    <div class="text-xs text-gray-500">{{ $baris->perbaikanData->jenis }}</div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-3">{{ optional($baris->pelaksana)->name ?? '-' }}</td>
                            <td class="px-6 py-3">{{ optional($baris->tgl_penunjukan)->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-6 py-3">
                                @if($baris->sudahDilaksanakan())
                                    <div>{{ $baris->tgl_pelaksanaan->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $baris->nama_petugas ?: '-' }}</div>
                                @else
                                    <span class="text-xs text-gray-500">Belum diisi</span>
                                @endif
                            </td>
                            <td class="px-6 py-3">
                                {{-- Warnanya dipetakan lewat array, bukan @switch:
                                     daftar statusnya datang dari
                                     config/surat_penunjukan.php, dan @switch yang
                                     menulis ulang setiap nilainya akan diam-diam
                                     kehilangan warna begitu daftar di config
                                     berubah. --}}
                                @php
                                    $warnaStatus = [
                                        \App\Models\PenunjukanPerbaikanData::STATUS_AWAL => 'text-yellow-800 bg-yellow-100',
                                        'Selesai & Sesuai' => 'text-emerald-800 bg-emerald-100',
                                        'Selesai Sebagian' => 'text-indigo-800 bg-indigo-100',
                                        'Tidak Dapat Dilaksanakan' => 'text-red-800 bg-red-100',
                                    ];
                                @endphp
                                <span class="inline-flex items-center whitespace-nowrap px-2 py-1 text-xs font-semibold rounded-full {{ $warnaStatus[$baris->status] ?? 'text-gray-800 bg-gray-100' }}">
                                    {{ $baris->status ?: 'Tidak Diketahui' }}
                                </span>
                            </td>
                            <td class="px-6 py-3">
                                @if($baris->form_penunjukan)
                                    <a href="{{ asset('storage/' . $baris->form_penunjukan) }}" target="_blank"
                                        class="text-indigo-600 hover:underline text-xs">Form penunjukan</a>
                                @else
                                    <span class="text-xs text-amber-700">Belum diunggah</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 flex space-x-2">
                                <a href="{{ route('penunjukan-perbaikan-data.show', $baris->id) }}"
                                    title="Detail &amp; isi pelaksanaan"
                                    class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-indigo-600 hover:border-indigo-600">
                                    <svg class="w-[16px] h-[16px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-width="2" d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z"/>
                                        <path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                    </svg>
                                </a>

                                <a href="{{ route('penunjukan-perbaikan-data.pdf', $baris->id) }}"
                                    title="Unduh surat penunjukan (Word)"
                                    class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-slate-700 hover:border-slate-700">
                                    <svg class="w-[16px] h-[16px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linejoin="round" stroke-width="2" d="M16 19h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m8-14H8a1 1 0 0 0-1 1v4h10V6a1 1 0 0 0-1-1Zm-8 9h8v6H8v-6Z"/>
                                    </svg>
                                </a>

                                {{-- Ubah dan hapus muncul pada status apa pun.
                                     Unggahan surat bertanda tangan lewat form
                                     Ubah, dan kertasnya sering baru kembali
                                     setelah pelaksanaannya diisi. --}}
                                    @can('edit-penunjukan-perbaikan-data')
                                        <a href="{{ route('penunjukan-perbaikan-data.edit', $baris->id) }}"
                                            title="Ubah surat &amp; unggah berkas"
                                            class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-yellow-600 hover:border-yellow-600">
                                            <svg class="w-[16px] h-[16px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
                                            </svg>
                                        </a>
                                    @endcan

                                    @can('hapus-penunjukan-perbaikan-data')
                                        <button wire:click="konfirmasiHapus({{ $baris->id }})" type="button"
                                            title="Hapus surat penunjukan"
                                            class="rounded-md border border-slate-300 py-1 px-2 text-center text-xs transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-red-600 hover:border-red-600">
                                            <svg class="w-[16px] h-[16px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                                            </svg>
                                        </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td colspan="9" class="px-6 py-4 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900">Belum ada penunjukan</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    Surat penunjukan diterbitkan dari pengajuan yang sudah masuk di tab Pengajuan.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4">
            {{ $daftarPenunjukan->links() }}
        </div>

        @if($isDeleteModalOpen)
            @include('pages.penunjukan-perbaikan-data.remove')
        @endif
    </div>
</div>
