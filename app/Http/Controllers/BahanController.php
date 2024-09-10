<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\Bahan;
use App\Models\JenisBahan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class BahanController extends Controller
{
    // Menampilkan daftar bahan
    public function index()
    {
        $bahans = Bahan::with('jenisBahan', 'dataUnit')->get(); // Mengambil semua data bahan
        return view('pages.bahan.index', compact('bahans'));
    }

    public function create()
    {
        $units = Unit::all();
        $jenisBahan = JenisBahan::all();
        return view('pages.bahan.create', compact('units', 'jenisBahan'));
    }

    // Menyimpan data bahan baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_bahan' => 'required|unique:bahan',
            'nama_bahan' => 'required|string|max:255',
            'jenis_bahan_id' => 'required|exists:jenis_bahan,id',
            'stok_awal' => 'required|integer',
            'total_stok' => 'nullable|integer',
            'unit_id' => 'required|exists:unit,id',
            'kondisi' => 'required|string|max:100',
            'penempatan' => 'required|string|max:255',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png',
        ]);
        // dd($request->all());
        // Upload gambar jika ada
        if ($request->hasFile('gambar')) {
            $filePath = $request->file('gambar')->store('uploads/bahan', 'public');
            $validated['gambar'] = $filePath;
        }

        Bahan::create($validated);

        return redirect()->route('bahan.index')->with('success', 'Bahan berhasil ditambahkan.');
    }

    // Menampilkan formulir edit bahan
    public function edit($id)
    {
        $units = Unit::all();
        $jenisBahan = JenisBahan::all();
        $bahan = Bahan::findOrFail($id);
        return view('pages.bahan.edit', compact('bahan','units','jenisBahan'));
    }

    // Memproses pembaruan bahan
    public function update(Request $request, $id)
    {
        $bahan = Bahan::findOrFail($id);

        $validated = $request->validate([
            'kode_bahan' => ['required', Rule::unique('bahan')->ignore($bahan->id)],
            'nama_bahan' => 'required|string|max:255',
            'jenis_bahan_id' => 'required|exists:jenis_bahan,id',
            'stok_awal' => 'required|integer',
            'total_stok' => 'required|integer',
            'unit_id' => 'required|exists:unit,id',
            'kondisi' => 'required|string|max:255',
            'penempatan' => 'required|string|max:255',
            'gambar' => 'nullable|image|max:2048',
        ]);
        // dd($validated);

        if ($request->hasFile('gambar')) {
            // Hapus gambar lama jika ada
            if ($bahan->gambar) {
                Storage::delete('public/' . $bahan->gambar);
            }
            // Simpan gambar baru


            $imagePath = $request->file('gambar')->store('images', 'public');
            $validated['gambar'] = $imagePath;
        }

        $bahan->update($validated);

        return redirect()->route('bahan.index')->with('success', 'Data bahan berhasil diupdate');
    }


    public function destroy($id)
    {
        $bahan = Bahan::findOrFail($id);

        // Hapus gambar dari storage jika ada
        if ($bahan->gambar && Storage::exists('public/' . $bahan->gambar)) {
            Storage::delete('public/' . $bahan->gambar);
        }

        // Hapus data dari database
        $bahan->delete();

        return redirect()->route('bahan.index')->with('success', 'Bahan berhasil dihapus.');
    }
}
