@props([
    'link' => null,
    'alt' => 'Gambar',
    // Aksi Livewire yang dipanggil saat gambar diklik, mis. "lihatGambar(12)".
    // Kalau dikosongkan, gambar dibuka di tab baru — dipakai di tempat yang tidak
    // punya komponen Livewire untuk menampung modalnya.
    'wireClick' => null,
])

@php
    // Satu sumber penguraian tautan Drive untuk seluruh aplikasi.
    $thumbnail = \App\Helpers\GoogleDriveHelper::thumbnail($link, 120);
@endphp

{{--
    Kotak berukuran tetap 64x64 dipakai untuk SEMUA keadaan — ada gambar, tidak ada
    gambar, maupun gagal dimuat. Tanpa itu, baris yang punya gambar jadi jauh lebih
    tinggi daripada baris yang isinya hanya "-", dan tinggi barisnya loncat-loncat
    sepanjang tabel.
--}}
<div class="flex items-center justify-center w-16 h-16 mx-auto">
    @if(empty($link))
        <span class="text-xs text-gray-400">-</span>
    @else
        {{--
            Tetap sebuah <a href>, bukan <button>, walau kliknya ditangani Livewire:
            href-nya membuat klik-tengah dan ctrl+klik tetap membuka gambar di tab
            baru, dan tautannya tetap bisa disalin. `.prevent` yang menahan navigasi
            biasa supaya modalnya yang muncul.
        --}}
        <a href="{{ $link }}"
            @if($wireClick)
                wire:click.prevent="{{ $wireClick }}"
            @else
                target="_blank" rel="noopener noreferrer"
            @endif
            title="Klik untuk lihat gambar"
            class="{{ $thumbnail
                ? 'block group relative w-16 h-16 overflow-hidden rounded-lg border border-gray-200 hover:border-indigo-400 transition-all duration-150 shadow-sm hover:shadow-md'
                : 'text-xs text-center text-indigo-600 hover:underline' }}">

            @if($thumbnail)
                <img
                    src="{{ $thumbnail }}"
                    alt="{{ $alt }}"
                    loading="lazy" decoding="async"
                    {{-- Drive kadang menolak permintaan yang membawa referrer dari domain lain. --}}
                    referrerpolicy="no-referrer"
                    class="w-16 h-16 object-cover group-hover:scale-105 transition-transform duration-150"
                    onerror="this.onerror=null; this.style.display='none'; this.parentElement.insertAdjacentHTML('beforeend', '<span class=\'flex items-center justify-center w-full h-full text-xs text-gray-400\'>Gagal</span>');"
                >
            @else
                {{-- Bukan tautan Drive, jadi tidak ada thumbnail yang bisa dibuat. --}}
                Lihat File
            @endif
        </a>
    @endif
</div>
