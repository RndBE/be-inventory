{{--
    Pemilih jumlah baris per halaman, dipakai bersama seluruh tabel Livewire.

    aria-label + title wajib ada: tanpa keduanya pengguna hanya melihat angka
    "25/50/100/250/500" tanpa keterangan apa pun tentang artinya, dan pembaca
    layar mengumumkannya sebagai daftar pilihan tanpa nama.

    Label dipasang sebagai atribut, bukan elemen <label>, supaya tidak ada
    pembungkus baru yang mengubah tata letak — partial ini disisipkan di dalam
    <li> dan grid pada tabel yang berbeda-beda.
--}}
<select wire:model.live='perPage' id="perPage" name="perPage"
    aria-label="Jumlah baris per halaman" title="Jumlah baris per halaman"
    class="mt-2 block w-fit rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700 dark:bg-gray-800">
    @foreach ([25, 50, 100, 250, 500] as $value)
        <option value="{{ $value }}">{{ $value }} baris</option>
    @endforeach
</select>
