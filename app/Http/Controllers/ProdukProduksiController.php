<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use App\Helpers\LogHelper;
use App\Livewire\BahanCart;
use Illuminate\Http\Request;
use App\Models\ProdukProduksi;
use App\Models\ProdukProduksiDetail;
use Illuminate\Support\Facades\Storage;

class ProdukProduksiController extends Controller
{
    public function index()
    {
        return view('pages.produk-produksis.index');
    }

    public function create()
    {
        $bahans = Bahan::whereHas('jenisBahan', function($query) {
            $query->where('nama', 'Produksi');
        })->get();
        return view('pages.produk-produksis.create', compact('bahans'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'bahan_id' => 'required',
                'cartItems' => 'required|array',
            ], [
                'bahan_id.required' => 'Nama produk wajib diisi.',

                'cartItems.required' => 'Bahan tidak boleh kosong. Anda harus memilih setidaknya satu bahan.',
                'cartItems.array' => 'Bahan harus berupa array yang valid.',
            ]);

            $produkproduksi = ProdukProduksi::create([
                'bahan_id' => $validated['bahan_id'],
            ]);

            if ($request->has('cartItems')) {
                foreach ($request->cartItems as $item) {
                    $item = json_decode($item, true);
                    $quantity = $request->input('jml_bahan.' . $item['id'], 0);
                    ProdukProduksiDetail::create([
                        'produk_produksis_id' => $produkproduksi->id,
                        'bahan_id' => $item['id'],
                        'jml_bahan' => $quantity,
                        'used_materials' => 0,
                    ]);
                }
            }
            session()->forget('cart');
            LogHelper::success('Berhasil Menambahkan Produk Produksi!');
            return redirect()->route('produk-produksis.index')->with('success', 'Berhasil Menambahkan Produk Produksi!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $bahans = Bahan::whereHas('jenisBahan', function($query) {
            $query->where('nama', 'Produksi');
        })->get();
        $produkProduksis = ProdukProduksi::findOrFail($id);
        return view('pages.produk-produksis.edit', [
            'produkProduksis' => $produkProduksis,
            'produkProduksisId' => $id,
            'bahans' => $bahans,
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'bahan_id' => 'required',
                'cartItems' => 'required|array',
            ], [
                'bahan_id.required' => 'Nama produk wajib diisi.',
                'bahan_id.max' => 'Nama produk tidak boleh lebih dari 255 karakter.',

                'cartItems.required' => 'Bahan tidak boleh kosong. Anda harus memilih setidaknya satu bahan.',
                'cartItems.array' => 'Bahan harus berupa array yang valid.',
            ]);
            $produkproduksi = ProdukProduksi::findOrFail($id);

            $produkproduksi->update([
                'bahan_id' => $validated['bahan_id'],
            ]);

            if ($request->has('cartItems')) {
                $produkproduksi->produkProduksiDetails()->delete();

                foreach ($request->cartItems as $item) {
                    $item = json_decode($item, true);
                    $quantity = $request->input('jml_bahan.' . $item['id'], 0);
                    ProdukProduksiDetail::create([
                        'produk_produksis_id' => $produkproduksi->id,
                        'bahan_id' => $item['id'],
                        'jml_bahan' => $quantity,
                        'used_materials' => 0,
                    ]);
                }
            }
            LogHelper::success('Berhasil Mengubah Produk Produksi!');
            return redirect()->back()->with('success', 'Bahan produk berhasil diperbarui.');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage());
        }
    }



    public function destroy($id)
    {
        try {
            $produkproduksi = ProdukProduksi::findOrFail($id);
            $produkproduksi->delete();
            LogHelper::success('Berhasil Menghapus Produk Produksi!');
            return redirect()->route('produk-produksis.index')->with('success', 'Produk berhasil dihapus.');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus produk: ' . $e->getMessage());
        }
    }



}
