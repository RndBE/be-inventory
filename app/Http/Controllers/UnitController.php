<?php

namespace App\Http\Controllers;

use App\Models\Unit;
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
        $validated = $request->validate([
            'nama' => 'required',
        ]);
        $unit = Unit::create($validated);

        if (!$unit) {
            return redirect()->back()->with('errors', 'Gagal menambahkan data');
        }
        return redirect()->route('unit.index')->with('success', 'Unit berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {

        $validated = $request->validate([
            'nama' => 'required',
        ]);
        $data = Unit::find($id);
        $data->nama = $validated['nama'];
        $Unit = $data->save();
        if (!$Unit) {
            return redirect()->back()->with('errors', 'Gagal menambahkan data');
        }
        return redirect()->route('unit.index')->with('success', 'Unit berhasil diubah.');
    }

    public function destroy(Request $request, $id)
    {

        $data = Unit::find($id);
        $unit = $data->delete();
        if (!$unit) {
            return redirect()->back()->with('gagal', 'menghapus');
        }
        return redirect()->route('unit.index')->with('success', 'Unit berhasil dihapus.');
    }
}
