<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Unit;
use App\Helpers\LogHelper;
use App\Exports\UnitExport;
use App\Models\JobPosition;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class JobPositionController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('permission:lihat-unit', ['only' => ['index']]);
    //     $this->middleware('permission:tambah-unit', ['only' => ['create','store']]);
    //     $this->middleware('permission:edit-unit', ['only' => ['update','edit']]);
    //     $this->middleware('permission:hapus-unit', ['only' => ['destroy']]);
    //     $this->middleware('permission:export-unit', ['only' => ['export']]);
    // }

    // public function export()
    // {
    //     return Excel::download(new UnitExport, 'satuan_units_be-inventory.xlsx');
    // }

    public function index(Request $request)
    {
        $jobposition = JobPosition::All();
        return view('pages.job-position.index', [
            "jobposition" => $jobposition
        ]);
    }

    public function store(Request $request)
    {
        try{
            $validated = $request->validate([
                'nama' => 'required',
            ]);
            $jobposition = JobPosition::create($validated);

            if (!$jobposition) {
                return redirect()->back()->with('errors', 'Gagal menambahkan data.');
            }
            LogHelper::success('Berhasil Menambahkan Job Position!');
            return redirect()->route('job-position.index')->with('success', 'Berhasil Menambahkan Job Position!');
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
            $data = JobPosition::find($id);
            $data->nama = $validated['nama'];
            $jobposition = $data->save();
            if (!$jobposition) {
                return redirect()->back()->with('errors', 'Gagal menambahkan data');
            }
            LogHelper::success('Berhasil Mengubah Job Position!');
            return redirect()->route('job-position.index')->with('success', 'Berhasil Mengubah Job Position');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function destroy(Request $request, $id)
    {
        try{
            $data = JobPosition::find($id);
            $jobposition = $data->delete();
            if (!$jobposition) {
                return redirect()->back()->with('gagal', 'menghapus');
            }
            LogHelper::success('Berhasil Menghapus Job Position!');
            return redirect()->route('job-position.index')->with('success', 'Berhasil Menghapus Job Position!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }
}
