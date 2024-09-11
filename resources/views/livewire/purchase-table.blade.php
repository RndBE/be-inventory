<div class="relative overflow-x-auto shadow-md sm:rounded-lg">
    <div class="flex flex-wrap sm:flex-nowrap items-center justify-between space-y-3">

        <div class="flex flex-wrap items-center space-x-3 w-full">
            @include('livewire.searchdata')

            @include('livewire.dataperpage')
        </div>
        <a href="{{ route('purchases.create') }}" class="inline-flex rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            Tambah
        </a>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Kode Transaksi</th>
                <th>Tanggal Masuk</th>
                <th>Divisi</th>
                <th>Total Item</th>
                <th>Total Harga</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchases as $purchase)
            <tr>
                <td>{{ $purchase->kode_transaksi }}</td>
                <td>{{ $purchase->tgl_masuk }}</td>
                <td>{{ $purchase->divisi }}</td>
                <td>{{ $purchase->details->sum('qty') }}</td>
                <td>Rp {{ number_format($purchase->details->sum('sub_total'), 2, ',', '.') }}</td>
                <td>
                    <a href="#" class="btn btn-info">Detail</a>
                    <a href="#" class="btn btn-danger">Hapus</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <!-- Table -->
    <div class="px-6 py-4">
        {{$purchases->links()}}
    </div>
    {{-- MODAL --}}
    {{-- @include('pages.jenis-bahan.edit')
    @include('pages.jenis-bahan.remove') --}}
</div>
