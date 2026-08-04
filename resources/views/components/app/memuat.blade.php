{{--
    Penanda bahwa Livewire sedang memproses permintaan.

    Sebelum ini seluruh modul aset tidak punya umpan balik apa pun: mengganti
    tab, mengubah filter, membuka modal detail/riwayat, atau menekan aksi baris
    tidak menghasilkan tanda apa-apa sampai layarnya berubah sendiri. Pada
    tabel yang datanya banyak, jeda itu terbaca sebagai "tombolnya tidak
    berfungsi", dan pengguna menekannya berulang kali.

    Ditaruh sekali per komponen Livewire dan tanpa wire:target, jadi berlaku
    untuk SEMUA aksi di komponen itu — termasuk aksi yang ditambahkan nanti,
    tanpa perlu menyentuh berkas ini lagi.

    .delay menahan tampilan sekitar 200ms. Balasan yang datang lebih cepat dari
    itu tidak sempat memunculkannya, sehingga aksi ringan tidak berkedip.

    Harus berada DI DALAM elemen akar komponen Livewire, bukan sesudahnya:
    Livewire hanya menerima satu elemen akar per komponen.
--}}
<div wire:loading.delay
    {{ $attributes->merge([
        'class' => 'fixed bottom-6 right-6 z-50 inline-flex items-center gap-2 rounded-full bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-lg dark:bg-white dark:text-gray-900',
    ]) }}
    role="status" aria-live="polite">

    <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
    </svg>
    Memuat…
</div>
