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
        <a href="side-menu-inbox.html" class="side-menu">
            <div class="side-menu__icon"> <i data-feather="inbox"></i> </div>
            <div class="side-menu__title"> Inbox </div>
        </a>
    </li>
</ul>

</nav>
