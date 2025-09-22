<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\User;
use App\Helpers\LogHelper;
use App\Models\JobPosition;
use App\Models\Organization;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lihat-user', ['only' => ['index']]);
        $this->middleware('permission:tambah-user', ['only' => ['create','store']]);
        $this->middleware('permission:edit-user', ['only' => ['update','edit']]);
        $this->middleware('permission:hapus-user', ['only' => ['destroy']]);
    }

    public function index()
    {
        $users = User::with(['atasanLevel1','atasanLevel2','atasanLevel3'])->get();
        return view('pages.user.index', ['users' => $users]);
    }

    public function create()
    {
        $users = User::all();
        $jobpositions = JobPosition::all();
        $organizations = Organization::all();
        $roles = Role::pluck('name','name')->all();
        return view('pages.user.create', [
            'users' => $users,
            'jobpositions' => $jobpositions,
            'organizations' => $organizations,
            'roles' => $roles
        ]);
    }

    public function store(Request $request)
    {
        //dd($request->all());
        try {
            // Validate the request inputs
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'organization_id' => 'nullable|exists:organization,id',
                'job_position_id' => 'nullable|exists:job_position,id',
                'job_level' => 'nullable',
                'email' => 'required|email|max:255|unique:users,email',
                'telephone' => 'nullable',
                'password' => 'required|string|min:8|max:20',
                'roles' => 'required|array',
                'atasan_level1_id' => 'nullable|exists:users,id',
                'atasan_level2_id' => 'nullable|exists:users,id',
                'atasan_level3_id' => 'nullable|exists:users,id',
                'tanda_tangan' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            // Handle file upload for tanda_tangan
            if ($request->hasFile('tanda_tangan')) {
                $file = $request->file('tanda_tangan');

                // Debugging: Ensure file upload works
                if (!$file->isValid()) {
                    throw new \Exception('File upload failed.');
                }

                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('public/tanda_tangan', $fileName);
                $validated['tanda_tangan'] = 'tanda_tangan/' . $fileName;
            }

            // Create a new user
            $user = User::create([
                'name' => $validated['name'],
                'organization_id' => $validated['organization_id'] ?? null,
                'job_position_id' => $validated['job_position_id'] ?? null,
                'job_level' => $validated['job_level'] ?? null,
                'email' => $validated['email'],
                'telephone' => $validated['telephone'] ?? null,
                'password' => Hash::make($validated['password']),
                'atasan_level1_id' => $validated['atasan_level1_id'] ?? null,
                'atasan_level2_id' => $validated['atasan_level2_id'] ?? null,
                'atasan_level3_id' => $validated['atasan_level3_id'] ?? null,
                'tanda_tangan' => $validated['tanda_tangan'] ?? null,
            ]);

            // Assign roles to the user
            $user->syncRoles($validated['roles']);

            // Log success
            LogHelper::success('Berhasil Menambahkan User!');
            return redirect()->route('users.index')->with('success', 'Berhasil Menambahkan User!');
        } catch (\Throwable $e) {
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }



    public function edit(User $user)
    {
        $users = User::all();
        $jobpositions = JobPosition::all();
        $organizations = Organization::all();
        $roles = Role::pluck('name','name')->all();
        $userRoles = $user->roles->pluck('name','name')->all();
        return view('pages.user.edit', [
            'users' => $users,
            'jobpositions' => $jobpositions,
            'organizations' => $organizations,
            'user' => $user,
            'roles' => $roles,
            'userRoles' => $userRoles
        ]);
    }

    public function update(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'organization_id' => 'nullable|exists:organization,id',
                'job_position_id' => 'nullable|exists:job_position,id',
                'job_level' => 'nullable',
                'email' => 'required|email|max:255|unique:users,email,' . $user->id,
                'telephone' => 'nullable|string|max:20',
                'password' => 'nullable|string|min:8|max:20',
                'roles' => 'required|array',
                'atasan_level1_id' => 'nullable|exists:users,id',
                'atasan_level2_id' => 'nullable|exists:users,id',
                'atasan_level3_id' => 'nullable|exists:users,id',
                'tanda_tangan' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'status' => 'required|in:Aktif,Non-Aktif',
            ]);

            // Handle file upload for tanda_tangan
            if ($request->hasFile('tanda_tangan')) {
                $file = $request->file('tanda_tangan');

                if (!$file->isValid()) {
                    throw new \Exception('File upload failed.');
                }

                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('public/tanda_tangan', $fileName);
                $validated['tanda_tangan'] = 'tanda_tangan/' . $fileName;

                // Delete old tanda_tangan file if exists
                if ($user->tanda_tangan) {
                    Storage::delete('public/' . $user->tanda_tangan);
                }
            }

            $user->update([
                'name' => $validated['name'],
                'organization_id' => $validated['organization_id'] ?? $user->organization_id,
                'job_position_id' => $validated['job_position_id'] ?? $user->job_position_id,
                'job_level' => $validated['job_level'] ?? $user->job_level,
                'email' => $validated['email'],
                'telephone' => $validated['telephone'] ?? $user->telephone,
                'atasan_level1_id' => $validated['atasan_level1_id'] ?? $user->atasan_level1_id,
                'atasan_level2_id' => $validated['atasan_level2_id'] ?? $user->atasan_level2_id,
                'atasan_level3_id' => $validated['atasan_level3_id'] ?? $user->atasan_level3_id,
                'tanda_tangan' => $validated['tanda_tangan'] ?? $user->tanda_tangan,
                'status' => $validated['status'],
            ]);

            if (!empty($validated['password'])) {
                $user->update([
                    'password' => Hash::make($validated['password']),
                ]);
            }

            $user->syncRoles($validated['roles']);

            return redirect()->route('users.index')->with('success', 'User updated successfully with roles!');
        } catch (\Throwable $e) {
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function destroy($userId)
    {
        try {
            $user = User::findOrFail($userId);
            if ($user->tanda_tangan) {
                Storage::delete('public/' . $user->tanda_tangan);
            }

            $user->delete();

            LogHelper::success("User with ID {$userId} deleted successfully.");
            return redirect('/users')->with('success', 'User deleted successfully.');
        } catch (\Throwable $e) {
            LogHelper::error("Failed to delete user with ID {$userId}: " . $e->getMessage());
            return redirect('/users')->with('error', 'Failed to delete user. Please try again.');
        }
    }

}
