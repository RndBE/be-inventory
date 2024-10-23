@if ($errors->any())
    <div {{ $attributes }} id="error-message">
        <div class="px-4 py-2 rounded-lg text-sm bg-red-500 text-white">
            <div class="font-medium">{{ __('Whoops! Something went wrong.') }}</div>
            <ul class="mt-1 list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<script>
    // Menampilkan pesan error dan menghilangkan setelah 5 detik
    document.addEventListener('DOMContentLoaded', function () {
        const errorMessage = document.getElementById('error-message');
        if (errorMessage) {
            setTimeout(() => {
                errorMessage.style.display = 'none';
            }, 5000); // 5000 ms = 5 detik
        }
    });
</script>
