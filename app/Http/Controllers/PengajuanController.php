<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Unit;
use App\Models\Bahan;
use App\Models\Produk;
use App\Models\Pengajuan;
use App\Models\BahanJadi;
use App\Helpers\LogHelper;
use App\Models\BahanRetur;
use App\Models\BahanRusak;
use App\Models\BahanKeluar;
use Illuminate\Http\Request;
use App\Exports\PengajuanExport;
use App\Models\PengajuanDetails;
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
use Maatwebsite\Excel\Facades\Excel;
use App\Models\BahanSetengahjadiDetails;
use Illuminate\Support\Facades\Validator;

class PengajuanController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:lihat-pengajuan', ['only' => ['index']]);
        $this->middleware('permission:selesai-pengajuan', ['only' => ['updateStatus']]);
        $this->middleware('permission:tambah-pengajuan', ['only' => ['create','store']]);
        $this->middleware('permission:edit-pengajuan', ['only' => ['update','edit']]);
        $this->middleware('permission:hapus-pengajuan', ['only' => ['destroy']]);
    }

    // public function export($pengajuan_id)
    // {
    //     // Ambil nama proyek berdasarkan `pengajuan_id`
    //     $pengajuan = Pengajuan::findOrFail($pengajuan_id); // Mengambil pengajuan dengan id terkait

    //     // Gunakan nama proyek di nama file ekspor
    //     $fileName = 'HPP_Project_' . $pengajuan->keterangan . '_be-inventory.xlsx';

    //     return Excel::download(new PengajuanExport($pengajuan_id), $fileName);
    // }



    public function index()
    {
        return view('pages.pengajuan.index');
    }

    public function create()
    {
        $units = Unit::all();
        $produkProduksi = ProdukProduksi::all();
        return view('pages.pengajuan.create', compact('units', 'produkProduksi'));
    }

    public function store(Request $request)
    {
        try {
            //dd($request->all());
            $cartItems = json_decode($request->cartItems, true);
            $validator = Validator::make([
                'divisi' => $request->divisi,
                'keterangan' => $request->keterangan,
                'cartItems' => $cartItems
            ], [
                'divisi' => 'required',
                'keterangan' => 'required',
                'cartItems' => 'required|array',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $tujuan = $request->keterangan;

            // Create transaction code for BahanKeluar
            $lastTransaction = BahanKeluar::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
            $new_transaction_number = ($lastTransaction ? intval(substr($lastTransaction->kode_transaksi, 6)) : 0) + 1;
            $kode_transaksi = 'KBK - ' . str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT);
            $tgl_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            // Create transaction code for Pengajuan
            $lastTransactioPengajuan = Pengajuan::orderByRaw('CAST(SUBSTRING(kode_pengajuan, 7) AS UNSIGNED) DESC')->first();
            $new_transaction_number_pengajuan = ($lastTransactioPengajuan ? intval(substr($lastTransactioPengajuan->kode_pengajuan, 6)) : 0) + 1;
            $kode_pengajuan = 'PB - ' . str_pad($new_transaction_number_pengajuan, 5, '0', STR_PAD_LEFT);

            $pengajuan = Pengajuan::create([
                'kode_pengajuan' => $kode_pengajuan,
                'mulai_pengajuan' => $tgl_pengajuan,
                'divisi' => $request->divisi,
                'keterangan' => $request->keterangan,
                'status' => 'Dalam Proses'
            ]);

            $bahan_keluar = BahanKeluar::create([
                'kode_transaksi' => $kode_transaksi,
                'pengajuan_id' => $pengajuan->id,
                'tgl_pengajuan' => $tgl_pengajuan,
                'tujuan' => $tujuan,
                'divisi' => $request->divisi,
                'status' => 'Belum disetujui'
            ]);

            // Group items by bahan_id
            $groupedItems = [];
            foreach ($cartItems as $item) {
                $itemId = $item['id'];
                $groupedItems[$itemId] = [
                    'qty' => 0,
                    'jml_bahan' => 0,
                    'details' => $item['details'] ?? [],
                    'sub_total' => 0,
                ];
                $groupedItems[$item['id']]['qty'] += $item['qty'];
                $groupedItems[$item['id']]['jml_bahan'] += $item['jml_bahan'];
                $groupedItems[$item['id']]['sub_total'] += $item['sub_total'];
            }

            // Save items to BahanKeluarDetails and PengajuanDetails
            foreach ($groupedItems as $bahan_id => $details) {
                BahanKeluarDetails::create([
                    'bahan_keluar_id' => $bahan_keluar->id,
                    'bahan_id' => $bahan_id,
                    'qty' => $details['qty'],
                    'jml_bahan' => $details['jml_bahan'],
                    'used_materials' => 0,
                    'details' => json_encode($details['details']),
                    'sub_total' => $details['sub_total'],
                ]);

            }
            $request->session()->forget('cartItems');
            LogHelper::success('Berhasil Menambahkan Pengajuan Bahan!');
            return redirect()->back()->with('success', 'Berhasil Menambahkan Pengajuan Bahan!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menambahkan data: ' . $e->getMessage());
        }
    }


    public function edit(string $id)
    {
        $units = Unit::all();
        $bahanPengajuan = Bahan::whereHas('jenisBahan', function ($query) {
            $query->where('nama', 'like', '%Produksi%');
        })->get();

        $pengajuan = Pengajuan::with(['pengajuanDetails.dataBahan', 'bahanKeluar'])->findOrFail($id);

        // Ambil bahan yang ada di pengajuanDetails
        $existingBahanIds = $pengajuan->pengajuanDetails->pluck('dataBahan.id')->toArray();

        $isComplete = true;
        if ($pengajuan->pengajuanDetails && count($pengajuan->pengajuanDetails) > 0) {
            foreach ($pengajuan->pengajuanDetails as $detail) {
                $kebutuhan = $detail->jml_bahan - $detail->used_materials;
                if ($kebutuhan > 0) {
                    $isComplete = false;
                    break;
                }
            }
        } else {
            $isComplete = false;
        }

        return view('pages.pengajuan.edit', [
            'pengajuanId' => $id,
            'bahanPengajuan' => $bahanPengajuan,
            'pengajuan' => $pengajuan,
            'units' => $units,
            'existingBahanIds' => $existingBahanIds,
            'isComplete' => $isComplete,
        ]);
    }



    public function update(Request $request, $id)
    {
        try {
            //dd($request->all());
            $pengajuanDetails = json_decode($request->pengajuanDetails, true) ?? [];
            $bahanRusak = json_decode($request->bahanRusak, true) ?? [];
            $bahanRetur = json_decode($request->bahanRetur, true) ?? [];
            $pengajuan = Pengajuan::findOrFail($id);

            $tujuan = $pengajuan->keterangan;

            $lastTransaction = BahanKeluar::orderByRaw('CAST(SUBSTRING(kode_transaksi, 7) AS UNSIGNED) DESC')->first();
            if ($lastTransaction) {
                $last_transaction_number = intval(substr($lastTransaction->kode_transaksi, 6));
            } else {
                $last_transaction_number = 0;
            }
            $new_transaction_number = $last_transaction_number + 1;
            $formatted_number = str_pad($new_transaction_number, 5, '0', STR_PAD_LEFT);
            $kode_transaksi = 'KBK - ' . $formatted_number;
            $tgl_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            // Kelompokkan item berdasarkan bahan_id dan jumlah
            $groupedItems = [];
            $totalQty = 0;  // Variabel untuk menghitung total qty

            foreach ($pengajuanDetails as $item) {
                if (!isset($groupedItems[$item['id']])) {
                    $groupedItems[$item['id']] = [
                        'qty' => 0,
                        'jml_bahan' => 0,
                        'details' => $item['details'],
                        'sub_total' => 0,
                    ];
                }
                $groupedItems[$item['id']]['qty'] += $item['qty'];
                $groupedItems[$item['id']]['jml_bahan'] += $item['jml_bahan'];
                $groupedItems[$item['id']]['sub_total'] += $item['sub_total'];
                $totalQty += $item['qty'];  // Tambahkan qty item ke total qty
            }

            if ($totalQty !== 0) {
                // Simpan data ke Bahan Keluar
                $bahan_keluar = new BahanKeluar();
                $bahan_keluar->kode_transaksi = $kode_transaksi;
                $bahan_keluar->pengajuan_id = $pengajuan->id;
                $bahan_keluar->tgl_pengajuan = $tgl_pengajuan;
                $bahan_keluar->tujuan = $tujuan;
                $bahan_keluar->divisi = $pengajuan->divisi;
                $bahan_keluar->status = 'Belum disetujui';
                $bahan_keluar->save();

                // Simpan data ke Bahan Keluar Detail dan Produksi Detail
                foreach ($groupedItems as $bahan_id => $details) {
                    BahanKeluarDetails::create([
                        'bahan_keluar_id' => $bahan_keluar->id,
                        'bahan_id' => $bahan_id,
                        'qty' => $details['qty'],
                        'jml_bahan' => $details['jml_bahan'],
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
                    'pengajuan_id' => $pengajuan->id,
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
                    'pengajuan_id' => $pengajuan->id,
                    'tujuan' => $tujuan,
                    'divisi' => $pengajuan->divisi,
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

            LogHelper::success('Berhasil Mengubah Detail Pengajuan!');
            return redirect()->back()->with('success', 'Pengajuan berhasil diperbarui!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try{
            $pengajuan = Pengajuan::findOrFail($id);
            //dd($pengajuan);
            $pengajuan->status = 'Selesai';
            $pengajuan->selesai_pengajuan = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            $pengajuan->save();
            LogHelper::success('Berhasil menyelesaikan pengajuan!');
            return redirect()->back()->with('error', 'Pengajuan tidak bisa diupdate ke selesai.');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function destroy(string $id)
    {
        try{
            $pengajuan = Pengajuan::find($id);
            //dd($pengajuan);
            if (!$pengajuan) {
                return redirect()->back()->with('gagal', 'Pengajuan tidak ditemukan.');
            }
            if ($pengajuan->status !== 'Konfirmasi') {
                return redirect()->back()->with('gagal', 'Pengajuan hanya dapat dihapus jika statusnya "Konfirmasi".');
            }
            $bahanKeluar = BahanKeluar::find($pengajuan->bahan_keluar_id);
            $pengajuan->delete();
            if ($bahanKeluar) {
                $bahanKeluar->delete();
            }
            return redirect()->back()->with('success', 'Pengajuan dan bahan keluar terkait berhasil dihapus.');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

}
