<?php

namespace App\Http\Controllers\Api;

use Throwable;
use App\Helpers\LogHelper;
use App\Models\JenisBahan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class JenisBahanController extends Controller
{
    // ✅ GET Semua Data
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 20);
            $search = $request->input('search');

            $query = JenisBahan::query();

            if ($search) {
                $query->where('nama', 'LIKE', "%$search%");
            }

            $jenisbahan = $query
                ->orderBy('nama', 'asc') // 🔁 Urutkan berdasarkan abjad
                ->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $jenisbahan,
                'meta' => [
                    'current_page' => $jenisbahan->currentPage(),
                    'last_page' => $jenisbahan->lastPage(),
                    'per_page' => $jenisbahan->perPage(),
                    'total' => $jenisbahan->total(),
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
            ]);

            $jenisbahan = JenisBahan::create($validated);

            LogHelper::success('Berhasil menambahkan data jenis bahan.');
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil ditambahkan',
                'data' => $jenisbahan
            ], 201);
        } catch (Throwable $e) {
            LogHelper::error('Error menambahkan data jenis bahan: ' . $e->getMessage());
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
            ]);

            $data = JenisBahan::find($id);

            if (!$data) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            $data->nama = $validated['nama'];
            $data->save();

            LogHelper::success('Berhasil mengubah data jenis bahan.');
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diupdate',
                'data' => $data
            ], 200);
        } catch (Throwable $e) {
            LogHelper::error('Error mengubah data jenis bahan: ' . $e->getMessage());
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
            $data = JenisBahan::find($id);

            if (!$data) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            $data->delete();

            LogHelper::success('Berhasil menghapus data jenis bahan.');
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil dihapus',
            ], 200);
        } catch (Throwable $e) {
            LogHelper::error('Error menghapus data jenis bahan: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
