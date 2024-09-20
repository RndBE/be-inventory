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
            'nama_bahan' => 'required|string|max:255',
            'jenis_bahan_id' => 'required|exists:jenis_bahan,id',
            'stok_awal' => 'required|integer',
            'unit_id' => 'required|exists:unit,id',
            'kondisi' => 'required|string|max:100',
            'penempatan' => 'required|string|max:255',
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Generate unique kode_bahan
        $kode_bahan = strtoupper(uniqid());

        // If there's an uploaded file, handle the file upload
        if ($request->hasFile('gambar')) {
            $fileName = time() . '_' . $request->file('gambar')->getClientOriginalName();
            $filePath = $request->file('gambar')->move(public_path('images/bahan'), $fileName);
            $validated['gambar'] = 'bahan/' . $fileName;
        }

        // Add the generated kode_bahan to the validated data
        $validated['kode_bahan'] = $kode_bahan;

        // Create the new Bahan record
        Bahan::create($validated);

        return redirect()->route('bahan.index')->with('success', 'Bahan berhasil ditambahkan.');
    }


    // Menampilkan formulir edit bahan
    public function edit($id)
    {
        $units = Unit::all();
        $jenisBahan = JenisBahan::all();
        $bahan = Bahan::with('jenisBahan', 'dataUnit', 'purchaseDetails')->findOrFail($id);
        $bahan->total_stok = $bahan->purchaseDetails->sum('sisa');
        return view('pages.bahan.edit',
            compact('bahan', 'units', 'jenisBahan')
        );
    }


    // Memproses pembaruan bahan
    public function update(Request $request, $id)
    {
        $bahan = Bahan::findOrFail($id);

        $validated = $request->validate([
            'nama_bahan' => 'required|string|max:255',
            'jenis_bahan_id' => 'required|exists:jenis_bahan,id',
            'unit_id' => 'required|exists:unit,id',
            'kondisi' => 'required|string|max:255',
            'penempatan' => 'required|string|max:255',
            'gambar' => 'nullable|image|max:2048',
        ]);
        // dd($validated);
        // Jika ada file gambar yang di-upload, proses penyimpanan
        if ($request->hasFile('gambar')) {
            // Hapus gambar lama jika ada
            if ($bahan->gambar && file_exists(public_path('images/' . $bahan->gambar))) {
                unlink(public_path('images/' . $bahan->gambar));
            }
            $fileName = time() . '_' . $request->file('gambar')->getClientOriginalName();
            $filePath = $request->file('gambar')->move(public_path('images/bahan'), $fileName);
            $validated['gambar'] = 'bahan/' . $fileName;
        } else {
            // Jika tidak ada gambar baru, pertahankan gambar lama
            $validated['gambar'] = $bahan->gambar;
        }

        $bahan->update($validated);

        return redirect()->route('bahan.index')->with('success', 'Data bahan berhasil diupdate');
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
