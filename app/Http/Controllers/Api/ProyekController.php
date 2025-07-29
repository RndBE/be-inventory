<?php

namespace App\Http\Controllers\Api;

use App\Models\Purchase;
use Illuminate\Http\Request;
use App\Models\PurchaseDetail;
use App\Exports\PurchasesExport;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Projek;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

class ProyekController extends Controller
{

    // public function export()
    // {
    //     return Excel::download(new BahansExport, 'bahan_be-inventory.xlsx');
    // }

    public function export(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date') . ' 00:00:00';
        $endDate = $request->input('end_date') . ' 23:59:59';

        $companyName = "PT ARTA TEKNOLOGI COMUNINDO";

        return Excel::download(new PurchasesExport($startDate, $endDate, $companyName), 'purchases.xlsx');
    }

    public function index(Request $request)
    {
        $search = $request->query('search'); // Ambil query search dari URL

        $proyeks = Projek::with(['projekDetails', 'bahanKeluar', 'dataKontrak'])->orderBy('id', 'desc')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery->where('mulai_projek', 'like', '%' . $search . '%')
                        ->orWhere('selesai_projek', 'like', '%' . $search . '%')
                        ->orWhere('pengaju', 'like', '%' . $search . '%')
                        ->orWhere('keterangan', 'like', '%' . $search . '%')
                        ->orWhere('status', 'like', '%' . $search . '%')
                        ->orWhere('kode_projek', 'like', '%' . $search . '%')
                        ->orWhereHas('projekDetails.dataKontrak', function ($q) use ($search) {
                            $q->where('nama_projek', 'like', '%' . $search . '%');
                        });
                });
            })
            ->get();

        return response()->json([
            'message' => 'Riwayat proyek',
            'data' => $proyeks,
        ]);
    }


    public function store(Request $request)
    {
        $cartItems = $request->input('cartItems');

        $validator = Validator::make($request->all(), [
            'tgl_masuk' => 'required|date_format:Y-m-d',
            'no_invoice' => 'nullable|string',
            'cartItems' => 'required|array',
            'cartItems.*.id' => 'required|integer',
            'cartItems.*.qty' => 'required|numeric',
            'cartItems.*.unit_price' => 'required|numeric',
            'cartItems.*.sub_total' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $kode_transaksi = 'KBM - ' . strtoupper(uniqid());

            $purchase = Purchase::create([
                'kode_transaksi' => $kode_transaksi,
                'tgl_masuk' => $request->tgl_masuk,
                'no_invoice' => $request->no_invoice,
            ]);

            foreach ($cartItems as $item) {
                PurchaseDetail::create([
                    'purchase_id' => $purchase->id,
                    'bahan_id' => $item['id'],
                    'qty' => $item['qty'],
                    'sisa' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'sub_total' => $item['sub_total'],
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Transaksi bahan masuk berhasil disimpan.'], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan saat menyimpan.', 'error' => $e->getMessage()], 500);
        }
    }
}
