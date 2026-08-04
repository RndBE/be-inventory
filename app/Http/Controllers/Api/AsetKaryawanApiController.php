<?php

namespace App\Http\Controllers\Api;

use App\Models\RekapAset;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Models\PeminjamanAsetDetails;
use Illuminate\Http\Request;

/**
 * Data aset yang sedang dipegang karyawan, untuk ditampilkan di halaman
 * detail karyawan pada HRIS.
 *
 * Inventory tetap jadi satu-satunya pemilik data aset — HRIS memanggil ke sini
 * setiap kali halaman dibuka, tidak menyalin datanya, supaya tidak pernah basi.
 *
 * Yang dilaporkan mencakup dua bentuk kepemilikan, dibedakan lewat field `sumber`:
 *
 *   Peminjaman - pinjaman sementara yang sudah keluar dan belum kembali
 *   PIC        - penugasan tetap lewat rekap aset, tanpa peminjaman
 *
 * Keduanya sama-sama aset yang secara fisik dibawa karyawan, jadi keduanya perlu
 * tampil di halaman detail karyawan. Sebelumnya hanya peminjaman yang dihitung,
 * sehingga aset yang ditugaskan tetap tidak pernah muncul di HRIS.
 */
class AsetKaryawanApiController extends Controller
{
    /**
     * Aset yang sedang dipegang seorang karyawan.
     * GET /api/hris/aset-karyawan?email=budi@bejogja.com
     *
     * Peminjaman yang dihitung hanya yang asetnya benar-benar sudah keluar:
     * sudah disetujui General Affair DAN diketahui HRD. Pengajuan yang masih
     * menggantung di rantai approval tidak dihitung karena asetnya belum
     * berpindah tangan.
     */
    public function byEmail(Request $request)
    {
        $email = trim((string) $request->query('email'));

        if ($email === '') {
            return response()->json([
                'success' => false,
                'message' => 'Parameter email wajib diisi.',
            ], 422);
        }

        $user = User::with(['dataJobPosition', 'dataOrganization'])
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan dengan email tersebut tidak terdaftar di inventory.',
            ], 404);
        }

        $dariPeminjaman = PeminjamanAsetDetails::with([
                'dataAset.barangAset',
                'dataAset.dataRuangan',
                'peminjamanAset',
            ])
            ->where('status_pengembalian', 'Belum dikembalikan')
            ->whereHas('peminjamanAset', function ($query) use ($user) {
                $query->where('pengaju', $user->id)->bolehDikeluarkan();
            })
            ->get()
            ->map(function ($detail) {
                $peminjaman = $detail->peminjamanAset;

                return [
                    'rekap_aset_id' => $detail->rekap_aset_id,
                    'sumber'        => 'Peminjaman',
                    'nomor_aset'    => $detail->dataAset->nomor_aset ?? null,
                    'nama_barang'   => $detail->dataAset->barangAset->nama_barang ?? null,
                    'serial_number' => $detail->dataAset->serial_number ?? null,
                    'kondisi'       => $detail->dataAset->kondisi ?? null,
                    'ruangan'       => $detail->dataAset->dataRuangan->nama_ruangan ?? null,
                    'jumlah'        => $detail->jumlah,
                    'peminjaman'    => [
                        'kode'              => $peminjaman->kode_peminjaman,
                        'tgl_pinjam'        => $peminjaman->tgl_pinjam,
                        'keperluan'         => $peminjaman->keperluan,
                        'divisi'            => $peminjaman->divisi,
                        'lama_dipinjam_hari' => $peminjaman->lama_dipinjam,
                    ],
                ];
            })
            ->values();

        // Penugasan tetap lewat rekap aset. Aset yang sudah terhitung dari peminjaman
        // dikecualikan: peminjaman yang disetujui memang menetapkan pic_id ke peminjam,
        // jadi tanpa pengecualian ini satu aset terhitung dua kali. Baris peminjaman
        // dimenangkan karena keterangannya lebih lengkap.
        $dariPic = RekapAset::with('barangAset', 'dataRuangan')
            ->where('pic_id', $user->id)
            ->whereNotIn('id', $dariPeminjaman->pluck('rekap_aset_id')->filter()->all())
            ->get()
            ->map(fn ($aset) => [
                'rekap_aset_id' => $aset->id,
                'sumber'        => 'PIC',
                'nomor_aset'    => $aset->nomor_aset,
                'nama_barang'   => $aset->barangAset->nama_barang ?? null,
                'serial_number' => $aset->serial_number,
                'kondisi'       => $aset->kondisi,
                'ruangan'       => $aset->dataRuangan->nama_ruangan ?? null,
                'jumlah'        => $aset->jumlah_aset,
                // Bukan pinjaman, jadi tidak ada kode/tanggal pinjam. Dibuat null
                // dan bukan dihilangkan supaya bentuk tiap baris tetap sama.
                'peminjaman'    => null,
            ])
            ->values();

        // Pinjaman lebih dulu: sifatnya sementara dan lebih perlu ditindaklanjuti
        // daripada penugasan tetap.
        $aset = $dariPeminjaman->concat($dariPic)->values();

        return response()->json([
            'success'  => true,
            'karyawan' => [
                'id'           => $user->id,
                'name'         => $user->name,
                'email'        => $user->email,
                'job_position' => $user->dataJobPosition?->nama ?? null,
                'organization' => $user->dataOrganization?->nama ?? null,
            ],
            'total' => $aset->count(),
            // Rincian per sumber supaya HRIS bisa memisahkan tampilannya tanpa
            // harus menghitung ulang dari data.
            'total_peminjaman' => $dariPeminjaman->count(),
            'total_pic' => $dariPic->count(),
            'data'  => $aset,
        ]);
    }
}
