<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Dashboard actions -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Dashboard</h1>
            </div>

            <!-- Right: Actions -->
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">


            </div>

        </div>

        <!-- Cards -->
        <div class="grid grid-cols-12 gap-6">

            {{-- Jumlah Pengajuan --}}
            <div class="flex flex-col col-span-full sm:col-span-6 bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60 flex justify-between items-center">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100">Pengajuan</h2>
                </header>
                <div class="flex justify-center items-center h-full">
                    <div class="group my-3 inline-flex flex-wrap justify-center items-center gap-12">
                        <div class="flex flex-col items-center">
                            {{-- <header class="px-5 py-4 border-gray-100 dark:border-gray-700/60 flex justify-between items-center"> --}}
                                <h2 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ $totalPengajuanBahanKeluar }}</h2>
                            {{-- </header> --}}
                            <button class="rounded-full pointer-events-none border border-slate-300 p-2.5 text-center text-sm transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-slate-800 hover:border-slate-800 focus:text-white focus:bg-slate-800 focus:border-slate-800 active:border-slate-800 active:text-white active:bg-slate-800 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none" type="button">
                                <svg  xmlns="http://www.w3.org/2000/svg"  width="32"  height="32"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-door-exit"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M13 12v.01" /><path d="M3 21h18" /><path d="M5 21v-16a2 2 0 0 1 2 -2h7.5m2.5 10.5v7.5" /><path d="M14 7h7m-3 -3l3 3l-3 3" /></svg>
                            </button>
                            <span>Bahan Keluar</span>
                        </div>

                        <div class="flex flex-col items-center">
                            {{-- <header class="px-5 py-4 border-gray-100 dark:border-gray-700/60 flex justify-between items-center"> --}}
                                <h2 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ $totalPembelianBahan }}</h2>
                            {{-- </header> --}}
                            <button class="rounded-full pointer-events-none border border-slate-300 p-2.5 text-center text-sm transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-slate-800 hover:border-slate-800 focus:text-white focus:bg-slate-800 focus:border-slate-800 active:border-slate-800 active:text-white active:bg-slate-800 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none" type="button">
                                <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-basket-dollar"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 10l-2 -6" /><path d="M7 10l2 -6" /><path d="M13 20h-5.756a3 3 0 0 1 -2.965 -2.544l-1.255 -7.152a2 2 0 0 1 1.977 -2.304h13.999a2 2 0 0 1 1.977 2.304" /><path d="M10 14a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 15h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5" /><path d="M19 21v1m0 -8v1" /></svg>
                            </button>
                            <span>Pembelian Bahan</span>
                        </div>

                        <div class="flex flex-col items-center">
                            {{-- <header class="px-5 py-4 border-gray-100 dark:border-gray-700/60 flex justify-between items-center"> --}}
                                <h2 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ $totalPengajuanBahanRusak }}</h2>
                            {{-- </header> --}}
                            <button class="rounded-full pointer-events-none border border-slate-300 p-2.5 text-center text-sm transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-slate-800 hover:border-slate-800 focus:text-white focus:bg-slate-800 focus:border-slate-800 active:border-slate-800 active:text-white active:bg-slate-800 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none" type="button">
                                <svg class="shrink-0 fill-current" xmlns="http://www.w3.org/2000/svg" width="32"  height="32" viewBox="0 0 24 24">
                                    <path d="M18 17h-.09c.058-.33.088-.665.09-1v-1h1a1 1 0 0 0 0-2h-1.09a5.97 5.97 0 0 0-.26-1H17a2 2 0 0 0 2-2V8a1 1 0 1 0-2 0v2h-.54a6.239 6.239 0 0 0-.46-.46V8a3.963 3.963 0 0 0-.986-2.6l.693-.693A1 1 0 0 0 16 4V3a1 1 0 1 0-2 0v.586l-.661.661a3.753 3.753 0 0 0-2.678 0L10 3.586V3a1 1 0 1 0-2 0v1a1 1 0 0 0 .293.707l.693.693A3.963 3.963 0 0 0 8 8v1.54a6.239 6.239 0 0 0-.46.46H7V8a1 1 0 0 0-2 0v2a2 2 0 0 0 2 2h-.65a5.97 5.97 0 0 0-.26 1H5a1 1 0 0 0 0 2h1v1a6 6 0 0 0 .09 1H6a2 2 0 0 0-2 2v2a1 1 0 1 0 2 0v-2h.812A6.012 6.012 0 0 0 11 21.907V12a1 1 0 0 1 2 0v9.907A6.011 6.011 0 0 0 17.188 19H18v2a1 1 0 0 0 2 0v-2a2 2 0 0 0-2-2Zm-4-8.65a5.922 5.922 0 0 0-.941-.251l-.111-.017a5.52 5.52 0 0 0-1.9 0l-.111.017A5.925 5.925 0 0 0 10 8.35V8a2 2 0 1 1 4 0v.35Z"/>
                                </svg>
                            </button>
                            <span>Bahan Rusak</span>
                        </div>

                        <div class="flex flex-col items-center">
                            {{-- <header class="px-5 py-4 border-gray-100 dark:border-gray-700/60 flex justify-between items-center"> --}}
                                <h2 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ $totalPengajuanBahanRetur }}</h2>
                            {{-- </header> --}}
                            <button class="rounded-full pointer-events-none border border-slate-300 p-2.5 text-center text-sm transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-slate-800 hover:border-slate-800 focus:text-white focus:bg-slate-800 focus:border-slate-800 active:border-slate-800 active:text-white active:bg-slate-800 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none" type="button">
                                <svg  xmlns="http://www.w3.org/2000/svg"  width="32"  height="32"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler shrink-0 icons-tabler-outline icon-tabler-refresh"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>
                            </button>
                            <span>Bahan Retur</span>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Bagian kanan (6 card kecil) -->
            <div class="col-span-full lg:col-span-6 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                <!-- Card 1 -->
                <div class="relative flex flex-col bg-white dark:bg-gray-800 shadow-sm rounded-xl h-40">
                <div class="bg-clip-border mx-4 rounded-xl overflow-hidden bg-gradient-to-tr from-blue-600 to-blue-400 text-white shadow-blue-500/40 shadow-lg absolute -mt-4 grid h-16 w-16 place-items-center">
                    <!-- Icon -->
                    <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-cpu"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 5m0 1a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v12a1 1 0 0 1 -1 1h-12a1 1 0 0 1 -1 -1z" /><path d="M9 9h6v6h-6z" /><path d="M3 10h2" /><path d="M3 14h2" /><path d="M10 3v2" /><path d="M14 3v2" /><path d="M21 10h-2" /><path d="M21 14h-2" /><path d="M14 21v-2" /><path d="M10 21v-2" /></svg>
                </div>
                <div class="flex-1 flex items-end justify-end p-4 text-right">
                    <div>
                        <h4 class="text-2xl font-semibold text-blue-gray-900">{{ $totalBahan }}</h4>
                        <p class="text-sm text-blue-gray-600">Total Bahan</p>
                    </div>
                </div>
                </div>

                <!-- Card 2 -->
                <div class="relative flex flex-col bg-white dark:bg-gray-800 shadow-sm rounded-xl h-40">
                <div class="bg-clip-border mx-4 rounded-xl overflow-hidden bg-gradient-to-tr from-green-600 to-green-400 text-white shadow-green-500/40 shadow-lg absolute -mt-4 grid h-16 w-16 place-items-center">
                    <!-- Icon -->
                    <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-box-multiple-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 3m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z" /><path d="M17 17v2a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-10a2 2 0 0 1 2 -2h2" /><path d="M14 14v-8l-2 2" /></svg>
                </div>
                <div class="flex-1 flex items-end justify-end p-4 text-right">
                    <div>
                        <h4 class="text-2xl font-semibold text-blue-gray-900">{{ $totalJenisBahan }}</h4>
                        <p class="text-sm text-blue-gray-600">Total Jenis Bahan</p>
                    </div>
                </div>
                </div>

                <!-- Card 3 -->
                <div class="relative flex flex-col bg-white dark:bg-gray-800 shadow-sm rounded-xl h-40">
                <div class="bg-clip-border mx-4 rounded-xl overflow-hidden bg-gradient-to-tr from-purple-600 to-purple-400 text-white shadow-purple-500/40 shadow-lg absolute -mt-4 grid h-16 w-16 place-items-center">
                    <!-- Icon -->
                    <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-packages"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 16.5l-5 -3l5 -3l5 3v5.5l-5 3z" /><path d="M2 13.5v5.5l5 3" /><path d="M7 16.545l5 -3.03" /><path d="M17 16.5l-5 -3l5 -3l5 3v5.5l-5 3z" /><path d="M12 19l5 3" /><path d="M17 16.5l5 -3" /><path d="M12 13.5v-5.5l-5 -3l5 -3l5 3v5.5" /><path d="M7 5.03v5.455" /><path d="M12 8l5 -3" /></svg>
                </div>
                <div class="flex-1 flex items-end justify-end p-4 text-right">
                    <div>
                        <h4 class="text-2xl font-semibold text-blue-gray-900">{{ $totalSatuanUnit }}</h4>
                        <p class="text-sm text-blue-gray-600">Total Satuan Unit</p>
                    </div>
                </div>
                </div>

                <!-- Card 4 -->
                <div class="relative flex flex-col bg-white dark:bg-gray-800 shadow-sm rounded-xl h-40">
                    <!-- Icon -->
                    <div class="bg-clip-border mx-4 rounded-xl overflow-hidden bg-gradient-to-tr from-red-600 to-red-400 text-white shadow-red-500/40 shadow-lg absolute -mt-4 grid h-16 w-16 place-items-center">
                        <svg class="shrink-0 fill-current" xmlns="http://www.w3.org/2000/svg" width="22"  height="22" viewBox="0 0 512 512"><path fill="currentColor" d="m0 379.652l72.276-3.87v33.816L0 405.064zm0-71.95l72.276 4.534V278.42L0 282.29zM456.2 87.566l-72.275-3.871v33.817l72.276-4.534zm-80.232-17.684h-39.066v61.442h39.066zM0 210.34l72.276 4.534v-33.817L0 184.927zm192.955 161.353l135.282-143.114V56.572c0-10.719-8.689-19.408-19.407-19.408H151.324c-10.718 0-19.407 8.69-19.407 19.408v382.963c0 10.719 8.689 19.408 19.407 19.408h125.547zM80.896 228.686h43.58v-61.441h-43.58zm0-97.362h43.581V69.882h-43.58zm0 194.724h43.58v-61.441h-43.58zM0 112.978l72.276 4.534V83.694L0 87.566zM80.896 423.41h43.58v-61.44h-43.58zM359.603 297.1l-75.427 74.593l75.427 71.777l-31.365 31.366l-99.143-103.143l99.143-105.96zm53.254-31.366L512 371.694l-99.143 103.142l-31.365-31.366l75.426-71.777l-75.426-74.594zm-75.955-37.048h39.065v-61.441h-39.065zm47.023-13.812l72.276-4.534v-25.412l-72.276-3.871z"/></svg>
                    </div>
                    <!-- Text di pojok kanan bawah -->
                    <div class="flex-1 flex items-end justify-end p-4 text-right">
                        <div>
                            <h4 class="text-2xl font-semibold text-blue-gray-900">{{ $totalProdukProduksi }}</h4>
                            <p class="text-sm text-blue-gray-600">Total Produk Setengah Jadi</p>
                        </div>
                    </div>
                </div>


                <!-- Card 5 -->
                <div class="relative flex flex-col bg-white dark:bg-gray-800 shadow-sm rounded-xl h-40">
                    <div class="bg-clip-border mx-4 rounded-xl overflow-hidden bg-gradient-to-tr from-yellow-600 to-yellow-400 text-white shadow-yellow-500/40 shadow-lg absolute -mt-4 grid h-16 w-16 place-items-center">
                        <!-- Icon -->
                        <svg class="shrink-0 fill-current" xmlns="http://www.w3.org/2000/svg" width="22"  height="22" viewBox="0 0 2048 2048"><path fill="currentColor" d="m960 120l832 416v1040l-832 415l-832-415V536zm625 456L960 264L719 384l621 314zM960 888l238-118l-622-314l-241 120zM256 680v816l640 320v-816zm768 1136l640-320V680l-640 320z"/></svg>
                    </div>
                    <div class="flex-1 flex items-end justify-end p-4 text-right">
                        <div>
                            <h4 class="text-2xl font-semibold text-blue-gray-900">{{ $totalProdukJadi }}</h4>
                            <p class="text-sm text-blue-gray-600">Total Produk Jadi</p>
                        </div>
                    </div>
                </div>

                <!-- Card 6 -->
                <div class="relative flex flex-col bg-white dark:bg-gray-800 shadow-sm rounded-xl h-40">
                    <div class="bg-clip-border mx-4 rounded-xl overflow-hidden bg-gradient-to-tr from-teal-600 to-teal-400 text-white shadow-teal-500/40 shadow-lg absolute -mt-4 grid h-16 w-16 place-items-center">
                        <!-- Icon -->
                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-truck"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M17 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M5 17h-2v-11a1 1 0 0 1 1 -1h9v12m-4 0h6m4 0h2v-6h-8m0 -5h5l3 5" /></svg>
                    </div>
                    <div class="flex-1 flex items-end justify-end p-4 text-right">
                        <div>
                            <h4 class="text-2xl font-semibold text-blue-gray-900">{{ $totalSupplier }}</h4>
                            <p class="text-sm text-blue-gray-600">Total Supplier</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bahan Masuk vs Bahan Keluar  --}}
            <div class="col-span-full xl:col-span-12 bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60 flex justify-between items-center">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100">Bahan Masuk vs Bahan Keluar</h2>
                    <form method="GET" action="{{ route('dashboard') }}" class="flex gap-2">
                        <div>
                            <select id="year" name="year" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm py-1">
                                @foreach($availableYears as $availableYear)
                                    <option value="{{ $availableYear }}" {{ $year == $availableYear ? 'selected' : '' }}>
                                        {{ $availableYear }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <select id="period" name="period" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm py-1">
                                <option value="7_days" {{ $period == '7_days' ? 'selected' : '' }}>7 Days</option>
                                <option value="monthly" {{ $period == 'monthly' ? 'selected' : '' }}>Monthly</option>
                            </select>
                        </div>

                        <div class="flex items-end">
                            <button type="submit" class="px-3 py-1 bg-green-600 text-white rounded-lg text-sm">Apply</button>
                        </div>
                </header>
                <div class="p-3">
                    <div id="dashboard-bar-chart"></div>

                </div>
            </div>
            @php
                $bahanRusak = collect($bahanRusakLabels)
                    ->zip($bahanRusakData) // gabungkan label + data
                    ->sortByDesc(fn($item) => $item[1]) // urutkan berdasarkan jumlah (index 1)
                    ->values();

                $sortedLabels = $bahanRusak->pluck(0);
                $sortedData   = $bahanRusak->pluck(1);
            @endphp

            {{-- Jumlah Bahan Rusak --}}
            <div class="flex flex-col col-span-full sm:col-span-6 bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                <!-- Header -->
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60 flex justify-between items-center">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100">Grafik Bahan Rusak</h2>
                </header>

                <!-- Body -->
                <div class="p-4">
                    <div class="w-full h-[350px]">
                        <div id="grafikBahanRusak" class="w-full h-full"></div>
                    </div>
                </div>
            </div>

            {{-- Total Bahan Setengah Jadi --}}
            <div class="flex flex-col col-span-full sm:col-span-6 xl:col-span-6 bg-white dark:bg-gray-800 shadow-sm rounded-xl min-h-[300px] max-h-[500px]">

                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60"><h2 class="font-semibold text-gray-800 dark:text-gray-100">Produk Setengah Jadi</h2>
                </header>
                <div class="grow">
                    <div id="bahan-pie-chart"></div>
                </div>
            </div>

            {{-- Proses Produksi --}}
            <div class="col-span-full xl:col-span-12 bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100">Proses Produksi</h2>
                </header>
                <div class="p-3">

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="table-auto w-full dark:text-gray-300">
                            <!-- Table header -->
                            <thead class="text-xs uppercase text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-700 dark:bg-opacity-50 rounded-sm">
                                <tr>
                                    <th class="p-2">
                                        <div class="font-semibold text-left">Nama Produk</div>
                                    </th>
                                    <th class="p-2">
                                        <div class="font-semibold text-center">Mulai Produksi</div>
                                    </th>
                                    <th class="p-2">
                                        <div class="font-semibold text-center">Jumlah Produksi</div>
                                    </th>
                                    <th class="p-2">
                                        <div class="font-semibold text-center">Penyelesaian</div>
                                    </th>
                                </tr>
                            </thead>
                            <!-- Table body -->
                            <tbody class="text-sm font-medium divide-y divide-gray-100 dark:divide-gray-700/60">
                                @forelse($prosesProduksi as $produksi)
                                <tr>
                                    <td class="p-2">
                                        <div class="text-gray-800 dark:text-gray-100">{{ $produksi->dataBahan->nama_bahan }}</div>
                                    </td>
                                    <td class="p-2">
                                        <div class="text-center">{{ $produksi->mulai_produksi }}</div>
                                    </td>
                                    <td class="p-2">
                                        <div class="text-center text-green-500">{{ $produksi->jml_produksi }}</div>
                                    </td>
                                    <td class="p-2">
                                        <div class="text-center text-sky-500">
                                            <div class="w-10/12 mx-auto">
                                                <p class="antialiased font-sans mb-1 block text-xs font-medium text-blue-gray-600">{{ $produksi->completion_percentage }}%</p>
                                                <div class="flex flex-start bg-blue-gray-50 overflow-hidden w-full rounded-sm font-sans text-xs font-medium h-1">
                                                    <div class="flex justify-center items-center h-full bg-gradient-to-tr from-blue-600 to-blue-400 text-white" style="width: {{ $produksi->completion_percentage }}%;"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                    <td colspan="9" class="px-6 py-4 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-semibold text-gray-900">Data Tidak Ditemukan!</h3>
                                        <p class="mt-1 text-sm text-gray-500">Maaf, data yang Anda cari tidak ada</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>

            {{-- Projek --}}
            <div class="col-span-full xl:col-span-6 bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100">Proyek</h2>
                </header>
                <div class="p-3">

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="table-auto w-full dark:text-gray-300">
                            <!-- Table header -->
                            <thead class="text-xs uppercase text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-700 dark:bg-opacity-50 rounded-sm">
                                <tr>
                                    <th class="p-2">
                                        <div class="font-semibold text-left">Nama Proyek</div>
                                    </th>
                                    <th class="p-2">
                                        <div class="font-semibold text-center">Mulai Proyek</div>
                                    </th>
                                    <th class="p-2">
                                        <div class="font-semibold text-center">Penyelesaian</div>
                                    </th>
                                </tr>
                            </thead>
                            <!-- Table body -->
                            <tbody class="text-sm font-medium divide-y divide-gray-100 dark:divide-gray-700/60">
                                @forelse($projeks as $projek)
                                <tr>
                                    <td class="p-2">
                                        <div class="text-gray-800 dark:text-gray-100">{{ $projek->dataKontrak->nama_kontrak }}</div>
                                    </td>
                                    <td class="p-2">
                                        <div class="text-center">{{ $projek->mulai_projek }}</div>
                                    </td>
                                    <td class="p-2">
                                        <div class="text-center text-sky-500">
                                            <div class="w-10/12 mx-auto">
                                                <p class="antialiased font-sans mb-1 block text-xs font-medium text-blue-gray-600">{{ $projek->completion_percentage_projek }}%</p>
                                                <div class="flex flex-start bg-blue-gray-50 overflow-hidden w-full rounded-sm font-sans text-xs font-medium h-1">
                                                    <div class="flex justify-center items-center h-full bg-gradient-to-tr from-blue-600 to-blue-400 text-white" style="width: {{ $projek->completion_percentage_projek }}%;"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                    <td colspan="9" class="px-6 py-4 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-semibold text-gray-900">Data Tidak Ditemukan!</h3>
                                        <p class="mt-1 text-sm text-gray-500">Maaf, data yang Anda cari tidak ada</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>

            {{-- Projek RnD --}}
            <div class="col-span-full xl:col-span-6 bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100">Proyek RnD</h2>
                </header>
                <div class="p-3">

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="table-auto w-full dark:text-gray-300">
                            <!-- Table header -->
                            <thead class="text-xs uppercase text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-700 dark:bg-opacity-50 rounded-sm">
                                <tr>
                                    <th class="p-2">
                                        <div class="font-semibold text-left">Nama Proyek RnD</div>
                                    </th>
                                    <th class="p-2">
                                        <div class="font-semibold text-center">Mulai Proyek</div>
                                    </th>
                                    <th class="p-2">
                                        <div class="font-semibold text-center">Penyelesaian</div>
                                    </th>
                                </tr>
                            </thead>
                            <!-- Table body -->
                            <tbody class="text-sm font-medium divide-y divide-gray-100 dark:divide-gray-700/60">
                                @forelse($projeks_rnd as $projek_rnd)
                                <tr>
                                    <td class="p-2">
                                        <div class="text-gray-800 dark:text-gray-100">{{ $projek_rnd->nama_projek_rnd }}</div>
                                    </td>
                                    <td class="p-2">
                                        <div class="text-center">{{ $projek_rnd->mulai_projek_rnd }}</div>
                                    </td>
                                    <td class="p-2">
                                        <div class="text-center text-sky-500">
                                            <div class="w-10/12 mx-auto">
                                                <p class="antialiased font-sans mb-1 block text-xs font-medium text-blue-gray-600">{{ $projek_rnd->completion_percentage_projekrnd }}%</p>
                                                <div class="flex flex-start bg-blue-gray-50 overflow-hidden w-full rounded-sm font-sans text-xs font-medium h-1">
                                                    <div class="flex justify-center items-center h-full bg-gradient-to-tr from-blue-600 to-blue-400 text-white" style="width: {{ $projek_rnd->completion_percentage_projekrnd }}%;"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                    <td colspan="9" class="px-6 py-4 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-semibold text-gray-900">Data Tidak Ditemukan!</h3>
                                        <p class="mt-1 text-sm text-gray-500">Maaf, data yang Anda cari tidak ada</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>


            {{-- Jml Bahan --}}
            <div class="col-span-full xl:col-span-12 bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100">Jumlah Sisa Stok Bahan Terbanyak vs Paling Sedikit</h2>
                </header>
                <div class="p-3">
                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="table-auto w-full dark:text-gray-300">
                            <!-- Table header -->
                            <thead class="text-xs uppercase text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-700 dark:bg-opacity-50 rounded-sm">
                                <tr>
                                    <th class="p-2">
                                        <div class="font-semibold text-left">Nama Bahan</div>
                                    </th>
                                    <th class="p-2">
                                        <div class="font-semibold text-center">Total Stok</div>
                                    </th>
                                    </th>
                                </tr>
                            </thead>
                            <!-- Table body -->
                            <tbody class="text-sm font-medium divide-y divide-gray-100 dark:divide-gray-700/60">
                                @forelse($bahanSisaTerbanyak as $bahan)
                                <tr>
                                    <td class="p-2">
                                        <div class="text-gray-800 dark:text-gray-100">{{ $bahan->dataBahan->nama_bahan }}</div>
                                    </td>
                                    <td class="p-2">
                                        <div class="text-center text-green-500">{{ $bahan->total_sisa  }} {{ $bahan->dataBahan->dataUnit->nama  ?? 'Null' }}</div>
                                    </td>
                                </tr>
                                @empty
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                    <td colspan="2" class="px-6 py-4 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-semibold text-gray-900">Data Tidak Ditemukan!</h3>
                                        <p class="mt-1 text-sm text-gray-500">Maaf, data yang Anda cari tidak ada</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>

                    </div>
                    <div class="overflow-x-auto">
                        <table class="table-auto w-full dark:text-gray-300">
                            <!-- Table header -->
                            <thead class="text-xs uppercase text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-700 dark:bg-opacity-50 rounded-sm">
                                <tr>
                                    <th class="p-2">
                                        <div class="font-semibold text-left">Nama Bahan</div>
                                    </th>
                                    <th class="p-2">
                                        <div class="font-semibold text-center">Total Stok</div>
                                    </th>
                                    </th>
                                </tr>
                            </thead>
                            <!-- Table body -->
                            <tbody class="text-sm font-medium divide-y divide-gray-100 dark:divide-gray-700/60">
                                @forelse($bahanSisaPalingSedikit as $bahan)
                                <tr>
                                    <td class="p-2">
                                        <div class="text-gray-800 dark:text-gray-100">{{ $bahan->dataBahan->nama_bahan }}</div>
                                    </td>
                                    <td class="p-2">
                                        <div class="text-center text-green-500">{{ $bahan->total_sisa  }} {{ $bahan->dataBahan->dataUnit->nama ?? 'Null'  }}</div>
                                    </td>
                                </tr>
                                @empty
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                    <td colspan="2" class="px-6 py-4 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-semibold text-gray-900">Data Tidak Ditemukan!</h3>
                                        <p class="mt-1 text-sm text-gray-500">Maaf, data yang Anda cari tidak ada</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
    </div>
    <script>
        function formatRupiah(value) {
            return value === 0 ? '' : 'Rp ' + value.toLocaleString('id-ID'); // Format as Rupiah
        }
        var options = {
            chart: {
                type: 'bar',
                height: '350',
            },
            series: [
                {
                    name: 'Sub Total Bahan Masuk',
                    data: @json($chartDataMasuk)
                },
                {
                    name: 'Sub Total Bahan Keluar',
                    data: @json($chartDataKeluar)
                }
            ],
            xaxis: {
                categories: @json($dates),
                labels: {
                    formatter: function (value) {
                        return value.includes('-') ? new Date(value).toLocaleDateString('id-ID', { month: 'short', day: 'numeric' }) : value;
                    }
                }
            },
            colors: ['#1E90FF', '#FF6347'],
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return formatRupiah(val);
                },
                style: {
                    colors: ['#000']
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return formatRupiah(val);
                    }
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 10,
                    dataLabels: {
                        position: 'top',
                    }
                }
            }
        };
        var chart = new ApexCharts(document.querySelector("#dashboard-bar-chart"), options);
        chart.render();
    </script>

    <script>
        var options = {
            chart: {
                type: 'pie',
                height: '350'
            },
            series: @json($chartData),
            labels: @json($chartLabels),
            colors: ['#f94144', '#f3722c', '#f8961e', '#f9844a', '#f9c74f', '#90be6d', '#43aa8b', '#577590'],
            tooltip: {
                y: {
                    formatter: function (value, { seriesIndex }) {
                    var totalQty = @json($chartTotalQty)[seriesIndex];
                    return totalQty ;
                }
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%'
                    }
                }
            }
        };

        var chart = new ApexCharts(document.querySelector("#bahan-pie-chart"), options);
        chart.render();
    </script>
    <script>
        var options = {
            chart: {
                type: 'bar',
                height: 350,
                toolbar: { show: false }
            },
            series: [{
                name: 'Jumlah',
                data: @json($sortedData) // sudah terurut
            }],
            xaxis: {
                categories: @json($sortedLabels),
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: '12px',
                        colors: '#6B7280'
                    }
                }
            },
            // warna berbeda untuk setiap bar
            colors: [
                '#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#8B5CF6', '#EC4899',
                '#14B8A6', '#F97316', '#84CC16', '#6366F1', '#D946EF', '#0EA5E9'
            ],
            plotOptions: {
                bar: {
                    borderRadius: 6,
                    columnWidth: '50%',
                    distributed: true // biar tiap bar ambil warna dari array colors
                }
            },
            dataLabels: {
                enabled: true
            },
            legend: {
                show: true,
                position: 'bottom',
                horizontalAlign: 'center',
                markers: {
                    width: 12,
                    height: 12,
                    radius: 12
                },
                itemMargin: {
                    horizontal: 8,
                    vertical: 0
                }
            },
            tooltip: {
                theme: 'dark'
            }
        };

        var chart = new ApexCharts(document.querySelector("#grafikBahanRusak"), options);
        chart.render();
    </script>
</x-app-layout>
