<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use App\Models\StokRnd;
use App\Models\BahanKeluar;
use App\Models\StokProduksi;
use Illuminate\Http\Request;
use App\Models\PurchaseDetail;
use App\Models\BahanKeluarDetails;
use Illuminate\Support\Facades\Validator;

class BahanKeluarController extends Controller
{
    public function index()
    {
        // Menampilkan semua transaksi barang masuk
        $bahan_keluars = BahanKeluar::with('bahanKeluarDetails')->get();
        return view('pages.bahan-keluars.index', compact('bahan_keluars'));
    }

    public function create()
    {
        return view('pages.bahan-keluars.create');
    }

    public function store(Request $request)
    {

        //dd($request->all());
        $cartItems = json_decode($request->cartItems, true);
        $validator = Validator::make([
            'tgl_keluar' => $request->tgl_keluar,
            'divisi' => $request->divisi,
            'cartItems' => $cartItems
        ], [
            'tgl_keluar' => 'required|date_format:Y-m-d',
            'divisi' => 'required',
            'cartItems' => 'required|array',
            'cartItems.*.id' => 'required|integer',
            'cartItems.*.qty' => 'required|integer|min:1',
            'cartItems.*.details' => 'required|array',
            'cartItems.*.sub_total' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Generate transaction code
        $kode_transaksi = 'KBK - ' . strtoupper(uniqid());
        $tgl_keluar = $request->tgl_keluar . ' ' . now()->setTimezone('Asia/Jakarta')->format('H:i:s');

        // Save main keluar data
        $bahan_keluar = new BahanKeluar();
        $bahan_keluar->kode_transaksi = $kode_transaksi;
        $bahan_keluar->tgl_keluar = $tgl_keluar;
        $bahan_keluar->divisi = $request->divisi;
        $bahan_keluar->status = 'Belum disetujui';
        $bahan_keluar->save();

        // Group items by bahan_id and aggregate quantities
        $groupedItems = [];
        foreach ($cartItems as $item) {
            if (!isset($groupedItems[$item['id']])) {
                $groupedItems[$item['id']] = [
                    'qty' => 0,
                    'details' => $item['details'], // Assuming you want to keep the same unit price
                    'sub_total' => 0,
                ];
            }
            $groupedItems[$item['id']]['qty'] += $item['qty'];
            $groupedItems[$item['id']]['sub_total'] += $item['sub_total'];
        }

        // Save the details
        foreach ($groupedItems as $bahan_id => $details) {
            BahanKeluarDetails::create([
                'bahan_keluar_id' => $bahan_keluar->id,
                'bahan_id' => $bahan_id,
                'qty' => $details['qty'],
                'details' => json_encode($details['details']), // Save as JSON
                'sub_total' => $details['sub_total'],
            ]);
        }

        return redirect()->back()->with('success', 'Permintaan berhasil ditambahkan!');
    }

    public function show(string $id)
    {
        $bahankeluar = BahanKeluar::with('bahanKeluarDetails.dataBahan.dataUnit')->findOrFail($id); // Mengambil detail pembelian
        return view('pages.bahan-keluars.show', [
            'kode_transaksi' => $bahankeluar->kode_transaksi,
            'tgl_keluar' => $bahankeluar->tgl_keluar,
            'divisi' => $bahankeluar->divisi,
            'bahanKeluarDetails' => $bahankeluar->bahanKeluarDetails,
        ]);
    }

    public function update(Request $request, string $id)
    {
        // Validasi input
        $validated = $request->validate([
            'status' => 'required',
        ]);

        try {
            // Temukan data BahanKeluar berdasarkan ID
            $data = BahanKeluar::find($id);
            // Ambil detail bahan keluar
            $details = BahanKeluarDetails::where('bahan_keluar_id', $id)->get();
            // Cek apakah ada 'sisa' yang <= 0
            foreach ($details as $detail) {
                // Cari berdasarkan bahan_id di tabel purchase_details
                $purchaseDetail = PurchaseDetail::where('bahan_id', $detail->bahan_id)->first();
                if ($purchaseDetail && $purchaseDetail->sisa <= 0) {
                    throw new \Exception('Tidak dapat mengubah status karena sisa bahan sudah habis.');
                }
            }

            // Jika status baru adalah 'Disetujui', lakukan proses pengurangan stok
            if ($validated['status'] === 'Disetujui') {
                $tgl_keluar = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
                $data->tgl_keluar = $tgl_keluar;

                foreach ($details as $detail) {
                    $bahan = Bahan::find($detail->bahan_id);
                    if ($bahan) {
                        // Kurangi sisa di purchase_detail
                        $transactionDetails = json_decode($detail->details, true);
                        if (is_array($transactionDetails)) {
                            foreach ($transactionDetails as $transaksiDetail) {
                                $purchaseDetail = PurchaseDetail::where('bahan_id', $detail->bahan_id)
                                    ->whereHas('purchase', function ($query) use ($transaksiDetail) {
                                        $query->where('kode_transaksi', $transaksiDetail['kode_transaksi']);
                                    })->first();

                                if ($purchaseDetail) {
                                    $purchaseDetail->sisa -= $transaksiDetail['qty'];
                                    if ($purchaseDetail->sisa < 0) {
                                        $purchaseDetail->sisa = 0;
                                    }
                                    $purchaseDetail->save();
                                }
                            }
                        }
                    }
                }
            }
            // Jika status baru adalah 'Ditolak', set tgl_keluar ke null
            if ($validated['status'] === 'Ditolak') {
                $data->tgl_keluar = null;
            }
            // Simpan status dan perubahan hanya jika semua operasi berhasil
            $data->status = $validated['status'];
            $data->save();
        } catch (\Exception $e) {
            // Ambil pesan error dan tentukan kolom yang bermasalah
            $errorMessage = $e->getMessage();

            // Tentukan kolom yang menyebabkan error
            $errorColumn = '';
            if (strpos($errorMessage, 'tgl_keluar') !== false) {
                $errorColumn = 'tgl_keluar';
            } elseif (strpos($errorMessage, 'status') !== false) {
                $errorColumn = 'status';
            } // Anda dapat menambahkan kolom lain yang perlu diperiksa

            // Berikan pesan yang lebih informatif
            return redirect()->back()->with('error', "Terjadi kesalahan pada kolom: $errorColumn. Pesan error: $errorMessage");
        }

        return redirect()->route('bahan-keluars.index')->with('success', 'Status berhasil diubah.');
    }





    public function destroy(string $id)
    {
        // Temukan transaksi pembelian
        $data = BahanKeluar::find($id);

        if (!$data) {
            return redirect()->back()->with('gagal', 'Transaksi tidak ditemukan.');
        }
        $data->delete();

        return redirect()->route('bahan-keluars.index')->with('success', 'Bahan Keluar berhasil dihapus.');
    }
}
