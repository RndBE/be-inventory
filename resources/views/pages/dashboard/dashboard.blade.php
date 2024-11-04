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

            <div class="flex flex-col col-span-full sm:col-span-6 xl:col-span-3 bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                <div class="bg-clip-border mx-4 rounded-xl overflow-hidden bg-gradient-to-tr from-blue-600 to-blue-400 text-white shadow-blue-500/40 shadow-lg absolute -mt-4 grid h-16 w-16 place-items-center">
                    <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="currentColor"  class="icon icon-tabler icons-tabler-filled icon-tabler-archive"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M2 3m0 2a2 2 0 0 1 2 -2h16a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-16a2 2 0 0 1 -2 -2z" /><path d="M19 9c.513 0 .936 .463 .993 1.06l.007 .14v7.2c0 1.917 -1.249 3.484 -2.824 3.594l-.176 .006h-10c-1.598 0 -2.904 -1.499 -2.995 -3.388l-.005 -.212v-7.2c0 -.663 .448 -1.2 1 -1.2h14zm-5 2h-4l-.117 .007a1 1 0 0 0 0 1.986l.117 .007h4l.117 -.007a1 1 0 0 0 0 -1.986l-.117 -.007z" /></svg>
                </div>
                <div class="p-4 text-right">
                    <p class="block antialiased font-sans text-sm leading-normal font-normal text-blue-gray-600">Total Bahan</p>
                    <h4 class="block antialiased tracking-normal font-sans text-2xl font-semibold leading-snug text-blue-gray-900">{{ $totalBahan }}</h4>
                </div>
                <!-- Chart built with Chart.js 3 -->
                <!-- Check out src/js/components/dashboard-card-01.js for config -->
                <div class="border-t border-blue-gray-50 p-4">
                    <p class="block antialiased font-sans text-base leading-relaxed font-normal text-blue-gray-600">
                        <strong class="text-green-500"></strong>&nbsp;
                    </p>
                </div>
            </div>

            <div class="flex flex-col col-span-full sm:col-span-6 xl:col-span-3 bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                <div class="bg-clip-border mx-4 rounded-xl overflow-hidden bg-gradient-to-tr from-blue-600 to-blue-400 text-white shadow-blue-500/40 shadow-lg absolute -mt-4 grid h-16 w-16 place-items-center">
                    <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="currentColor"  class="icon icon-tabler icons-tabler-filled icon-tabler-archive"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M2 3m0 2a2 2 0 0 1 2 -2h16a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-16a2 2 0 0 1 -2 -2z" /><path d="M19 9c.513 0 .936 .463 .993 1.06l.007 .14v7.2c0 1.917 -1.249 3.484 -2.824 3.594l-.176 .006h-10c-1.598 0 -2.904 -1.499 -2.995 -3.388l-.005 -.212v-7.2c0 -.663 .448 -1.2 1 -1.2h14zm-5 2h-4l-.117 .007a1 1 0 0 0 0 1.986l.117 .007h4l.117 -.007a1 1 0 0 0 0 -1.986l-.117 -.007z" /></svg>
                </div>
                <div class="p-4 text-right">
                    <p class="block antialiased font-sans text-sm leading-normal font-normal text-blue-gray-600">Total Jenis Bahan</p>
                    <h4 class="block antialiased tracking-normal font-sans text-2xl font-semibold leading-snug text-blue-gray-900">{{ $totalJenisBahan }}</h4>
                </div>
                <!-- Chart built with Chart.js 3 -->
                <!-- Check out src/js/components/dashboard-card-01.js for config -->
                <div class="border-t border-blue-gray-50 p-4">
                    <p class="block antialiased font-sans text-base leading-relaxed font-normal text-blue-gray-600">
                        <strong class="text-green-500"></strong>&nbsp;
                    </p>
                </div>
            </div>

            <div class="flex flex-col col-span-full sm:col-span-6 xl:col-span-3 bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                <div class="bg-clip-border mx-4 rounded-xl overflow-hidden bg-gradient-to-tr from-blue-600 to-blue-400 text-white shadow-blue-500/40 shadow-lg absolute -mt-4 grid h-16 w-16 place-items-center">
                    <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="currentColor"  class="icon icon-tabler icons-tabler-filled icon-tabler-archive"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M2 3m0 2a2 2 0 0 1 2 -2h16a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-16a2 2 0 0 1 -2 -2z" /><path d="M19 9c.513 0 .936 .463 .993 1.06l.007 .14v7.2c0 1.917 -1.249 3.484 -2.824 3.594l-.176 .006h-10c-1.598 0 -2.904 -1.499 -2.995 -3.388l-.005 -.212v-7.2c0 -.663 .448 -1.2 1 -1.2h14zm-5 2h-4l-.117 .007a1 1 0 0 0 0 1.986l.117 .007h4l.117 -.007a1 1 0 0 0 0 -1.986l-.117 -.007z" /></svg>
                </div>
                <div class="p-4 text-right">
                    <p class="block antialiased font-sans text-sm leading-normal font-normal text-blue-gray-600">Total Satuan Unit</p>
                    <h4 class="block antialiased tracking-normal font-sans text-2xl font-semibold leading-snug text-blue-gray-900">{{ $totalSatuanUnit }}</h4>
                </div>
                <!-- Chart built with Chart.js 3 -->
                <!-- Check out src/js/components/dashboard-card-01.js for config -->
                <div class="border-t border-blue-gray-50 p-4">
                    <p class="block antialiased font-sans text-base leading-relaxed font-normal text-blue-gray-600">
                        <strong class="text-green-500"></strong>&nbsp;
                    </p>
                </div>
            </div>

            <div class="flex flex-col col-span-full sm:col-span-6 xl:col-span-3 bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                <div class="bg-clip-border mx-4 rounded-xl overflow-hidden bg-gradient-to-tr from-blue-600 to-blue-400 text-white shadow-blue-500/40 shadow-lg absolute -mt-4 grid h-16 w-16 place-items-center">
                    <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="currentColor"  class="icon icon-tabler icons-tabler-filled icon-tabler-archive"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M2 3m0 2a2 2 0 0 1 2 -2h16a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-16a2 2 0 0 1 -2 -2z" /><path d="M19 9c.513 0 .936 .463 .993 1.06l.007 .14v7.2c0 1.917 -1.249 3.484 -2.824 3.594l-.176 .006h-10c-1.598 0 -2.904 -1.499 -2.995 -3.388l-.005 -.212v-7.2c0 -.663 .448 -1.2 1 -1.2h14zm-5 2h-4l-.117 .007a1 1 0 0 0 0 1.986l.117 .007h4l.117 -.007a1 1 0 0 0 0 -1.986l-.117 -.007z" /></svg>
                </div>
                <div class="p-4 text-right">
                    <p class="block antialiased font-sans text-sm leading-normal font-normal text-blue-gray-600">Total Produk Produksi</p>
                    <h4 class="block antialiased tracking-normal font-sans text-2xl font-semibold leading-snug text-blue-gray-900">{{ $totalProdukProduksi }}</h4>
                </div>
                <!-- Chart built with Chart.js 3 -->
                <!-- Check out src/js/components/dashboard-card-01.js for config -->
                <div class="border-t border-blue-gray-50 p-4">
                    <p class="block antialiased font-sans text-base leading-relaxed font-normal text-blue-gray-600">
                        <strong class="text-green-500"></strong>&nbsp;
                    </p>
                </div>
            </div>

            <div class="flex flex-col col-span-full sm:col-span-8 bg-white dark:bg-gray-800 shadow-sm rounded-xl">
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
                    </form>
                </header>


                <div class="grow">
                    <div id="dashboard-bar-chart"></div>
                </div>
            </div>

            <div class="flex flex-col col-span-full sm:col-span-3 bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60 flex justify-between items-center">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100">Pengajuan Bahan</h2>
                </header>
                <div class="flex justify-center items-center h-full">
                    <div class="group my-3 inline-flex flex-wrap justify-center items-center gap-12">
                        <div class="flex flex-col items-center">
                            <header class="px-5 py-4 border-gray-100 dark:border-gray-700/60 flex justify-between items-center">
                                <h2 class="font-semibold text-5xl text-gray-800 dark:text-gray-100">{{ $totalPengajuanBahanKeluar }}</h2>
                            </header>
                            <button class="rounded-full pointer-events-none border border-slate-300 p-2.5 text-center text-sm transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-slate-800 hover:border-slate-800 focus:text-white focus:bg-slate-800 focus:border-slate-800 active:border-slate-800 active:text-white active:bg-slate-800 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none" type="button">
                                <svg  xmlns="http://www.w3.org/2000/svg"  width="32"  height="32"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-door-exit"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M13 12v.01" /><path d="M3 21h18" /><path d="M5 21v-16a2 2 0 0 1 2 -2h7.5m2.5 10.5v7.5" /><path d="M14 7h7m-3 -3l3 3l-3 3" /></svg>
                            </button>
                            <span>Bahan Keluar</span>
                        </div>

                        <div class="flex flex-col items-center">
                            <header class="px-5 py-4 border-gray-100 dark:border-gray-700/60 flex justify-between items-center">
                                <h2 class="font-semibold text-5xl text-gray-800 dark:text-gray-100">{{ $totalPengajuanBahanRusak }}</h2>
                            </header>
                            <button class="rounded-full pointer-events-none border border-slate-300 p-2.5 text-center text-sm transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-slate-800 hover:border-slate-800 focus:text-white focus:bg-slate-800 focus:border-slate-800 active:border-slate-800 active:text-white active:bg-slate-800 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none" type="button">
                                <svg class="shrink-0 fill-current" xmlns="http://www.w3.org/2000/svg" width="32"  height="32" viewBox="0 0 24 24">
                                    <path d="M18 17h-.09c.058-.33.088-.665.09-1v-1h1a1 1 0 0 0 0-2h-1.09a5.97 5.97 0 0 0-.26-1H17a2 2 0 0 0 2-2V8a1 1 0 1 0-2 0v2h-.54a6.239 6.239 0 0 0-.46-.46V8a3.963 3.963 0 0 0-.986-2.6l.693-.693A1 1 0 0 0 16 4V3a1 1 0 1 0-2 0v.586l-.661.661a3.753 3.753 0 0 0-2.678 0L10 3.586V3a1 1 0 1 0-2 0v1a1 1 0 0 0 .293.707l.693.693A3.963 3.963 0 0 0 8 8v1.54a6.239 6.239 0 0 0-.46.46H7V8a1 1 0 0 0-2 0v2a2 2 0 0 0 2 2h-.65a5.97 5.97 0 0 0-.26 1H5a1 1 0 0 0 0 2h1v1a6 6 0 0 0 .09 1H6a2 2 0 0 0-2 2v2a1 1 0 1 0 2 0v-2h.812A6.012 6.012 0 0 0 11 21.907V12a1 1 0 0 1 2 0v9.907A6.011 6.011 0 0 0 17.188 19H18v2a1 1 0 0 0 2 0v-2a2 2 0 0 0-2-2Zm-4-8.65a5.922 5.922 0 0 0-.941-.251l-.111-.017a5.52 5.52 0 0 0-1.9 0l-.111.017A5.925 5.925 0 0 0 10 8.35V8a2 2 0 1 1 4 0v.35Z"/>
                                </svg>
                            </button>
                            <span>Bahan Rusak</span>
                        </div>

                        <div class="flex flex-col items-center">
                            <header class="px-5 py-4 border-gray-100 dark:border-gray-700/60 flex justify-between items-center">
                                <h2 class="font-semibold text-5xl text-gray-800 dark:text-gray-100">{{ $totalPengajuanBahanRetur }}</h2>
                            </header>
                            <button class="rounded-full pointer-events-none border border-slate-300 p-2.5 text-center text-sm transition-all shadow-sm hover:shadow-lg text-slate-600 hover:text-white hover:bg-slate-800 hover:border-slate-800 focus:text-white focus:bg-slate-800 focus:border-slate-800 active:border-slate-800 active:text-white active:bg-slate-800 disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none" type="button">
                                <svg  xmlns="http://www.w3.org/2000/svg"  width="32"  height="32"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler shrink-0 icons-tabler-outline icon-tabler-refresh"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>
                            </button>
                            <span>Bahan Retur</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col col-span-full sm:col-span-6 xl:col-span-4 bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60"><h2 class="font-semibold text-gray-800 dark:text-gray-100">Produk Setengah Jadi</h2>
                </header>
                <div class="grow">
                    <div id="bahan-pie-chart"></div>
                </div>
            </div>

            <div class="col-span-full xl:col-span-8 bg-white dark:bg-gray-800 shadow-sm rounded-xl">
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
                                        <div class="font-semibold text-center">Subtotal</div>
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
                                        <div class="text-center">Rp {{ number_format($produksi->total_subtotal, 0, ',', '.') }}</div>
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


            <div class="col-span-full xl:col-span-6 bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100">Projek</h2>
                </header>
                <div class="p-3">

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="table-auto w-full dark:text-gray-300">
                            <!-- Table header -->
                            <thead class="text-xs uppercase text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-700 dark:bg-opacity-50 rounded-sm">
                                <tr>
                                    <th class="p-2">
                                        <div class="font-semibold text-left">Nama Projek</div>
                                    </th>
                                    <th class="p-2">
                                        <div class="font-semibold text-center">Mulai Projek</div>
                                    </th>
                                    <th class="p-2">
                                        <div class="font-semibold text-center">Subtotal</div>
                                    </th>
                                </tr>
                            </thead>
                            <!-- Table body -->
                            <tbody class="text-sm font-medium divide-y divide-gray-100 dark:divide-gray-700/60">
                                @forelse($projeks as $projek)
                                <tr>
                                    <td class="p-2">
                                        <div class="text-gray-800 dark:text-gray-100">{{ $projek->nama_projek }}</div>
                                    </td>
                                    <td class="p-2">
                                        <div class="text-center">{{ $projek->mulai_projek }}</div>
                                    </td>
                                    <td class="p-2">
                                        <div class="text-center">Rp {{ number_format($projek->total_subtotal, 0, ',', '.') }}</div>
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

            <div class="col-span-full xl:col-span-6 bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100">Projek RnD</h2>
                </header>
                <div class="p-3">

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="table-auto w-full dark:text-gray-300">
                            <!-- Table header -->
                            <thead class="text-xs uppercase text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-700 dark:bg-opacity-50 rounded-sm">
                                <tr>
                                    <th class="p-2">
                                        <div class="font-semibold text-left">Nama Projek RnD</div>
                                    </th>
                                    <th class="p-2">
                                        <div class="font-semibold text-center">Mulai Projek</div>
                                    </th>
                                    <th class="p-2">
                                        <div class="font-semibold text-center">Subtotal</div>
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
                                        <div class="text-center">Rp {{ number_format($projek_rnd->total_subtotal, 0, ',', '.') }}</div>
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
            colors: ['#f94144', '#f3722c', '#f8961e', '#f9844a', '#f9c74f', '#90be6d', '#43aa8b', '#577590'], // Customize colors as needed
        };

        var chart = new ApexCharts(document.querySelector("#bahan-pie-chart"), options);
        chart.render();
    </script>
</x-app-layout>
