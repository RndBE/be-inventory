<?php

namespace App\Http\Controllers\Api;

use App\Models\Unit;
use App\Models\Bahan;
use App\Models\Supplier;
use App\Helpers\LogHelper;
use App\Models\JenisBahan;
use Illuminate\Http\Request;
use App\Exports\BahansExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Resources\BahanResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class BahanApiController extends Controller
{
    public function export()
    {
        return Excel::download(new BahansExport, 'bahan_be-inventory.xlsx');
    }


    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $search = $request->input('search');

        $query = Bahan::with('jenisBahan', 'dataUnit', 'dataSupplier', 'purchaseDetails');

        if ($search) {
            $query->where('nama_bahan', 'LIKE', "%$search%")
                ->orWhere('kode_bahan', 'LIKE', "%$search%")
                ->orWhere('penempatan', 'LIKE', "%$search%")
                ->orWhereHas('jenisBahan', function($q) use ($search) {
                    $q->where('nama', 'LIKE', "%$search%");
                })
                ->orWhereHas('dataUnit', function($q) use ($search) {
                    $q->where('nama', 'LIKE', "%$search%");
                })
                ->orWhereHas('dataSupplier', function($q) use ($search) {
                    $q->where('nama', 'LIKE', "%$search%");
                });
        }

        $bahans = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => BahanResource::collection($bahans),
            'meta' => [
                'current_page' => $bahans->currentPage(),
                'last_page' => $bahans->lastPage(),
                'per_page' => $bahans->perPage(),
                'total' => $bahans->total(),
            ]
        ]);
    }

    public function create()
    {
        try {
            $jenisBahan = JenisBahan::orderBy('nama', 'asc')->get(['id', 'nama']);
            $units = Unit::orderBy('nama', 'asc')->get(['id', 'nama']);
            $suppliers = Supplier::orderBy('nama', 'asc')->get(['id', 'nama']);

            return response()->json([
                'status' => true,
                'message' => 'Berhasil mengambil data opsi',
                'data' => [
                    'jenis_bahan' => $jenisBahan,
                    'unit' => $units,
                    'supplier' => $suppliers,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'kode_bahan' => 'required|string|max:255|unique:bahan,kode_bahan',
                'nama_bahan' => 'required|string|max:255',
                'jenis_bahan_id' => 'required|exists:jenis_bahan,id',
                'unit_id' => 'required|exists:unit,id',
                'supplier_id' => 'nullable|exists:supplier,id',
                'penempatan' => 'required|string|max:255',
                'gambar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            if ($validator->fails()) {
                LogHelper::error('Gagal menambahkan data bahan.' . $validator->errors());
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            // Handle upload gambar
            if ($request->hasFile('gambar')) {
                $fileName = time() . '_' . $request->file('gambar')->getClientOriginalName();
                $request->file('gambar')->storeAs('public/bahan', $fileName);
                $data['gambar'] = 'bahan/' . $fileName;
            }

            $bahan = Bahan::create($data);

            LogHelper::success('Berhasil menambahkan data bahan.');
            return response()->json([
                'status' => true,
                'message' => 'Berhasil menambah bahan',
                'data' => $bahan,
            ], 201);

        } catch (\Throwable $e) {
            LogHelper::error('Error menambahkan data bahan: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function show($id)
    {
        $bahan = Bahan::with('jenisBahan', 'dataUnit', 'dataSupplier', 'purchaseDetails')
                    ->findOrFail($id);

        $bahan->total_stok = $bahan->purchaseDetails->sum('sisa');

        $jenis = JenisBahan::orderBy('nama')->get(['id', 'nama']);
        $unit = Unit::orderBy('nama')->get(['id', 'nama']);
        $supplier = Supplier::orderBy('nama')->get(['id', 'nama']);

        return response()->json([
            'status' => 'success',
            'data' => new BahanResource($bahan),
            'options' => [
                'jenis_bahan' => $jenis,
                'unit' => $unit,
                'supplier' => $supplier,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $bahan = Bahan::findOrFail($id);

            // Validasi
            $validator = Validator::make($request->all(), [
                'kode_bahan' => 'required|string|max:255|unique:bahan,kode_bahan,' . $id,
                'nama_bahan' => 'required|string|max:255',
                'jenis_bahan_id' => 'required|exists:jenis_bahan,id',
                'unit_id' => 'required|exists:unit,id',
                'supplier_id' => 'nullable|exists:supplier,id',
                'penempatan' => 'required|string|max:255',
                'gambar' => 'nullable|image|max:2048', // ✅ Validasi file image
            ]);

            if ($validator->fails()) {
                LogHelper::error('Gagal mengubah data bahan.' . $validator->errors());
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Handle gambar
            if ($request->hasFile('gambar')) {
                if ($bahan->gambar && Storage::exists('public/' . $bahan->gambar)) {
                    Storage::delete('public/' . $bahan->gambar);
                }
                $file = $request->file('gambar');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('public/bahan', $fileName);
                $gambarPath = 'bahan/' . $fileName;
            } else {
                $gambarPath = $bahan->gambar;
            }

            // Update data
            $bahan->update([
                'kode_bahan' => $request->kode_bahan,
                'nama_bahan' => $request->nama_bahan,
                'jenis_bahan_id' => $request->jenis_bahan_id,
                'unit_id' => $request->unit_id,
                'supplier_id' => $request->supplier_id,
                'penempatan' => $request->penempatan,
                'gambar' => $gambarPath,
            ]);

            LogHelper::success('Berhasil mengubah data bahan.');
            return response()->json([
                'status' => true,
                'message' => 'Berhasil update bahan',
                'data' => $bahan,
            ], 200);

        } catch (\Throwable $e) {
            LogHelper::error('Error mengubah data bahan: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $bahan = Bahan::findOrFail($id);

            // Hapus gambar jika ada
            if ($bahan->gambar && Storage::exists('public/' . $bahan->gambar)) {
                Storage::delete('public/' . $bahan->gambar);
            }

            // Hapus data
            $bahan->delete();

            LogHelper::success('Berhasil menhapus data bahan.');
            return response()->json([
                'status' => true,
                'message' => 'Berhasil menghapus bahan',
            ], 200);

        } catch (\Throwable $e) {
            LogHelper::error('Error menghapus data bahan: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus bahan: ' . $e->getMessage(),
            ], 500);
        }
    }

}
