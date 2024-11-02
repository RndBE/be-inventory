<?php

namespace App\Http\Controllers;

use App\Exports\JenisBahanExport;
use Throwable;
use App\Helpers\LogHelper;
use App\Models\JenisBahan;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class JenisBahanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lihat-jenis-bahan', ['only' => ['index']]);
        $this->middleware('permission:tambah-jenis-bahan', ['only' => ['create','store']]);
        $this->middleware('permission:edit-jenis-bahan', ['only' => ['update','edit']]);
        $this->middleware('permission:hapus-jenis-bahan', ['only' => ['destroy']]);
        $this->middleware('permission:export-jenisbahan', ['only' => ['export']]);
    }

    public function export()
    {
        return Excel::download(new JenisBahanExport, 'JenisBahan_be-inventory.xlsx');
    }

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
