<div class="relative overflow-x-auto shadow-md sm:rounded-lg">
    <div class="flex flex-wrap sm:flex-nowrap items-center justify-between space-y-3">

        <div class="flex flex-wrap items-center space-x-3 w-full">
            @include('livewire.searchdata')

            @include('livewire.dataperpage')
        </div>
        <a href="{{ route('bahan-keluars.create') }}" class="inline-flex rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            Tambah
        </a>
    </div>

    <section class="bg-gray-50 py-8 antialiased dark:bg-gray-900 md:py-12">
        <div class="mx-auto max-w-screen-xl px-4 2xl:px-0">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($stokRnds as $index => $row)
                    <div class="w-full max-w-sm bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
                        <div class="px-6 py-4">
                            <img src="{{ $row->dataBahan->gambar ? asset('images/' . $row->dataBahan->gambar) : asset('images/image-4@2x.jpg') }}" alt="Gambar {{ $row->nama_bahan }}" class="h-32 w-full object-cover rounded-lg">
                        </div>
                        <div class="px-5 pb-5">
                            <a href="#">
                                <h5 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $row->dataBahan->nama_bahan }}</h5>
                            </a>
                            <div class="flex items-center mt-2.5 mb-5">
                                <div class="flex items-center space-x-1 rtl:space-x-reverse">
                                    {{ $row->dataBahan->kode_bahan }}
                                </div>
                                <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-blue-200 dark:text-blue-800 ms-3">{{ $row->total_stok }} {{ $row->dataBahan->dataUnit->nama }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-3xl font-bold text-gray-900 dark:text-white"></span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="w-full text-center">
                {{ $stokRnds->links() }}
            </div>
        </div>
    </section>
</div>
