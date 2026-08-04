@section('title', 'Serah Terima Aset | BE INVENTORY')
<x-app-layout>
    {{-- Judul, alert, dan tombol "Buat BAST" pindah ke dalam komponennya, mengikuti
         halaman daftar lain (Pengajuan Peminjaman, Rekapitulasi Aset). Sebelumnya
         halaman ini memakai x-app.secondary-header, yang di sistem ini dipakai
         halaman form — bukan halaman daftar — sehingga tampilannya terasa berbeda. --}}
    @livewire('serah-terima-aset-table')
</x-app-layout>
