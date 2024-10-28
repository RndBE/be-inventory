<?php

namespace App\Http\Controllers;

use Throwable;
use App\Helpers\LogHelper;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lihat-permission', ['only' => ['index']]);
        $this->middleware('permission:tambah-permission', ['only' => ['create','store']]);
        $this->middleware('permission:edit-permission', ['only' => ['update','edit']]);
        $this->middleware('permission:hapus-permission', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $permissions = Permission::paginate(10);
        return view('pages.permission.index', compact('permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.permission.create');
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
                    'unique:permissions,name'
                ]
            ]);

            Permission::create([
                'name' => $request->name,
                'guard_name' => 'web',
            ]);
            LogHelper::success('Berhasil Menambah Permission!');
            return redirect('permissions')->with('success', 'Permission Created Successfully');
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
        $permissions = Permission::findOrFail($id);
        return view('pages.permission.edit', compact('permissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $permissions = Permission::findOrFail($id);
            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'unique:permissions,name,' . $permissions->id
                ]
            ]);

            $permissions->update([
                'name' => $request->name
            ]);
            LogHelper::success('Berhasil Mengubah Permission!');
            return redirect('permissions')->with('success', 'Permission Updated Successfully');
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
            $permissions = Permission::findOrFail($id);
            $data = $permissions->delete();
            if (!$data) {
                return redirect()->back()->with('gagal', 'menghapus');
            }
            LogHelper::success('Berhasil Menghapus Permission!');
            return redirect()->route('permissions.index')->with('success', 'Permission Deleted Successfully!');
        } catch (Throwable $e) {
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }
}
