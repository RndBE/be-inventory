@section('title', 'Edit Bahan | BE INVENTORY')
<x-app-layout>
    @props(['variant' => ''])
    <x-app.secondary-header :variant="$attributes['headerVariant']">
        <div class="flex">
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <nav class="flex flex-wrap" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
                        <li class="inline-flex items-center">
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                                <svg class="w-3 h-3 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                                </svg>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                </svg>
                                <a href="{{ route('bahan.index') }}" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Bahan</a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                </svg>
                                <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Edit Bahan</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Header: Right side -->
        <div class="flex items-center space-x-3">
            <div class="p-1 flex items-center justify-end gap-x-2">
                <a href="{{ route('bahan.index', ['page' => request('page', 1)]) }}" type="button" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">Kembali</a>
                <button id="saveButton" type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Simpan</button>
            </div>
        </div>
    </x-app.secondary-header>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        @if (session('success'))
            <div id="successAlert" class="flex items-center p-4 mb-4 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800" role="alert">
                <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <span class="sr-only">Info</span>
                <div>
                    <strong class="font-bold">Success!</strong>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div id="errorAlert" class="flex items-center p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800" role="alert">
                <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <span class="sr-only">Info</span>
                <div>
                    <strong class="font-bold">Error!</strong>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <div class="w-full bg-white border border-gray-200 rounded-lg shadow sm:p-8 dark:bg-gray-800 dark:border-gray-700">
            <form action="{{ route('bahan.update', $bahan->id) }}" method="POST" enctype="multipart/form-data" id="bahanEditForm">
                @csrf
                @method('PUT')
                 <input type="hidden" name="page" value="{{ request('page') }}">

                <div class="space-y-12">
                    <div class="border-b border-gray-900/10 pb-12">
                        <div class="p-4 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">

                            <div class="sm:col-span-2 sm:col-start-1">
                            <label for="kode_bahan" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">Kode Bahan</label>
                            <div class="mt-2">
                                <input type="text" name="kode_bahan" id="kode_bahan" value="{{ old('kode_bahan', $bahan->kode_bahan) }}" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
                                @error('kode_bahan')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            </div>

                            <div class="sm:col-span-2">
                            <label for="nama_bahan" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">Nama Bahan</label>
                            <div class="mt-2">
                                <input value="{{ old('nama_bahan', $bahan->nama_bahan) }}" type="text" name="nama_bahan" id="nama_bahan" autocomplete="address-level1" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6
                                dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
                                @error('nama_bahan')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            </div>

                            <div class="sm:col-span-2" x-data="{
                                searchQuery: '',
                                options: [
                                    @foreach($jenisBahan as $jenis)
                                        { value: '{{ $jenis->id }}', text: '{{ addslashes($jenis->nama) }}' },
                                    @endforeach
                                ],
                                selected: '{{ old('jenis_bahan_id', $bahan->jenis_bahan_id) }}',
                                open: false,
                                select(val) {
                                    this.selected = val;
                                    this.open = false;
                                    this.searchQuery = '';
                                },
                                get selectedText() {
                                    if (!this.selected) return 'Pilih Jenis Bahan';
                                    const opt = this.options.find(o => o.value == this.selected);
                                    return opt ? opt.text : 'Pilih Jenis Bahan';
                                },
                                get filteredOptions() {
                                    if (this.searchQuery === '') return this.options;
                                    return this.options.filter(opt => opt.text.toLowerCase().includes(this.searchQuery.toLowerCase()));
                                }
                            }">
                                <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">Jenis Bahan</label>
                                <div class="mt-2 text-sm z-40 relative">
                                    <select x-model="selected" name="jenis_bahan_id" class="hidden">
                                        <option value=""></option>
                                        <template x-for="option in options" :key="option.value">
                                            <option :value="option.value" x-text="option.text"></option>
                                        </template>
                                    </select>

                                    <!-- Button -->
                                    <button type="button" @click="open = !open" @click.outside="open = false" class="relative w-full cursor-pointer rounded-md bg-white border-0 py-1.5 pl-3 pr-10 text-left text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600 dark:placeholder-gray-400">
                                        <span class="block truncate" x-text="selectedText"></span>
                                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                            <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    </button>

                                    <!-- Options List -->
                                    <div x-show="open" x-transition x-cloak class="absolute z-50 mt-1 top-full w-full rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-700">
                                        <!-- Search Input -->
                                        <div class="p-2 border-b border-gray-200 dark:border-gray-600">
                                            <input type="text" x-model="searchQuery" @click.stop placeholder="Cari jenis bahan..." class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600">
                                        </div>
                                        <!-- List -->
                                        <ul class="py-1 text-base sm:text-sm" style="max-height: 15rem; overflow-y: auto;">
                                            <template x-for="option in filteredOptions" :key="option.value">
                                                <li @click="select(option.value)" 
                                                    :class="{'bg-indigo-600 text-white': selected == option.value, 'text-gray-900 dark:text-white hover:bg-indigo-50 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white': selected != option.value}"
                                                    class="relative cursor-pointer select-none py-2 pl-3 pr-9">
                                                    <span class="block truncate font-normal" x-text="option.text"></span>
                                                    <!-- Checkmark for selected -->
                                                    <span x-show="selected == option.value" class="absolute inset-y-0 right-0 flex items-center pr-4 text-white">
                                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                                        </svg>
                                                    </span>
                                                </li>
                                            </template>
                                            <li x-show="filteredOptions.length === 0" class="text-gray-500 dark:text-gray-400 relative cursor-default select-none py-2 pl-3 pr-9">
                                                Tidak ada jenis bahan ditemukan.
                                            </li>
                                        </ul>
                                    </div>
                                    @error('jenis_bahan_id')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="gambar" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">Gambar</label>
                                <div class="mt-2">
                                    <input class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 file:rounded-lg file:w-24 file:h-9" id="gambar" name="gambar" type="file" accept=".png, .jpg, .jpeg">
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-300" id="file_input_help">PNG, JPG or JPEG (MAX. 2 MB).</p>
                                    @error('gambar')
                                        <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="sm:col-span-2" x-data="{
                                searchQuery: '',
                                options: [
                                    @foreach($units as $unit)
                                        { value: '{{ $unit->id }}', text: '{{ addslashes($unit->nama) }}' },
                                    @endforeach
                                ],
                                selected: '{{ old('unit_id', $bahan->unit_id) }}',
                                open: false,
                                select(val) {
                                    this.selected = val;
                                    this.open = false;
                                    this.searchQuery = '';
                                },
                                get selectedText() {
                                    if (!this.selected) return 'Pilih Satuan Unit';
                                    const opt = this.options.find(o => o.value == this.selected);
                                    return opt ? opt.text : 'Pilih Satuan Unit';
                                },
                                get filteredOptions() {
                                    if (this.searchQuery === '') return this.options;
                                    return this.options.filter(opt => opt.text.toLowerCase().includes(this.searchQuery.toLowerCase()));
                                }
                            }">
                                <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">Unit</label>
                                <div class="mt-2 text-sm z-30 relative">
                                    <select x-model="selected" name="unit_id" class="hidden">
                                        <option value=""></option>
                                        <template x-for="option in options" :key="option.value">
                                            <option :value="option.value" x-text="option.text"></option>
                                        </template>
                                    </select>

                                    <!-- Button -->
                                    <button type="button" @click="open = !open" @click.outside="open = false" class="relative w-full cursor-pointer rounded-md bg-white border-0 py-1.5 pl-3 pr-10 text-left text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600 dark:placeholder-gray-400">
                                        <span class="block truncate" x-text="selectedText"></span>
                                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                            <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    </button>

                                    <!-- Options List -->
                                    <div x-show="open" x-transition x-cloak class="absolute z-50 mt-1 top-full w-full rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-700">
                                        <!-- Search Input -->
                                        <div class="p-2 border-b border-gray-200 dark:border-gray-600">
                                            <input type="text" x-model="searchQuery" @click.stop placeholder="Cari satuan unit..." class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600">
                                        </div>
                                        <!-- List -->
                                        <ul class="py-1 text-base sm:text-sm" style="max-height: 15rem; overflow-y: auto;">
                                            <template x-for="option in filteredOptions" :key="option.value">
                                                <li @click="select(option.value)" 
                                                    :class="{'bg-indigo-600 text-white': selected == option.value, 'text-gray-900 dark:text-white hover:bg-indigo-50 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white': selected != option.value}"
                                                    class="relative cursor-pointer select-none py-2 pl-3 pr-9">
                                                    <span class="block truncate font-normal" x-text="option.text"></span>
                                                    <!-- Checkmark for selected -->
                                                    <span x-show="selected == option.value" class="absolute inset-y-0 right-0 flex items-center pr-4 text-white">
                                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                                        </svg>
                                                    </span>
                                                </li>
                                            </template>
                                            <li x-show="filteredOptions.length === 0" class="text-gray-500 dark:text-gray-400 relative cursor-default select-none py-2 pl-3 pr-9">
                                                Tidak ada satuan unit ditemukan.
                                            </li>
                                        </ul>
                                    </div>
                                    @error('unit_id')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>


                            <div class="sm:col-span-2">
                            <label for="penempatan" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">Penempatan</label>
                            <div class="mt-2">
                                <input value="{{ old('penempatan', $bahan->penempatan) }}" type="text" name="penempatan" id="penempatan" autocomplete="street-address" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
                                @error('penempatan')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            </div>

                            <div class="sm:col-span-2" x-data="{
                                searchQuery: '',
                                options: [
                                    @foreach($suppliers as $supplier)
                                        { value: '{{ $supplier->id }}', text: '{{ addslashes($supplier->nama) }}' },
                                    @endforeach
                                ],
                                selected: [
                                    @foreach(old('supplier_id', $bahan->suppliers ? $bahan->suppliers->pluck('id')->toArray() : []) as $sid)
                                        '{{ $sid }}',
                                    @endforeach
                                ],
                                open: false,
                                select(val) {
                                    if (!this.selected.includes(val)) this.selected.push(val);
                                    this.open = false;
                                    this.searchQuery = '';
                                },
                                remove(val) {
                                    this.selected = this.selected.filter(item => item !== val);
                                },
                                get selectedOptions() {
                                    return this.selected.map(val => this.options.find(opt => opt.value === val)).filter(Boolean);
                                },
                                get unselectedOptions() {
                                    return this.options.filter(opt => !this.selected.includes(opt.value) && opt.text.toLowerCase().includes(this.searchQuery.toLowerCase()));
                                }
                            }">
                                <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">Supplier</label>
                                <div class="mt-2 text-sm z-50">
                                    <!-- Dropdown Trigger & Hidden Select -->
                                    <div class="relative">
                                        <!-- Real Select untuk Submit -->
                                        <select class="hidden" multiple name="supplier_id[]">
                                            <template x-for="option in options" :key="option.value">
                                                <option :value="option.value" :selected="selected.includes(option.value)"></option>
                                            </template>
                                        </select>
                            
                                        <!-- Button -->
                                        <button type="button" @click="open = !open" @click.outside="open = false" class="relative w-full cursor-pointer rounded-md bg-white border-0 py-1.5 pl-3 pr-10 text-left text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600 dark:placeholder-gray-400">
                                            <span class="block truncate">Pilih Supplier</span>
                                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                                <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        </button>
                            
                                        <!-- Options List -->
                                        <div x-show="open" x-transition x-cloak class="absolute z-50 mt-1 top-full w-full rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-700">
                                            <!-- Search Input -->
                                            <div class="p-2 border-b border-gray-200 dark:border-gray-600">
                                                <input type="text" x-model="searchQuery" @click.stop placeholder="Cari supplier..." class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600">
                                            </div>
                                            <!-- List -->
                                            <ul class="py-1 text-base sm:text-sm" style="max-height: 15rem; overflow-y: auto;">
                                                <template x-for="option in unselectedOptions" :key="option.value">
                                                    <li @click="select(option.value)" class="text-gray-900 dark:text-white relative cursor-pointer select-none py-2 pl-3 pr-9 hover:bg-indigo-600 hover:text-white">
                                                        <span class="block truncate font-normal" x-text="option.text"></span>
                                                    </li>
                                                </template>
                                                <li x-show="unselectedOptions.length === 0" class="text-gray-500 dark:text-gray-400 relative cursor-default select-none py-2 pl-3 pr-9">
                                                    Tidak ada supplier ditemukan.
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <!-- Selected Chips (Moved to bottom) -->
                                    <div class="flex flex-wrap gap-2 mt-3" x-show="selectedOptions.length > 0" x-cloak>
                                        <template x-for="item in selectedOptions" :key="item.value">
                                            <span class="inline-flex items-center gap-1.5 py-1 px-3 rounded-md text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                                <span x-text="item.text"></span>
                                                <button type="button" @click="remove(item.value)" class="flex-shrink-0 h-4 w-4 rounded-full inline-flex items-center justify-center text-indigo-400 hover:bg-indigo-200 hover:text-indigo-900 focus:outline-none focus:bg-indigo-200 focus:text-indigo-900 dark:hover:bg-indigo-800 dark:hover:text-indigo-100">
                                                    <span class="sr-only">Remove</span>
                                                    <svg class="h-2 w-2" stroke="currentColor" fill="none" viewBox="0 0 8 8">
                                                        <path stroke-linecap="round" stroke-width="1.5" d="M1 1l6 6m0-6L1 7" />
                                                    </svg>
                                                </button>
                                            </span>
                                        </template>
                                    </div>

                                    @error('supplier_id')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="sm:col-span-2 sm:col-start-1">
                            <div class="mt-2">
                                @if($bahan->gambar)
                                    <img src="{{ $bahan->gambar ? asset('storage/' . $bahan->gambar) : asset('images/image-4@2x.jpg') }}" alt="Gambar {{ $bahan->nama_bahan }}" class="h-auto w-24 rounded-lg">

                                @else
                                    <p>Tidak ada gambar tersedia.</p>
                                @endif
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>


    </div>
</x-app-layout>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Atur waktu delay dalam milidetik (contoh: 5000 = 5 detik)
        const delay = 5000;

        // Menghilangkan alert sukses
        const successAlert = document.getElementById('successAlert');
        if (successAlert) {
            setTimeout(() => {
                successAlert.style.display = 'none';
            }, delay);
        }

        // Menghilangkan alert error
        const errorAlert = document.getElementById('errorAlert');
        if (errorAlert) {
            setTimeout(() => {
                errorAlert.style.display = 'none';
            }, delay);
        }
    });
</script>
<script>
    document.getElementById('saveButton').addEventListener('click', function() {
        document.getElementById('bahanEditForm').submit();
    });
</script>
