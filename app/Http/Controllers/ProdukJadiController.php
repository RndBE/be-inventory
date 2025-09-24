<?php

namespace App\Http\Controllers;

use App\Helpers\LogHelper;
use App\Models\ProdukJadi;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProdukJadiController extends Controller
{
    public function index()
    {
        return view('pages.produk-jadis.index');
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama_produk' => 'required|string|max:255',
                'sub_solusi' => 'required|string|max:255',
                'kode_bahan' => 'nullable|string|max:255',
                'gambar'     => 'nullable|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
            ], [
                'gambar.image'        => 'File harus berupa gambar.',
                'gambar.mimes'        => 'Format gambar harus jpg, jpeg, png, gif, atau svg.',
                'gambar.max'          => 'Ukuran gambar maksimal 2MB.',
            ]);

            $path = null;
            if ($request->hasFile('gambar')) {
                $extension   = $request->file('gambar')->getClientOriginalExtension();
                $fileName    = Str::slug($validated['nama_produk'], '_') . '_' . time() . '.' . $extension;
                $path        = $request->file('gambar')->storeAs('produk_jadi', $fileName, 'public');
            }

            // Simpan produk jadi
            $produkjadi = ProdukJadi::create([
                'nama_produk' => $validated['nama_produk'],
                'sub_solusi' => $validated['sub_solusi'] ?? null,
                'kode_bahan' => $validated['kode_bahan'] ?? null,
                'gambar'     => $path,
            ]);

            LogHelper::success('Berhasil Menambahkan Produk Jadi!');
            return redirect()->route('produk-jadis.index')
                ->with('success', 'Berhasil Menambahkan Produk Jadi!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'nama_produk' => 'required|string|max:255',
                'sub_solusi'  => 'nullable|string|max:255',
                'kode_bahan'  => 'nullable|string|max:255',
                'gambar'      => 'nullable|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
            ], [
                'nama_produk.required' => 'Nama produk wajib diisi.',
                'gambar.image'         => 'File harus berupa gambar.',
                'gambar.mimes'         => 'Format gambar harus jpg, jpeg, png, gif, atau svg.',
                'gambar.max'           => 'Ukuran gambar maksimal 2MB.',
            ]);

            $produk = ProdukJadi::findOrFail($id);

            // Update file gambar jika ada
           $path = $produk->gambar; // simpan path lama
            if ($request->hasFile('gambar')) {
                // Hapus gambar lama jika ada
                if ($produk->gambar && Storage::disk('public')->exists($produk->gambar)) {
                    Storage::disk('public')->delete($produk->gambar);
                }

                // Ambil nama file asli
                $originalName = $request->file('gambar')->getClientOriginalName();
                $extension    = $request->file('gambar')->getClientOriginalExtension();

                // Buat nama file custom: nama_produk_aslinya.ext
                $fileName = Str::slug($validated['nama_produk'], '_') . '_' . time() . '.' . $extension;

                // Simpan dengan nama custom
                $path = $request->file('gambar')->storeAs('produk_jadi', $fileName, 'public');
            }


            // Update produk jadi
            $produk->update([
                'nama_produk' => $validated['nama_produk'],
                'sub_solusi'  => $validated['sub_solusi'] ?? null,
                'kode_bahan'  => $validated['kode_bahan'] ?? null,
                'gambar'      => $path,
            ]);

            LogHelper::success('Berhasil Mengupdate Produk Jadi!');
            return redirect()->route('produk-jadis.index')
                ->with('success', 'Berhasil Mengupdate Produk Jadi!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat mengupdate data: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $produk = ProdukJadi::findOrFail($id);

            // Hapus gambar dari storage jika ada
            if ($produk->gambar && Storage::disk('public')->exists($produk->gambar)) {
                Storage::disk('public')->delete($produk->gambar);
            }

            // Hapus data produk
            $produk->delete();

            LogHelper::success('Berhasil Menghapus Produk Jadi!');
            return redirect()->route('produk-jadis.index')
                ->with('success', 'Berhasil Menghapus Produk Jadi!');
        } catch (\Exception $e) {
            LogHelper::error($e->getMessage());
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage());
        }
    }


}
