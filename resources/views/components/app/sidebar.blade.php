<div class="min-w-fit">
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
                <svg class="fill-violet-500" xmlns="http://www.w3.org/2000/svg" width="32" height="32">
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
                    <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['dashboard'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif">
                        <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['dashboard'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('dashboard') }}">
                            <div class="flex items-center">
                                <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['dashboard'])){{ 'text-violet-500' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
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
                    <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['datamaster'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif" x-data="{ open: {{ in_array(Request::segment(1), ['datamaster']) ? 1 : 0 }} }">
                        <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['datamaster'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="#0" @click.prevent="open = !open; sidebarExpanded = true">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['datamaster'])){{ 'text-violet-500' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
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
                                @can('lihat-bahan')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('bahan.index')){{ '!text-violet-500' }}@endif" href="{{ route('bahan.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Bahan</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('lihat-jenis-bahan')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('jenis-bahan.index')){{ '!text-violet-500' }}@endif" href="{{ route('jenis-bahan.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Jenis Bahan</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('lihat-unit')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('unit.index')){{ '!text-violet-500' }}@endif" href="{{ route('unit.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Unit</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('lihat-supplier')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('supplier.index')){{ '!text-violet-500' }}@endif" href="{{ route('supplier.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Supplier</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('lihat-produk-produksi')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('produk-produksis.index')){{ '!text-violet-500' }}@endif" href="{{ route('produk-produksis.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Produk Produksi</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                    <!-- Transaksi -->
                    @role('superadmin|purchasing|administrasi|admin|direksi|marketing manager|administration manager|hardware manager|software manager')
                    <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['transaksi'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif" x-data="{ open: {{ in_array(Request::segment(1), ['transaksi']) ? 1 : 0 }} }">
                        <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['transaksi'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="#0" @click.prevent="open = !open; sidebarExpanded = true">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['transaksi'])){{ 'text-violet-500' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" aria-hidden="true"  xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24">
                                        <path d="M12.268 6A2 2 0 0 0 14 9h1v1a2 2 0 0 0 3.04 1.708l-.311 1.496a1 1 0 0 1-.979.796H8.605l.208 1H16a3 3 0 1 1-2.83 2h-2.34a3 3 0 1 1-4.009-1.76L4.686 5H4a1 1 0 0 1 0-2h1.5a1 1 0 0 1 .979.796L6.939 6h5.329Z"/>
                                        <path d="M18 4a1 1 0 1 0-2 0v2h-2a1 1 0 1 0 0 2h2v2a1 1 0 1 0 2 0V8h2a1 1 0 1 0 0-2h-2V4Z"/>
                                    </svg>

                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Transaksi
                                        @if($jumlahBahanKeluar > 0)
                                            <span class="inline-flex items-center justify-center w-4 h-4 ms-2 text-xs font-semibold text-indigo-800  rounded-full" style="background-color: rgb(199, 210, 254)">
                                                {{ $jumlahBahanKeluar }}
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
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('purchases.index')){{ '!text-violet-500' }}@endif" href="{{ route('purchases.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Bahan Masuk</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('lihat-bahan-keluar')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('bahan-keluars.index')){{ '!text-violet-500' }}@endif" href="{{ route('bahan-keluars.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Bahan Keluar
                                                @if($jumlahBahanKeluar > 0)
                                                    <span class="inline-flex items-center justify-center w-4 h-4 ms-2 text-xs font-semibold text-indigo-800 bg-indigo-200 rounded-full" style="background-color: rgb(199, 210, 254)">
                                                        {{ $jumlahBahanKeluar }}
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
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['bahan-rusaks'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['bahan-rusaks.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('bahan-rusaks.index') }}">
                                <div class="flex items-center">
                                    <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['bahan-rusaks'])){{ 'text-violet-500' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
                                        <path d="M18 17h-.09c.058-.33.088-.665.09-1v-1h1a1 1 0 0 0 0-2h-1.09a5.97 5.97 0 0 0-.26-1H17a2 2 0 0 0 2-2V8a1 1 0 1 0-2 0v2h-.54a6.239 6.239 0 0 0-.46-.46V8a3.963 3.963 0 0 0-.986-2.6l.693-.693A1 1 0 0 0 16 4V3a1 1 0 1 0-2 0v.586l-.661.661a3.753 3.753 0 0 0-2.678 0L10 3.586V3a1 1 0 1 0-2 0v1a1 1 0 0 0 .293.707l.693.693A3.963 3.963 0 0 0 8 8v1.54a6.239 6.239 0 0 0-.46.46H7V8a1 1 0 0 0-2 0v2a2 2 0 0 0 2 2h-.65a5.97 5.97 0 0 0-.26 1H5a1 1 0 0 0 0 2h1v1a6 6 0 0 0 .09 1H6a2 2 0 0 0-2 2v2a1 1 0 1 0 2 0v-2h.812A6.012 6.012 0 0 0 11 21.907V12a1 1 0 0 1 2 0v9.907A6.011 6.011 0 0 0 17.188 19H18v2a1 1 0 0 0 2 0v-2a2 2 0 0 0-2-2Zm-4-8.65a5.922 5.922 0 0 0-.941-.251l-.111-.017a5.52 5.52 0 0 0-1.9 0l-.111.017A5.925 5.925 0 0 0 10 8.35V8a2 2 0 1 1 4 0v.35Z"/>
                                    </svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Bahan Rusak
                                        @if($jumlahBahanRusak > 0)
                                            <span class="inline-flex items-center justify-center w-4 h-4 ms-2 text-xs font-semibold text-indigo-800 bg-indigo-200 rounded-full" style="background-color: rgb(199, 210, 254)">
                                                {{ $jumlahBahanRusak }}
                                            </span>
                                        @endif
                                    </span>
                                </div>
                            </a>
                        </li>
                    @endcan
                    @can('lihat-bahan-retur')
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['bahan-returs'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['bahan-returs.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('bahan-returs.index') }}">
                                <div class="flex items-center">
                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="16" height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler shrink-0 icons-tabler-outline icon-tabler-refresh @if(in_array(Request::segment(1), ['bahan-returs'])){{ 'text-violet-500' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>

                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Bahan Retur
                                        @if($jumlahBahanRetur > 0)
                                            <span class="inline-flex items-center justify-center w-4 h-4 ms-2 text-xs font-semibold text-indigo-800 bg-indigo-200 rounded-full" style="background-color: rgb(199, 210, 254)">
                                                {{ $jumlahBahanRetur }}
                                            </span>
                                        @endif
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
                    <span class="lg:hidden lg:sidebar-expanded:block 2xl:block">Pengajuan</span>
                </h3>
                <ul class="mt-3">
                    @can('lihat-pengajuan')
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['pengajuans'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['pengajuans.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('pengajuans.index') }}">
                                <div class="flex items-center">
                                    <svg  class="shrink-0 fill-current @if(in_array(Request::segment(1), ['pengajuans'])){{ 'text-violet-500' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-list-check">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3.5 5.5l1.5 1.5l2.5 -2.5" /><path d="M3.5 11.5l1.5 1.5l2.5 -2.5" />
                                        <path d="M3.5 17.5l1.5 1.5l2.5 -2.5" /><path d="M11 6l9 0" /><path d="M11 12l9 0" /><path d="M11 18l9 0" />
                                    </svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Pengajaun Bahan Lokal</span>
                                </div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </div>
            <!-- Produksi -->
            @role('superadmin|produksi|purchasing|administrasi|admin|direksi|marketing manager|administration manager|hardware manager|software manager')
            <div>
                <h3 class="text-xs uppercase text-gray-400 dark:text-gray-500 font-semibold pl-3">
                    <span class="hidden lg:block lg:sidebar-expanded:hidden 2xl:hidden text-center w-6" aria-hidden="true">•••</span>
                    <span class="lg:hidden lg:sidebar-expanded:block 2xl:block">Produksi</span>
                </h3>
                <ul class="mt-3">
                    @can('lihat-proses-produksi')
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['produksis'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['produksis.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('produksis.index') }}">
                                <div class="flex items-center">
                                    <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['produksis'])){{ 'text-violet-500' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M17.44 3a1 1 0 0 1 .707.293l2.56 2.56a1 1 0 0 1 0 1.414L18.194 9.78 14.22 5.806l2.513-2.513A1 1 0 0 1 17.44 3Zm-4.634 4.22-9.513 9.513a1 1 0 0 0 0 1.414l2.56 2.56a1 1 0 0 0 1.414 0l9.513-9.513-3.974-3.974ZM6 6a1 1 0 0 1 1 1v1h1a1 1 0 0 1 0 2H7v1a1 1 0 1 1-2 0v-1H4a1 1 0 0 1 0-2h1V7a1 1 0 0 1 1-1Zm9 9a1 1 0 0 1 1 1v1h1a1 1 0 1 1 0 2h-1v1a1 1 0 1 1-2 0v-1h-1a1 1 0 1 1 0-2h1v-1a1 1 0 0 1 1-1Z" clip-rule="evenodd"/>
                                        <path d="M19 13h-2v2h2v-2ZM13 3h-2v2h2V3Zm-2 2H9v2h2V5ZM9 3H7v2h2V3Zm12 8h-2v2h2v-2Zm0 4h-2v2h2v-2Z"/>
                                    </svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Proses Produksi</span>
                                </div>
                            </a>
                        </li>
                    @endcan
                    <!-- Bahan Setengah Jadi -->
                    @can('lihat-bahan-setengahjadi')
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['bahan-setengahjadis'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['bahan-setengahjadis.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('bahan-setengahjadis.index') }}">
                                <div class="flex items-center">
                                    <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['bahan-setengahjadis'])){{ 'text-violet-500' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M5.005 10.19a1 1 0 0 1 1 1v.233l5.998 3.464L18 11.423v-.232a1 1 0 1 1 2 0V12a1 1 0 0 1-.5.866l-6.997 4.042a1 1 0 0 1-1 0l-6.998-4.042a1 1 0 0 1-.5-.866v-.81a1 1 0 0 1 1-1ZM5 15.15a1 1 0 0 1 1 1v.232l5.997 3.464 5.998-3.464v-.232a1 1 0 1 1 2 0v.81a1 1 0 0 1-.5.865l-6.998 4.042a1 1 0 0 1-1 0L4.5 17.824a1 1 0 0 1-.5-.866v-.81a1 1 0 0 1 1-1Z" clip-rule="evenodd"/>
                                        <path d="M12.503 2.134a1 1 0 0 0-1 0L4.501 6.17A1 1 0 0 0 4.5 7.902l7.002 4.047a1 1 0 0 0 1 0l6.998-4.04a1 1 0 0 0 0-1.732l-6.997-4.042Z"/>
                                    </svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Produk Setengah Jadi</span>
                                </div>
                            </a>
                        </li>
                    @endcan
                    @can('lihat-projek')
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['projeks'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['projeks.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('projeks.index') }}">
                                <div class="flex items-center">
                                    <svg class="shrink-0 fill-current @if(in_array(Request::segment(1), ['projeks'])){{ 'text-violet-500' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" d="M20.337 3.664c.213.212.354.486.404.782.294 1.711.657 5.195-.906 6.76-1.77 1.768-8.485 5.517-10.611 6.683a.987.987 0 0 1-1.176-.173l-.882-.88-.877-.884a.988.988 0 0 1-.173-1.177c1.165-2.126 4.913-8.841 6.682-10.611 1.562-1.563 5.046-1.198 6.757-.904.296.05.57.191.782.404ZM5.407 7.576l4-.341-2.69 4.48-2.857-.334a.996.996 0 0 1-.565-1.694l2.112-2.111Zm11.357 7.02-.34 4-2.111 2.113a.996.996 0 0 1-1.69-.565l-.422-2.807 4.563-2.74Zm.84-6.21a1.99 1.99 0 1 1-3.98 0 1.99 1.99 0 0 1 3.98 0Z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Projek</span>
                                </div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </div>
            @endrole
            <!-- RnD -->
            @role('superadmin|rnd|administrasi|admin|direksi|marketing manager|administration manager|hardware manager|software manager')
            <div>
                <h3 class="text-xs uppercase text-gray-400 dark:text-gray-500 font-semibold pl-3">
                    <span class="hidden lg:block lg:sidebar-expanded:hidden 2xl:hidden text-center w-6" aria-hidden="true">•••</span>
                    <span class="lg:hidden lg:sidebar-expanded:block 2xl:block">RnD</span>
                </h3>
                <ul class="mt-3">
                    @can('lihat-projek-rnd')
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['projek-rnd'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['projek-rnd.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('projek-rnd.index') }}">
                                <div class="flex items-center">
                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="shrink-0 icon icon-tabler icons-tabler-outline icon-tabler-settings-search @if(in_array(Request::segment(1), ['projek-rnd'])){{ 'text-violet-500' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M11.646 20.965a1.67 1.67 0 0 1 -1.321 -1.282a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c.728 .177 1.154 .71 1.279 1.303" /><path d="M14.985 11.694a3 3 0 1 0 -3.29 3.29" /><path d="M18 18m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M20.2 20.2l1.8 1.8" /></svg>
                                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Projek RnD</span>
                                </div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </div>
            @endrole
            <!-- Informasi Akun -->
            @role('superadmin')
            <div>
                <h3 class="text-xs uppercase text-gray-400 dark:text-gray-500 font-semibold pl-3">
                    <span class="hidden lg:block lg:sidebar-expanded:hidden 2xl:hidden text-center w-6" aria-hidden="true">•••</span>
                    <span class="lg:hidden lg:sidebar-expanded:block 2xl:block">Informasi Akun</span>
                </h3>
                <ul class="mt-3">
                    <!-- Manajemen User -->
                    <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['manajemen-user'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif" x-data="{ open: {{ in_array(Request::segment(1), ['manajemen-user']) ? 1 : 0 }} }">
                        <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['manajemen-user'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="#0" @click.prevent="open = !open; sidebarExpanded = true">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="shrink-0 icon icon-tabler icons-tabler-outline icon-tabler-clock-search @if(in_array(Request::segment(1), ['manajemen-user'])){{ 'text-violet-500' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif">
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
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('log-activities.index')){{ '!text-violet-500' }}@endif" href="{{ route('log-activities.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Log Activity</span>
                                        </a>
                                    </li>
                                @endcan --}}
                                @can('lihat-permission')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('permissions.index')){{ '!text-violet-500' }}@endif" href="{{ route('permissions.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">User Permission</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('lihat-role')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('roles.index')){{ '!text-violet-500' }}@endif" href="{{ route('roles.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">User Role</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('lihat-user')
                                    <li class="mb-1 last:mb-0">
                                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate @if(Route::is('users.index')){{ '!text-violet-500' }}@endif" href="{{ route('users.index') }}">
                                            <span class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">User</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    </li>
                </ul>
                <ul class="mt-3">
                    @can('lihat-log-activity')
                        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-[linear-gradient(135deg,var(--tw-gradient-stops))] @if(in_array(Request::segment(1), ['log-activities'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif">
                            <a class="block text-gray-800 dark:text-gray-100 truncate transition @if(!in_array(Request::segment(1), ['log-activities.index'])){{ 'hover:text-gray-900 dark:hover:text-white' }}@endif" href="{{ route('log-activities.index') }}">
                                <div class="flex items-center">
                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-report-search @if(in_array(Request::segment(1), ['log-activities'])){{ 'text-violet-500' }}@else{{ 'text-gray-400 dark:text-gray-500' }}@endif"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h5.697" /><path d="M18 12v-5a2 2 0 0 0 -2 -2h-2" /><path d="M8 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" /><path d="M8 11h4" /><path d="M8 15h3" /><path d="M16.5 17.5m-2.5 0a2.5 2.5 0 1 0 5 0a2.5 2.5 0 1 0 -5 0" /><path d="M18.5 19.5l2.5 2.5" /></svg>
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
                    <svg class="shrink-0 fill-current text-gray-400 dark:text-gray-500 sidebar-expanded:rotate-180" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                        <path d="M15 16a1 1 0 0 1-1-1V1a1 1 0 1 1 2 0v14a1 1 0 0 1-1 1ZM8.586 7H1a1 1 0 1 0 0 2h7.586l-2.793 2.793a1 1 0 1 0 1.414 1.414l4.5-4.5A.997.997 0 0 0 12 8.01M11.924 7.617a.997.997 0 0 0-.217-.324l-4.5-4.5a1 1 0 0 0-1.414 1.414L8.586 7M12 7.99a.996.996 0 0 0-.076-.373Z" />
                    </svg>
                </button>
            </div>
        </div>

    </div>
</div>
