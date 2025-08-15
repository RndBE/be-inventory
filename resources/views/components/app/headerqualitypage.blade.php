<div class="top-bar">
    <!-- BEGIN: Breadcrumb -->
    <div class="-intro-x breadcrumb mr-auto hidden sm:flex">
        @if (request()->segment(1))
            <a href="{{ url(request()->segment(1)) }}">
                {{ ucwords(str_replace('-', ' ', request()->segment(1))) }}
            </a>
        @endif

        @if (request()->segment(2))
            <i data-feather="chevron-right" class="breadcrumb__icon"></i>
            <a href="{{ url(request()->segment(1) . '/' . request()->segment(2)) }}"
            class="{{ request()->segment(3) ? '' : 'breadcrumb--active' }}">
                {{ ucwords(str_replace('-', ' ', request()->segment(2))) }}
            </a>
        @endif

        @if (request()->segment(3))
            <i data-feather="chevron-right" class="breadcrumb__icon"></i>
            <span class="breadcrumb--active">
                {{ ucwords(str_replace('-', ' ', request()->segment(3))) }}
            </span>
        @endif
    </div>
    <!-- END: Breadcrumb -->

    <!-- BEGIN: Account Menu -->
    <div class="intro-x dropdown w-8 h-8 relative">
        <div class="dropdown-toggle w-8 h-8 rounded-full overflow-hidden shadow-lg image-fit zoom-in">
            <img alt="Midone Tailwind HTML Admin Template" src="{{ asset('dist/images/profile-12.jpg')}}">
        </div>
        <div class="dropdown-box mt-10 absolute w-56 top-0 right-0 z-20">
            <div class="dropdown-box__content box bg-theme-38 text-white">
                <div class="p-4 border-b border-theme-40">
                    <div class="font-medium">{{ Auth::user()->name }}</div>
                    <div class="text-xs text-theme-41">{{ implode(', ', Auth::user()->getRoleNames()->toArray()) }}</div>
                </div>
                <div class="p-2">
                    <a href="" class="flex items-center block p-2 transition duration-300 ease-in-out hover:bg-theme-1 rounded-md"> <i data-feather="user" class="w-4 h-4 mr-2"></i> Profile </a>
                </div>
                <div class="p-2 border-t border-theme-40">
                    <form method="POST" action="{{ route('logout') }}" x-data>
                        @csrf
                        <button type="submit"
                            class="flex items-center w-full p-2 transition duration-300 ease-in-out hover:bg-theme-1 rounded-md">
                            <i data-feather="toggle-right" class="w-4 h-4 mr-2"></i>
                            {{ __('Logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- END: Account Menu -->
</div>

