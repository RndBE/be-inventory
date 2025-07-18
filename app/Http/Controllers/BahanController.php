<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Unit;
use App\Models\Bahan;
use App\Models\Produksi;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Helpers\LogHelper;
use App\Models\JenisBahan;
use App\Models\BahanKeluar;
use Illuminate\Http\Request;
use App\Exports\BahansExport;
use App\Models\ProjekDetails;
use App\Models\ProdukProduksi;
use App\Models\PurchaseDetail;
use App\Models\ProduksiDetails;
use Illuminate\Validation\Rule;
use App\Models\BahanReturDetails;
use App\Models\BahanRusakDetails;
use Illuminate\Http\UploadedFile;
use App\Models\BahanKeluarDetails;
use App\Models\ProdukProduksiDetail;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Models\BahanSetengahjadiDetails;
use Illuminate\Validation\ValidationException;

class BahanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lihat-bahan', ['only' => ['index']]);
        $this->middleware('permission:tambah-bahan', ['only' => ['create','store']]);
        $this->middleware('permission:edit-bahan', ['only' => ['update','edit','updateMultiple','editMultiple']]);
        $this->middleware('permission:hapus-bahan', ['only' => ['destroy']]);
        $this->middleware('permission:export-bahan', ['only' => ['export']]);
    }

    public function export()
    {
        return Excel::download(new BahansExport, 'bahan_be-inventory.xlsx');
    }



    public function index()
    {
        $bahans = Bahan::with('jenisBahan', 'dataUnit')->get();
        return view('pages.bahan.index', compact('bahans'));
    }

    public function create()
    {
        $units = Unit::orderBy('nama', 'asc')->get();
        $suppliers = Supplier::orderBy('nama', 'asc')->get();
        $jenisBahan = JenisBahan::orderBy('nama', 'asc')->get();
        return view('pages.bahan.create', compact('units', 'suppliers', 'jenisBahan'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'kode_bahan' => 'required|string|max:255|unique:bahan,kode_bahan',
                'nama_bahan' => 'required|string|max:255',
                'jenis_bahan_id' => 'required|exists:jenis_bahan,id',
                // 'stok_awal' => 'required|integer',
                'unit_id' => 'required|exists:unit,id',
                'supplier_id' => 'nullable|exists:supplier,id',
                'penempatan' => 'required|string|max:255',
                'gambar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            if ($request->hasFile('gambar')) {
                $fileName = time() . '_' . $request->file('gambar')->getClientOriginalName();
                $filePath = $request->file('gambar')->storeAs('public/bahan', $fileName);
                $validated['gambar'] = 'bahan/' . $fileName;
            }

            Bahan::create($validated);
            LogHelper::success('Berhasil Menambah Bahan!');
            return redirect()->route('bahan.index')->with('success', 'Berhasil Menambah Bahan!');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Throwable $e) {
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function edit($id)
    {
        $units = Unit::orderBy('nama', 'asc')->get();
        $suppliers = Supplier::orderBy('nama', 'asc')->get();
        $jenisBahan = JenisBahan::orderBy('nama', 'asc')->get();
        $bahan = Bahan::with('jenisBahan', 'dataUnit', 'purchaseDetails')->findOrFail($id);
        $bahan->total_stok = $bahan->purchaseDetails->sum('sisa');
        return view('pages.bahan.edit',
            compact('bahan', 'units', 'suppliers', 'jenisBahan')
        );
    }

    public function update(Request $request, $id)
    {
        try{
            // dd(request()->all());
            $bahan = Bahan::findOrFail($id);

            $validated = $request->validate([
                'kode_bahan' => 'required|string|max:255|unique:bahan,kode_bahan,'. $id,
                'nama_bahan' => 'required|string|max:255',
                'jenis_bahan_id' => 'required|exists:jenis_bahan,id',
                'unit_id' => 'required|exists:unit,id',
                'supplier_id' => 'nullable|exists:supplier,id',
                'penempatan' => 'required|string|max:255',
                'gambar' => 'nullable|image|max:2048',
            ]);
            if ($request->hasFile('gambar')) {
                // Hapus gambar lama jika ada
                if ($bahan->gambar && Storage::exists('public/' . $bahan->gambar)) {
                    Storage::delete('public/' . $bahan->gambar);
                }
                $fileName = time() . '_' . $request->file('gambar')->getClientOriginalName();
                $filePath = $request->file('gambar')->storeAs('public/bahan', $fileName);
                $validated['gambar'] = 'bahan/' . $fileName;
            } else {
                $validated['gambar'] = $bahan->gambar;
            }
            $bahan->update($validated);
            LogHelper::success('Berhasil Mengubah Bahan!');
           $page = $request->input('page', 1);
        return redirect()->route('bahan.index', ['page' => $page])
            ->with('success', 'Berhasil Mengubah Bahan!');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function editMultiple(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:bahan,id',
        ]);
        $units = Unit::all();
        $jenisBahan = JenisBahan::all();
        $suppliers = Supplier::all();

        $bahans = Bahan::with('jenisBahan', 'dataUnit', 'purchaseDetails')
            ->whereIn('id', $validated['ids'])
            ->get();

        return view('pages.bahan.editmultiple', compact('bahans', 'units', 'suppliers', 'jenisBahan'));
    }


    public function updateMultiple(Request $request)
    {
        try {
            $validated = $request->validate([
                'bahan' => 'required|array',
                'bahan.*.id' => 'required|exists:bahan,id',
                'bahan.*.kode_bahan' => 'required|string|max:255',
                'bahan.*.nama_bahan' => 'required|string|max:255',
                'bahan.*.jenis_bahan_id' => 'required|exists:jenis_bahan,id',
                'bahan.*.unit_id' => 'required|exists:unit,id',
                'bahan.*.supplier_id' => 'nullable|exists:supplier,id',
                'bahan.*.penempatan' => 'required|string|max:255',
                'bahan.*.gambar' => 'nullable|image|max:2048',
            ]);
            $updatedCount = 0;
            foreach ($validated['bahan'] as $data) {
                $bahan = Bahan::findOrFail($data['id']);

                if (isset($data['gambar']) && $data['gambar'] instanceof UploadedFile) {
                    if ($bahan->gambar && Storage::exists('public/' . $bahan->gambar)) {
                        Storage::delete('public/' . $bahan->gambar);
                    }

                    $fileName = time() . '_' . $data['gambar']->getClientOriginalName();
                    $filePath = $data['gambar']->storeAs('public/bahan', $fileName);
                    $data['gambar'] = 'bahan/' . $fileName;
                } else {
                    $data['gambar'] = $bahan->gambar;
                }

                $bahan->update([
                    'kode_bahan' => $data['kode_bahan'],
                    'nama_bahan' => $data['nama_bahan'],
                    'jenis_bahan_id' => $data['jenis_bahan_id'],
                    'unit_id' => $data['unit_id'],
                    'supplier_id' => $data['supplier_id'],
                    'penempatan' => $data['penempatan'],
                    'gambar' => $data['gambar'],
                ]);

                $updatedCount++;
            }
            LogHelper::success('Berhasil Mengubah Bahan!');
            return redirect()->back()->with('success', "Berhasil mengubah $updatedCount bahan!");
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }


    public function destroy(Request $request, $id)
    {
        try{
            // dd(request()->all());
            $bahan = Bahan::findOrFail($id);
            if ($bahan->gambar && Storage::exists('public/' . $bahan->gambar)) {
                Storage::delete('public/' . $bahan->gambar);
            }
            $bahan->delete();
            LogHelper::success('Berhasil Menghapus Bahan!');
            // return redirect()->route('bahan.index')->with('success', 'Berhasil Menghapus Bahan!');
            $page = $request->input('page', 1);
            return redirect()->route('bahan.index', ['page' => $page])
                ->with('success', 'Berhasil Menghapus Bahan!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

}
