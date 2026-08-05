<?php

namespace App\Http\Controllers;

use App\Exports\BahansExport;
use App\Exports\SaldoPersediaanExport;
use App\Helpers\LogHelper;
use App\Imports\BahanImport;
use App\Models\Bahan;
use App\Models\JenisBahan;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class BahanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lihat-bahan', ['only' => ['index']]);
        // Import dapat menambah sekaligus mengubah data, jadi kedua permission
        // harus lolos. Dua middleware terpisah di sini berlaku sebagai AND.
        $this->middleware('permission:tambah-bahan', ['only' => ['create', 'store', 'import']]);
        $this->middleware('permission:edit-bahan', ['only' => ['update', 'edit', 'updateMultiple', 'editMultiple', 'import']]);
        $this->middleware('permission:hapus-bahan', ['only' => ['destroy']]);
        $this->middleware('permission:export-bahan', ['only' => ['export', 'exportSaldoPersediaan']]);
    }

    public function export()
    {
        return Excel::download(new BahansExport, 'bahan_be-inventory.xlsx');
    }

    /**
     * Tambah atau perbarui bahan berdasarkan Kode Bahan di file import.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ], [
            'file.required' => 'File import wajib dipilih.',
            'file.mimes' => 'File harus berformat xlsx, xls, atau csv.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $import = new BahanImport;

        try {
            $rows = $import->bacaFile($request->file('file')->getRealPath());
            DB::transaction(fn () => $import->prosesBaris($rows));
        } catch (\RuntimeException $e) {
            LogHelper::error('Import bahan gagal: '.$e->getMessage());

            return redirect()->route('bahan.index')->with('error', 'Import gagal. '.$e->getMessage());
        } catch (Throwable $e) {
            LogHelper::error('Import bahan gagal: '.$e->getMessage());

            return redirect()->route('bahan.index')
                ->with('error', 'Import gagal karena file tidak dapat diproses. Periksa format file lalu coba lagi.');
        }

        LogHelper::success('Berhasil import bahan. '.$import->ringkasan());

        return redirect()->route('bahan.index')
            ->with('success', 'Data bahan berhasil di-import. '.$import->ringkasan());
    }

    public function exportSaldoPersediaan(Request $request)
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $filename = sprintf(
            'saldo-persediaan-%s-sd-%s.xlsx',
            $validated['start_date'],
            $validated['end_date']
        );

        return Excel::download(
            new SaldoPersediaanExport($validated['start_date'], $validated['end_date']),
            $filename
        );
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
                'supplier_id' => 'nullable|array',
                'supplier_id.*' => 'exists:supplier,id',
                'penempatan' => 'required|string|max:255',
                'gambar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            if ($request->hasFile('gambar')) {
                $fileName = time().'_'.$request->file('gambar')->getClientOriginalName();
                $filePath = $request->file('gambar')->storeAs('public/bahan', $fileName);
                $validated['gambar'] = 'bahan/'.$fileName;
            }

            $supplier_ids = $validated['supplier_id'] ?? [];
            unset($validated['supplier_id']);

            $bahan = Bahan::create($validated);

            if (! empty($supplier_ids)) {
                $bahan->suppliers()->sync($supplier_ids);
            }
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
        try {
            // dd(request()->all());
            $bahan = Bahan::findOrFail($id);

            $validated = $request->validate([
                'kode_bahan' => 'required|string|max:255|unique:bahan,kode_bahan,'.$id,
                'nama_bahan' => 'required|string|max:255',
                'jenis_bahan_id' => 'required|exists:jenis_bahan,id',
                'unit_id' => 'required|exists:unit,id',
                'supplier_id' => 'nullable|array',
                'supplier_id.*' => 'exists:supplier,id',
                'penempatan' => 'required|string|max:255',
                'gambar' => 'nullable|image|max:2048',
            ]);
            if ($request->hasFile('gambar')) {
                // Hapus gambar lama jika ada
                if ($bahan->gambar && Storage::exists('public/'.$bahan->gambar)) {
                    Storage::delete('public/'.$bahan->gambar);
                }
                $fileName = time().'_'.$request->file('gambar')->getClientOriginalName();
                $filePath = $request->file('gambar')->storeAs('public/bahan', $fileName);
                $validated['gambar'] = 'bahan/'.$fileName;
            } else {
                $validated['gambar'] = $bahan->gambar;
            }

            $supplier_ids = $validated['supplier_id'] ?? [];
            unset($validated['supplier_id']);

            $bahan->update($validated);
            $bahan->suppliers()->sync($supplier_ids);

            LogHelper::success('Berhasil Mengubah Bahan!');
            $page = $request->input('page', 1);

            return redirect()->route('bahan.index', ['page' => $page])
                ->with('success', 'Berhasil Mengubah Bahan!');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Throwable $e) {
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
                'bahan.*.supplier_id' => 'nullable|array',
                'bahan.*.supplier_id.*' => 'exists:supplier,id',
                'bahan.*.penempatan' => 'required|string|max:255',
                'bahan.*.gambar' => 'nullable|image|max:2048',
            ]);
            $updatedCount = 0;
            foreach ($validated['bahan'] as $data) {
                $bahan = Bahan::findOrFail($data['id']);

                if (isset($data['gambar']) && $data['gambar'] instanceof UploadedFile) {
                    if ($bahan->gambar && Storage::exists('public/'.$bahan->gambar)) {
                        Storage::delete('public/'.$bahan->gambar);
                    }

                    $fileName = time().'_'.$data['gambar']->getClientOriginalName();
                    $filePath = $data['gambar']->storeAs('public/bahan', $fileName);
                    $data['gambar'] = 'bahan/'.$fileName;
                } else {
                    $data['gambar'] = $bahan->gambar;
                }

                $supplier_ids = $data['supplier_id'] ?? [];
                unset($data['supplier_id']);

                $bahan->update([
                    'kode_bahan' => $data['kode_bahan'],
                    'nama_bahan' => $data['nama_bahan'],
                    'jenis_bahan_id' => $data['jenis_bahan_id'],
                    'unit_id' => $data['unit_id'],
                    'penempatan' => $data['penempatan'],
                    'gambar' => $data['gambar'],
                ]);

                $bahan->suppliers()->sync($supplier_ids);

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
        try {
            // dd(request()->all());
            $bahan = Bahan::findOrFail($id);
            if ($bahan->gambar && Storage::exists('public/'.$bahan->gambar)) {
                Storage::delete('public/'.$bahan->gambar);
            }
            $bahan->delete();
            LogHelper::success('Berhasil Menghapus Bahan!');
            // return redirect()->route('bahan.index')->with('success', 'Berhasil Menghapus Bahan!');
            $page = $request->input('page', 1);

            return redirect()->route('bahan.index', ['page' => $page])
                ->with('success', 'Berhasil Menghapus Bahan!');
        } catch (Throwable $e) {
            LogHelper::error($e->getMessage());

            return view('pages.utility.404');
        }
    }
}
