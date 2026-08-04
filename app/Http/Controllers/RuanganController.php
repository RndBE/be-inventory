<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Ruangan;
use App\Helpers\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RuanganController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lihat-ruangan', ['only' => ['index']]);
        $this->middleware('permission:tambah-ruangan', ['only' => ['create','store']]);
        $this->middleware('permission:edit-ruangan', ['only' => ['update','edit']]);
        $this->middleware('permission:hapus-ruangan', ['only' => ['destroy']]);
    }

    public function index()
    {
        return view('pages.ruangan.index');
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'kode_ruangan' => 'required|string|max:255|unique:ruangan,kode_ruangan',
                'nama_ruangan' => 'required|string|max:255',
                'keterangan' => 'nullable|string|max:255',
            ]);

            Ruangan::create($validated);
            LogHelper::success('Berhasil Menambah Ruangan!');
            return redirect()->route('ruangan.index')->with('success', 'Berhasil Menambah Ruangan!');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Throwable $e) {
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $ruangan = Ruangan::findOrFail($id);

            $validated = $request->validate([
                'kode_ruangan' => 'required|string|max:255|unique:ruangan,kode_ruangan,'. $id,
                'nama_ruangan' => 'required|string|max:255',
                'keterangan' => 'nullable|string|max:255',
            ]);

            $ruangan->update($validated);
            LogHelper::success('Berhasil Mengubah Ruangan!');
            return redirect()->back()->with('success', 'Berhasil Mengubah Ruangan!');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Throwable $e) {
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }

    public function destroy($id)
    {
        try {
            $ruangan = Ruangan::findOrFail($id);

            if ($ruangan->rekapAsets()->exists()) {
                return redirect()->route('ruangan.index')
                    ->with('error', 'Ruangan tidak dapat dihapus karena masih dipakai oleh data aset!');
            }

            $ruangan->delete();
            LogHelper::success('Berhasil Menghapus Ruangan!');
            return redirect()->route('ruangan.index')->with('success', 'Berhasil Menghapus Ruangan!');
        } catch (Throwable $e) {
            LogHelper::error($e->getMessage());
            return view('pages.utility.404');
        }
    }
}
