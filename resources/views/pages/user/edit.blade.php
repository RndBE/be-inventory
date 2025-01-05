<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <div class="sm:flex sm:justify-between sm:items-center mb-2">

            <div class="mb-4 sm:mb-0">
                <h6 class="text-2xl text-gray-800 dark:text-gray-100 font-bold">Edit User</h6>
            </div>

        </div>

        <ul class="flex flex-wrap -m-1">
            <a href="{{ url('users') }}" class="mt-2 block w-fit rounded-md py-1.5 px-2 bg-red-600 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">Kembali</a>
        </ul>
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <div class="w-full bg-white border border-gray-200 rounded-lg shadow sm:p-8 dark:bg-gray-800 dark:border-gray-700">
                <form action="{{ route('users.update', $user->id) }}" enctype="multipart/form-data" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="name">Name</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm
                            ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2
                            focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                    </div>
                    <div class="mb-3">
                        <label for="organization_id">Organization</label>
                        <select name="organization_id" id="organization_id"
                            class="dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400
                            block rounded-md border-0 py-1.5 w-full text-gray-900 shadow-sm
                            ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2
                            focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            <option value="" selected>-- Pilih organization --</option>
                            @foreach($organizations as $organization)
                                <option value="{{ $organization->id }}"
                                    {{ old('organization_id', $user->organization_id) == $organization->id ? 'selected' : '' }}>
                                    {{ $organization->nama }}
                                </option>
                            @endforeach
                        </select>
                        @error('organization_id')
                            <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="job_position_id">Job Position</label>
                        <select name="job_position_id" id="job_position_id"
                            class="dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400
                            block rounded-md border-0 py-1.5 w-full text-gray-900 shadow-sm
                            ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2
                            focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            <option value="" selected>-- Pilih job position --</option>
                            @foreach($jobpositions as $jobposition)
                                <option value="{{ $jobposition->id }}"
                                    {{ old('job_position_id', $user->job_position_id) == $jobposition->id ? 'selected' : '' }}>
                                    {{ $jobposition->nama }}
                                </option>
                            @endforeach
                        </select>
                        @error('job_position_id')
                            <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="job_level">Job Level</label>
                        <select name="job_level" id="job_level"
                            class="dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400
                            block rounded-md border-0 py-1.5 w-full text-gray-900 shadow-sm
                            ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2
                            focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            <option value="">-- Pilih job level --</option>
                            @for ($i = 1; $i <= 4; $i++)
                                <option value="{{ $i }}" {{ old('job_level', $user->job_level) == $i ? 'selected' : '' }}>
                                    {{ $i }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="telephone">Whatsapp</label>
                        <input type="number" name="telephone" value="{{ old('telephone', $user->telephone) }}"
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm
                            ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2
                            focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                    </div>
                    <div class="mb-3">
                        <label for="email">Email</label>
                        <input type="text" name="email" value="{{ old('email', $user->email) }}"
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm
                            ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2
                            focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                    </div>
                    <div class="mb-3">
                        <label for="password">Password</label>
                        <input type="password" name="password"
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm
                            ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2
                            focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                    </div>
                    <div class="mb-3">
                        <label for="roles">Roles</label>
                        <select id="roles" name="roles[]" multiple
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm
                            ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2
                            focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            @foreach($roles as $role)
                                <option value="{{ $role }}"
                                    {{ in_array($role, old('roles', $userRoles)) ? 'selected' : '' }}>
                                    {{ $role }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="atasan_level1_id">Atasan Level 1</label>
                        <select name="atasan_level1_id" id="atasan_level1_id"
                                class="dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 block rounded-md border-0 py-1.5 w-full text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" autofocus>
                            <option value="" selected>-- Pilih Atasan Level 1 --</option>
                            @foreach($users as $potentialAtasan)
                                <option value="{{ $potentialAtasan->id }}"
                                    {{ old('atasan_level1_id', $user->atasan_level1_id) == $potentialAtasan->id ? 'selected' : '' }}>
                                    {{ $potentialAtasan->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('atasan_level1_id')
                            <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="atasan_level2_id">Atasan Level 2</label>
                        <select name="atasan_level2_id" id="atasan_level2_id" class="dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 block rounded-md border-0 py-1.5 w-full text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" autofocus >
                            <option value="" selected>-- Pilih Atasan Level 2 --</option>
                            @foreach($users as $potentialAtasan)
                                <option value="{{ $potentialAtasan->id }}"
                                    {{ old('atasan_level2_id', $user->atasan_level2_id) == $potentialAtasan->id ? 'selected' : '' }}>
                                    {{ $potentialAtasan->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('atasan_level2_id')
                            <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="atasan_level3_id">Atasan Level 3</label>
                        <select name="atasan_level3_id" id="atasan_level3_id" class="dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 block rounded-md border-0 py-1.5 w-full text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" autofocus>
                            <option value="" selected>-- Pilih Atasan Level 3 --</option>
                            @foreach($users as $potentialAtasan)
                            <option value="{{ $potentialAtasan->id }}"
                                {{ old('atasan_level3_id', $user->atasan_level3_id) == $potentialAtasan->id ? 'selected' : '' }}>
                                {{ $potentialAtasan->name }}
                            </option>
                        @endforeach
                        </select>
                        @error('atasan_level3_id')
                            <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="tanda_tangan" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">Tanda Tangan</label>
                        <div class="mt-2">
                            @if ($user->tanda_tangan)
                                <p>Current file: <a href="{{ asset('storage/' . $user->tanda_tangan) }}" target="_blank" class="text-indigo-600">View</a></p>
                            @endif
                            <input type="file" name="tanda_tangan" id="tanda_tangan"
                                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer
                                bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700
                                dark:border-gray-600 dark:placeholder-gray-400 file:rounded-lg file:w-24 file:h-9" />
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-300" id="file_input_help">PNG, JPG or JPEG (MAX. 2 MB).</p>
                            @error('tanda_tangan')
                                <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <button type="submit"
                            class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm
                            hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2
                            focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            Update
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <script>
        // Fungsi untuk menghilangkan pesan error setelah 5 detik
        setTimeout(function() {
            const errorMessages = document.querySelectorAll('.error-message');
            errorMessages.forEach(function(message) {
                message.style.display = 'none';
            });
        }, 3000); // 3000 ms = 3 detik
    </script>
</x-app-layout>
