
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <div class="sm:flex sm:justify-between sm:items-center mb-2">

            <div class="mb-4 sm:mb-0">
                <h6 class="text-2xl text-gray-800 dark:text-gray-100 font-bold">Log Activity</h6>
            </div>

            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
                <ul class="flex flex-wrap -m-1">
                    <li class="m-1">
                        @include('livewire.searchdata')
                    </li>
                    <li class="m-1">
                        @include('livewire.dataperpage')
                    </li>
                    <li class="m-1">
                    </li>
                    <li class="m-1">
                    </li>
                    <li class="m-1">
                    </li>
                </ul>
            </div>
        </div>

        <ul class="flex flex-wrap -m-1">

        </ul>
        <div class="relative overflow-x-auto pt-2">
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-3 w-1/4">Waktu</th>
                            <th class="px-6 py-3 w-1/4">User</th>
                            <th class="px-6 py-3 w-1/4">Pesan</th>
                            <th class="px-6 py-3 w-1/4">Platform</th>
                            <th class="px-6 py-3">Method</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">URL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $activity)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 w-1/4">{{ $activity->created_at }}</td>
                            <td class="px-6 py-4 w-1/4">
                                <div class="inline-flex justify-center items-center group">
                                    <img
                                        class="w-8 h-8 rounded-full"
                                        src="{{ $activity->user->profile_photo_url ?? 'default.jpg' }}"
                                        alt="{{ $activity->user->name ?? 'N/A' }}"
                                    />
                                    {{ $activity->user->name ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 w-1/4">{{ $activity->message }}</td>
                            <td class="px-6 py-4 w-1/4">
                                <div class="ms-3">
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $activity->platform }} - {{ $activity->browser }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ $activity->ip_address }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="{{ $activity->method == 'POST' ? 'bg-green-100 text-green-800' : ($activity->method == 'PUT' ? 'bg-orange-100 text-orange-800' : ($activity->method == 'DELETE' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800')) }} text-xs font-medium px-2.5 py-0.5 rounded dark:bg-opacity-75 dark:text-opacity-75">
                                    {{ $activity->method }}
                                </span>
                            </td>
                            <td class="px-6 py-4">{{ $activity->status }}</td>
                            <td class="px-6 py-4">{{ $activity->url }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center px-6 py-4">No activities found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>
            <div class="mt-4">
                {{ $activities->links() }}
            </div>
        </div>

        <!-- Active Users Table -->
        <div class="relative overflow-x-auto pt-8">
            <div class="mt-8" wire:poll.1s>
                <h2 class="text-lg font-semibold">Active Users</h2>
                <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">User</th>
                                <th scope="col" class="px-6 py-3">Platform - Browser</th>
                                <th scope="col" class="px-6 py-3">IP Address</th>
                                <th scope="col" class="px-6 py-3">Last Active</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activeSessionsWithAgent as $session)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="px-6 py-4">{{ $session['user'] }}</td>
                                <td class="px-6 py-4">{{ $session['platform'] . ' - ' . $session['browser'] }}</td>
                                <td class="px-6 py-4">{{ $session['ip_address'] }}</td>
                                <td class="px-6 py-4">{{ $session['last_active'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
