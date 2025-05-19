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

            <form method="POST" action="{{ route('password.update') }}">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="mb-4">
                    <x-label
                        for="email"
                        class="block mb-2 text-sm font-medium text-[#2E2E4D] dark:text-[#D2D2DB]"
                        :value="__('Email')" />
                    <x-input
                        id="email"
                        type="email"
                        name="email"
                        :value="old('email', $request->email)"
                        required
                        autofocus
                        class="w-full px-4 py-2 border border-[#D2D2DB] rounded-md focus:outline-none focus:ring-2 focus:ring-[#B40404] dark:bg-[#2E2E4D] dark:border-[#D2D2DB] dark:text-[#FFFFFF]"
                    />
                </div>

                <div class="mb-4">
                    <x-label
                        for="password"
                        class="block mb-2 text-sm font-medium text-[#2E2E4D] dark:text-[#D2D2DB]"
                        :value="__('Password')" />
                    <x-input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        class="w-full px-4 py-2 border border-[#D2D2DB] rounded-md focus:outline-none focus:ring-2 focus:ring-[#B40404] dark:bg-[#2E2E4D] dark:border-[#D2D2DB] dark:text-[#FFFFFF]"
                    />
                </div>

                <div class="mb-6">
                    <x-label
                        for="password_confirmation"
                        class="block mb-2 text-sm font-medium text-[#2E2E4D] dark:text-[#D2D2DB]"
                        :value="__('Confirm Password')" />
                    <x-input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        class="w-full px-4 py-2 border border-[#D2D2DB] rounded-md focus:outline-none focus:ring-2 focus:ring-[#B40404] dark:bg-[#2E2E4D] dark:border-[#D2D2DB] dark:text-[#FFFFFF]"
                    />
                </div>

                <div class="flex justify-end">
                    <x-button class="bg-[#B40404] hover:bg-[#2E2E4D] text-[#FFFFFF] px-6 py-2 rounded-md">
                        {{ __('Reset Password') }}
                    </x-button>
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
