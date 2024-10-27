<x-authentication-layout>
    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('status') }}
        </div>
    @endif
    <section class="bg-gray-100 dark:bg-gray-900">
        <div class="flex flex-col items-center justify-center px-6 py-8 mx-auto md:h-screen lg:py-0">
            <a href="#" class="flex items-center mb-6 text-2xl font-semibold text-gray-900 dark:text-white">
                <img class="w-25 h-12 mr-2" src="{{ asset('images/logo_be2.png') }}" alt="logo">
            </a>
            <div class="w-full bg-white rounded-lg shadow dark:border md:mt-0 sm:max-w-md xl:p-0 dark:bg-gray-800 dark:border-gray-700">
                <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                    <h1 class="text-3xl text-gray-800 dark:text-gray-100 font-bold mb-6">{{ __('Reset your Password') }}</h1>
                    <!-- Form -->
                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf
                        <div>
                            <x-label for="email">{{ __('Email Address') }} <span class="text-red-500">*</span></x-label>
                            <x-input id="email" type="email" name="email" :value="old('email')" required autofocus />
                        </div>
                        <div class="flex justify-end mt-6">
                            <x-button>
                                {{ __('Send Reset Link') }}
                            </x-button>
                        </div>
                    </form>
                    <x-validation-errors class="mt-4" />
                </div>
            </div>
        </div>
    </section>
</x-authentication-layout>
