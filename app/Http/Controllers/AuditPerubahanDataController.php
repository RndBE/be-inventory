<?php

namespace App\Http\Controllers;

/**
 * Halaman jejak perubahan data. Baca saja.
 *
 * Tidak ada aksi tulis di sini dan tidak akan pernah ada: barisnya ditulis oleh
 * PerbaikanDataService saat koreksi dieksekusi, dan AuditPerubahanData menolak
 * update maupun delete. Controller ini hanya membuka halamannya.
 */
class AuditPerubahanDataController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lihat-audit-perubahan-data', ['only' => ['index']]);
    }

    public function index()
    {
        return view('pages.audit-perubahan-data.index');
    }
}
