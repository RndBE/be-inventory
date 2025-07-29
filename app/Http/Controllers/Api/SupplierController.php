<?php

namespace App\Http\Controllers\Api;

use Throwable;
use App\Models\Supplier;
use App\Helpers\LogHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SupplierController extends Controller
{
   // ✅ GET Semua Data
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 20);
            $search = $request->input('search');

            $query = Supplier::query();

            if ($search) {
                $query->where('nama', 'LIKE', "%$search%")
                ->orWhere('alamat', 'LIKE', "%$search%")
                ->orWhere('telepon', 'LIKE', "%$search%")
                ->orWhere('npwp', 'LIKE', "%$search%");
            }

            $supplier = $query
                ->orderBy('nama', 'asc') // 🔁 Urutkan berdasarkan abjad
                ->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $supplier,
                'meta' => [
                    'current_page' => $supplier->currentPage(),
                    'last_page' => $supplier->lastPage(),
                    'per_page' => $supplier->perPage(),
                    'total' => $supplier->total(),
                ]
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    // ✅ POST Tambah Data
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama' => 'required|string|max:255',
                'alamat' => 'nullable|string|max:255',
                'telepon' => 'nullable|string|max:255',
                'npwp' => 'nullable|string|max:255',
            ]);

            $supplier = Supplier::create($validated);

            if (!$supplier) {
                LogHelper::error('Gagal menambahkan data supplier.');
                return response()->json([
                    'status' => false,
                    'message' => 'Gagal menambahkan data',
                ], 500);
            }

            LogHelper::success('Berhasil menambahkan data supplier.');

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil ditambahkan',
                'data' => $supplier
            ], 201);

        } catch (\Throwable $e) {
            LogHelper::error('Error menambahkan data supplier: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Gagal menambahkan data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    // ✅ PUT/PATCH Update Data
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'nama' => 'required|string|max:255',
                'alamat' => 'nullable|string|max:255',
                'telepon' => 'nullable|string|max:255',
                'npwp' => 'nullable|string|max:255',
            ]);

            $data = Supplier::find($id);

            if (!$data) {
                LogHelper::error('Gagal mengubah data supplier.');
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            $data->nama = $validated['nama'];
            $data->alamat = $validated['alamat'];
            $data->telepon = $validated['telepon'];
            $data->npwp = $validated['npwp'];
            $data->save();

            LogHelper::success('Berhasil mengubah data supplier.');

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diupdate',
                'data' => $data
            ], 200);
        } catch (\Throwable $e) {
            LogHelper::error('Error mengubah data supplier: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Gagal mengupdate data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ✅ DELETE Hapus Data
    public function destroy($id)
    {
        try {
            $data = Supplier::find($id);

            if (!$data) {
                LogHelper::error('Gagal menghapus data supplier.');
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            $data->delete();

            LogHelper::success('Berhasil menghapus data supplier.');

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil dihapus',
            ], 200);
        } catch (\Throwable $e) {
            LogHelper::error('Error menghapus data supplier: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
