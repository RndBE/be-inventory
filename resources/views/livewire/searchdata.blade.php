{{--
    Kolom pencarian yang dipakai bersama seluruh tabel Livewire.

    debounceMs bersifat opsional dan TIDAK didaftarkan di @props: pada konteks
    @include, @props menimpa variabel yang namanya terdaftar dengan nilai
    defaultnya, sehingga nilai yang dikirim lewat @include justru hilang.
    Dibiarkan di luar @props, nilainya lolos apa adanya.

    Tanpa debounceMs, perilakunya sama seperti sebelumnya: Livewire sendiri sudah
    memberi debounce 150ms untuk text input pada wire:model.live. Tabel berat
    (rekap aset 16 kolom, pergerakan aset dengan enam filter) mengirim 400ms
    supaya tidak menembak query di tiap ketikan.
--}}
@props([
    'width' => 'full',
])

@php
    $debounce = isset($debounceMs) ? '.debounce.' . (int) $debounceMs . 'ms' : '';
@endphp

<div class="{{$width}} justify-self-end">
    {{-- Label tersembunyi, bukan hanya placeholder: placeholder hilang begitu
         pengguna mengetik, dan pembaca layar tidak mendapat nama kolom apa pun. --}}
    <label for="search" class="sr-only">Cari data</label>
    <div class="relative mt-2 flex items-center">
        {{-- span, bukan kbd: <kbd> menandai tombol yang harus ditekan pengguna,
             sedangkan ini cuma ikon hiasan. --}}
        <span class="absolute inset-y-0 right-0 flex items-center pr-3" aria-hidden="true">
            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
            </svg>
        </span>
        <input wire:model.live{{ $debounce }}='search' placeholder="Pencarian ..." type="search" name="search" id="search" class="block w-full rounded-md border-0 py-1.5 pr-10 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700 dark:bg-gray-800">
    </div>
</div>
