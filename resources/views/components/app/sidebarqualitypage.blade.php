<nav class="side-nav">
    <a href="" class="intro-x flex items-center pl-5 pt-4">
        <img alt="Quality Qontroll Management" class="w-6" src="{{ asset('dist/images/logo.svg')}}">
        <span class="hidden xl:block text-white text-lg ml-3"> Quality Control<span class="font-medium"> Management</span> </span>
    </a>
    <div class="side-nav__devider my-6"></div>
    <ul>
    <li>
        <a href="{{ route('quality-page.index') }}"
            class="side-menu {{ request()->routeIs('quality-page.index') ? 'side-menu--active' : '' }}">
            <div class="side-menu__icon"> <i data-feather="home"></i> </div>
            <div class="side-menu__title"> Dashboard </div>
        </a>
    </li>

    <li>
        {{-- Parent menu: aktif jika salah satu submenu aktif --}}
        @php
            $isGudangActive = request()->routeIs('quality-page.qc-bahan-masuk.*');
        @endphp
        <a href="javascript:;" class="side-menu {{ $isGudangActive ? 'side-menu--active' : '' }}">
            <div class="side-menu__icon"> <i data-feather="box"></i> </div>
            <div class="side-menu__title">
                Gudang
                <i data-feather="chevron-down" class="side-menu__sub-icon"></i>
            </div>
        </a>
        <ul class="{{ $isGudangActive ? 'side-menu__sub-open' : '' }}">
            <li>
                <a href="{{ route('quality-page.qc-bahan-masuk.index') }}"
                    class="side-menu {{ request()->routeIs('quality-page.qc-bahan-masuk.index') ? 'side-menu--active' : '' }}">
                    <div class="side-menu__icon"> <i data-feather="activity"></i> </div>
                    <div class="side-menu__title"> QC Bahan Masuk </div>
                </a>
            </li>
        </ul>
    </li>

    <li>
        {{-- Parent menu: aktif jika salah satu submenu aktif --}}
        @php
            $isProduksiActive = request()->routeIs('quality-page.qc-produk-setengah-jadi.*');
        @endphp
        <a href="javascript:;" class="side-menu {{ $isProduksiActive ? 'side-menu--active' : '' }}">
            <div class="side-menu__icon"> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 14 14">
                    <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1">
                        <path d="M3.808 5.086C4.94 4.08 5.642 3.74 7 3.49c1.358.251 2.06.59 3.192 1.596L7 6.682z" />
                        <path d="M3.808 5.086c-.393 1.08-.397 2.368-.085 3.453a.93.93 0 0 0 .3.454c.988.824 1.664 1.127 2.766 1.368a1 1 0 0 0 .422 0c1.102-.241 1.778-.544 2.765-1.368a.93.93 0 0 0 .301-.454c.312-1.085.308-2.373-.085-3.453L7 6.682zM7 6.674v3.731" />
                        <path d="M.75 7A6.25 6.25 0 0 1 12.5 4.029c.076-1.102.053-1.732-.1-2.875M13.25 7a6.25 6.25 0 0 1-11.734 3c-.076 1.102-.054 1.732.1 2.875" />
                    </g>
                </svg>
            </div>
            <div class="side-menu__title">
                Produksi
                <i data-feather="chevron-down" class="side-menu__sub-icon"></i>
            </div>
        </a>
        <ul class="{{ $isProduksiActive ? 'side-menu__sub-open' : '' }}">
            <li>
                <a href="{{ route('quality-page.qc-produk-setengah-jadi.index') }}"
                    class="side-menu {{ request()->routeIs('quality-page.qc-produk-setengah-jadi.index') ? 'side-menu--active' : '' }}">
                    <div class="side-menu__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 512 512">
                            <path fill="currentColor" d="M372.5 33.27c-24.9.2-51.8 13.41-70.6 46.03l-.2.4l14.4 8.3l.2-.4c16.2-27.8 39.1-38.9 60.2-37.6c30.6 1.9 56.5 29.9 47.6 66.4c-2 8.4-5.9 17.3-11.8 26.4c-33 50.5-73 84.1-103.3 116.7c-32.3 34.8-53.8 68.7-47.4 117.9C268.1 428 317 458 371.4 461c39.3 3 81-8 110.1-33v-23c-25.3 30-68.7 43-108.9 40c-46.1-3-89-27-94.5-69.7c-5.6-43.8 14.4-73.5 43.1-104.4c30.8-33.2 71.5-67.6 105-119c7.1-10.9 11.7-21.5 14.1-31.6c11.7-47.8-22.5-84.4-62.8-86.9c-1.6-.1-3.3-.14-5-.13M84.26 41.44C-6.511 138.9 158.5 160.1 75.56 268.1c-62.2 80.9-10.68 102.6-.96 195.1c0 0 .32-1.7.86-4.4c4.77-23.9 1.58-48.8-9.12-70.8c-26.01-53.4-5.18-74.8 56.26-143.4c71.9-80.4-58.81-126.2-38.34-203.16M287.3 90.3s-17.6 29.9-38.9 62.9c-13.8 21.4-30.8 42.9-41.4 61.4c-4.9 8.5-8.7 16-11.3 21.8l-10-5.8l-9.3 16l57.8 33.4l9.2-16l-10-5.8c3.7-5.2 8.3-12.3 13.2-20.7c10.7-18.4 20.9-43.9 32.5-66.6c17.9-35 35-65.1 35-65.1zM180.5 264.5l-5.4 9.4l36.1 20.8l5.4-9.4zm-4.6 24.7l-55 95.2l21.7 12.5l54.9-95.2zm-60.4 107.3l-3.7 12.2l14.8 8.6l8.8-9.3zm-7.8 23.4l-15.53 26.9l-3.11 17.9L103 453l15.5-26.8z" />
                        </svg>
                    </div>
                    <div class="side-menu__title"> QC Produk Setengah Jadi</div>
                </a>
            </li>
        </ul>
    </li>
</ul>

</nav>
