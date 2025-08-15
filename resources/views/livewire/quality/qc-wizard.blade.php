<div>
    {{-- <h2 class="intro-y text-lg font-medium">Create</h2> --}}

    <!-- Wizard Steps Navigation -->
    <div class="intro-y box py-10 sm:py-20 mt-5">
        <div class="wizard flex flex-col lg:flex-row justify-center px-5 sm:px-20 relative">
            @foreach ([1 => 'Informasi', 2 => 'Detail Bahan', 3 => 'Preview'] as $i => $label)
                <div class="intro-x lg:text-center flex items-center mt-5 lg:mt-0 lg:block flex-1 z-10">
                    <button
                        class="w-10 h-10 rounded-full button {{ $step == $i ? 'bg-theme-1 text-white' : 'bg-gray-200 text-gray-600' }}">
                        {{ $i }}
                    </button>
                    <div class="lg:w-32 text-base lg:mt-3 ml-3 lg:mx-auto {{ $step == $i ? 'font-medium' : 'text-gray-700' }}">
                        {{ $label }}
                    </div>
                </div>
            @endforeach
            <div class="wizard__line hidden lg:block bg-gray-200 absolute mt-5" style="width: calc(65% - 4rem);"></div>
        </div>

        <!-- Step Content -->
        <div class="px-5 sm:px-20 mt-10 pt-10 border-t border-gray-200">
            @includeWhen($step === 1, 'livewire.quality.steps.step-1-informasi')
            @includeWhen($step === 2, 'livewire.quality.steps.step-2-detail-bahan')
            @includeWhen($step === 3, 'livewire.quality.steps.step-3-preview')
        </div>

        <!-- Navigation Buttons -->
        <div class="flex justify-end mt-8 px-5 sm:px-20">
            @if ($step > 1)
                <button wire:click="previousStep" class="button w-24 justify-center block bg-gray-200 text-gray-600">Sebelumnya</button>
            @else
                <div></div>
            @endif

            @if ($step < 3)
                <button wire:click="nextStep" class="button w-24 justify-center block bg-theme-1 text-white ml-2">Selanjutnya</button>
            @endif
        </div>
    </div>
</div>

