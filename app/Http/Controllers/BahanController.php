<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Unit;
use App\Models\Bahan;
use App\Helpers\LogHelper;
use App\Models\JenisBahan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class BahanController extends Controller
{
    public function index()
    {
        $bahans = Bahan::with('jenisBahan', 'dataUnit')->get();
        return view('pages.bahan.index', compact('bahans'));
    }

    public function create()
    {
        $units = Unit::all();
        $jenisBahan = JenisBahan::all();
        return view('pages.bahan.create', compact('units', 'jenisBahan'));
    }

    public function store(Request $request)
    {
        try{
            $validated = $request->validate([
                'kode_bahan' => 'required|string|max:255|unique:bahan,kode_bahan',
                'nama_bahan' => 'required|string|max:255',
                'jenis_bahan_id' => 'required|exists:jenis_bahan,id',
                'stok_awal' => 'required|integer',
                'unit_id' => 'required|exists:unit,id',
                'penempatan' => 'required|string|max:255',
                'gambar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            if ($request->hasFile('gambar')) {
                $fileName = time() . '_' . $request->file('gambar')->getClientOriginalName();
                $filePath = $request->file('gambar')->storeAs('public/bahan', $fileName);
                $validated['gambar'] = 'bahan/' . $fileName;
            }

            Bahan::create($validated);
            LogHelper::success('Berhasil Menambah Bahan!');
            return redirect()->route('bahan.index')->with('success', 'Berhasil Menambah Bahan!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

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

    public function update(Request $request, $id)
    {
        try{
            $bahan = Bahan::findOrFail($id);

            $validated = $request->validate([
                'kode_bahan' => 'required|string|max:255',
                'nama_bahan' => 'required|string|max:255',
                'jenis_bahan_id' => 'required|exists:jenis_bahan,id',
                'unit_id' => 'required|exists:unit,id',
                'penempatan' => 'required|string|max:255',
                'gambar' => 'nullable|image|max:2048',
            ]);
            if ($request->hasFile('gambar')) {
                // Hapus gambar lama jika ada
                if ($bahan->gambar && Storage::exists('public/' . $bahan->gambar)) {
                    Storage::delete('public/' . $bahan->gambar);
                }
                $fileName = time() . '_' . $request->file('gambar')->getClientOriginalName();
                $filePath = $request->file('gambar')->storeAs('public/bahan', $fileName);
                $validated['gambar'] = 'bahan/' . $fileName;
            } else {
                $validated['gambar'] = $bahan->gambar;
            }
            $bahan->update($validated);
            LogHelper::success('Berhasil Mengubah Bahan!');
            return redirect()->back()->with('success', 'Berhasil Mengubah Bahan!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }


    public function destroy($id)
    {
        try{
            $bahan = Bahan::findOrFail($id);
            if ($bahan->gambar && Storage::exists('public/' . $bahan->gambar)) {
                Storage::delete('public/' . $bahan->gambar);
            }
            $bahan->delete();
            LogHelper::success('Berhasil Menghapus Bahan!');
            return redirect()->route('bahan.index')->with('success', 'Berhasil Menghapus Bahan!');
        }catch(Throwable $e){
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

}
