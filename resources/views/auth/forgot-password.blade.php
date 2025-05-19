<x-authentication-layout>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <section class="bg-gray-100 dark:bg-gray-900 min-h-screen flex items-center justify-center px-6 py-8">
        <div class="w-full max-w-md bg-white rounded-lg shadow-md dark:bg-gray-800 dark:border dark:border-gray-700 p-8">

            <!-- Logo Centered -->
            <div class="flex justify-center mb-6">
                <a href="{{ route('login') }}">
                    <img class="w-28 h-auto" src="{{ asset('images/logo_be2.png') }}" alt="logo">
                </a>
            </div>

            <h1 class="text-3xl font-bold text-[#2E2E4D] dark:text-[#D2D2DB] mb-6 text-center">
                {{ __('Reset your Password') }}
            </h1>

            <!-- Validation Errors -->
            <x-validation-errors class="mb-4" />

            <!-- Form -->
            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="mb-6">
                    <x-label for="email" class="block mb-2 text-sm font-medium text-[#2E2E4D] dark:text-[#D2D2DB]">
                        {{ __('Email Address') }} <span class="text-[#B40404]">*</span>
                    </x-label>
                    <x-input
                        id="email"
                        type="email"
                        name="email"
                        :value="old('email')"
                        required
                        autofocus
                        class="w-full px-4 py-2 border border-[#D2D2DB] rounded-md focus:outline-none focus:ring-2 focus:ring-[#B40404] dark:bg-[#2E2E4D] dark:border-[#D2D2DB] dark:text-[#FFFFFF]"
                    />
                </div>

                <div class="flex flex-col space-y-4">
                    <x-button class="w-full bg-[#B40404] hover:bg-[#2E2E4D] text-[#FFFFFF]">
                        {{ __('Send Reset Link') }}
                    </x-button>

                    <a href="{{ route('login') }}" class="text-sm text-center text-[#2E2E4D] hover:underline hover:text-[#2E2E4D]">
                        {{ __('Back to login') }}
                    </a>
                </div>
            </form>
        </div>
    </section>

    @if (session('status'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: @json(session('status')),
                background: '#2E2E4D',  // biru gelap
                color: '#FFFFFF',       // putih
                confirmButtonColor: '#B40404', // merah gelap
                confirmButtonText: 'OK'
            });
        </script>
    @endif

</x-authentication-layout>
