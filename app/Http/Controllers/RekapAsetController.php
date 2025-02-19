<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Unit;
use App\Models\User;
use App\Models\Supplier;
use App\Models\RekapAset;
use App\Helpers\LogHelper;
use App\Models\BarangAset;
use App\Models\JenisBahan;
use Illuminate\Http\Request;
use App\Imports\RekapAsetImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\ValidationException;

class RekapAsetController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lihat-rekap-aset', ['only' => ['index']]);
        $this->middleware('permission:tambah-barang', ['only' => ['create','store']]);
        $this->middleware('permission:edit-barang', ['only' => ['update','edit','updateMultiple','editMultiple']]);
        $this->middleware('permission:hapus-barang', ['only' => ['destroy']]);
        $this->middleware('permission:export-barang', ['only' => ['export']]);
    }

    public function index()
    {
        $rekapgAsets = RekapAset::with('jenisBahan', 'dataUnit')->get();
        return view('pages.rekap_aset.index', compact('rekapgAsets'));
    }

    public function create()
    {
        $units = Unit::all();
        $suppliers = Supplier::all();
        $jenisBahan = JenisBahan::all();
        $barangAset = BarangAset::all();
        $dataUser = User::all();
        return view('pages.rekap_aset.create', compact('units', 'suppliers', 'jenisBahan','barangAset','dataUser'));
    }


    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ], [
            'file.required' => 'File is required.',
            'file.mimes' => 'The file must be a valid Excel or CSV file.',
        ]);

        try {
            Excel::import(new RekapAsetImport, $request->file('file'));

            LogHelper::success('Berhasil Menambah Rekap Aset!');
            return redirect()->route('rekap-aset.index')->with('success', 'Data Rekap Aset berhasil diimport!');
        }catch (\Throwable $e) {
            LogHelper::error($e->getMessage());
            return redirect()->route('rekap-aset.index')->with('error', 'Terjadi kesalahan yang tidak terduga. Silakan coba lagi.');
        }
    }




    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nomor_aset' => 'required|string|max:255|unique:rekap_aset,nomor_aset',
                'barang_aset_id' => 'required|exists:barang_aset,id',
                'link_gambar' => 'nullable|string',
                'tgl_perolehan' => 'nullable',
                'jumlah_aset' => 'nullable',
                'harga_perolehan' => 'nullable',
                'kondisi' => 'nullable',
                'keterangan' => 'nullable',
                'user_id' => 'required|exists:users,id',
            ]);

            RekapAset::create($validated);
            LogHelper::success('Berhasil Menambah Rekap Aset!');
            return redirect()->route('rekap-aset.index')->with('success', 'Berhasil Menambah Rekap Aset!');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Throwable $e) {
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function edit($id)
    {
        $units = Unit::all();
        $suppliers = Supplier::all();
        $jenisBahan = JenisBahan::all();
        $barangAset = BarangAset::all();
        $dataUser = User::all();
        $rekap_aset = RekapAset::with('jenisBahan','barangAset','dataUser')->findOrFail($id);
        return view('pages.rekap_aset.edit',
            compact('rekap_aset','units', 'suppliers', 'jenisBahan','barangAset','dataUser')
        );
    }

    public function update(Request $request, $id)
    {
        try{
            $rekap_aset = RekapAset::findOrFail($id);

            $validated = $request->validate([
                'nomor_aset' => 'required|unique:rekap_aset,nomor_aset,'. $id,
                'barang_aset_id' => 'required|exists:barang_aset,id',
                'link_gambar' => 'nullable|string',
                'tgl_perolehan' => 'nullable',
                'jumlah_aset' => 'nullable',
                'harga_perolehan' => 'nullable',
                'kondisi' => 'nullable',
                'keterangan' => 'nullable',
                'user_id' => 'required|exists:users,id',
            ]);

            $rekap_aset->update($validated);
            LogHelper::success('Berhasil Mengubah Rekap Aset!');
            return redirect()->route('rekap-aset.index')->with('success', 'Berhasil Mengubah Rekap Aset!');
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
            $rekap_aset = RekapAset::findOrFail($id);
            $rekap_aset->delete();
            LogHelper::success('Berhasil Menghapus Rekap Aset');
            return redirect()->route('rekap-aset.index')->with('success', 'Berhasil Menghapus Rekap Aset');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }
}
