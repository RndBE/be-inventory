<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <div class="sm:flex sm:justify-between sm:items-center mb-2">

            <div class="mb-4 sm:mb-0">
                <h6 class="text-2xl text-gray-800 dark:text-gray-100 font-bold">Role : {{ $role->name }}</h6>
            </div>

        </div>

        <ul class="flex flex-wrap -m-1">
            <a href="{{ url('roles') }}" class="mt-2 block w-fit rounded-md py-1.5 px-2 bg-red-600 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">Kembali</a>
        </ul>
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <div class="w-full bg-white border border-gray-200 rounded-lg shadow sm:p-8 dark:bg-gray-800 dark:border-gray-700">
                <form action="{{ url('roles/'.$role->id.'/give-permissions') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="">Permission</label>
                        <div class="row">
                            @foreach ($permissions as $permission)
                            <div class="col-md-2">
                                <label>
                                    <input
                                        type="checkbox"
                                        name="permission[]"
                                        value="{{ $permission->name }}"
                                        {{ in_array($permission->id, $rolePermissions) ? 'checked':'' }}
                                    />
                                    {{ $permission->name }}
                                </label>
                            </div>
                            @endforeach
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
