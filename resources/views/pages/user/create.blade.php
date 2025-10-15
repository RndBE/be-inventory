<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <div class="sm:flex sm:justify-between sm:items-center mb-2">

            <div class="mb-4 sm:mb-0">
                <h6 class="text-2xl text-gray-800 dark:text-gray-100 font-bold">Add User</h6>
            </div>

        </div>

        <ul class="flex flex-wrap -m-1">
            <a href="{{ url('users') }}" class="mt-2 block w-fit rounded-md py-1.5 px-2 bg-red-600 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">Kembali</a>
        </ul>
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <div class="w-full bg-white border border-gray-200 rounded-lg shadow sm:p-8 dark:bg-gray-800 dark:border-gray-700">
                <form action="{{ route('users.store') }}" enctype="multipart/form-data" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="name">Name</label>
                        <input type="text" name="name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                    </div>
                    <div class="mb-3">
                        <label for="organization_id">Organization</label>
                        <select name="organization_id" id="organization_id" class="dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 block rounded-md border-0 py-1.5 w-full text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" autofocus required>
                            <option value="" selected>-- Pilih organization --</option>
                            @foreach($organizations as $organization)
                                <option value="{{ $organization->id }}" {{ old('organization_id') == $organization->id ? 'selected' : '' }}>{{ $organization->nama }}</option>
                            @endforeach
                        </select>
                        @error('organization_id')
                            <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="job_position_id">Job Position</label>
                        <select name="job_position_id" id="job_position_id" class="dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 block rounded-md border-0 py-1.5 w-full text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" autofocus required>
                            <option value="" selected>-- Pilih job position --</option>
                            @foreach($jobpositions as $jobposition)
                                <option value="{{ $jobposition->id }}" {{ old('job_position_id') == $jobposition->id ? 'selected' : '' }}>{{ $jobposition->nama }}</option>
                            @endforeach
                        </select>
                        @error('job_position_id')
                            <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="job_level">Job Level</label>
                        <select name="job_level" id="job_level" class="dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 block rounded-md border-0 py-1.5 w-full text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" autofocus required>
                            <option value="">-- Pilih job level --</option>
                            <option value="1" {{ old('job_level') == '1' ? 'selected' : '' }}>1</option>
                            <option value="2" {{ old('job_level') == '2' ? 'selected' : '' }}>2</option>
                            <option value="3" {{ old('job_level') == '3' ? 'selected' : '' }}>3</option>
                            <option value="4" {{ old('job_level') == '4' ? 'selected' : '' }}>4</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="telephone">Whatsapp</label>
                        <input type="number" name="telephone" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                    </div>
                    <div class="mb-3">
                        <label for="email">Email</label>
                        <input type="text" name="email" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                    </div>
                    <div class="mb-3">
                        <label for="password">Password</label>
                        <input type="text" name="password" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                    </div>
                    <div class="mb-3">
                        <label class="block mb-1 text-sm font-medium text-gray-700">Status</label>
                        <div class="flex items-center space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="status" value="Aktif"
                                    {{ old('status', $user->status) === 'Aktif' ? 'checked' : '' }}
                                    class="text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                <span class="ml-2">Aktif</span>
                            </label>

                            <label class="inline-flex items-center">
                                <input type="radio" name="status" value="Non-Aktif"
                                    {{ old('status', $user->status) === 'Non-Aktif' ? 'checked' : '' }}
                                    class="text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                <span class="ml-2">Non-Aktif</span>
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="roles" class="block mb-2 font-medium text-sm text-gray-700">Roles</label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($roles as $role)
                                <label class="inline-flex items-center space-x-2">
                                    <input
                                        type="checkbox"
                                        name="roles[]"
                                        value="{{ $role }}"
                                        {{ in_array($role, old('roles', $userRoles)) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    >
                                    <span class="text-sm text-gray-700">{{ $role }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Atasan Level 1 --}}
<div class="mb-3">
    <label for="atasan_level1_id" class="block mb-1 text-sm font-medium text-gray-700">Atasan Level 1</label>
    <select
        name="atasan_level1_id"
        id="atasan_level1_id"
        class="dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400
               block rounded-md border-0 py-1.5 w-full text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300
               placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
    >
        <option value="">-- Pilih Atasan Level 1 --</option>
        @foreach($users as $u)
            <option value="{{ $u->id }}" {{ old('atasan_level1_id') == $u->id ? 'selected' : '' }}>
                {{ $u->name }}
            </option>
        @endforeach
    </select>
    @error('atasan_level1_id')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

{{-- Atasan Level 2 --}}
<div class="mb-3">
    <label for="atasan_level2_id" class="block mb-1 text-sm font-medium text-gray-700">Atasan Level 2</label>
    <select
        name="atasan_level2_id"
        id="atasan_level2_id"
        class="dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400
               block rounded-md border-0 py-1.5 w-full text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300
               placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
    >
        <option value="">-- Pilih Atasan Level 2 --</option>
        @foreach($users as $u)
            <option value="{{ $u->id }}" {{ old('atasan_level2_id') == $u->id ? 'selected' : '' }}>
                {{ $u->name }}
            </option>
        @endforeach
    </select>
    @error('atasan_level2_id')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

{{-- Atasan Level 3 --}}
<div class="mb-3">
    <label for="atasan_level3_id" class="block mb-1 text-sm font-medium text-gray-700">Atasan Level 3</label>
    <select
        name="atasan_level3_id"
        id="atasan_level3_id"
        class="dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400
               block rounded-md border-0 py-1.5 w-full text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300
               placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
    >
        <option value="">-- Pilih Atasan Level 3 --</option>
        @foreach($users as $u)
            <option value="{{ $u->id }}" {{ old('atasan_level3_id') == $u->id ? 'selected' : '' }}>
                {{ $u->name }}
            </option>
        @endforeach
    </select>
    @error('atasan_level3_id')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

                    <div class="mb-3">
                        <label for="tanda_tangan" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">Tanda Tangan</label>
                        <div class="mt-2">
                            <input class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 file:rounded-lg file:w-24 file:h-9" id="tanda_tangan" name="tanda_tangan" type="file">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-300" id="file_input_help">PNG, JPG or JPEG (MAX. 2 MB).</p>
                            @error('tanda_tangan')
                                <p class="text-red-500 text-sm mt-1 error-message">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
