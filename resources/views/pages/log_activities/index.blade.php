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
                            <th scope="col" class="px-6 py-3 w-1/4">Waktu</th>
                            <th scope="col" class="px-6 py-3 w-1/4">User</th>
                            <th scope="col" class="px-6 py-3">Platform</th>
                            <th scope="col" class="px-6 py-3">Method</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                            <th scope="col" class="px-6 py-3">Pesan</th>
                            <th scope="col" class="px-6 py-3">URL</th>
                            {{-- <th scope="col" class="px-6 py-3">Platform</th>
                            <th scope="col" class="px-6 py-3">Browser</th> --}}

                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activities as $activity)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 w-1/4">{{ $activity->created_at }}</td>
                            <td class="px-6 py-4 w-1/4">
                                <div class="inline-flex justify-center items-center group">
                                    <img class="w-8 h-8 rounded-full" src="{{ Auth::user()->profile_photo_url }}" width="32" height="32" alt="{{ Auth::user()->name }}" />{{ $activity->user->name ?? null }}
                                </div>
                            </td>
                            <td class="px-6 py-4 w-1/4">
                                <div class="ms-3">
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $activity->platform }}-{{ $activity->browser }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $activity->ip_address }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
								@php
								$bgColor = '';
								$textColor = '';
								if ($activity->method === 'POST') {
								$bgColor = 'bg-green-100';
								$textColor = 'text-green-800';
								} elseif ($activity->method === 'PUT') {
								$bgColor = 'bg-orange-100';
								$textColor = 'text-orange-800';
								} elseif ($activity->method === 'DELETE') {
								$bgColor = 'bg-red-100';
								$textColor = 'text-red-800';
								} elseif ($activity->method === 'GET') {
								$bgColor = 'bg-blue-100';
								$textColor = 'text-blue-800';
								} else {
								$bgColor = 'bg-gray-100';
								$textColor = 'text-gray-800';
								}
								@endphp

								<span class="{{ $bgColor }} {{ $textColor }} text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-opacity-75 dark:text-opacity-75">
									{{ $activity->method }}
								</span>
							</td>
                            <td class="px-6 py-4">{{ $activity->status }}</td>
                            <td class="px-6 py-4">{{ $activity->message }}</td>
                            <td class="px-6 py-4">{{ $activity->url }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $activities->links() }} <!-- Pagination links -->
            </div>
        </div>

        <!-- Active Users Table -->
        <div class="relative overflow-x-auto pt-8">
            <h2 class="text-lg font-semibold">Active Users</h2>
            <div class="relative overflow-x-auto">
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
</x-app-layout>
