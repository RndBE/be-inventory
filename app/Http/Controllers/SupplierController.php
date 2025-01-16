<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Supplier;
use App\Helpers\LogHelper;
use Illuminate\Http\Request;
use App\Exports\SupplierExport;
use Maatwebsite\Excel\Facades\Excel;

class SupplierController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-supplier', ['only' => ['index']]);
        $this->middleware('permission:tambah-supplier', ['only' => ['create','store']]);
        $this->middleware('permission:edit-supplier', ['only' => ['update','edit']]);
        $this->middleware('permission:hapus-supplier', ['only' => ['destroy']]);
        $this->middleware('permission:export-supplier', ['only' => ['export']]);
    }

    public function export()
    {
        return Excel::download(new SupplierExport, 'Supplier_be-inventory.xlsx');
    }

    public function index()
    {
        $suppliers = Supplier::All();
        return view('pages.supplier.index', [
            "suppliers" => $suppliers
        ]);
    }

    public function store(Request $request)
    {
        try{
            $validated = $request->validate([
                'nama' => 'nullable',
                'alamat' => 'nullable',
                'telepon' => 'nullable',
                'npwp' => 'nullable',
            ]);
            $supplier = Supplier::create($validated);

            if (!$supplier) {
                return redirect()->back()->with('errors', 'Gagal menambahkan data.');
            }
            LogHelper::success('Berhasil Menambahkan Supplier!');
            return redirect()->route('supplier.index')->with('success', 'Berhasil Menambahkan Supplier!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function update(Request $request, $id)
    {
        try{
            $validated = $request->validate([
                'nama' => 'nullable',
                'alamat' => 'nullable',
                'telepon' => 'nullable',
                'npwp' => 'nullable',
            ]);
            $data = Supplier::find($id);
            $data->nama = $validated['nama'];
            $data->alamat = $validated['alamat'];
            $data->telepon = $validated['telepon'];
            $data->npwp = $validated['npwp'];
            $supplier = $data->save();
            if (!$supplier) {
                return redirect()->back()->with('errors', 'Gagal menambahkan data');
            }
            LogHelper::success('Berhasil Mengubah Supplier!');
            return redirect()->route('supplier.index')->with('success', 'Berhasil Mengubah Supplier');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function destroy(Request $request, $id)
    {
        try{
            $data = Supplier::find($id);
            $supplier = $data->delete();
            if (!$supplier) {
                return redirect()->back()->with('gagal', 'menghapus');
            }
            LogHelper::success('Berhasil Menghapus Supplier!');
            return redirect()->route('supplier.index')->with('success', 'Berhasil Menghapus Supplier!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }
}
