<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Unit;
use App\Helpers\LogHelper;
use Illuminate\Http\Request;

class UnitController extends Controller
{
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
