<?php

namespace App\Http\Controllers;

use App\Models\JenisBahan;
use Illuminate\Http\Request;

class JenisBahanController extends Controller
{
    public function index(Request $request)
    {
        $jenisbahan = JenisBahan::All();
        return view('pages.jenis-bahan.index',[
            "jenisbahan" => $jenisbahan
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required',
        ]);
        $jenisbahan = JenisBahan::create($validated);

        if (!$jenisbahan) {
            return redirect()->back()->with('errors', 'Gagal menambahkan data');
        }
        return redirect()->route('jenis-bahan.index')->with('success', 'Bahan berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {

        $validated = $request->validate([
            'nama' => 'required',
        ]);
        $data = JenisBahan::find($id);
        $data->nama = $validated['nama'];
        $JenisBahan = $data->save();
        if (!$JenisBahan) {
            return redirect()->back()->with('errors', 'Gagal menambahkan data');
        }
        return redirect()->route('jenis-bahan.index')->with('success', 'Bahan berhasil diubah.');
    }

    public function destroy(Request $request, $id)
    {

        $data = JenisBahan::find($id);
        $jenisbahan = $data->delete();
        if (!$jenisbahan) {
            return redirect()->back()->with('gagal', 'menghapus');
        }
        return redirect()->route('jenis-bahan.index')->with('success', 'Bahan berhasil dihapus.');
    }
}
