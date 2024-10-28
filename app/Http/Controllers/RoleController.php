<?php

namespace App\Http\Controllers;

use Throwable;
use App\Helpers\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('permission:lihat-role', ['only' => ['index']]);
    //     $this->middleware('permission:tambah-role', ['only' => ['create','store','addPermissionToRole','givePermissionToRole']]);
    //     $this->middleware('permission:edit-role', ['only' => ['update','edit']]);
    //     $this->middleware('permission:hapus-role', ['only' => ['destroy']]);
    // }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::paginate(10);
        return view('pages.role.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.role.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'unique:roles,name'
                ]
            ]);

            Role::create([
                'name' => $request->name,
                'guard_name' => 'web',
            ]);
            LogHelper::success('Berhasil Menambah Role!');
            return redirect('roles')->with('success', 'Role Created Successfully');
        } catch (Throwable $e) {
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $roles = Role::findOrFail($id);
        return view('pages.role.edit', compact('roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $roles = Role::findOrFail($id);
            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'unique:roles,name,' . $roles->id
                ]
            ]);

            $roles->update([
                'name' => $request->name
            ]);
            LogHelper::success('Berhasil Mengubah Role!');
            return redirect('roles')->with('success', 'Role Updated Successfully');
        } catch (Throwable $e) {
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $roles = Role::findOrFail($id);
            $data = $roles->delete();
            if (!$data) {
                return redirect()->back()->with('gagal', 'menghapus');
            }
            LogHelper::success('Berhasil Menghapus Role!');
            return redirect()->route('roles.index')->with('success', 'Role Deleted Successfully!');
        } catch (Throwable $e) {
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }


    public function addPermissionToRole($roleId)
    {
        $permissions = Permission::all()->groupBy('category'); // Mengelompokkan permission berdasarkan kategori
        $role = Role::findOrFail($roleId);
        $rolePermissions = DB::table('role_has_permissions')
            ->where('role_has_permissions.role_id', $role->id)
            ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
            ->all();

        return view('pages.role.add-permissions', [
            'role' => $role,
            'permissions' => $permissions, // Data permission terkelompok
            'rolePermissions' => $rolePermissions
        ]);
    }


    public function givePermissionToRole(Request $request, $roleId)
    {
        $request->validate([
            'permission' => 'required'
        ]);

        $role = Role::findOrFail($roleId);
        $role->syncPermissions($request->permission);

        return redirect()->back()->with('success', 'Permissions added to role');
    }
}
