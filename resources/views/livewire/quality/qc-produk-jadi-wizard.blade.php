<div>
    {{-- Do your work, then step back. --}}

<div>
    {{-- <h2 class="intro-y text-lg font-medium">Create</h2> --}}
    <script>
        window.addEventListener('swal:error', event => {
            // console.log(event.detail);
            Swal.fire({
                icon: 'error',
                title: event.detail[0].title,
                text: event.detail[0].text,
                showConfirmButton: true,
                timer: 5000
            });
        });
    </script>

    <!-- Wizard Steps Navigation -->
    <div class="intro-y box py-10 sm:py-20 mt-5">
        <div class="wizard flex flex-col lg:flex-row justify-center px-5 sm:px-20 relative">
            @foreach ([1 => 'Informasi', 2 => 'Detail Produk'] as $i => $label)
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
            <div class="wizard__line hidden lg:block bg-gray-200 absolute mt-5" style="width: calc(50% - 4rem);"></div>
        </div>

        <!-- Step Content -->
        <div class="px-5 sm:px-20 mt-10 pt-10 border-t border-gray-200">
            @includeWhen($step === 1, 'livewire.quality.steps-qc-produk-jadi.step-1-informasi')
            @includeWhen($step === 2, 'livewire.quality.steps-qc-produk-jadi.step-2-detail-produk')
        </div>

        <!-- Navigation Buttons -->
        <div class="flex justify-end mt-8 px-5 sm:px-20">
            @if ($step > 1)
                <button wire:click="previousStep" class="button w-24 justify-center block bg-gray-200 text-gray-600">Sebelumnya</button>
            @else
                <div></div>
            @endif

            @if ($step < 2)
                <button wire:click="nextStep"
                    class="button w-24 justify-center block bg-theme-1 text-white ml-2">
                    Selanjutnya
                </button>
            @elseif ($step == 2)
                <button wire:click="simpanQcProduk"
                    class="px-6 py-2 bg-theme-1 text-white rounded-lg shadow hover:bg-red-500 ml-2">
                    Simpan
                </button>
            @endif
        </div>
    </div>
</div>

</div>
