<x-authentication-layout>
    @if (session('status'))
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Sukses',
                text: @json(session('status')),
                background: '#2E2E4D',       // Warna background biru gelap
                color: '#FFFFFF',            // Warna teks putih
                confirmButtonColor: '#B40404', // Warna tombol merah gelap
                confirmButtonText: 'OK'
            });
        </script>
    @endif

    <section class="min-h-screen bg-[#D2D2DB] flex items-center justify-center">
        <div class="flex flex-col md:flex-row w-full max-w-6xl bg-white rounded-xl shadow-lg overflow-hidden">

            <!-- KIRI: Branding dengan background warehouse -->
            <div class="hidden md:flex md:w-1/2 relative bg-[#2E2E4D] items-center justify-center">
                <img src="{{ asset('images/warehousing.jpg') }}" alt="Warehouse" class="absolute inset-0 object-cover w-full h-full opacity-20" />
                <div class="relative z-10 text-center px-6 text-white">
                    <h2 class="text-5xl font-extrabold tracking-tight mb-4 drop-shadow-lg">
                        BE-Inventory
                    </h2>
                    <p class="text-2xl font-semibold italic text-[#FFFFFF] drop-shadow-md">
                        Optimalkan Stok Anda, Maksimalkan Produktivitas
                    </p>
                    <p class="mt-6 max-w-md mx-auto text-lg leading-relaxed drop-shadow-sm">
                        Kendalikan inventaris Anda secara real-time. Efisien, presisi, dan siap mendukung setiap kebutuhan bisnis.
                    </p>
                </div>
            </div>

            <!-- KANAN: Form Login -->
            <div class="w-full md:w-1/2 p-8 sm:p-10 bg-[#FFFFFF]">
                <div class="flex justify-center mb-6">
                    <img src="{{ asset('images/logo_be2.png') }}" alt="Logo" class="w-32">
                </div>

                <h1 class="text-2xl font-bold text-[#2E2E4D] mb-6 text-center">
                    Masuk ke Akun Anda
                </h1>

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <x-label for="email" value="{{ __('Email') }}" class="text-[#2E2E4D]" />
                        <x-input id="email" type="email" name="email" placeholder="email@perusahaan.com" :value="old('email')" required autofocus class="border-gray-300 focus:ring-[#B40404] focus:border-[#B40404]" />
                    </div>

                    <div>
                        <x-label for="password" value="{{ __('Password') }}" class="text-[#2E2E4D]" />
                        <div class="relative">
                            <input type="password" id="password" name="password" placeholder="••••••••" required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-[#B40404] focus:border-[#B40404]">
                            <button type="button" id="togglePassword" class="absolute right-3 top-2.5 text-gray-600">
                                <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <input type="hidden" name="g-recaptcha-response" id="recaptchaToken">

                    <div class="text-right">
                        <a href="{{ route('password.request') }}" class="text-sm text-[#2E2E4D] hover:underline">
                            {{ __('Lupa password?') }}
                        </a>
                    </div>

                    <button type="submit"
                        class="w-full bg-[#B40404] hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#B40404] focus:ring-offset-1">
                        {{ __('Sign in') }}
                    </button>

                    <x-validation-errors class="mt-4" />
                </form>
            </div>
        </div>
    </section>
    {{-- Script Google reCAPTCHA v3 --}}
<script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
<script>
grecaptcha.ready(function() {
    grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {action: 'submit'}).then(function(token) {
        document.getElementById('recaptchaToken').value = token;
    });
});
</script>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordField = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');

            if (passwordField.type === "password") {
                passwordField.type = "text";
                eyeIcon.innerHTML = `<path d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a10.05 10.05 0 012.302-3.775M3 3l18 18" />`;
            } else {
                passwordField.type = "password";
                eyeIcon.innerHTML = `<path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />`;
            }
        });
    </script>
</x-authentication-layout>
