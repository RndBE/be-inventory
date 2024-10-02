<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use App\Models\Produksi;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;
use Illuminate\Http\Request;
use App\Models\DetailProduksi;
use App\Models\PurchaseDetail;
use App\Models\ProduksiDetails;
use App\Models\BahanRusakDetails;
use App\Models\BahanKeluarDetails;
use Illuminate\Support\Facades\Validator;

class ProduksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('pages.produksis.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.produksis.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $cartItems = json_decode($request->cartItems, true);
        $validator = Validator::make([
            'nama_produk' => $request->nama_produk,
            'jml_produksi' => $request->jml_produksi,
            'mulai_produksi' => $request->mulai_produksi,
            'jenis_produksi' => $request->jenis_produksi,
            'cartItems' => $cartItems
        ], [
            'nama_produk' => 'required',
            'jml_produksi' => 'required',
            'mulai_produksi' => 'required',
            'jenis_produksi' => 'required',
            'cartItems' => 'required|array',
            'cartItems.*.id' => 'required|integer',
            'cartItems.*.qty' => 'required|integer|min:1',
            'cartItems.*.details' => 'required|array',
            'cartItems.*.sub_total' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $lastTransaction = BahanKeluar::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
        if ($lastTransaction) {
            $last_transaction_number = intval(substr($lastTransaction->kode_transaksi, 6));
        } else {
            $last_transaction_number = 0;
        }
        $new_transaction_number = $last_transaction_number + 1;
        $formatted_number = str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT);
        $kode_transaksi = 'KBK - ' . $formatted_number;

        $tgl_keluar = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

        // Save main keluar data
        $bahan_keluar = new BahanKeluar();
        $bahan_keluar->kode_transaksi = $kode_transaksi;
        $bahan_keluar->tgl_keluar = $tgl_keluar;
        $bahan_keluar->divisi = 'Produksi';
        $bahan_keluar->status = 'Belum disetujui';
        $bahan_keluar->save();

        $lastTransactionProduksi = Produksi::orderByRaw('CAST(SUBSTRING(kode_produksi, 7) AS UNSIGNED) DESC')->first();
        if ($lastTransactionProduksi) {
            $last_transaction_number_produksi = intval(substr($lastTransactionProduksi->kode_produksi, 6));
        } else {
            $last_transaction_number_produksi = 0;
        }
        $new_transaction_number_produksi = $last_transaction_number_produksi + 1;
        $formatted_number_produksi = str_pad($new_transaction_number_produksi, 5, '0', STR_PAD_LEFT);
        $kode_produksi = 'PR - ' . $formatted_number_produksi;

        $produksi = new Produksi();
        $produksi->bahan_keluar_id = $bahan_keluar->id;
        $produksi->kode_produksi = $kode_produksi;
        $produksi->nama_produk = $request->nama_produk;
        $produksi->jml_produksi = $request->jml_produksi;
        $produksi->mulai_produksi = $request->mulai_produksi;
        $produksi->jenis_produksi = $request->jenis_produksi;
        $produksi->status = 'Konfirmasi';
        $produksi->save();

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
                'details' => json_encode($details['details']),
                'sub_total' => $details['sub_total'],
            ]);

            ProduksiDetails::create([
                'produksi_id' => $produksi->id,
                'bahan_id' => $bahan_id,
                'qty' => $details['qty'],
                'details' => json_encode($details['details']),
                'sub_total' => $details['sub_total'],
            ]);
        }
        return redirect()->back()->with('success', 'Permintaan berhasil ditambahkan!');
    }

    public function show(string $id)
    {

    }

    public function edit(string $id)
    {
        $produksi = Produksi::with(['produksiDetails.dataBahan', 'bahanKeluar'])->findOrFail($id);
        if ($produksi->bahanKeluar->status != 'Disetujui') {
            return redirect()->back()->with('error', 'Produksi belum disetujui. Anda tidak dapat mengakses halaman tersebut.');
        }

        return view('pages.produksis.edit', [
            'produksiId' => $produksi->id,
            'produksi' => $produksi,
            'id' => $id
        ]);
    }


    public function update(Request $request, $id)
    {
        try {
            //dd($request->all());
            $cartItems = json_decode($request->cartItems, true) ?? [];
            $bahanRusak = json_decode($request->bahanRusak, true) ?? [];

            // Find the existing production entry
            $produksi = Produksi::findOrFail($id);

            // Validate input
            $validator = Validator::make($request->all(), [
                'nama_produk' => 'required',
                'jml_produksi' => 'required',
                'mulai_produksi' => 'required',
                'jenis_produksi' => 'required',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Update production data
            $produksi->update([
                'nama_produk' => $request->nama_produk,
                'jml_produksi' => $request->jml_produksi,
                'mulai_produksi' => $request->mulai_produksi,
                'jenis_produksi' => $request->jenis_produksi,
            ]);

            // Process cartItems if available
            if (!empty($cartItems)) {
                foreach ($cartItems as $item) {
                    $bahan_id = $item['id'];
                    $qty = $item['qty'] ?? 0; // Default to 0 if not set
                    $sub_total = $item['sub_total'] ?? 0; // Default to 0 if not set
                    $details = $item['details'] ?? []; // Default to empty array if not set

                    // Check if there's an existing ProduksiDetails entry for this bahan_id
                    $existingDetail = ProduksiDetails::where('produksi_id', $produksi->id)
                        ->where('bahan_id', $bahan_id)
                        ->first();

                    if ($existingDetail) {
                        // Decode existing details
                        $existingDetailsArray = json_decode($existingDetail->details, true) ?? [];

                        // Initialize total quantity for this detail
                        $totalQty = $existingDetail->qty;

                        // Update quantities for matching kode_transaksi
                        foreach ($details as $newDetail) {
                            $found = false;

                            foreach ($existingDetailsArray as &$existingDetailItem) {
                                if ($existingDetailItem['kode_transaksi'] === $newDetail['kode_transaksi'] && $existingDetailItem['unit_price'] === $newDetail['unit_price']) {
                                    $existingDetailItem['qty'] += $newDetail['qty']; // Increase quantity in details
                                    $found = true;
                                    break;
                                }
                            }

                            // If not found, add as a new entry
                            if (!$found) {
                                $existingDetailsArray[] = $newDetail;
                            }

                            // Add newDetail qty to totalQty
                            $totalQty += $newDetail['qty'];
                        }

                        // Update the existing detail with new quantities
                        $existingDetail->details = json_encode($existingDetailsArray);
                        $existingDetail->qty = $totalQty; // Update the total qty
                        $existingDetail->sub_total += $sub_total; // Update subtotal
                        $existingDetail->save();
                    } else {
                        // If no existing detail, create a new one
                        ProduksiDetails::create([
                            'produksi_id' => $produksi->id,
                            'bahan_id' => $bahan_id,
                            'qty' => $qty, // Set initial qty
                            'details' => json_encode($details),
                            'sub_total' => $sub_total,
                        ]);
                    }
                    foreach ($details as $newDetail) {
                        $purchaseDetail = PurchaseDetail::where('bahan_id', $bahan_id)
                            ->whereHas('purchase', function ($query) use ($newDetail) {
                                $query->where('kode_transaksi', $newDetail['kode_transaksi']);
                            })->first();

                        if ($purchaseDetail) {
                            $purchaseDetail->sisa -= $newDetail['qty'];
                            if ($purchaseDetail->sisa < 0) {
                                $purchaseDetail->sisa = 0;
                            }
                            $purchaseDetail->save();
                        }
                    }
                }
            }

            // Save bahan rusak if available
            if (!empty($bahanRusak)) {
                // Create a new entry in the bahan_rusaks table
                $bahanRusakRecord = BahanRusak::create([
                    'tgl_masuk' => now(),
                    'kode_transaksi' => uniqid('TRX_'), // You can customize the transaction code as needed
                ]);

                foreach ($bahanRusak as $item) {
                    $bahan_id = $item['id'];
                    $qtyRusak = $item['qty'] ?? 0; // Default to 0 if not set
                    $unit_price = $item['unit_price'] ?? 0; // Default to 0 if not set
                    $sub_total = $qtyRusak * $unit_price;

                    // Create entry in the bahan_rusak_details table
                    BahanRusakDetails::create([
                        'bahan_rusak_id' => $bahanRusakRecord->id,
                        'bahan_id' => $bahan_id,
                        'qty' => $qtyRusak,
                        'sisa' => $qtyRusak,
                        'unit_price' => $unit_price,
                        'sub_total' => $sub_total,
                    ]);
                // Update ProduksiDetails by subtracting the qty of rusak
                $produksiDetail = ProduksiDetails::where('produksi_id', $produksi->id)
                    ->where('bahan_id', $bahan_id)
                    ->first();

                if ($produksiDetail) {
                    $produksiDetail->qty -= $qtyRusak; // Reduce the quantity

                    $reducedSubTotal = $qtyRusak * $unit_price;
                    $produksiDetail->sub_total -= $reducedSubTotal;
                    // Decode existing details to update qty
                    $existingDetailsArray = json_decode($produksiDetail->details, true) ?? [];

                    foreach ($existingDetailsArray as &$detail) {
                        if ($detail['unit_price'] === $unit_price) {
                            $detail['qty'] -= $qtyRusak; // Reduce qty based on rusak
                        }
                    }

                    $produksiDetail->details = json_encode($existingDetailsArray); // Update details
                    $produksiDetail->save(); // Save changes

                    // Check if qty becomes zero and delete if necessary
                    if ($produksiDetail->qty <= 0) {
                        $produksiDetail->delete(); // Delete the entry
                    }
                }
            }
        }

            return redirect()->back()->with('success', 'Produksi berhasil diperbarui!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }








    public function updateStatus($id)
    {
        // Temukan produksi berdasarkan id
        $produksi = Produksi::findOrFail($id);
        // Cek apakah status bahan keluar sudah "Disetujui" dan produksi belum selesai
        if ($produksi->bahanKeluar->status === 'Disetujui' && $produksi->status !== 'Selesai') {
            // Update status produksi menjadi "Selesai"
            $produksi->status = 'Selesai';
            // Simpan perubahan
            $produksi->save();
            // Redirect kembali ke halaman produksi dengan pesan sukses
            return redirect()->back()->with('success', 'Produksi telah selesai.');
        }
        // Jika status bahan keluar belum disetujui atau produksi sudah selesai, tampilkan pesan error
        return redirect()->back()->with('error', 'Produksi tidak bisa diupdate ke selesai.');
    }


    public function destroy(string $id)
    {
        $produksi = Produksi::find($id);
        if (!$produksi) {
            return redirect()->back()->with('gagal', 'Produksi tidak ditemukan.');
        }
        if ($produksi->status !== 'Konfirmasi') {
            return redirect()->back()->with('gagal', 'Produksi hanya dapat dihapus jika statusnya "Konfirmasi".');
        }
        $bahanKeluar = BahanKeluar::find($produksi->bahan_keluar_id);
        $produksi->delete();
        if ($bahanKeluar) {
            $bahanKeluar->delete();
        }
        return redirect()->back()->with('success', 'Produksi dan bahan keluar terkait berhasil dihapus.');
    }

}
