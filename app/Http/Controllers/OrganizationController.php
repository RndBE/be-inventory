<?php

namespace App\Http\Controllers;

use Throwable;
use App\Helpers\LogHelper;
use App\Exports\organizationExport;
use App\Models\Organization;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class OrganizationController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('permission:lihat-organization', ['only' => ['index']]);
    //     $this->middleware('permission:tambah-organization', ['only' => ['create','store']]);
    //     $this->middleware('permission:edit-organization', ['only' => ['update','edit']]);
    //     $this->middleware('permission:hapus-organization', ['only' => ['destroy']]);
    //     $this->middleware('permission:export-organization', ['only' => ['export']]);
    // }

    // public function export()
    // {
    //     return Excel::download(new organizationExport, _organizations_be-inventory.xlsx');
    // }

    public function index(Request $request)
    {
        $organization = Organization::All();
        return view('pages.organization.index', [
            "organization" => $organization
        ]);
    }

    public function store(Request $request)
    {
        try{
            $validated = $request->validate([
                'nama' => 'required',
            ]);
            $organization = Organization::create($validated);

            if (!$organization) {
                return redirect()->back()->with('errors', 'Gagal menambahkan data.');
            }
            LogHelper::success('Berhasil Menambahkan organization!');
            return redirect()->route('organization.index')->with('success', 'Berhasil Menambahkan organization!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function update(Request $request, $id)
    {
        try{
            $validated = $request->validate([
                'nama' => 'required',
            ]);
            $data = Organization::find($id);
            $data->nama = $validated['nama'];
            $organization = $data->save();
            if (!$organization) {
                return redirect()->back()->with('errors', 'Gagal menambahkan data');
            }
            LogHelper::success('Berhasil Mengubah organization!');
            return redirect()->route('organization.index')->with('success', 'Berhasil Mengubah organization');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function destroy(Request $request, $id)
    {
        try{
            $data = Organization::find($id);
            $organization = $data->delete();
            if (!$organization) {
                return redirect()->back()->with('gagal', 'menghapus');
            }
            LogHelper::success('Berhasil Menghapus organization!');
            return redirect()->route('organization.index')->with('success', 'Berhasil Menghapus organization!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }
}
