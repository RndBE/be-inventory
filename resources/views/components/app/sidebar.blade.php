<div class="max-w-fit">
    <!-- Sidebar backdrop (mobile only) -->
    <div
        class="fixed inset-0 bg-gray-900 bg-opacity-30 z-40 lg:hidden lg:z-auto transition-opacity duration-200"
        :class="sidebarOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'"
        aria-hidden="true"
        x-cloak
    ></div>

    <!-- Sidebar -->
    <div
        id="sidebar"
        class="flex lg:!flex flex-col absolute z-40 left-0 top-0 lg:static lg:left-auto lg:top-auto lg:translate-x-0 h-[100dvh] overflow-y-scroll lg:overflow-y-auto no-scrollbar w-64 lg:w-20 lg:sidebar-expanded:!w-64 2xl:!w-64 shrink-0 bg-white dark:bg-gray-800 p-4 transition-all duration-200 ease-in-out {{ $variant === 'v2' ? 'border-r border-gray-200 dark:border-gray-700/60' : 'rounded-r-2xl shadow-sm' }}"
        :class="sidebarOpen ? 'max-lg:translate-x-0' : 'max-lg:-translate-x-64'"
        @click.outside="sidebarOpen = false"
        @keydown.escape.window="sidebarOpen = false"
    >

        <!-- Sidebar header -->
        <div class="flex justify-between mb-10 pr-3 sm:px-2">
            <!-- Close button -->
            <button class="lg:hidden text-gray-500 hover:text-gray-400" @click.stop="sidebarOpen = !sidebarOpen" aria-controls="sidebar" :aria-expanded="sidebarOpen">
                <span class="sr-only">Close sidebar</span>
                <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10.7 18.7l1.4-1.4L7.8 13H20v-2H7.8l4.3-4.3-1.4-1.4L4 12z" />
                </svg>
            </button>
            <!-- Logo -->
            <a class="block" href="{{ route('dashboard') }}">
                <svg class="fill-red-500" xmlns="http://www.w3.org/2000/svg" width="32" height="32">
                    <img src="{{ asset('images/logo_be2.png') }}" alt="Logo-Be">
                </svg>
            </a>
        </div>

        <!-- Links -->
        <div class="space-y-8">
            <!-- Dashboard group -->
            <div>
                <h3 class="text-xs uppercase text-gray-400 dark:text-gray-500 font-semibold pl-3">
                    <span class="hidden lg:block lg:sidebar-expanded:hidden 2xl:hidden text-center w-6" aria-hidden="true">•••</span>
                    <span class="lg:hidden lg:sidebar-expanded:block 2xl:block">Dashboard</span>
                </h3>
                <ul class="mt-3">
                    <!-- Dashboard -->
                    <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['dashboard'])){{ 'from-red-500/[0.12] dark:from-red-500/[0.24] to-red-500/[0.04]' }}@endif">
                        <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['dashboard'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('dashboard') }}">
                            <div class="flex items-center">
                                <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['dashboard'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="22"  height="22" viewBox="0 0 16 16">
                                        <path d="M5.936.278A7.983 7.983 0 0 1 8 0a8 8 0 1 1-8 8c0-.722.104-1.413.278-2.064a1 1 0 1 1 1.932.516A5.99 5.99 0 0 0 2 8a6 6 0 1 0 6-6c-.53 0-1.045.076-1.548.21A1 1 0 1 1 5.936.278Z" />
                                        <path d="M6.068 7.482A2.003 2.003 0 0 0 8 10a2 2 0 1 0-.518-3.932L3.707 2.293a1 1 0 0 0-1.414 1.414l3.775 3.775Z" />
                                    </svg>
                                <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Dashboard</span>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>
            <!-- Master Data -->
            <div>
                <h3 class="text-xs uppercase text-gray-400 dark:text-gray-500 font-semibold pl-3">
                    <span class="hidden lg:block lg:sidebar-expanded:hidden 2xl:hidden text-center w-6" aria-hidden="true">•••</span>
                    <span class="lg:hidden lg:sidebar-expanded:block 2xl:block">Data</span>
                </h3>
                <ul class="mt-3">
                    <!-- Data Master -->
                    <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['datamaster'])){{ 'from-red-500/[0.12] dark:from-red-500/[0.24] to-red-500/[0.04]' }}@endif" x-data="{ open: {{ in_array(Request::segment(1), ['datamaster']) ? 1 : 0 }} }">
                        <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['datamaster'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="#0" @click.prevent="open = !open; sidebarExpanded = true">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['datamaster'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="22"  height="22" viewBox="0 0 24 24">
                                        <path d="M12 7.205c4.418 0 8-1.165 8-2.602C20 3.165 16.418 2 12 2S4 3.165 4 4.603c0 1.437 3.582 2.602 8 2.602ZM12 22c4.963 0 8-1.686 8-2.603v-4.404c-.052.032-.112.06-.165.09a7.75 7.75 0 0 1-.745.387c-.193.088-.394.173-.6.253-.063.024-.124.05-.189.073a18.934 18.934 0 0 1-6.3.998c-2.135.027-4.26-.31-6.3-.998-.065-.024-.126-.05-.189-.073a10.143 10.143 0 0 1-.852-.373 7.75 7.75 0 0 1-.493-.267c-.053-.03-.113-.058-.165-.09v4.404C4 20.315 7.037 22 12 22Zm7.09-13.928a9.91 9.91 0 0 1-.6.253c-.063.025-.124.05-.189.074a18.935 18.935 0 0 1-6.3.998c-2.135.027-4.26-.31-6.3-.998-.065-.024-.126-.05-.189-.074a10.163 10.163 0 0 1-.852-.372 7.816 7.816 0 0 1-.493-.268c-.055-.03-.115-.058-.167-.09V12c0 .917 3.037 2.603 8 2.603s8-1.686 8-2.603V7.596c-.052.031-.112.059-.165.09a7.816 7.816 0 0 1-.745.386Z"/>
                                    </svg>

                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Data Master</span>
                                </div>

                                <!-- Icon -->
                                <div class="flex shrink-0 ml-2 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">
                                    <svg class="w-3 h-3 shrink-0 ml-1 fill-current text-gray-400 dark:text-gray-500 @if(in_array(Request::segment(1), ['datamaster'])){{ 'rotate-180' }}@endif" :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                                        <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                        <div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
                            <ul class="pl-8 mt-1 @if(!in_array(Request::segment(1), ['datamaster'])){{ 'hidden' }}@endif" :class="open ? '!block' : 'hidden'">
                                @can('lihat-barang')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('barang-aset.index')){{ '!text-[#B40404]' }}@endif" href="{{ route('barang-aset.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Barang Aset</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('lihat-bahan')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('bahan.index')){{ '!text-[#B40404]' }}@endif" href="{{ route('bahan.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Bahan</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('lihat-jenis-bahan')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('jenis-bahan.index')){{ '!text-[#B40404]' }}@endif" href="{{ route('jenis-bahan.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Jenis Bahan</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('lihat-unit')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('unit.index')){{ '!text-[#B40404]' }}@endif" href="{{ route('unit.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Unit</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('lihat-supplier')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('supplier.index')){{ '!text-[#B40404]' }}@endif" href="{{ route('supplier.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Supplier</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('lihat-produk-produksi')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('produk-produksis.index')){{ '!text-[#B40404]' }}@endif" href="{{ route('produk-produksis.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Produk Setengah Jadi</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('lihat-produk-produksi')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('produk-jadis.index')){{ '!text-[#B40404]' }}@endif" href="{{ route('produk-jadis.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Produk Jadi</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('lihat-kontrak')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('kontrak.index')){{ '!text-[#B40404]' }}@endif" href="{{ route('kontrak.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Kontrak</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                    <!-- Transaksi -->
                    @role('superadmin|purchasing|purchasing level 3|rnd level 3|teknisi level 3|marketing level 3|administrasi|admin|direksi|marketing manager|administration manager|hardware manager|software manager|sekretaris|general_affair')
                    <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['transaksi'])){{ 'from-red-500/[0.12] dark:from-red-500/[0.24] to-red-500/[0.04]' }}@endif" x-data="{ open: {{ in_array(Request::segment(1), ['transaksi']) ? 1 : 0 }} }">
                        <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['transaksi'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="#0" @click.prevent="open = !open; sidebarExpanded = true">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['transaksi'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" aria-hidden="true"  xmlns="http://www.w3.org/2000/svg" width="22"  height="22" fill="none" viewBox="0 0 24 24">
                                        <path d="M12.268 6A2 2 0 0 0 14 9h1v1a2 2 0 0 0 3.04 1.708l-.311 1.496a1 1 0 0 1-.979.796H8.605l.208 1H16a3 3 0 1 1-2.83 2h-2.34a3 3 0 1 1-4.009-1.76L4.686 5H4a1 1 0 0 1 0-2h1.5a1 1 0 0 1 .979.796L6.939 6h5.329Z"/>
                                        <path d="M18 4a1 1 0 1 0-2 0v2h-2a1 1 0 1 0 0 2h2v2a1 1 0 1 0 2 0V8h2a1 1 0 1 0 0-2h-2V4Z"/>
                                    </svg>

                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Transaksi
                                        @if($jumlahBahanKeluar > 0 || $jumlahPembelianBahan > 0)
                                            <span class="inline-flex items-center justify-center w-4 h-4 ms-2 text-xs font-semibold text-black  rounded-full" style="background-color: #f1b1b1">
                                                {{ $jumlahBahanKeluar + $jumlahPembelianBahan }}
                                            </span>
                                        @endif
                                    </span>
                                </div>
                                <!-- Icon -->
                                <div class="flex shrink-0 ml-2 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">
                                    <svg class="w-3 h-3 shrink-0 ml-1 fill-current text-gray-400 dark:text-gray-500 @if(in_array(Request::segment(1), ['transaksi'])){{ 'rotate-180' }}@endif" :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                                        <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                        <div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
                            <ul class="pl-8 mt-1 @if(!in_array(Request::segment(1), ['transaksi'])){{ 'hidden' }}@endif" :class="open ? '!block' : 'hidden'">
                                @can('lihat-bahan-masuk')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('purchases.index')){{ '!text-[#B40404]' }}@endif" href="{{ route('purchases.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Bahan Masuk</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('lihat-bahan-keluar')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('bahan-keluars.index')){{ '!text-[#B40404]' }}@endif" href="{{ route('bahan-keluars.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Bahan Keluar
                                                @if($jumlahBahanKeluar > 0)
                                                    <span class="inline-flex items-center justify-center w-4 h-4 ms-2 text-xs font-semibold text-black bg-indigo-200 rounded-full" style="background-color: #f1b1b1">
                                                        {{ $jumlahBahanKeluar }}
                                                    </span>
                                                @endif
                                            </span>
                                        </a>
                                    </li>
                                @endcan
                                @can('lihat-pembelian-bahan')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('pengajuan-pembelian-bahan.index')){{ '!text-[#B40404]' }}@endif" href="{{ route('pengajuan-pembelian-bahan.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Pembelian Bahan
                                                @if($jumlahPembelianBahan > 0)
                                                    <span class="inline-flex items-center justify-center w-4 h-4 ms-2 text-xs font-semibold text-black bg-indigo-200 rounded-full" style="background-color: #f1b1b1">
                                                        {{ $jumlahPembelianBahan }}
                                                    </span>
                                                @endif
                                            </span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                    @endrole
                    <!-- Bahan Rusak -->
                    @can('lihat-bahan-rusak')
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['bahan-rusaks'])){{ 'from-red-500/[0.12] dark:from-red-500/[0.24] to-red-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['bahan-rusaks.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('bahan-rusaks.index') }}">
                                <div class="flex items-center">
                                    <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['bahan-rusaks'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="22"  height="22" viewBox="0 0 24 24">
                                        <path d="M18 17h-.09c.058-.33.088-.665.09-1v-1h1a1 1 0 0 0 0-2h-1.09a5.97 5.97 0 0 0-.26-1H17a2 2 0 0 0 2-2V8a1 1 0 1 0-2 0v2h-.54a6.239 6.239 0 0 0-.46-.46V8a3.963 3.963 0 0 0-.986-2.6l.693-.693A1 1 0 0 0 16 4V3a1 1 0 1 0-2 0v.586l-.661.661a3.753 3.753 0 0 0-2.678 0L10 3.586V3a1 1 0 1 0-2 0v1a1 1 0 0 0 .293.707l.693.693A3.963 3.963 0 0 0 8 8v1.54a6.239 6.239 0 0 0-.46.46H7V8a1 1 0 0 0-2 0v2a2 2 0 0 0 2 2h-.65a5.97 5.97 0 0 0-.26 1H5a1 1 0 0 0 0 2h1v1a6 6 0 0 0 .09 1H6a2 2 0 0 0-2 2v2a1 1 0 1 0 2 0v-2h.812A6.012 6.012 0 0 0 11 21.907V12a1 1 0 0 1 2 0v9.907A6.011 6.011 0 0 0 17.188 19H18v2a1 1 0 0 0 2 0v-2a2 2 0 0 0-2-2Zm-4-8.65a5.922 5.922 0 0 0-.941-.251l-.111-.017a5.52 5.52 0 0 0-1.9 0l-.111.017A5.925 5.925 0 0 0 10 8.35V8a2 2 0 1 1 4 0v.35Z"/>
                                    </svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Bahan Rusak
                                        @if($jumlahBahanRusak > 0)
                                            <span class="inline-flex items-center justify-center w-4 h-4 ms-2 text-xs font-semibold text-black bg-indigo-200 rounded-full" style="background-color: #f1b1b1">
                                                {{ $jumlahBahanRusak }}
                                            </span>
                                        @endif
                                    </span>
                                </div>
                            </a>
                        </li>
                    @endcan
                    @can('lihat-bahan-retur')
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['bahan-returs'])){{ 'from-red-500/[0.12] dark:from-red-500/[0.24] to-red-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['bahan-returs.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('bahan-returs.index') }}">
                                <div class="flex items-center">
                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="22"  height="22"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler shrink-0 icons-tabler-outline icon-tabler-refresh @if(in_array(Request::segment(1), ['bahan-returs'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>

                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Bahan Retur
                                        @if($jumlahBahanRetur > 0)
                                            <span class="inline-flex items-center justify-center w-4 h-4 ms-2 text-xs font-semibold text-black bg-indigo-200 rounded-full" style="background-color: #f1b1b1">
                                                {{ $jumlahBahanRetur }}
                                            </span>
                                        @endif
                                    </span>
                                </div>
                            </a>
                        </li>
                    @endcan
                    @can('lihat-stock-opname')
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['stock-opname'])){{ 'from-red-500/[0.12] dark:from-red-500/[0.24] to-red-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['stock-opname.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('stock-opname.index') }}">
                                <div class="flex items-center">
                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="22"  height="22"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler shrink-0 icons-tabler-outline icon-tabler-checkup-list @if(in_array(Request::segment(1), ['stock-opname'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" /><path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" /><path d="M9 14h.01" /><path d="M9 17h.01" /><path d="M12 16l1 1l3 -3" /></svg>

                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Stock Opname
                                        {{-- @if($jumlahBahanRetur > 0)
                                            <span class="inline-flex items-center justify-center w-4 h-4 ms-2 text-xs font-semibold text-black bg-indigo-200 rounded-full" style="background-color: #f1b1b1">
                                                {{ $jumlahBahanRetur }}
                                            </span>
                                        @endif --}}
                                    </span>
                                </div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </div>
            <!-- Pengajuan -->
            <div>
                <h3 class="text-xs uppercase text-gray-400 dark:text-gray-500 font-semibold pl-3">
                    <span class="hidden lg:block lg:sidebar-expanded:hidden 2xl:hidden text-center w-6" aria-hidden="true">•••</span>
                    <span class="lg:hidden lg:sidebar-expanded:block 2xl:block">Pengajuan Pembelian Bahan</span>
                </h3>
                <ul class="mt-3">
                    @can('lihat-pengajuan')
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['pengajuans'])){{ 'from-red-500/[0.12] dark:from-red-500/[0.24] to-red-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['pengajuans.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('pengajuans.index') }}">
                                <div class="flex items-center">
                                    <svg  class="shrink-0 fill-current @if(in_array(Request::segment(1), ['pengajuans'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg"  width="22"  height="22"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-list-check">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3.5 5.5l1.5 1.5l2.5 -2.5" /><path d="M3.5 11.5l1.5 1.5l2.5 -2.5" />
                                        <path d="M3.5 17.5l1.5 1.5l2.5 -2.5" /><path d="M11 6l9 0" /><path d="M11 12l9 0" /><path d="M11 18l9 0" />
                                    </svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Pengajuan Pembelian</span>
                                </div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </div>
            <!-- Aset -->
            @role('superadmin|sekretaris|general_affair')
            <div>
                <h3 class="text-xs uppercase text-gray-400 dark:text-gray-500 font-semibold pl-3">
                    <span class="hidden lg:block lg:sidebar-expanded:hidden 2xl:hidden text-center w-6" aria-hidden="true">•••</span>
                    <span class="lg:hidden lg:sidebar-expanded:block 2xl:block">Aset</span>
                </h3>
                <ul class="mt-3">
                    @can('lihat-rekap-aset')
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['rekap-aset'])){{ 'from-red-500/[0.12] dark:from-red-500/[0.24] to-red-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['rekap-aset.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('rekap-aset.index') }}">
                                <div class="flex items-center">
                                    <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['rekap-aset'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg"  width="22"  height="22"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-server-spark"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19 22.5a4.75 4.75 0 0 1 3.5 -3.5a4.75 4.75 0 0 1 -3.5 -3.5a4.75 4.75 0 0 1 -3.5 3.5a4.75 4.75 0 0 1 3.5 3.5" /><path d="M3 7a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v2a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" /><path d="M12 20h-6a3 3 0 0 1 -3 -3v-2a3 3 0 0 1 3 -3h10.5" /><path d="M7 8v.01" /><path d="M7 16v.01" /></svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Rekapitulasi Aset</span>
                                </div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </div>
            @endrole
            <!-- Pengambilan -->
            <div>
                <h3 class="text-xs uppercase text-gray-400 dark:text-gray-500 font-semibold pl-3">
                    <span class="hidden lg:block lg:sidebar-expanded:hidden 2xl:hidden text-center w-6" aria-hidden="true">•••</span>
                    <span class="lg:hidden lg:sidebar-expanded:block 2xl:block">Pengambilan Bahan</span>
                </h3>
                <ul class="mt-3">
                    @can('lihat-pengambilan')
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['pengambilan-bahan'])){{ 'from-red-500/[0.12] dark:from-red-500/[0.24] to-red-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['pengambilan-bahan.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('pengambilan-bahan.index') }}">
                                <div class="flex items-center">
                                    <svg  class="shrink-0 fill-current @if(in_array(Request::segment(1), ['pengambilan-bahan'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg"  width="22"  height="22"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-list-check">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3.5 5.5l1.5 1.5l2.5 -2.5" /><path d="M3.5 11.5l1.5 1.5l2.5 -2.5" />
                                        <path d="M3.5 17.5l1.5 1.5l2.5 -2.5" /><path d="M11 6l9 0" /><path d="M11 12l9 0" /><path d="M11 18l9 0" />
                                    </svg>
                                    <span
                                        class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200 break-words whitespace-normal">
                                        Pengambilan Bahan <br> Non Proyek/Produksi
                                    </span>
                                </div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </div>
            <!-- Produksi -->
            <div>
                @if(Gate::allows('lihat-menu-produksi'))
                    <h3 class="text-xs uppercase text-gray-400 dark:text-gray-500 font-semibold pl-3">
                        <span class="hidden lg:block lg:sidebar-expanded:hidden 2xl:hidden text-center w-6" aria-hidden="true">•••</span>
                        <span class="lg:hidden lg:sidebar-expanded:block 2xl:block">Produksi</span>
                    </h3>
                @endif
                <ul class="mt-3">
                    @can('lihat-proses-produksi')
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['produksis'])){{ 'from-red-500/[0.12] dark:from-red-500/[0.24] to-red-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['produksis.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('produksis.index') }}">
                                <div class="flex items-center">
                                    {{-- <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['produksis'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="22"  height="22" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M17.44 3a1 1 0 0 1 .707.293l2.56 2.56a1 1 0 0 1 0 1.414L18.194 9.78 14.22 5.806l2.513-2.513A1 1 0 0 1 17.44 3Zm-4.634 4.22-9.513 9.513a1 1 0 0 0 0 1.414l2.56 2.56a1 1 0 0 0 1.414 0l9.513-9.513-3.974-3.974ZM6 6a1 1 0 0 1 1 1v1h1a1 1 0 0 1 0 2H7v1a1 1 0 1 1-2 0v-1H4a1 1 0 0 1 0-2h1V7a1 1 0 0 1 1-1Zm9 9a1 1 0 0 1 1 1v1h1a1 1 0 1 1 0 2h-1v1a1 1 0 1 1-2 0v-1h-1a1 1 0 1 1 0-2h1v-1a1 1 0 0 1 1-1Z" clip-rule="evenodd"/>
                                        <path d="M19 13h-2v2h2v-2ZM13 3h-2v2h2V3Zm-2 2H9v2h2V5ZM9 3H7v2h2V3Zm12 8h-2v2h2v-2Zm0 4h-2v2h2v-2Z"/>
                                    </svg> --}}

                                    <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['produksis'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="22"  height="22" viewBox="0 0 2048 2048"><path fill="currentColor" d="M640 768H256V384h384zm512 0H768V384h384zm-128-256H896v128h128zm640 256h-384V384h384zm-128-256h-128v128h128zm-128 768q0-27 10-50t27-40t41-28t50-10q27 0 50 10t40 27t28 41t10 50q0 27-10 50t-27 40t-41 28t-50 10q-27 0-50-10t-40-27t-28-41t-10-50m-1152 0q0-27 10-50t27-40t41-28t50-10q27 0 50 10t40 27t28 41t10 50q0 27-10 50t-27 40t-41 28t-50 10q-27 0-50-10t-40-27t-28-41t-10-50m384 0q0-27 10-50t27-40t41-28t50-10q27 0 50 10t40 27t28 41t10 50q0 27-10 50t-27 40t-41 28t-50 10q-27 0-50-10t-40-27t-28-41t-10-50m640 0q0 27-10 50t-27 40t-41 28t-50 10q-27 0-50-10t-40-27t-28-41t-10-50q0-27 10-50t27-40t41-28t50-10q27 0 50 10t40 27t28 41t10 50m-1152 0q0 53 20 99t55 82t81 55t100 20h640v128H384q-79 0-149-30t-122-83t-82-122t-31-149q0-79 30-149t83-122t122-82t149-31h1152q71 0 135 25t114 68t84 103t45 130l-135 135q6-19 9-38t4-39q0-53-20-99t-55-82t-81-55t-100-20H384q-53 0-99 20t-82 55t-55 81t-20 100m1901 173l-557 558l-269-270l90-90l179 178l467-466z"/></svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Produksi Produk <br> Setengah Jadi</span>
                                </div>
                            </a>
                        </li>
                    @endcan
                    {{-- Proses QC --}}
                    {{-- @can('lihat-proses-produksi') --}}
                        {{-- <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['quality-page'])){{ 'from-red-500/[0.12] dark:from-red-500/[0.24] to-red-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['quality-page.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('quality-page.index') }}">
                                <div class="flex items-center">
                                    <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['quality-page'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="22"  height="22" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M17.44 3a1 1 0 0 1 .707.293l2.56 2.56a1 1 0 0 1 0 1.414L18.194 9.78 14.22 5.806l2.513-2.513A1 1 0 0 1 17.44 3Zm-4.634 4.22-9.513 9.513a1 1 0 0 0 0 1.414l2.56 2.56a1 1 0 0 0 1.414 0l9.513-9.513-3.974-3.974ZM6 6a1 1 0 0 1 1 1v1h1a1 1 0 0 1 0 2H7v1a1 1 0 1 1-2 0v-1H4a1 1 0 0 1 0-2h1V7a1 1 0 0 1 1-1Zm9 9a1 1 0 0 1 1 1v1h1a1 1 0 1 1 0 2h-1v1a1 1 0 1 1-2 0v-1h-1a1 1 0 1 1 0-2h1v-1a1 1 0 0 1 1-1Z" clip-rule="evenodd"/>
                                        <path d="M19 13h-2v2h2v-2ZM13 3h-2v2h2V3Zm-2 2H9v2h2V5ZM9 3H7v2h2V3Zm12 8h-2v2h2v-2Zm0 4h-2v2h2v-2Z"/>
                                    </svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Quality Page</span>
                                </div>
                            </a>
                        </li> --}}
                    {{-- @endcan --}}
                    <!-- Bahan Setengah Jadi -->
                    @can('lihat-bahan-setengahjadi')
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['bahan-setengahjadis'])){{ 'from-red-500/[0.12] dark:from-red-500/[0.24] to-red-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['bahan-setengahjadis.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('bahan-setengahjadis.index') }}">
                                <div class="flex items-center">
                                    {{-- <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['bahan-setengahjadis'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="22"  height="22" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M5.005 10.19a1 1 0 0 1 1 1v.233l5.998 3.464L18 11.423v-.232a1 1 0 1 1 2 0V12a1 1 0 0 1-.5.866l-6.997 4.042a1 1 0 0 1-1 0l-6.998-4.042a1 1 0 0 1-.5-.866v-.81a1 1 0 0 1 1-1ZM5 15.15a1 1 0 0 1 1 1v.232l5.997 3.464 5.998-3.464v-.232a1 1 0 1 1 2 0v.81a1 1 0 0 1-.5.865l-6.998 4.042a1 1 0 0 1-1 0L4.5 17.824a1 1 0 0 1-.5-.866v-.81a1 1 0 0 1 1-1Z" clip-rule="evenodd"/>
                                        <path d="M12.503 2.134a1 1 0 0 0-1 0L4.501 6.17A1 1 0 0 0 4.5 7.902l7.002 4.047a1 1 0 0 0 1 0l6.998-4.04a1 1 0 0 0 0-1.732l-6.997-4.042Z"/>
                                    </svg> --}}

                                    <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['bahan-setengahjadis'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="22"  height="22" viewBox="0 0 512 512"><path fill="currentColor" d="m0 379.652l72.276-3.87v33.816L0 405.064zm0-71.95l72.276 4.534V278.42L0 282.29zM456.2 87.566l-72.275-3.871v33.817l72.276-4.534zm-80.232-17.684h-39.066v61.442h39.066zM0 210.34l72.276 4.534v-33.817L0 184.927zm192.955 161.353l135.282-143.114V56.572c0-10.719-8.689-19.408-19.407-19.408H151.324c-10.718 0-19.407 8.69-19.407 19.408v382.963c0 10.719 8.689 19.408 19.407 19.408h125.547zM80.896 228.686h43.58v-61.441h-43.58zm0-97.362h43.581V69.882h-43.58zm0 194.724h43.58v-61.441h-43.58zM0 112.978l72.276 4.534V83.694L0 87.566zM80.896 423.41h43.58v-61.44h-43.58zM359.603 297.1l-75.427 74.593l75.427 71.777l-31.365 31.366l-99.143-103.143l99.143-105.96zm53.254-31.366L512 371.694l-99.143 103.142l-31.365-31.366l75.426-71.777l-75.426-74.594zm-75.955-37.048h39.065v-61.441h-39.065zm47.023-13.812l72.276-4.534v-25.412l-72.276-3.871z"/></svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Produk Setengah Jadi</span>
                                </div>
                            </a>
                        </li>
                    @endcan
                    {{-- @can('lihat-proses-produksi') --}}
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['produksi-produk-jadi'])){{ 'from-red-500/[0.12] dark:from-red-500/[0.24] to-red-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['produksi-produk-jadi.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('produksi-produk-jadi.index') }}">
                                <div class="flex items-center">
                                    {{-- <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['produksi-produk-jadi'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="22"  height="22" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M17.44 3a1 1 0 0 1 .707.293l2.56 2.56a1 1 0 0 1 0 1.414L18.194 9.78 14.22 5.806l2.513-2.513A1 1 0 0 1 17.44 3Zm-4.634 4.22-9.513 9.513a1 1 0 0 0 0 1.414l2.56 2.56a1 1 0 0 0 1.414 0l9.513-9.513-3.974-3.974ZM6 6a1 1 0 0 1 1 1v1h1a1 1 0 0 1 0 2H7v1a1 1 0 1 1-2 0v-1H4a1 1 0 0 1 0-2h1V7a1 1 0 0 1 1-1Zm9 9a1 1 0 0 1 1 1v1h1a1 1 0 1 1 0 2h-1v1a1 1 0 1 1-2 0v-1h-1a1 1 0 1 1 0-2h1v-1a1 1 0 0 1 1-1Z" clip-rule="evenodd"/>
                                        <path d="M19 13h-2v2h2v-2ZM13 3h-2v2h2V3Zm-2 2H9v2h2V5ZM9 3H7v2h2V3Zm12 8h-2v2h2v-2Zm0 4h-2v2h2v-2Z"/>
                                    </svg> --}}

                                    <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['produksi-produk-jadi'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" width="22"  height="22" viewBox="0 0 48 48"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3"><path d="M7.291 24.18c.204 2.53 2.202 4.39 4.738 4.532C14.663 28.859 18.595 29 24 29s9.337-.14 11.971-.288c2.536-.142 4.534-2.001 4.738-4.532c.158-1.968.291-4.668.291-8.18s-.133-6.211-.291-8.18c-.204-2.53-2.202-4.39-4.738-4.532C33.337 3.141 29.405 3 24 3s-9.337.14-11.971.288C9.493 3.43 7.495 5.29 7.29 7.821C7.133 9.789 7 12.488 7 16s.133 6.212.291 8.18"/><path d="M3.002 37.405c.043 4.493 3.813 7.37 8.305 7.47C14.436 44.944 18.629 45 24 45s9.564-.056 12.693-.125c4.492-.1 8.262-2.977 8.305-7.47a45 45 0 0 0 0-.81c-.043-4.493-3.813-7.37-8.305-7.47A579 579 0 0 0 24 29c-5.37 0-9.564.056-12.693.125c-4.492.1-8.262 2.977-8.305 7.47a42 42 0 0 0 0 .81"/><path d="M15 37a3 3 0 1 1-6 0a3 3 0 1 1 6 0m12 0a3 3 0 1 1-6 0a3 3 0 1 1 6 0m12 0a3 3 0 1 1-6 0a3 3 0 1 1 6 0M28.958 3.046a188 188 0 0 1-.34 7.3a.987.987 0 0 1-1.52.764l-2.83-1.797a.5.5 0 0 0-.537 0l-2.83 1.797a.987.987 0 0 1-1.519-.765c-.11-1.667-.267-4.4-.341-7.3M35 23h-8m8-5h-5"/></g></svg>

                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Produksi Produk Jadi</span>
                                </div>
                            </a>
                        </li>
                    {{-- @endcan --}}
                    {{-- @can('lihat-bahan-setengahjadi') --}}
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['produk-jadi'])){{ 'from-red-500/[0.12] dark:from-red-500/[0.24] to-red-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['produk-jadi.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('produk-jadi.index') }}">
                                <div class="flex items-center">
                                    <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['produk-jadi'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="22"  height="22" viewBox="0 0 2048 2048"><path fill="currentColor" d="m960 120l832 416v1040l-832 415l-832-415V536zm625 456L960 264L719 384l621 314zM960 888l238-118l-622-314l-241 120zM256 680v816l640 320v-816zm768 1136l640-320V680l-640 320z"/></svg>

                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Produk Jadi</span>
                                </div>
                            </a>
                        </li>
                    {{-- @endcan --}}
                    @can('lihat-projek')
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['projeks'])){{ 'from-red-500/[0.12] dark:from-red-500/[0.24] to-red-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['projeks.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('projeks.index') }}">
                                <div class="flex items-center">
                                    <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['projeks'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="22"  height="22" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M20.337 3.664c.213.212.354.486.404.782.294 1.711.657 5.195-.906 6.76-1.77 1.768-8.485 5.517-10.611 6.683a.987.987 0 0 1-1.176-.173l-.882-.88-.877-.884a.988.988 0 0 1-.173-1.177c1.165-2.126 4.913-8.841 6.682-10.611 1.562-1.563 5.046-1.198 6.757-.904.296.05.57.191.782.404ZM5.407 7.576l4-.341-2.69 4.48-2.857-.334a.996.996 0 0 1-.565-1.694l2.112-2.111Zm11.357 7.02-.34 4-2.111 2.113a.996.996 0 0 1-1.69-.565l-.422-2.807 4.563-2.74Zm.84-6.21a1.99 1.99 0 1 1-3.98 0 1.99 1.99 0 0 1 3.98 0Z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Proyek</span>
                                </div>
                            </a>
                        </li>
                    @endcan
                    @can('lihat-produk-sample')
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['produk-sample'])){{ 'from-red-500/[0.12] dark:from-red-500/[0.24] to-red-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['produk-sample.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('produk-sample.index') }}">
                                <div class="flex items-center">
                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="22"  height="22"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="shrink-0 fill-current icon icon-tabler icons-tabler-outline icon-tabler-bookmark-plus @if(in_array(Request::segment(1), ['produk-sample'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 17l-6 4v-14a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v5" /><path d="M16 19h6" /><path d="M19 16v6" /></svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Produk Sample</span>
                                </div>
                            </a>
                        </li>
                    @endcan
                    @can('lihat-garansi-projek')
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['garansi-projeks'])){{ 'from-red-500/[0.12] dark:from-red-500/[0.24] to-red-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['garansi-projeks.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('garansi-projeks.index') }}">
                                <div class="flex items-center">
                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="22"  height="22"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="shrink-0 icon icon-tabler icons-tabler-outline icon-tabler-shield-cog @if(in_array(Request::segment(1), ['garansi-projeks'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 21a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3a12 12 0 0 0 8.5 3c.568 1.933 .635 3.957 .223 5.89" /><path d="M19.001 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M19.001 15.5v1.5" /><path d="M19.001 21v1.5" /><path d="M22.032 17.25l-1.299 .75" /><path d="M17.27 20l-1.3 .75" /><path d="M15.97 17.25l1.3 .75" /><path d="M20.733 20l1.3 .75" /></svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Garansi Proyek</span>
                                </div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </div>
            <!-- RnD -->
            @role('superadmin|produksi|purchasing|purchasing level 3|rnd|rnd level 3|administrasi|admin|direksi|marketing manager|administration manager|hardware manager|software manager')
            <div>
                <h3 class="text-xs uppercase text-gray-400 dark:text-gray-500 font-semibold pl-3">
                    <span class="hidden lg:block lg:sidebar-expanded:hidden 2xl:hidden text-center w-6" aria-hidden="true">•••</span>
                    <span class="lg:hidden lg:sidebar-expanded:block 2xl:block">RnD</span>
                </h3>
                <ul class="mt-3">
                    @can('lihat-projek-rnd')
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['projek-rnd'])){{ 'from-red-500/[0.12] dark:from-red-500/[0.24] to-red-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['projek-rnd.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('projek-rnd.index') }}">
                                <div class="flex items-center">
                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="22"  height="22"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="shrink-0 icon icon-tabler icons-tabler-outline icon-tabler-settings-search @if(in_array(Request::segment(1), ['projek-rnd'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M11.646 20.965a1.67 1.67 0 0 1 -1.321 -1.282a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c.728 .177 1.154 .71 1.279 1.303" /><path d="M14.985 11.694a3 3 0 1 0 -3.29 3.29" /><path d="M18 18m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M20.2 20.2l1.8 1.8" /></svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Proyek RnD</span>
                                </div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </div>
            @endrole
            <div>
                @if(Gate::allows('lihat-laporan'))
                    <h3 class="text-xs uppercase text-gray-400 dark:text-gray-500 font-semibold pl-3">
                        <span class="hidden lg:block lg:sidebar-expanded:hidden 2xl:hidden text-center w-6" aria-hidden="true">•••</span>
                        <span class="lg:hidden lg:sidebar-expanded:block 2xl:block">Laporan</span>
                    </h3>
                @endif
                <ul class="mt-3">
                    @can('lihat-laporan-proyek')
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['laporan-proyek'])){{ 'from-red-500/[0.12] dark:from-red-500/[0.24] to-red-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['laporan-proyek.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('laporan-proyek.index') }}">
                                <div class="flex items-center">
                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="22"  height="22"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="shrink-0 icon icon-tabler icons-tabler-outline icon-tabler-book-2 @if(in_array(Request::segment(1), ['laporan-proyek'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19 4v16h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12z" /><path d="M19 16h-12a2 2 0 0 0 -2 2" /><path d="M9 8h6" /></svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Laporan Proyek</span>
                                </div>
                            </a>
                        </li>
                    @endcan
                    @can('lihat-laporan-garansi-proyek')
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['laporan-garansi-proyek'])){{ 'from-red-500/[0.12] dark:from-red-500/[0.24] to-red-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['laporan-garansi-proyek.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('laporan-garansi-proyek.index') }}">
                                <div class="flex items-center">
                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="22"  height="22"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="shrink-0 icon icon-tabler icons-tabler-outline icon-tabler-book-2 @if(in_array(Request::segment(1), ['laporan-garansi-proyek'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19 4v16h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12z" /><path d="M19 16h-12a2 2 0 0 0 -2 2" /><path d="M9 8h6" /></svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Laporan Garansi Proyek</span>
                                </div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </div>
            <!-- Informasi Akun -->
            @role('superadmin')
            <div>
                <h3 class="text-xs uppercase text-gray-400 dark:text-gray-500 font-semibold pl-3">
                    <span class="hidden lg:block lg:sidebar-expanded:hidden 2xl:hidden text-center w-6" aria-hidden="true">•••</span>
                    <span class="lg:hidden lg:sidebar-expanded:block 2xl:block">Informasi Akun</span>
                </h3>
                <ul class="mt-3">
                    <!-- Manajemen User -->
                    <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['manajemen-user'])){{ 'from-red-500/[0.12] dark:from-red-500/[0.24] to-red-500/[0.04]' }}@endif" x-data="{ open: {{ in_array(Request::segment(1), ['manajemen-user']) ? 1 : 0 }} }">
                        <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['manajemen-user'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="#0" @click.prevent="open = !open; sidebarExpanded = true">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg width="22"  height="22"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="shrink-0 icon icon-tabler icons-tabler-outline icon-tabler-clock-search @if(in_array(Request::segment(1), ['manajemen-user'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif">
                                        <path fill-rule="evenodd"  d="M17 10v1.126c.367.095.714.24 1.032.428l.796-.797 1.415 1.415-.797.796c.188.318.333.665.428 1.032H21v2h-1.126c-.095.367-.24.714-.428 1.032l.797.796-1.415 1.415-.796-.797a3.979 3.979 0 0 1-1.032.428V20h-2v-1.126a3.977 3.977 0 0 1-1.032-.428l-.796.797-1.415-1.415.797-.796A3.975 3.975 0 0 1 12.126 16H11v-2h1.126c.095-.367.24-.714.428-1.032l-.797-.796 1.415-1.415.796.797A3.977 3.977 0 0 1 15 11.126V10h2Zm.406 3.578.016.016c.354.358.574.85.578 1.392v.028a2 2 0 0 1-3.409 1.406l-.01-.012a2 2 0 0 1 2.826-2.83ZM5 8a4 4 0 1 1 7.938.703 7.029 7.029 0 0 0-3.235 3.235A4 4 0 0 1 5 8Zm4.29 5H7a4 4 0 0 0-4 4v1a2 2 0 0 0 2 2h6.101A6.979 6.979 0 0 1 9 15c0-.695.101-1.366.29-2Z" clip-rule="evenodd"/>
                                        </svg>

                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Manajemen Pengguna</span>
                                </div>

                                <!-- Icon -->
                                <div class="flex shrink-0 ml-2 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">
                                    <svg class="w-3 h-3 shrink-0 ml-1 fill-current text-gray-400 dark:text-gray-500 @if(in_array(Request::segment(1), ['manajemen-user'])){{ 'rotate-180' }}@endif" :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                                        <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                        <div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
                            <ul class="pl-8 mt-1 @if(!in_array(Request::segment(1), ['manajemen-user'])){{ 'hidden' }}@endif" :class="open ? '!block' : 'hidden'">
                                {{-- @can('lihat-log-activity')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('log-activities.index')){{ '!text-[#B40404]' }}@endif" href="{{ route('log-activities.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Log Activity</span>
                                        </a>
                                    </li>
                                @endcan --}}
                                @can('lihat-permission')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('permissions.index')){{ '!text-[#B40404]' }}@endif" href="{{ route('permissions.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">User Permission</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('lihat-role')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('roles.index')){{ '!text-[#B40404]' }}@endif" href="{{ route('roles.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">User Role</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('lihat-user')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('users.index')){{ '!text-[#B40404]' }}@endif" href="{{ route('users.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">User</span>
                                        </a>
                                    </li>
                                @endcan
                                <li class="mb-1 last:mb-0">
                                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('organization.index')){{ '!text-[#B40404]' }}@endif" href="{{ route('organization.index') }}">
                                        <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Organization</span>
                                    </a>
                                </li>
                                <li class="mb-1 last:mb-0">
                                    <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('job-position.index')){{ '!text-[#B40404]' }}@endif" href="{{ route('job-position.index') }}">
                                        <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Job Position</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                </ul>
                <ul class="mt-3">
                    @can('lihat-log-activity')
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['log-activities'])){{ 'from-red-500/[0.12] dark:from-red-500/[0.24] to-red-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['log-activities.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('log-activities.index') }}">
                                <div class="flex items-center">
                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="22"  height="22"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-report-search @if(in_array(Request::segment(1), ['log-activities'])){{ 'text-[#B40404]' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h5.697" /><path d="M18 12v-5a2 2 0 0 0 -2 -2h-2" /><path d="M8 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" /><path d="M8 11h4" /><path d="M8 15h3" /><path d="M16.5 17.5m-2.5 0a2.5 2.5 0 1 0 5 0a2.5 2.5 0 1 0 -5 0" /><path d="M18.5 19.5l2.5 2.5" /></svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Log Activity</span>
                                </div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </div>
            @endrole
        </div>

        <!-- Expand / collapse button -->
        <div class="pt-3 hidden lg:inline-flex 2xl:hidden justify-end mt-auto">
            <div class="w-12 pl-4 pr-3 py-2">
                <button class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400 transition-colors" @click="sidebarExpanded = !sidebarExpanded">
                    <span class="sr-only">Expand / collapse sidebar</span>
                    <svg class="shrink-0 fill-current text-gray-400 dark:text-gray-500 sidebar-expanded:rotate-180" xmlns="http://www.w3.org/2000/svg" width="22"  height="22" viewBox="0 0 16 16">
                        <path d="M15 16a1 1 0 0 1-1-1V1a1 1 0 1 1 2 0v14a1 1 0 0 1-1 1ZM8.586 7H1a1 1 0 1 0 0 2h7.586l-2.793 2.793a1 1 0 1 0 1.414 1.414l4.5-4.5A.997.997 0 0 0 12 8.01M11.924 7.617a.997.997 0 0 0-.217-.324l-4.5-4.5a1 1 0 0 0-1.414 1.414L8.586 7M12 7.99a.996.996 0 0 0-.076-.373Z" />
                    </svg>
                </button>
            </div>
        </div>

    </div>
</div>
