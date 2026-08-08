<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\HargaModalService;
use Illuminate\Http\Request;

/**
 * Harga modal produk untuk ditampilkan di CRM.
 *
 * Inventory tetap pemilik satu-satunya data harga modal — CRM memanggil endpoint
 * ini tiap halaman dibuka dan tidak menyalin datanya. Harga modal berubah setiap
 * ada produksi baru; salinan yang basi berarti marketing menghitung margin dari
 * angka yang sudah tidak berlaku.
 *
 * Otorisasinya dua lapis dan keduanya wajib:
 *   1. X-API-KEY  — membuktikan pemanggilnya memang CRM
 *   2. permission `lihat-harga-modal` pada user pemilik email
 *
 * Lapis kedua tidak boleh dilewat. Kalau email cuma dipakai untuk memilih data
 * (seperti di endpoint HRIS), siapa pun yang memegang API key bisa mengganti
 * query string ke email lain dan tetap menerima seluruh harga modal.
 */
class HargaModalCrmController extends Controller
{
    public function __construct(private HargaModalService $hargaModal)
    {
    }

    /**
     * GET /api/crm/harga-modal?email=...&hanya_tersedia=1
     */
    public function index(Request $request)
    {
        $user = $this->userBerhak($request);

        if (!$user instanceof User) {
            return $user;
        }

        $hanyaTersedia = $request->boolean('hanya_tersedia');

        // Tab bahan sendirian sudah ~1.800 baris. Tanpa `tab`, CRM terpaksa menarik
        // ketiganya tiap kali halaman dibuka walau yang dilihat cuma satu.
        $diminta = $this->tabDiminta($request);

        if ($diminta === []) {
            return response()->json([
                'success' => false,
                'message' => 'Nilai tab tidak dikenal. Pilih: produk-jadi, setengah-jadi, bahan.',
            ], 422);
        }

        $hasil = [
            'success' => true,
            'diambil_pada' => now('Asia/Jakarta')->toIso8601String(),
            'hanya_tersedia' => $hanyaTersedia,
            'tab' => $diminta,
            'pengguna' => [
                'nama' => $user->name,
                'email' => $user->email,
            ],
        ];

        if (in_array('produk-jadi', $diminta, true)) {
            $produkJadi = $this->hargaModal->produkJadi($hanyaTersedia);
            $hasil['produk_jadi'] = [
                'jumlah_unit' => $produkJadi->count(),
                'ringkasan' => $this->hargaModal->ringkasan($produkJadi),
                'data' => $produkJadi,
            ];
        }

        if (in_array('setengah-jadi', $diminta, true)) {
            $setengahJadi = $this->hargaModal->produkSetengahJadi($hanyaTersedia);
            $hasil['produk_setengah_jadi'] = [
                'jumlah_unit' => $setengahJadi->count(),
                'ringkasan' => $this->hargaModal->ringkasan($setengahJadi),
                'data' => $setengahJadi,
            ];
        }

        if (in_array('bahan', $diminta, true)) {
            $bahan = $this->hargaModal->bahan($hanyaTersedia);
            // Tanpa `ringkasan`: barisnya sudah satu per bahan, jadi ringkasan
            // per nama hanya akan menyalin ulang isinya.
            $hasil['bahan'] = [
                'jumlah_bahan' => $bahan->count(),
                'data' => $bahan,
            ];
        }

        return response()->json($hasil);
    }

    /**
     * GET /api/crm/harga-modal/rincian?email=...&tipe=produk-jadi&produksi_id=34
     *
     * Isi tombol "lihat bahan" pada tab Produk Jadi dan Produk Setengah Jadi.
     */
    public function rincian(Request $request)
    {
        $user = $this->userBerhak($request);

        if (!$user instanceof User) {
            return $user;
        }

        $tipe = trim((string) $request->query('tipe'));
        $produksiId = (int) $request->query('produksi_id');

        if (!in_array($tipe, ['produk-jadi', 'setengah-jadi'], true) || $produksiId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter tipe (produk-jadi|setengah-jadi) dan produksi_id wajib diisi.',
            ], 422);
        }

        $rincian = $this->hargaModal->rincianBahan($tipe, $produksiId);

        if ($rincian === null) {
            return response()->json([
                'success' => false,
                'message' => 'Kode produksi tersebut tidak ditemukan.',
            ], 404);
        }

        return response()->json(['success' => true, 'diambil_pada' => now('Asia/Jakarta')->toIso8601String()] + $rincian);
    }

    /**
     * User pemilik email bila berhak, atau respons penolakan bila tidak.
     *
     * Satu pintu untuk semua endpoint CRM. Rincian bahan membuka biaya per bahan,
     * jadi tidak boleh lebih longgar daripada tab-nya sendiri — dan cara paling
     * aman memastikan itu adalah memakai pemeriksaan yang sama, bukan salinannya.
     *
     * @return User|\Illuminate\Http\JsonResponse
     */
    private function userBerhak(Request $request)
    {
        $email = trim((string) $request->query('email'));

        if ($email === '') {
            return response()->json([
                'success' => false,
                'message' => 'Parameter email wajib diisi.',
            ], 422);
        }

        $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email tersebut tidak terdaftar di inventory.',
            ], 404);
        }

        if (!$user->can('lihat-harga-modal')) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna ini tidak punya akses harga modal.',
            ], 403);
        }

        return $user;
    }

    /**
     * @return array<int, string> daftar tab yang diminta, kosong bila ada yang tidak dikenal
     */
    private function tabDiminta(Request $request): array
    {
        $semua = ['produk-jadi', 'setengah-jadi', 'bahan'];
        $tab = trim((string) $request->query('tab'));

        if ($tab === '') {
            return $semua;
        }

        $diminta = array_values(array_filter(array_map('trim', explode(',', $tab))));

        return array_diff($diminta, $semua) === [] ? $diminta : [];
    }
}
