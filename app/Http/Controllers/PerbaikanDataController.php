<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Helpers\LogHelper;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\LaporanProyek;
use App\Models\PerbaikanData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\LampiranPerbaikanData;
use App\Jobs\SendWhatsAppNotification;
use Illuminate\Support\Facades\Storage;

class PerbaikanDataController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lihat-perbaikan-data', ['only' => ['index']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('pages.perbaikan-data.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // return view('pages.perbaikan-data.create');
        return view('pages.perbaikan-data.create', [
            'perbaikanData' => null,
            'isEdit' => false,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'jenis' => 'required|array',
            'jenis.*' => 'string',
            'form_pengajuan' => 'nullable|file|mimes:pdf|max:5120', // maksimal 5 MB
            'lampiran.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
        ]);

        try {
            DB::beginTransaction();

            // Generate kode pengajuan unik
            $kodePengajuan = 'PD-' . now()->setTimezone('Asia/Jakarta')->format('YmdHis') . '-' . strtoupper(Str::random(4));

            // Upload form pengajuan (jika ada)
            $formPath = null;
            if ($request->hasFile('form_pengajuan')) {
                $file = $request->file('form_pengajuan');
                $fileName = $kodePengajuan . '_form_' . now()->setTimezone('Asia/Jakarta')->format('YmdHis') . '.' . $file->getClientOriginalExtension();
                $formPath = $file->storeAs('form_pengajuan', $fileName, 'public');
            }

            // Simpan ke tabel perbaikan_data
            $perbaikan = PerbaikanData::create([
                'kode_pengajuan' => $kodePengajuan,
                'jenis' => implode(', ', $request->jenis),
                'pengaju' => Auth::user()->name ?? 'Unknown',
                'tgl_pengajuan' => Carbon::now(),
                'form_pengajuan' => $formPath,
                'status' => 'Diajukan',
            ]);

            // Upload dan simpan semua lampiran (jika ada)
            if ($request->hasFile('lampiran')) {
                foreach ($request->file('lampiran') as $file) {
                    $fileName = $kodePengajuan . '_lampiran_' . now()->setTimezone('Asia/Jakarta')->format('YmdHis') . '_' . $file->getClientOriginalName();
                    $lampiranPath = $file->storeAs('lampiran_perbaikan_data', $fileName, 'public');

                    LampiranPerbaikanData::create([
                        'perbaikan_data_id' => $perbaikan->id,
                        'lampiran' => $lampiranPath,
                    ]);
                }
            }

            // Kirim notifikasi ke semua user role "software" dengan job_level = 3
            $softwareUsers = User::role('software')
                ->where('job_level', 3)
                ->whereNotNull('telephone')
                ->get();

            foreach ($softwareUsers as $user) {
                $message = "Halo {$user->name},\n\n";
                $message .= "Terdapat *pengajuan perbaikan data baru* dengan kode: *{$kodePengajuan}*.\n";
                $message .= "Jenis pengajuan: " . implode(', ', $request->jenis) . "\n";
                $message .= "Diajukan oleh: *" . (Auth::user()->name ?? 'Unknown') . "*\n";
                $message .= "Status saat ini: *Diajukan*\n\n";
                $message .= "Silakan cek detail di sistem:\n";
                $message .= "https://inventory.beacontelemetry.com/";

                SendWhatsAppNotification::dispatch($user->telephone, $message, $user->name);
            }

            DB::commit();

            LogHelper::success('Pengajuan perbaikan data berhasil disimpan dan notifikasi dikirim ke user software level 3. Kode: ' . $kodePengajuan);
            return redirect()->back()->with('success', 'Pengajuan perbaikan data berhasil disimpan. Kode: ' . $kodePengajuan);
        } catch (\Exception $e) {
            DB::rollBack();
            LogHelper::error($e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }



    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $perbaikanData = PerbaikanData::with('lampiran')->findOrFail($id);

        return view('pages.perbaikan-data.create', [
            'perbaikanData' => $perbaikanData,
            'isEdit' => true,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Validasi input
        $validated = $request->validate([
            'jenis' => 'required|array',
            'jenis.*' => 'string',
            'form_pengajuan' => 'nullable|file|mimes:pdf|max:5120', // maksimal 5 MB
            'lampiran.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
        ]);

        try {
            $perbaikan = PerbaikanData::with('lampiran')->findOrFail($id);

            // === Update field dasar ===
            $perbaikan->jenis = implode(', ', $request->jenis);
            $perbaikan->pengaju = Auth::user()->name ?? $perbaikan->pengaju;
            $perbaikan->tgl_pengajuan = $perbaikan->tgl_pengajuan ?? now();
            $perbaikan->status = $perbaikan->status ?? 'Menunggu';

            // === Update Form Pengajuan (PDF) ===
            if ($request->hasFile('form_pengajuan')) {
                // Hapus file lama kalau ada
                if ($perbaikan->form_pengajuan && Storage::disk('public')->exists($perbaikan->form_pengajuan)) {
                    Storage::disk('public')->delete($perbaikan->form_pengajuan);
                }

                // Upload baru
                $file = $request->file('form_pengajuan');
                $fileName = $perbaikan->kode_pengajuan . '_form_' . now()->setTimezone('Asia/Jakarta')->format('YmdHis') . '.' . $file->getClientOriginalExtension();
                $formPath = $file->storeAs('form_pengajuan', $fileName, 'public');
                $perbaikan->form_pengajuan = $formPath;
            }

            $perbaikan->save();

            // === Upload Lampiran Baru (kalau ada) ===
            if ($request->hasFile('lampiran')) {
                foreach ($request->file('lampiran') as $file) {
                    $fileName = $perbaikan->kode_pengajuan . '_lampiran_' . now()->setTimezone('Asia/Jakarta')->format('YmdHis') . '_' . $file->getClientOriginalName();
                    $lampiranPath = $file->storeAs('lampiran_perbaikan_data', $fileName, 'public');

                    LampiranPerbaikanData::create([
                        'perbaikan_data_id' => $perbaikan->id,
                        'lampiran' => $lampiranPath,
                    ]);
                }
            }

            $softwareUsers = User::role('software')
                ->where('job_level', 3)
                ->whereNotNull('telephone')
                ->get();

            foreach ($softwareUsers as $user) {
                $message = "Halo {$user->name},\n\n";
                $message .= "Perbaikan data dengan kode " . $perbaikan->kode_pengajuan . " telah diperbarui oleh " . Auth::user()->name . ".";
                $message .= "Silakan cek detail di sistem:\n";
                $message .= "https://inventory.beacontelemetry.com/";

                SendWhatsAppNotification::dispatch($user->telephone, $message, $user->name);
            }

            // === Logging & redirect ===
            LogHelper::success('Perbaikan data berhasil diperbarui. Kode: ' . $perbaikan->kode_pengajuan);
            return redirect()->route('perbaikan-data.index')->with('success', 'Perbaikan data berhasil diperbarui.');

        } catch (\Exception $e) {
            LogHelper::error('Gagal update perbaikan data: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function updateApproval(Request $request, int $id)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'catatan' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $data = PerbaikanData::with(['lampiran'])->findOrFail($id);
            $tgl_diubah = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');

            // Update status dan tanggal perubahan
            $data->status = $validated['status'];
            $data->tgl_diubah = $tgl_diubah;

            // Jika ditolak, tambahkan catatan
            if ($data->status === 'Ditolak') {
                $data->catatan = $validated['catatan'] ?? '-';
            } else {
                $data->catatan = null;
            }

            $data->save();

            // Cari user berdasarkan nama pengaju
            $user = User::where('name', $data->pengaju)->first();

            if ($user && !empty($user->telephone)) {
                $targetPhone = $user->telephone;
                $recipientName = $user->name;

                // Tentukan pesan berdasarkan status
                $statusMessage = match ($data->status) {
                    'Diajukan' => "telah *Diajukan* oleh Administrator.",
                    'Disetujui' => "telah *Disetujui* oleh Administrator.",
                    'Ditolak' => "telah *Ditolak* dengan alasan: {$data->catatan}",
                    'Dalam Pemeriksaan' => "sedang *dalam pemeriksaan* oleh Administrator.",
                    'Sedang Diperbaiki' => "sedang *diperbaiki oleh Administrator*.",
                    'Selesai' => "telah *selesai diproses*.",
                    default => "memiliki status yang tidak dikenal.",
                };

                // Format pesan WA
                $message = "Halo *{$recipientName}*, \n\n";
                $message .= "Pengajuan perbaikan data Anda dengan *Kode Pengajuan* *{$data->kode_pengajuan}* {$statusMessage}\n\n";
                $message .= "Tanggal update: {$tgl_diubah}\n\n";
                $message .= "_Pesan otomatis dari sistem Inventory_\n";
                $message .= "https://inventory.beacontelemetry.com/";

                // Kirim notifikasi via WhatsApp Queue
                SendWhatsAppNotification::dispatch($targetPhone, $message, $recipientName);
            } else {
                LogHelper::error("Tidak ditemukan nomor telepon untuk pengaju {$data->pengaju}");
            }

            DB::commit();

            LogHelper::success('Status approval berhasil diubah.');
            $page = $request->input('page', 1);
            return redirect()
                ->route('perbaikan-data.index', ['page' => $page])
                ->with('success', 'Status approval berhasil diubah.');

        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            LogHelper::error($errorMessage);
            return redirect()->back()->with('error', "Terjadi kesalahan: $errorMessage");
        }
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $perbaikanData = PerbaikanData::with('lampiran')->findOrFail($id);

            // Hapus file form_pengajuan jika ada
            if ($perbaikanData->form_pengajuan && Storage::disk('public')->exists($perbaikanData->form_pengajuan)) {
                Storage::disk('public')->delete($perbaikanData->form_pengajuan);
            }

            // Hapus semua lampiran yang terkait
            if ($perbaikanData->lampiran && $perbaikanData->lampiran->count() > 0) {
                foreach ($perbaikanData->lampiran as $lampiran) {
                    if ($lampiran->lampiran && Storage::disk('public')->exists($lampiran->lampiran)) {
                        Storage::disk('public')->delete($lampiran->lampiran);
                    }
                    $lampiran->delete();
                }
            }

            // Hapus data utama
            $perbaikanData->delete();

            LogHelper::success("Data perbaikan {$perbaikanData->kode_pengajuan} berhasil dihapus.");
            return redirect()->back()->with('success', 'Data perbaikan beserta lampiran berhasil dihapus.');
        } catch (\Exception $e) {
            LogHelper::error('Gagal menghapus data perbaikan: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus data.');
        }
    }
}
