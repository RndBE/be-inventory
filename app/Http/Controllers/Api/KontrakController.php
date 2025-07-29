<?php

namespace App\Http\Controllers\Api;

use Throwable;
use App\Models\Kontrak;
use App\Helpers\LogHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class KontrakController extends Controller
{
    // ✅ GET Semua Data
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 20);
            $search = $request->input('search');

            $query = Kontrak::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama_kontrak', 'LIKE', "%$search%")
                    ->orWhere('kode_kontrak', 'LIKE', "%$search%")
                    ->orWhere('garansi', 'LIKE', "%$search%")
                    ->orWhere('mulai_kontrak', 'LIKE', "%$search%")
                    ->orWhere('selesai_kontrak', 'LIKE', "%$search%");
                });
            }

            $query->orderByRaw("
                CAST(SUBSTRING_INDEX(kode_kontrak, '/', -1) AS UNSIGNED) ASC,
                CAST(SUBSTRING_INDEX(kode_kontrak, '/', 1) AS UNSIGNED) ASC
            ");

            $kontrak = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $kontrak,
                'meta' => [
                    'current_page' => $kontrak->currentPage(),
                    'last_page' => $kontrak->lastPage(),
                    'per_page' => $kontrak->perPage(),
                    'total' => $kontrak->total(),
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
                'nama_kontrak' => 'required|string|max:255',
                'kode_kontrak' => 'required|string|max:255|unique:kontrak,kode_kontrak',
                'garansi' => 'nullable|string|max:255',
                'mulai_kontrak' => 'nullable|max:255',
                'selesai_kontrak' => 'nullable|max:255',
            ]);

            $supplier = Kontrak::create($validated);

            if (!$supplier) {
                LogHelper::error('Gagal menambahkan data kontrak.');
                return response()->json([
                    'status' => false,
                    'message' => 'Gagal menambahkan data',
                ], 500);
            }

            LogHelper::success('Berhasil menambahkan data kontrak.');

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil ditambahkan',
                'data' => $supplier
            ], 201);

        } catch (\Throwable $e) {
            LogHelper::error('Error menambahkan data kontrak: ' . $e->getMessage());

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
                'nama_kontrak' => 'required|string|max:255',
                'kode_kontrak' => 'required|string|max:255|unique:kontrak,kode_kontrak',
                'garansi' => 'nullable|string|max:255',
                'mulai_kontrak' => 'nullable|max:255',
                'selesai_kontrak' => 'nullable|max:255',
            ]);

            $data = Kontrak::find($id);

            if (!$data) {
                LogHelper::error('Gagal mengubah data kontrak.');
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            $data->nama_kontrak = $validated['nama_kontrak'];
            $data->kode_kontrak = $validated['kode_kontrak'];
            $data->garansi = $validated['garansi'];
            $data->mulai_kontrak = $validated['mulai_kontrak'];
            $data->selesai_kontrak = $validated['selesai_kontrak'];
            $data->save();

            LogHelper::success('Berhasil mengubah data kontrak.');

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diupdate',
                'data' => $data
            ], 200);
        } catch (\Throwable $e) {
            LogHelper::error('Error mengubah data kontrak: ' . $e->getMessage());

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
            $data = Kontrak::find($id);

            if (!$data) {
                LogHelper::error('Gagal menghapus data kontrak.');
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            $data->delete();

            LogHelper::success('Berhasil menghapus data kontrak.');

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil dihapus',
            ], 200);
        } catch (\Throwable $e) {
            LogHelper::error('Error menghapus data kontrak: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
