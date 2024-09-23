<div class="relative">
    <div class="card mb-0 border-0 shadow-sm bg-white">
        <div class="card-body">
            <div class="form-group mb-0">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <div class="input-group-text bg-gray-100">
                            <i class="bi bi-search text-primary"></i>
                        </div>
                    </div>
                    <input wire:keydown.escape="resetQuery"
                        wire:keydown.up="selectPrevious"
                        wire:keydown.down="selectNext"
                        wire:keydown.enter="selectCurrent"
                        wire:model.live.debounce.500ms="query"
                        type="text"
                        class="block w-full rounded-md border-gray-300 py-1.5 pr-14 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                        placeholder="Tuliskan nama bahan atau kode bahan....">
                </div>
            </div>
        </div>
    </div>

    <div wire:loading class="card absolute mt-1 border-0 left-0 right-0 z-10 bg-white shadow-lg">
        <div class="card-body shadow">
            <div class="flex justify-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        </div>
    </div>

    @if(!empty($query))
        <div wire:click="resetQuery" class="fixed w-full h-full left-0 top-0 z-10"></div>
        @if($search_results->isNotEmpty())
            <div class="card absolute mt-1 left-0 right-0 border-0 z-20 bg-white shadow-lg">
                <div class="card-body shadow">
                    <ul class="list-group list-group-flush">
                        <!-- List Hasil Pencarian -->
                        @foreach($search_results as $index => $bahan)
                            <li wire:click="selectBahan({{ $bahan->id }})"
                                class="cursor-pointer {{ $selectedIndex === $index ? 'bg-blue-100 text-blue-900' : 'hover:bg-blue-50' }} p-2">
                                {{ $bahan->nama_bahan }} | {{ $bahan->kode_bahan }} |
                                <span class="bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded border border-green-400">
                                    {{ $bahan->total_stok }} {{ $bahan->dataUnit->nama }}
                                </span>
                            </li>
                        @endforeach
                        @if($search_results->count() >= $how_many)
                            <li class="list-group-item list-group-item-action text-center">
                                <a wire:click.prevent="loadMore" class="btn btn-primary btn-sm" href="#">
                                    Load More <i class="bi bi-arrow-down-circle"></i>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        @else
            <div class="card absolute mt-1 border-0 left-0 right-0 z-10 bg-white">
                <div class="card-body shadow">
                    <div class="alert alert-warning mb-0">
                        No Product Found....
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
