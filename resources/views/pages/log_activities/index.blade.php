<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <div class="sm:flex sm:justify-between sm:items-center mb-2">

        <div class="mb-4 sm:mb-0">
            <h6 class="text-2xl text-gray-800 dark:text-gray-100 font-bold">Log Activity</h6>
        </div>

    </div>

    <ul class="flex flex-wrap -m-1">

    </ul>
        <div class="relative overflow-x-auto pt-2">
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">Description</th>
                            <th scope="col" class="px-6 py-3">User ID</th>
                            <th scope="col" class="px-6 py-3">Method</th>
                            <th scope="col" class="px-6 py-3">IP Address</th>
                            <th scope="col" class="px-6 py-3">URL</th>
                            <th scope="col" class="px-6 py-3">Data</th>
                            <th scope="col" class="px-6 py-3">Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activities as $activity)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4">{{ $activity->description }}</td>
                            <td class="px-6 py-4">{{ $activity->user->name }}</td>
                            <td class="px-6 py-4">{{ $activity->method }}</td>
                            <td class="px-6 py-4">{{ $activity->ip_address }}</td>
                            <td class="px-6 py-4">{{ $activity->url }}</td>
                            <td class="px-6 py-4">{{ $activity->data }}</td>
                            <td class="px-6 py-4">{{ $activity->created_at }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $activities->links() }} <!-- Pagination links -->
            </div>
        </div>
    </div>
</x-app-layout>
