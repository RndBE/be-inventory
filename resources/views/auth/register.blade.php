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
                    <h1 class="text-3xl text-gray-800 dark:text-gray-100 font-bold mb-6">{{ __('Create your Account') }}</h1>
                    <form method="POST" action="{{ route('register') }}">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <x-label for="name">{{ __('Full Name') }} <span class="text-red-500">*</span></x-label>
                                <x-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                            </div>

                            <div>
                                <x-label for="email">{{ __('Email Address') }} <span class="text-red-500">*</span></x-label>
                                <x-input id="email" type="email" name="email" :value="old('email')" required autocomplete="email"/>
                            </div>

                            <div>
                                <x-label for="usertype">{{ __('User Type') }} <span class="text-red-500">*</span></x-label>
                                <select id="usertype" name="usertype" class="form-select w-full" required>
                                    <option value="">{{ __('Select User Type') }}</option>
                                    <option value="superadmin" {{ old('usertype') == 'superadmin' ? 'selected' : '' }}>{{ __('Super Admin') }}</option>
                                    <option value="produksi" {{ old('usertype') == 'produksi' ? 'selected' : '' }}>{{ __('Produksi') }}</option>
                                    <option value="purchasing" {{ old('usertype') == 'purchasing' ? 'selected' : '' }}>{{ __('Purchasing') }}</option>
                                    <option value="rnd" {{ old('usertype') == 'rnd' ? 'selected' : '' }}>{{ __('RnD') }}</option>
                                </select>
                            </div>

                            <div>
                                <x-label for="password" value="{{ __('Password') }}" />
                                <x-input id="password" type="password" name="password" required autocomplete="new-password" />
                            </div>

                            <div>
                                <x-label for="password_confirmation" value="{{ __('Confirm Password') }}" />
                                <x-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-6">
                            <div class="mr-1">
                            </div>
                            <x-button>
                                {{ __('Sign Up') }}
                            </x-button>
                        </div>
                        @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                            <div class="mt-6">
                                <label class="flex items-start">
                                    <input type="checkbox" class="form-checkbox mt-1" name="terms" id="terms" />
                                    <span class="text-sm ml-2">
                                        {!! __('I agree to the :terms_of_service and :privacy_policy', [
                                            'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="text-sm underline hover:no-underline">'.__('Terms of Service').'</a>',
                                            'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="text-sm underline hover:no-underline">'.__('Privacy Policy').'</a>',
                                        ]) !!}
                                    </span>
                                </label>
                            </div>
                        @endif
                    </form>
                    <x-validation-errors class="mt-4" />
                    <!-- Footer -->
                    <div class="pt-5 mt-6 border-t border-gray-100 dark:border-gray-700/60">
                        <div class="text-sm">
                            {{ __('Have an account?') }} <a class="font-medium text-violet-500 hover:text-violet-600 dark:hover:text-violet-400" href="{{ route('login') }}">{{ __('Sign In') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-authentication-layout>
