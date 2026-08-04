{{--
    Pemberitahuan sukses/gagal.

    Menggantikan blok alert yang sebelumnya disalin di tiap halaman bersama
    <script> berisi setTimeout pada DOMContentLoaded. Pola itu punya dua
    masalah:

      - DOMContentLoaded hanya menyala sekali saat halaman dimuat. Alert yang
        muncul dari aksi Livewire (approve, catat pengembalian, tandai selesai)
        tidak pernah menjalankan timer-nya, jadi menempel selamanya.
      - Tidak ada tombol tutup, sehingga alert yang menempel tidak bisa
        dihilangkan tanpa memuat ulang halaman.

    Timer-nya sekarang dipegang Alpine lewat x-init, yang berjalan setiap kali
    elemennya masuk DOM — baik dari page load maupun dari patch Livewire.

    Alert gagal sengaja TIDAK hilang sendiri: isinya perlu dibaca, dan pesan
    kesalahan yang lenyap setelah lima detik memaksa pengguna mengulang aksinya
    hanya untuk membaca alasannya. Yang sukses tetap hilang sendiri karena cuma
    kabar sekilas.
--}}
@props([
    'type' => 'success',
    'sembunyiOtomatis' => null,
    'jeda' => 5000,
])

@php
    $gaya = match ($type) {
        'error' => [
            'wadah' => 'text-red-800 border-red-300 bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800',
            'tombol' => 'text-red-600 hover:bg-red-100 dark:text-red-400 dark:hover:bg-gray-700',
            'judul' => 'Error!',
        ],
        default => [
            'wadah' => 'text-green-800 border-green-300 bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800',
            'tombol' => 'text-green-600 hover:bg-green-100 dark:text-green-400 dark:hover:bg-gray-700',
            'judul' => 'Success!',
        ],
    };

    // Default per jenis, tapi tetap bisa dipaksa lewat prop kalau ada kasus
    // yang butuh perilaku lain.
    $otomatis = $sembunyiOtomatis ?? ($type !== 'error');
@endphp

<div x-data="{ tampil: true }"
    x-show="tampil"
    @if($otomatis)
        x-init="setTimeout(() => tampil = false, {{ (int) $jeda }})"
    @endif
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    {{ $attributes->merge(['class' => 'flex items-start p-4 mb-4 text-sm border rounded-lg ' . $gaya['wadah']]) }}
    role="alert">

    <svg class="flex-shrink-0 inline w-4 h-4 me-3 mt-0.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
    </svg>
    <span class="sr-only">Info</span>

    <div class="min-w-0 flex-1">
        <strong class="font-bold">{{ $gaya['judul'] }}</strong>
        {{ $slot }}
    </div>

    <button type="button" x-on:click="tampil = false"
        class="ms-3 -me-1 -mt-1 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-transparent {{ $gaya['tombol'] }}">
        <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
        </svg>
        <span class="sr-only">Tutup pemberitahuan</span>
    </button>
</div>
