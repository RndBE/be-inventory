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
                    <h1 class="text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl dark:text-white">
                        Sign in to your account
                    </h1>
                    <form class="space-y-4 md:space-y-6" method="POST" action="{{ route('login') }}">
                        @csrf
                        <div>
                            <x-label for="email" value="{{ __('Email') }}" />
                            <x-input id="email" placeholder="name@company.com" type="email" name="email" :value="old('email')" required autofocus />
                        </div>
                        <div>
                            <x-label for="password" value="{{ __('Password') }}" />

                            <!-- Password Input Field -->
                            <div class="relative">
                                <x-input id="password" placeholder="••••••••" type="password" name="password" required autocomplete="current-password" />

                                <!-- Eye Icon to Toggle Password Visibility -->
                                <button type="button" id="togglePassword" class="absolute right-2 top-1/2 transform -translate-y-1/2">
                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-eye"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">

                                </div>
                                <div class="ml-3 text-sm">

                                </div>
                            </div>
                            {{-- @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-500">{{ __('Forgot Password?') }}</a>
                            @endif --}}
                        </div>
                        <button type="submit" class="w-full text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-primary-800">{{ __('Sign in') }}</button>
                        {{-- <p class="text-sm font-light text-gray-500 dark:text-gray-400">
                            Don’t have an account yet? <a href="{{ route('register') }}" class="font-medium text-primary-600 hover:underline dark:text-primary-500">{{ __('Sign Up') }}</a>
                        </p> --}}
                    </form>
                    <x-validation-errors class="mt-4" />
                </div>
            </div>
        </div>
    </section>
    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordField = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');

            // Toggle the password field type between text and password
            if (passwordField.type === "password") {
                passwordField.type = "text";
                eyeIcon.setAttribute('class', 'bi bi-eye-slash'); // Change icon to eye-slash
            } else {
                passwordField.type = "password";
                eyeIcon.setAttribute('class', 'bi bi-eye'); // Change icon back to eye
            }
        });
    </script>

</x-authentication-layout>
