<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Unit;
use App\Helpers\LogHelper;
use App\Models\BarangAset;
use App\Models\JenisBahan;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BarangAsetController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lihat-barang', ['only' => ['index']]);
        $this->middleware('permission:tambah-barang', ['only' => ['create','store']]);
        $this->middleware('permission:edit-barang', ['only' => ['update','edit','updateMultiple','editMultiple']]);
        $this->middleware('permission:hapus-barang', ['only' => ['destroy']]);
        $this->middleware('permission:export-barang', ['only' => ['export']]);
    }

    public function index()
    {
        $barangAsets = BarangAset::with('jenisBahan', 'dataUnit')->get();
        return view('pages.barang_aset.index', compact('barangAsets'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'kode_barang' => 'required|string|max:255|unique:barang_aset,kode_barang',
                'nama_barang' => 'required|string|max:255',
                'jenis_bahan_id' => 'nullable|exists:jenis_bahan,id',
                'unit_id' => 'nullable|exists:unit,id',
            ]);

            BarangAset::create($validated);
            LogHelper::success('Berhasil Menambah Barang Aset!');
            return redirect()->route('barang-aset.index')->with('success', 'Berhasil Menambah Barang Aset!');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Throwable $e) {
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function update(Request $request, $id)
    {
        try{
            $barang_asets = BarangAset::findOrFail($id);

            $validated = $request->validate([
                'kode_barang' => 'required|string|max:255|unique:barang_aset,kode_barang,'. $id,
                'nama_barang' => 'required|string|max:255',
                'jenis_bahan_id' => 'nullable|exists:jenis_bahan,id',
                'unit_id' => 'nullable|exists:unit,id',
            ]);
            $barang_asets->update($validated);
            LogHelper::success('Berhasil Mengubah Barang Aset!');
            return redirect()->back()->with('success', 'Berhasil Mengubah Barang Aset!');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }


    public function destroy($id)
    {
        try{
            $bagan_asets = BarangAset::findOrFail($id);
            $bagan_asets->delete();
            LogHelper::success('Berhasil Menghapus Barang Aset!');
            return redirect()->route('barang-aset.index')->with('success', 'Berhasil Menghapus Barang Aset!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }


}
