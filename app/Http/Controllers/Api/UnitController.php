<?php

namespace App\Http\Controllers\Api;

use Throwable;
use App\Models\Unit;
use App\Helpers\LogHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UnitController extends Controller
{
    // ✅ GET Semua Data
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 20);
            $search = $request->input('search');

            $query = Unit::query();

            if ($search) {
                $query->where('nama', 'LIKE', "%$search%");
            }

            $unit = $query
                ->orderBy('nama', 'asc') // 🔁 Urutkan berdasarkan abjad
                ->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $unit,
                'meta' => [
                    'current_page' => $unit->currentPage(),
                    'last_page' => $unit->lastPage(),
                    'per_page' => $unit->perPage(),
                    'total' => $unit->total(),
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

            $unit = Unit::create($validated);

            if (!$unit) {
                LogHelper::error('Gagal menambahkan data satuan unit.');
                return response()->json([
                    'status' => false,
                    'message' => 'Gagal menambahkan data',
                ], 500);
            }

            LogHelper::success('Berhasil menambahkan data satuan unit.');

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil ditambahkan',
                'data' => $unit
            ], 201);

        } catch (\Throwable $e) {
            LogHelper::error('Error menambahkan data satuan unit: ' . $e->getMessage());

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

            $data = Unit::find($id);

            if (!$data) {
                LogHelper::error('Gagal mengubah data satuan unit.');
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            $data->nama = $validated['nama'];
            $data->save();

            LogHelper::success('Berhasil mengubah data satuan unit.');

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diupdate',
                'data' => $data
            ], 200);
        } catch (\Throwable $e) {
            LogHelper::error('Error mengubah data satuan unit: ' . $e->getMessage());

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
            $data = Unit::find($id);

            if (!$data) {
                LogHelper::error('Gagal menghapus data satuan unit.');
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            $data->delete();

            LogHelper::success('Berhasil menghapus data satuan unit.');

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil dihapus',
            ], 200);
        } catch (\Throwable $e) {
            LogHelper::error('Error menghapus data satuan unit: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
