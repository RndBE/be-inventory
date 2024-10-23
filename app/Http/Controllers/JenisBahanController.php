<?php

namespace App\Http\Controllers;

use Throwable;
use App\Helpers\LogHelper;
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
        try{
            $validated = $request->validate([
                'nama' => 'required',
            ]);
            $jenisbahan = JenisBahan::create($validated);

            if (!$jenisbahan) {
                return redirect()->back()->with('errors', 'Gagal menambahkan data');
            }
            LogHelper::success('Berhasil Menambah Jenis Bahan!');
            return redirect()->route('jenis-bahan.index')->with('success', 'Berhasil Menambah Jenis Bahan!');
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
            $data = JenisBahan::find($id);
            $data->nama = $validated['nama'];
            $JenisBahan = $data->save();
            if (!$JenisBahan) {
                return redirect()->back()->with('errors', 'Gagal menambahkan data');
            }
            LogHelper::success('Berhasil Mengubah Jenis Bahan');
            return redirect()->route('jenis-bahan.index')->with('success', 'Berhasil Mengubah Jenis Bahan!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function destroy(Request $request, $id)
    {
        try{
            $data = JenisBahan::find($id);
            $jenisbahan = $data->delete();
            if (!$jenisbahan) {
                return redirect()->back()->with('gagal', 'Gagal menghapus data');
            }
            LogHelper::success('Berhasil Menghapus Jenis Bahan!');
            return redirect()->route('jenis-bahan.index')->with('success', 'Berhasil Menghapus Jenis Bahan!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }
}
