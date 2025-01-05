<!-- Main modal -->
@if($isShowModalOpen)
<div wire:ignore.self id="showbahan-modal" tabindex="-1" aria-hidden="true" class="fixed inset-0 flex items-center justify-center z-50 w-full h-full bg-black bg-opacity-50" wire:click.self="closeModal">
    <div class="relative p-4 w-full max-w-md max-h-full">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Detail Bahan
                </h3>
                <button wire:click="closeModal" type="button" class="end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="showbahan-modal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <!-- Modal body -->
            <div class="pt-0 p-5">
                <div class="p-4">
                    <img src="{{ $gambar ? asset('storage/' . $gambar) : asset('images/image-4@2x.jpg') }}" alt="Gambar {{ $nama_bahan }}" class="rounded-lg mx-auto block">
                    <ul class="my-4 space-y-3">
                        <li><h6>Kode Bahan: {{ $kode_bahan }}</h6></li>
                        <li><h6>Jenis Bahan: {{ $jenis_bahan_id }}</h6></li>
                        <li><h6>Stok Awal: <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-green-400 border border-green-400">{{ $stok_awal }} {{ $unit_id }}</span></h6></li>
                        <li><h6>Total Stok: <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-green-400 border border-green-400">{{ $total_stok }} {{ $unit_id }}</span></h6></li>
                        <li><h6>Penempatan: {{ $penempatan }}</h6></li>
                        <li><h6>Supplier: {{ $supplier }}</h6></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
