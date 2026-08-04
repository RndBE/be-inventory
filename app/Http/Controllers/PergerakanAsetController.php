<?php

namespace App\Http\Controllers;

/**
 * Halaman pemantauan pergerakan aset.
 *
 * Hanya membaca riwayat_mutasi_aset, jadi tidak ada aksi tulis apa pun di sini
 * dan permission-nya cuma satu. Riwayat itu sendiri diisi otomatis oleh observer
 * di model RekapAset, bukan dari halaman ini.
 */
class PergerakanAsetController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lihat-pergerakan-aset', ['only' => ['index']]);
    }

    public function index()
    {
        return view('pages.pergerakan-aset.index');
    }
}
