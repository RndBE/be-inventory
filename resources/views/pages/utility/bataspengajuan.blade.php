<x-app-layout>
    <section class="bg-white dark:bg-gray-900 min-h-screen flex items-center justify-center">
        <div class="text-center px-4">
            <p class="text-4xl font-semibold text-gray-900 dark:text-white mt-4">Akses Ditutup</p>
            <p class="text-lg text-gray-500 dark:text-gray-400 mt-2">
                Halaman pengajuan hanya dapat diakses antara pukul <span class="font-medium">07:00 - 11:45</span>.
            </p>
            <div class="mt-6">
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-900">
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </section>
</x-app-layout>
