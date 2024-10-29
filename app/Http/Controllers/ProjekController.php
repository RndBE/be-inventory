<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Unit;
use App\Models\Bahan;
use App\Models\Produk;
use App\Models\Projek;
use App\Models\BahanJadi;
use App\Helpers\LogHelper;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;
use Illuminate\Http\Request;
use App\Models\ProjekDetails;
use App\Models\DetailProduksi;
use App\Models\ProdukProduksi;
use App\Models\PurchaseDetail;
use App\Models\ProduksiDetails;
use App\Models\BahanJadiDetails;
use App\Models\BahanReturDetails;
use App\Models\BahanRusakDetails;
use App\Models\BahanSetengahjadi;
use App\Models\BahanKeluarDetails;
use Illuminate\Support\Facades\DB;
use App\Models\BahanSetengahjadiDetails;
use Illuminate\Support\Facades\Validator;

class ProjekController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-projek', ['only' => ['index']]);
        $this->middleware('permission:selesai-projek', ['only' => ['updateStatus']]);
        $this->middleware('permission:tambah-projek', ['only' => ['create','store']]);
        $this->middleware('permission:edit-projek', ['only' => ['update','edit']]);
        $this->middleware('permission:hapus-projek', ['only' => ['destroy']]);
    }

    public function index()
    {
        return view('pages.projek.index');
    }

    public function create()
    {
        $units = Unit::all();
        $produkProduksi = ProdukProduksi::all();
        return view('pages.projek.create', compact('units', 'produkProduksi'));
    }

    public function store(Request $request)
    {
        try {

            $cartItems = json_decode($request->cartItems, true);
            $validator = Validator::make([
                'nama_projek' => $request->nama_projek,
                'jml_projek' => $request->jml_projek,
                'mulai_projek' => $request->mulai_projek,
                'cartItems' => $cartItems
            ], [
                'nama_projek' => 'required',
                'jml_projek' => 'required',
                'mulai_projek' => 'required',
                'cartItems' => 'required|array',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $tujuan = $request->nama_projek;

            // Create transaction code for BahanKeluar
            $lastTransaction = BahanKeluar::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
            $new_transaction_number = ($lastTransaction ? intval(substr($lastTransaction->kode_transaksi, 6)) : 0) + 1;
            $kode_transaksi = 'KBK - ' . str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT);
            $tgl_keluar = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            // Create transaction code for Projek
            $lastTransactionProjek = Projek::orderByRaw('CAST(SUBSTRING(kode_projek, 7) AS UNSIGNED) DESC')->first();
            $new_transaction_number_produksi = ($lastTransactionProjek ? intval(substr($lastTransactionProjek->kode_projek, 6)) : 0) + 1;
            $kode_projek = 'PRJ - ' . str_pad($new_transaction_number_produksi, 5, '0', STR_PAD_LEFT);

            $projek = Projek::create([
                'kode_projek' => $kode_projek,
                'nama_projek' => $request->nama_projek,
                'jml_projek' => $request->jml_projek,
                'mulai_projek' => $request->mulai_projek,
                'status' => 'Dalam Proses'
            ]);

            $bahan_keluar = BahanKeluar::create([
                'kode_transaksi' => $kode_transaksi,
                'projek_id' => $projek->id,
                'tgl_keluar' => $tgl_keluar,
                'tujuan' => 'Projek ' . $tujuan,
                'divisi' => 'Produksi',
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
            LogHelper::success('Berhasil Menambahkan Pengajuan Projek!');
            return redirect()->back()->with('success', 'Berhasil Menambahkan Pengajuan Projek!');
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
        $projek = Projek::with(['projekDetails.dataBahan', 'bahanKeluar'])->findOrFail($id);
        // if ($projek->bahanKeluar->status != 'Disetujui') {
        //     return redirect()->back()->with('error', 'Projek belum disetujui. Anda tidak dapat mengakses halaman tersebut.');
        // }
        return view('pages.projek.edit', [
            'projekId' => $projek->id,
            'bahanProjek' => $bahanProjek,
            'projek' => $projek,
            'units' => $units,
            'id' => $id
        ]);
    }


    public function update(Request $request, $id)
    {
        try {
            //dd($request->all());
            $cartItems = json_decode($request->cartItems, true) ?? [];
            $bahanRusak = json_decode($request->bahanRusak, true) ?? [];
            $bahanRetur = json_decode($request->bahanRetur, true) ?? [];
            $projek = Projek::findOrFail($id);

            $tujuan = $projek->nama_projek;

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
                $bahan_keluar->projek_id = $projek->id;
                $bahan_keluar->tgl_keluar = $tgl_keluar;
                $bahan_keluar->tujuan = 'Projek ' . $tujuan;
                $bahan_keluar->divisi = 'Produksi';
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
                    'projek_id' => $projek->id,
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
                    'projek_id' => $projek->id,
                    'tujuan' => 'Projek ' . $tujuan,
                    'divisi' => 'Produksi',
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

            LogHelper::success('Berhasil Mengubah Detail Projek!');
            return redirect()->back()->with('success', 'Projek berhasil diperbarui!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try{
            $projek = Projek::findOrFail($id);
            //dd($projek);
            $projek->status = 'Selesai';
            $projek->selesai_projek = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            $projek->save();
            LogHelper::success('Berhasil menyelesaikan projek Produk Setengah Jadi!');
            return redirect()->back()->with('error', 'Projek tidak bisa diupdate ke selesai.');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function destroy(string $id)
    {
        try{
            $projek = Projek::find($id);
            //dd($projek);
            if (!$projek) {
                return redirect()->back()->with('gagal', 'Projek tidak ditemukan.');
            }
            if ($projek->status !== 'Konfirmasi') {
                return redirect()->back()->with('gagal', 'Projek hanya dapat dihapus jika statusnya "Konfirmasi".');
            }
            $bahanKeluar = BahanKeluar::find($projek->bahan_keluar_id);
            $projek->delete();
            if ($bahanKeluar) {
                $bahanKeluar->delete();
            }
            return redirect()->back()->with('success', 'Projek dan bahan keluar terkait berhasil dihapus.');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

}
