<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Unit;
use App\Helpers\LogHelper;
use App\Exports\UnitExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class UnitController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lihat-unit', ['only' => ['index']]);
        $this->middleware('permission:tambah-unit', ['only' => ['create','store']]);
        $this->middleware('permission:edit-unit', ['only' => ['update','edit']]);
        $this->middleware('permission:hapus-unit', ['only' => ['destroy']]);
        $this->middleware('permission:export-unit', ['only' => ['export']]);
    }

    public function export()
    {
        return Excel::download(new UnitExport, 'satuan_units_be-inventory.xlsx');
    }

    public function index(Request $request)
    {
        $unit = Unit::All();
        return view('pages.unit.index', [
            "unit" => $unit
        ]);
    }

    public function store(Request $request)
    {
        try{
            $validated = $request->validate([
                'nama' => 'required',
            ]);
            $unit = Unit::create($validated);

            if (!$unit) {
                return redirect()->back()->with('errors', 'Gagal menambahkan data.');
            }
            LogHelper::success('Berhasil Menambahkan Satuan Unit!');
            return redirect()->route('unit.index')->with('success', 'Berhasil Menambahkan Satuan Unit!');
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
            $data = Unit::find($id);
            $data->nama = $validated['nama'];
            $Unit = $data->save();
            if (!$Unit) {
                return redirect()->back()->with('errors', 'Gagal menambahkan data');
            }
            LogHelper::success('Berhasil Mengubah Satuan Unit!');
            return redirect()->route('unit.index')->with('success', 'Berhasil Mengubah Satuan Unit');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function destroy(Request $request, $id)
    {
        try{
            $data = Unit::find($id);
            $unit = $data->delete();
            if (!$unit) {
                return redirect()->back()->with('gagal', 'menghapus');
            }
            LogHelper::success('Berhasil Menghapus Satuan Unit!');
            return redirect()->route('unit.index')->with('success', 'Berhasil Menghapus Satuan Unit!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }
}
