{{--
    Pemantauan pergerakan aset lintas seluruh aset.

    Sumbernya tabel riwayat_mutasi_aset yang sama dengan modal riwayat per aset,
    jadi keduanya tidak pernah menampilkan versi yang berbeda. Halaman ini murni
    baca — tidak ada aksi yang mengubah data, jadi tidak ada kolom aksi.
--}}
<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
    <x-app.memuat />

    <div class="sm:flex sm:justify-between sm:items-center mb-2">
        <div class="mb-4 sm:mb-0">
            <h6 class="text-2xl text-gray-800 dark:text-gray-100 font-bold">Pergerakan Aset</h6>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Perpindahan PIC &amp; ruangan dari semua jalur — form rekap aset, import Excel,
                peminjaman, pengembalian, dan serah terima aset.
            </p>
        </div>

        <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
            <ul class="flex flex-wrap -m-1">
                <li class="m-1">
                    @include('livewire.searchdata', ['debounceMs' => 400])
                </li>
                <li class="m-1">
                    @include('livewire.dataperpage')
                </li>
            </ul>
        </div>
    </div>

    @php
        $kelasKontrol = 'rounded-md border-0 py-1.5 pl-3 pr-8 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-600';
        $kelasTanggal = 'rounded-md border-0 py-1.5 px-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-600';
        $adaFilter = $jenis !== '' || $tujuan !== '' || $ruanganId !== '' || $orangId !== ''
            || $dariTanggal !== '' || $sampaiTanggal !== '' || $search !== '';
    @endphp

    <div class="flex flex-wrap items-center gap-2 pt-1 pb-3">
        <label class="flex items-center gap-1.5 text-xs font-medium text-gray-600 dark:text-gray-400">
            Jenis
            <select wire:model.live="jenis" class="{{ $kelasKontrol }}">
                <option value="">Semua</option>
                <option value="PIC">PIC</option>
                <option value="Ruangan">Ruangan</option>
            </select>
        </label>

        {{-- Inilah filter yang paling sering dipakai memantau: aset yang dilepas
             dari PIC atau dikeluarkan dari ruangan, alias kembali ke manajemen. --}}
        <label class="flex items-center gap-1.5 text-xs font-medium text-gray-600 dark:text-gray-400">
            Tujuan
            <select wire:model.live="tujuan" class="{{ $kelasKontrol }}">
                <option value="">Semua tujuan</option>
                <option value="manajemen">Dikembalikan ke manajemen</option>
            </select>
        </label>

        @if (count($opsiRuangan))
            <label class="flex items-center gap-1.5 text-xs font-medium text-gray-600 dark:text-gray-400">
                Ruangan
                <select wire:model.live="ruanganId" class="{{ $kelasKontrol }}">
                    <option value="">Semua ruangan</option>
                    @foreach ($opsiRuangan as $opsi)
                        <option value="{{ $opsi['nilai'] }}">{{ $opsi['label'] }}</option>
                    @endforeach
                </select>
            </label>
        @endif

        @if (count($opsiOrang))
            <label class="flex items-center gap-1.5 text-xs font-medium text-gray-600 dark:text-gray-400">
                Orang
                <select wire:model.live="orangId" class="{{ $kelasKontrol }}">
                    <option value="">Semua orang</option>
                    @foreach ($opsiOrang as $opsi)
                        <option value="{{ $opsi['nilai'] }}">{{ $opsi['label'] }}</option>
                    @endforeach
                </select>
            </label>
        @endif

        <label class="flex items-center gap-1.5 text-xs font-medium text-gray-600 dark:text-gray-400">
            Tanggal
            <input type="date" wire:model.live="dariTanggal" class="{{ $kelasTanggal }}" aria-label="Dari tanggal">
            <span class="text-gray-400">&ndash;</span>
            <input type="date" wire:model.live="sampaiTanggal" class="{{ $kelasTanggal }}" aria-label="Sampai tanggal">
        </label>

        @if ($adaFilter)
            <button type="button" wire:click="resetFilter"
                class="rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                Reset filter
            </button>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ $pergerakan->total() }} pergerakan cocok
            </span>
        @endif
    </div>

    <div class="relative overflow-x-auto">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            {{-- Diredupkan + dikunci klik selama permintaan berjalan. Halaman ini punya
                 enam filter wire:model.live, jadi tanpa penanda apa pun setiap perubahan
                 filter terasa seperti tidak terjadi apa-apa. --}}
            <table wire:loading.class.delay="opacity-50 pointer-events-none"
                class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">Waktu</th>
                        <th scope="col" class="px-6 py-3">Aset</th>
                        <th scope="col" class="px-6 py-3">Jenis</th>
                        <th scope="col" class="px-6 py-3">Perpindahan</th>
                        <th scope="col" class="px-6 py-3">Alasan</th>
                        <th scope="col" class="px-6 py-3">Dicatat Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pergerakan as $baris)
                        @php $keManajemen = empty($baris->ke_id); @endphp
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            {{-- Yang ditampilkan tanggal KEJADIAN, bukan waktu pengetikan.
                                 Kalau keduanya beda, waktu pencatatannya tetap disebut di
                                 bawahnya — supaya jelas catatannya dibuat belakangan, bukan
                                 disembunyikan. --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $baris->tanggal_efektif?->format('d/m/Y') ?? '-' }}
                                @if ($baris->dicatat_belakangan)
                                    <span class="block text-xs text-amber-600 dark:text-amber-500"
                                        title="Tanggal serah terima {{ $baris->tgl_kejadian->format('d/m/Y') }}, dicatat {{ $baris->created_at->format('d/m/Y H:i') }}">
                                        dicatat {{ $baris->created_at->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="block text-xs text-gray-400">{{ $baris->created_at?->format('H:i') }}</span>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ $baris->dataAset->barangAset->nama_barang ?? '-' }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $baris->dataAset->nomor_aset ?? 'aset sudah dihapus' }}
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <span @class([
                                    'rounded px-2 py-0.5 text-xs font-semibold',
                                    'bg-indigo-100 text-indigo-800' => $baris->jenis === 'PIC',
                                    'bg-teal-100 text-teal-800' => $baris->jenis !== 'PIC',
                                ])>{{ $baris->jenis }}</span>
                            </td>

                            <td class="px-6 py-4">
                                {{-- ringkasan dipakai bersama modal riwayat per aset, jadi
                                     kalimatnya selalu sama di kedua tempat. --}}
                                <div class="text-gray-900 dark:text-white">{{ $baris->ringkasan }}</div>
                                @if ($keManajemen)
                                    <span class="mt-1 inline-block rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
                                        Kembali ke manajemen
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                @if ($baris->keterangan)
                                    <span class="text-xs text-indigo-700 dark:text-indigo-400">{{ $baris->keterangan }}</span>
                                @else
                                    {{-- Baris lama dari sebelum alasan mutasi dilabeli. Ditandai
                                         apa adanya, bukan dikarang isinya. --}}
                                    <span class="text-xs text-gray-400 italic">tidak tercatat</span>
                                @endif

                                {{-- Bukti foto serah terima ke manajemen. Satu set foto dipakai
                                     bersama semua aset dalam pencatatan yang sama, jadi baris PIC
                                     dan Ruangan dari aset yang sama menampilkan foto yang sama. --}}
                                @if ($baris->pengembalianManajemen && $baris->pengembalianManajemen->buktiFoto->isNotEmpty())
                                    <div class="mt-1.5 flex flex-wrap gap-1">
                                        @foreach ($baris->pengembalianManajemen->buktiFoto as $bukti)
                                            <a href="{{ $bukti->url }}" target="_blank"
                                                title="Bukti serah terima ke manajemen"
                                                class="inline-block h-10 w-10 overflow-hidden rounded border border-gray-200 hover:border-indigo-400 dark:border-gray-600">
                                                <img src="{{ $bukti->url }}" alt="Bukti serah terima" class="h-full w-full object-cover">
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $baris->pencatat->name ?? 'sistem' }}
                            </td>
                        </tr>
                    @empty
                        <tr class="bg-white dark:bg-gray-800">
                            <td colspan="6" class="px-6 py-10 text-center">
                                <svg class="mx-auto h-10 w-10 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Tidak ada pergerakan</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    @if ($adaFilter)
                                        Coba ubah atau reset filternya.
                                    @else
                                        Riwayat akan terisi otomatis begitu PIC atau ruangan sebuah aset berubah.
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($pergerakan->hasPages())
        <div class="mt-4">
            {{ $pergerakan->links() }}
        </div>
    @endif
</div>
