<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use App\Models\ProdukProduksi;
use Illuminate\Http\Request;

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
        // Validasi input
        $validated = $request->validate([
            'nama_produk' => 'required|string|max:255',
            'gambar' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('gambar')) {
            // Menghasilkan nama file unik
            $fileName = time() . '_' . $request->file('gambar')->getClientOriginalName();
            $filePath = $request->file('gambar')->storeAs('public/produk-produksi', $fileName);
            $validated['gambar'] = 'produk-produksi/' . $fileName;
        }

        $produkproduksi = ProdukProduksi::create($validated);
        if (!$produkproduksi) {
            return redirect()->back()->with('errors', 'Gagal menambahkan data');
        }
        return redirect()->route('produk-produksis.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function destroy($id)
    {
        $bahan = Bahan::findOrFail($id);
        // Hapus gambar dari penyimpanan jika ada
        if ($bahan->gambar && file_exists(public_path('images/' . $bahan->gambar))) {
            unlink(public_path('images/' . $bahan->gambar));
        }
        // Hapus data dari database
        $bahan->delete();

        return redirect()->route('bahan.index')->with('success', 'Bahan berhasil dihapus.');
    }


}
