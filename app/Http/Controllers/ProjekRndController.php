<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Unit;
use App\Models\Bahan;
use App\Helpers\LogHelper;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;
use Illuminate\Http\Request;
use App\Models\ProdukProduksi;
use App\Models\BahanReturDetails;
use App\Models\BahanRusakDetails;
use App\Models\BahanKeluarDetails;
use App\Models\ProjekRnd;
use Illuminate\Support\Facades\Validator;

class ProjekRndController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lihat-projek-rnd', ['only' => ['index']]);
        $this->middleware('permission:selesai-projek-rnd', ['only' => ['updateStatus']]);
        $this->middleware('permission:tambah-projek-rnd', ['only' => ['create','store']]);
        $this->middleware('permission:edit-projek-rnd', ['only' => ['update','edit']]);
        $this->middleware('permission:hapus-projek-rnd', ['only' => ['destroy']]);
    }

    public function index()
    {
        return view('pages.projek-rnd.index');
    }

    public function create()
    {
        $units = Unit::all();
        $produkProduksi = ProdukProduksi::all();
        return view('pages.projek-rnd.create', compact('units', 'produkProduksi'));
    }

    public function store(Request $request)
    {
        try {
            // dd($request->all());
            $cartItems = json_decode($request->cartItems, true);
            $validator = Validator::make([
                'nama_projek_rnd' => $request->nama_projek_rnd,
                'mulai_projek_rnd' => $request->mulai_projek_rnd,
                'cartItems' => $cartItems
            ], [
                'nama_projek_rnd' => 'required',
                'mulai_projek_rnd' => 'required',
                'cartItems' => 'required|array',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $tujuan = $request->nama_projek_rnd;

            // Create transaction code for BahanKeluar
            $lastTransaction = BahanKeluar::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
            $new_transaction_number = ($lastTransaction ? intval(substr($lastTransaction->kode_transaksi, 6)) : 0) + 1;
            $kode_transaksi = 'KBK - ' . str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT);
            $tgl_keluar = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            // Create transaction code for Projek
            $lastTransactionProjek = ProjekRnd::orderByRaw('CAST(SUBSTRING(kode_projek_rnd, 7) AS UNSIGNED) DESC')->first();
            $new_transaction_number_produksi = ($lastTransactionProjek ? intval(substr($lastTransactionProjek->kode_projek_rnd, 6)) : 0) + 1;
            $kode_projek_rnd = 'PRJ - ' . str_pad($new_transaction_number_produksi, 5, '0', STR_PAD_LEFT);

            $projek_rnd = ProjekRnd::create([
                'kode_projek_rnd' => $kode_projek_rnd,
                'nama_projek_rnd' => $request->nama_projek_rnd,
                'mulai_projek_rnd' => $request->mulai_projek_rnd,
                'status' => 'Dalam Proses'
            ]);

            $bahan_keluar = BahanKeluar::create([
                'kode_transaksi' => $kode_transaksi,
                'projek_rnd_id' => $projek_rnd->id,
                'tgl_keluar' => $tgl_keluar,
                'tujuan' => 'Projek ' . $tujuan,
                'divisi' => 'RnD',
                'status' => 'Belum disetujui'
            ]);

            // Group items by bahan_id
            $groupedItems = [];
            foreach ($cartItems as $item) {
                $itemId = $item['id'];
                $groupedItems[$itemId] = [
                    'qty' => $item['qty'],
                    'details' => $item['details'] ?? [],
                    'sub_total' => $item['sub_total'] ?? 0,
                ];
            }

            // Save items to BahanKeluarDetails and ProjekDetails
            foreach ($groupedItems as $bahan_id => $details) {
                BahanKeluarDetails::create([
                    'bahan_keluar_id' => $bahan_keluar->id,
                    'bahan_id' => $bahan_id,
                    'qty' => $details['qty'],
                    'jml_bahan' => 0,
                    'used_materials' => 0,
                    'details' => json_encode($details['details']),
                    'sub_total' => $details['sub_total'],
                ]);

            }
            $request->session()->forget('cartItems');
            LogHelper::success('Berhasil Menambahkan Pengajuan Projek RnD!');
            return redirect()->back()->with('success', 'Berhasil Menambahkan Pengajuan Projek RnD!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menambahkan data: ' . $e->getMessage());
        }
    }


    public function edit(string $id)
    {
        $units = Unit::all();
        $bahanProjek = Bahan::whereHas('jenisBahan', function ($query) {
            $query->where('nama', 'like', '%Produksi%');
        })->get();
        $projek_rnd = ProjekRnd::with(['projekRndDetails.dataBahan', 'bahanKeluar'])->findOrFail($id);
        // if ($projek_rnd->bahanKeluar->status != 'Disetujui') {
        //     return redirect()->back()->with('error', 'Projek belum disetujui. Anda tidak dapat mengakses halaman tersebut.');
        // }
        return view('pages.projek-rnd.edit', [
            'projekId' => $projek_rnd->id,
            'bahanProjek' => $bahanProjek,
            'projek_rnd' => $projek_rnd,
            'units' => $units,
            'id' => $id
        ]);
    }


    public function update(Request $request, $id)
    {
        try {
            dd($request->all());
            $cartItems = json_decode($request->cartItems, true) ?? [];
            $bahanRusak = json_decode($request->bahanRusak, true) ?? [];
            $bahanRetur = json_decode($request->bahanRetur, true) ?? [];
            $projek_rnd = ProjekRnd::findOrFail($id);

            $tujuan = $projek_rnd->nama_projek_rnd;

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

            // Kelompokkan item berdasarkan bahan_id dan jumlah
            $groupedItems = [];
            $totalQty = 0;  // Variabel untuk menghitung total qty

            foreach ($cartItems as $item) {
                if (!isset($groupedItems[$item['id']])) {
                    $groupedItems[$item['id']] = [
                        'qty' => 0,
                        'details' => $item['details'],
                        'sub_total' => 0,
                    ];
                }
                $groupedItems[$item['id']]['qty'] += $item['qty'];
                $groupedItems[$item['id']]['sub_total'] += $item['sub_total'];
                $totalQty += $item['qty'];  // Tambahkan qty item ke total qty
            }

            if ($totalQty !== 0) {
                // Simpan data ke Bahan Keluar
                $bahan_keluar = new BahanKeluar();
                $bahan_keluar->kode_transaksi = $kode_transaksi;
                $bahan_keluar->projek_rnd_id = $projek_rnd->id;
                $bahan_keluar->tgl_keluar = $tgl_keluar;
                $bahan_keluar->tujuan = 'Projek ' . $tujuan;
                $bahan_keluar->divisi = 'RnD';
                $bahan_keluar->status = 'Belum disetujui';
                $bahan_keluar->save();

                // Simpan data ke Bahan Keluar Detail dan Produksi Detail
                foreach ($groupedItems as $bahan_id => $details) {
                    BahanKeluarDetails::create([
                        'bahan_keluar_id' => $bahan_keluar->id,
                        'bahan_id' => $bahan_id,
                        'qty' => $details['qty'],
                        'used_materials' => 0,
                        'details' => json_encode($details['details']),
                        'sub_total' => $details['sub_total'],
                    ]);
                }
            }

            // Save bahan rusak if available
            if (!empty($bahanRusak)) {
                $lastTransaction = BahanRusak::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
                if ($lastTransaction) {
                    $last_transaction_number = intval(substr($lastTransaction->kode_transaksi, 6));
                } else {
                    $last_transaction_number = 0;
                }
                $new_transaction_number = $last_transaction_number + 1;
                $formatted_number = str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT);
                $kode_transaksi = 'BR - ' . $formatted_number;

                $bahanRusakRecord = BahanRusak::create([
                    'tgl_pengajuan' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'kode_transaksi' => $kode_transaksi,
                    'projek_rnd_id' => $projek_rnd->id,
                    'status' => 'Belum disetujui',
                ]);

                foreach ($bahanRusak as $item) {
                    $bahan_id = $item['id'];
                    $qtyRusak = $item['qty'] ?? 0;
                    $unit_price = $item['unit_price'] ?? 0;
                    $sub_total = $qtyRusak * $unit_price;

                    BahanRusakDetails::create([
                        'bahan_rusak_id' => $bahanRusakRecord->id,
                        'bahan_id' => $bahan_id,
                        'qty' => $qtyRusak,
                        'unit_price' => $unit_price,
                        'sub_total' => $sub_total,
                    ]);
                }
            }

            if (!empty($bahanRetur)) {
                $lastTransaction = BahanRetur::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
                if ($lastTransaction) {
                    $last_transaction_number = intval(substr($lastTransaction->kode_transaksi, 6));
                } else {
                    $last_transaction_number = 0;
                }
                $new_transaction_number = $last_transaction_number + 1;
                $formatted_number = str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT);
                $kode_transaksi = 'BR - ' . $formatted_number;

                $bahanReturRecord = BahanRetur::create([
                    'tgl_pengajuan' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'kode_transaksi' => $kode_transaksi,
                    'projek_rnd_id' => $projek_rnd->id,
                    'tujuan' => 'Projek ' . $tujuan,
                    'divisi' => 'RnD',
                    'status' => 'Belum disetujui',
                ]);

                foreach ($bahanRetur as $item) {
                    $bahan_id = $item['id'];
                    $qtyRetur = $item['qty'] ?? 0;
                    $unit_price = $item['unit_price'] ?? 0;
                    $sub_total = $qtyRetur * $unit_price;

                    BahanReturDetails::create([
                        'bahan_retur_id' => $bahanReturRecord->id,
                        'bahan_id' => $bahan_id,
                        'qty' => $qtyRetur,
                        'unit_price' => $unit_price,
                        'sub_total' => $sub_total,
                    ]);
                }
            }

            LogHelper::success('Berhasil Mengubah Detail Projek RnD!');
            return redirect()->back()->with('success', 'Projek RnD berhasil diperbarui!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try{
            $projek_rnd = ProjekRnd::findOrFail($id);
            //dd($projek_rnd);
            $projek_rnd->status = 'Selesai';
            $projek_rnd->selesai_projek_rnd = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            $projek_rnd->save();
            LogHelper::success('Berhasil menyelesaikan projek RnD!');
            return redirect()->back()->with('error', 'Projek RnD tidak bisa diupdate ke selesai.');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function destroy(string $id)
    {
        try{
            $projek_rnd = ProjekRnd::find($id);
            //dd($projek_rnd);
            if (!$projek_rnd) {
                return redirect()->back()->with('gagal', 'Projek RnD tidak ditemukan.');
            }
            if ($projek_rnd->status !== 'Konfirmasi') {
                return redirect()->back()->with('gagal', 'Projek RnD hanya dapat dihapus jika statusnya "Konfirmasi".');
            }
            $bahanKeluar = BahanKeluar::find($projek_rnd->bahan_keluar_id);
            $projek_rnd->delete();
            if ($bahanKeluar) {
                $bahanKeluar->delete();
            }
            return redirect()->back()->with('success', 'Projek terkait berhasil dihapus.');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }
}
