<?php

namespace App\Http\Controllers;

use App\Livewire\BahanCart;
use App\Models\Bahan;
use Illuminate\Http\Request;
use App\Models\ProdukProduksi;
use Illuminate\Support\Facades\Storage;
use App\Models\ProdukProduksiDetail;

class ProdukProduksiController extends Controller
{
    public function index()
    {
        return view('pages.produk-produksis.index');
    }

    public function create()
    {
        return view('pages.produk-produksis.create');
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama_produk' => 'required|string|max:255',
                'gambar' => 'required|image|mimes:jpg,jpeg,png|max:2048',
                'cartItems' => 'required|array',
            ], [
                'nama_produk.required' => 'Nama produk wajib diisi.',
                'nama_produk.max' => 'Nama produk tidak boleh lebih dari 255 karakter.',

                'gambar.required' => 'Wajib upload gambar.',
                'gambar.image' => 'Gambar harus berupa file gambar.',
                'gambar.mimes' => 'Gambar harus berformat JPG, JPEG, atau PNG.',
                'gambar.max' => 'Ukuran gambar tidak boleh lebih dari 2MB.',

                'cartItems.required' => 'Bahan tidak boleh kosong. Anda harus memilih setidaknya satu bahan.',
                'cartItems.array' => 'Bahan harus berupa array yang valid.',
            ]);

            // Handle the file upload
            if ($request->hasFile('gambar')) {
                $fileName = time() . '_' . $request->file('gambar')->getClientOriginalName();
                $filePath = $request->file('gambar')->storeAs('public/produk-produksi', $fileName);
                $validated['gambar'] = 'produk-produksi/' . $fileName;
            }

            $produkproduksi = ProdukProduksi::create([
                'nama_produk' => $validated['nama_produk'],
                'gambar' => $validated['gambar'],
            ]);

            if ($request->has('cartItems')) {
                foreach ($request->cartItems as $item) {
                    $item = json_decode($item, true);
                    ProdukProduksiDetail::create([
                        'produk_produksis_id' => $produkproduksi->id,
                        'bahan_id' => $item['id'],
                    ]);
                }
            }
            session()->forget('cart');

            return redirect()->route('produk-produksis.index')->with('success', 'Produk berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $produkProduksis = ProdukProduksi::findOrFail($id);
        return view('pages.produk-produksis.edit', [
            'produkProduksis' => $produkProduksis,
            'produkProduksisId' => $id,
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'nama_produk' => 'required|string|max:255',
                'gambar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'cartItems' => 'required|array',
            ], [
                'nama_produk.required' => 'Nama produk wajib diisi.',
                'nama_produk.max' => 'Nama produk tidak boleh lebih dari 255 karakter.',

                'gambar.image' => 'Gambar harus berupa file gambar.',
                'gambar.mimes' => 'Gambar harus berformat JPG, JPEG, atau PNG.',
                'gambar.max' => 'Ukuran gambar tidak boleh lebih dari 2MB.',

                'cartItems.required' => 'Bahan tidak boleh kosong. Anda harus memilih setidaknya satu bahan.',
                'cartItems.array' => 'Bahan harus berupa array yang valid.',
            ]);
            $produkproduksi = ProdukProduksi::findOrFail($id);
            if ($request->hasFile('gambar')) {
                if ($produkproduksi->gambar && Storage::exists('public/' . $produkproduksi->gambar)) {
                    Storage::delete('public/' . $produkproduksi->gambar);
                }
                $fileName = time() . '_' . $request->file('gambar')->getClientOriginalName();
                $filePath = $request->file('gambar')->storeAs('public/produk-produksi', $fileName);
                $validated['gambar'] = 'produk-produksi/' . $fileName;
            } else {
                $validated['gambar'] = $produkproduksi->gambar;
            }

            $produkproduksi->update([
                'nama_produk' => $validated['nama_produk'],
                'gambar' => $validated['gambar'],
            ]);

            if ($request->has('cartItems')) {
                $produkproduksi->produkProduksiDetails()->delete();

                foreach ($request->cartItems as $item) {
                    $item = json_decode($item, true);
                    ProdukProduksiDetail::create([
                        'produk_produksis_id' => $produkproduksi->id,
                        'bahan_id' => $item['id'],
                    ]);
                }
            }
            return redirect()->back()->with('success', 'Bahan produk berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage());
        }
    }



    public function destroy($id)
    {
        try {
            // Cari data produk berdasarkan ID
            $produkproduksi = ProdukProduksi::findOrFail($id);

            // Hapus file gambar dari storage jika ada
            if ($produkproduksi->gambar && Storage::exists('public/' . $produkproduksi->gambar)) {
                Storage::delete('public/' . $produkproduksi->gambar);
            }

            // Hapus data produk dari database
            $produkproduksi->delete();

            return redirect()->route('produk-produksis.index')->with('success', 'Produk berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus produk: ' . $e->getMessage());
        }
    }



}
