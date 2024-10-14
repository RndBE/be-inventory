@section('title', 'Edit Produk | BE INVENTORY')

<x-app-layout>
    @props(['variant' => ''])
    <x-app.secondary-header :variant="$attributes['headerVariant']">
        <div class="flex">
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <nav class="flex flex-wrap" aria-label="Breadcrumb">
                    <!-- Breadcrumb Navigation -->
                </nav>
            </div>
        </div>

        <!-- Header: Right side -->
        <div class="flex items-center space-x-3">
            <div class="p-1 flex items-center justify-end gap-x-2">
                <a href="{{ route('produk-produksis.index') }}" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">Kembali</a>
                <button id="saveButton" type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Simpan</button>
            </div>
        </div>
    </x-app.secondary-header>

    <div class="px-4 sm:px-6 lg:px-8 py-2 w-full max-w-9xl mx-auto">
        <div class="w-full bg-white border border-gray-200 rounded-lg shadow sm:p-8">
            <form action="{{ route('produk-produksis.update', $produkProduksis->id) }}" method="POST" enctype="multipart/form-data" id="produksiForm">
                @csrf
                @method('PUT')

                <div class="space-y-12">
                    <div class="border-gray-900/10 pb-12">
                        <div class="p-4 grid grid-cols-2 gap-x-6 gap-y-2 sm:grid-cols-2">
                            <div class="col-span-1 col-start-1">
                                <label for="nama_produk" class="block text-sm font-medium leading-6 text-gray-900">Nama Produk</label>
                                <div class="mt-2">
                                    <input value="{{ old('nama_produk', $produkProduksis->nama_produk) }}" type="text" name="nama_produk" id="nama_produk" class="border-b lock w-full border-0 py-1 text-gray-900 text-4xl leading-6" required autofocus>
                                    @error('nama_produk')
                                        <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="sm:col-span-1 flex justify-end">
                                <div class="mt-2 flex flex-col justify-center items-center rounded-lg border border-dashed border-gray-900/25 px-6 py-4 cursor-pointer" onclick="triggerFileInput()">
                                    <div class="text-center">
                                        <span class="text-sm text-gray-600"></span>
                                    </div>
                                    <div id="imagePreview" class="mt-4 w-full max-w-[200px] {{ $produkProduksis->gambar ? '' : 'hidden' }}">
                                        @if($produkProduksis->gambar)
                                            <img id="previewImg" class="w-full h-auto rounded-lg" src="{{ asset('storage/'.$produkProduksis->gambar) }}" alt="Image preview">
                                        @else
                                            <img id="previewImg" class="w-full h-auto rounded-lg hidden" alt="Image preview">
                                        @endif
                                    </div>
                                    <p id="fileName" class="mt-0 text-sm text-gray-600 text-center"></p>
                                    <input id="gambar" name="gambar" type="file" class="sr-only" accept=".png, .jpg, .jpeg" onchange="previewImage()">
                                    @error('gambar')
                                        <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <livewire:search-bahan/>
                <livewire:bahan-cart :produkProduksisId="$produkProduksisId" />
            </form>

        </div>
    </div>
    <script>
        document.getElementById('saveButton').addEventListener('click', function() {
            document.getElementById('produksiForm').submit();
        });

        function triggerFileInput() {
            document.getElementById('gambar').click();
        }

        function previewImage() {
            const fileInput = document.getElementById('gambar');
            const fileNameDisplay = document.getElementById('fileName');
            const imagePreview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            const uploadInstructions = document.getElementById('uploadInstructions');
            const fileInstructions = document.getElementById('fileInstructions');
            const iconInstructions = document.getElementById('iconInstructions');

            const fileName = fileInput.files[0] ? fileInput.files[0].name : '';
            fileNameDisplay.textContent = fileName ? `${fileName}` : 'No file selected';

            if (fileInput.files && fileInput.files[0]) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    previewImg.classList.remove('hidden');
                    imagePreview.classList.remove('hidden');

                    uploadInstructions.classList.add('hidden');
                    fileInstructions.classList.add('hidden');
                    iconInstructions.classList.add('hidden');
                };

                reader.readAsDataURL(fileInput.files[0]);
            } else {
                previewImg.src = '';
                previewImg.classList.add('hidden');
                imagePreview.classList.add('hidden');

                fileNameDisplay.textContent = 'No file selected';

                uploadInstructions.classList.remove('hidden');
                fileInstructions.classList.remove('hidden');
                iconInstructions.classList.remove('hidden');
            }
        }
    </script>
</x-app-layout>
