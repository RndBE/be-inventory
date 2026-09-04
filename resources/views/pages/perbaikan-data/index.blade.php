@section('title', 'Perbaikan Data | BE INVENTORY')
<x-app-layout>
    @php
        // Tab dipilih lewat query string, bukan properti Livewire, supaya
        // masing-masing tab punya alamat sendiri: tombol kembali browser dan
        // tautan yang dibagikan lewat WhatsApp mendarat di tab yang benar.
        // Tab Penunjukan digerbangi permissionnya sendiri, tapi permission itu
        // diberikan lewat migration ke setiap role yang sudah memegang
        // `lihat-perbaikan-data` — jadi tidak ada pelaksana yang terkunci dari
        // surat yang menugaskannya. Penyaringan barisnya tetap dikerjakan
        // komponen tabelnya: yang tidak boleh melihat semua hanya melihat surat
        // yang menyangkut dirinya.
        $bolehLihatPenunjukan = auth()->user()->can('lihat-penunjukan-perbaikan-data');
        $tab = request('tab') === 'penunjukan' && $bolehLihatPenunjukan ? 'penunjukan' : 'pengajuan';
    @endphp

    <div class="px-4 sm:px-6 lg:px-8 pt-8 w-full max-w-9xl mx-auto">
        <nav class="flex gap-1 border-b border-gray-200 dark:border-gray-700" aria-label="Tab Perbaikan Data">
            @php
                $aktif = 'border-indigo-600 text-indigo-600 dark:text-indigo-400';
                $pasif = 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400';
            @endphp

            <a href="{{ route('perbaikan-data.index') }}"
                @if($tab === 'pengajuan') aria-current="page" @endif
                class="border-b-2 px-4 py-2 text-sm font-medium {{ $tab === 'pengajuan' ? $aktif : $pasif }}">
                Pengajuan
            </a>

            @if($bolehLihatPenunjukan)
                <a href="{{ route('perbaikan-data.index', ['tab' => 'penunjukan']) }}"
                    @if($tab === 'penunjukan') aria-current="page" @endif
                    class="border-b-2 px-4 py-2 text-sm font-medium {{ $tab === 'penunjukan' ? $aktif : $pasif }}">
                    Penunjukan
                </a>
            @endif
        </nav>
    </div>

    {{-- Dua komponen terpisah, bukan satu yang berganti mode: kolom, query, dan
         aturan penyaringannya berbeda seluruhnya. --}}
    @if($tab === 'penunjukan')
        @livewire('penunjukan-perbaikan-data-table')
    @else
        @livewire('perbaikan-data-table')
    @endif
</x-app-layout>
