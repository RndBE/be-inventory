<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Kontrak;
use App\Helpers\LogHelper;
use App\Models\JenisBahan;
use Illuminate\Http\Request;
use App\Exports\JenisBahanExport;
use Maatwebsite\Excel\Facades\Excel;

class KontrakController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lihat-kontrak', ['only' => ['index']]);
        $this->middleware('permission:tambah-kontrak', ['only' => ['create','store']]);
        $this->middleware('permission:edit-kontrak', ['only' => ['update','edit']]);
        $this->middleware('permission:hapus-kontrak', ['only' => ['destroy']]);
    }

    // public function export()
    // {
    //     return Excel::download(new JenisBahanExport, 'JenisBahan_be-inventory.xlsx');
    // }

    public function index(Request $request)
    {
        $kontrak = Kontrak::All();
        return view('pages.kontrak.index',[
            "kontrak" => $kontrak
        ]);
    }

    public function store(Request $request)
    {
        try{
            $validated = $request->validate([
                'kode_kontrak' => 'required',
                'nama_kontrak' => 'required',
                'mulai_kontrak' => 'required',
                'selesai_kontrak' => 'required',
                'garansi' => 'nullable',
            ]);
            $kontrak = Kontrak::create($validated);

            if (!$kontrak) {
                return redirect()->back()->with('errors', 'Gagal menambahkan data');
            }
            LogHelper::success('Berhasil Menambah Kontrak!');
            return redirect()->route('kontrak.index')->with('success', 'Berhasil Menambah Kontrak!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function update(Request $request, $id)
    {
        try{
            $validated = $request->validate([
                'kode_kontrak' => 'required',
                'nama_kontrak' => 'required',
                'mulai_kontrak' => 'required',
                'selesai_kontrak' => 'required',
                'garansi' => 'nullable',
            ]);
            $data = Kontrak::find($id);
            $data->kode_kontrak = $validated['kode_kontrak'];
            $data->nama_kontrak = $validated['nama_kontrak'];
            $data->mulai_kontrak = $validated['mulai_kontrak'];
            $data->selesai_kontrak = $validated['selesai_kontrak'];
            $data->garansi = $validated['garansi'];
            $Kontrak = $data->save();
            if (!$Kontrak) {
                return redirect()->back()->with('errors', 'Gagal menambahkan data');
            }
            LogHelper::success('Berhasil Mengubah Kontrak');
            return redirect()->route('kontrak.index')->with('success', 'Berhasil Mengubah Kontrak!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function destroy(Request $request, $id)
    {
        try{
            $data = Kontrak::find($id);
            $kontrak = $data->delete();
            if (!$kontrak) {
                return redirect()->back()->with('gagal', 'Gagal menghapus data');
            }
            LogHelper::success('Berhasil Menghapus Kontrak!');
            return redirect()->route('kontrak.index')->with('success', 'Berhasil Menghapus Kontrak!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }
}
