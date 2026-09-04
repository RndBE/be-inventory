<?php

namespace App\Livewire;

use App\Models\AuditPerubahanData;
use App\Services\PerbaikanDataService;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tabel jejak perubahan data.
 *
 * Nilai lama dan barunya ditampilkan apa adanya, tanpa diformat ulang. Kalau
 * salah ketiknya justru soal titik, koma, atau nol berlebih, memformat ulang
 * angkanya akan menyembunyikan buktinya — dua nilai yang berbeda bisa tampil
 * sama persis.
 */
class AuditPerubahanDataTable extends Component
{
    use WithPagination;

    public $search = '';

    public $perPage = 25;

    public $filterJenis = '';

    public $dariTanggal = '';

    public $sampaiTanggal = '';

    protected $paginationTheme = 'tailwind';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterJenis()
    {
        $this->resetPage();
    }

    public function updatedDariTanggal()
    {
        $this->resetPage();
    }

    public function updatedSampaiTanggal()
    {
        $this->resetPage();
    }

    public function render(PerbaikanDataService $perbaikan)
    {
        $modulPerJenis = $perbaikan->modulPerJenis();

        $audit = AuditPerubahanData::with(['pengaju', 'approver', 'perbaikanData'])
            ->when($this->filterJenis !== '', fn ($query) => $query->whereIn(
                'modul',
                // Sengaja tidak menyaring lewat jenis pada tiket pengajuannya.
                // Satu baris audit bisa berdiri tanpa tiket, dan baris seperti
                // itu akan hilang dari setiap pilihan jenis kalau penyaringannya
                // butuh join. Slug modulnya sendiri sudah ada di baris ini, dan
                // config yang memetakannya ke jenis — jadi pemetaan lokal ini
                // menjawab pertanyaan yang sama tanpa lubang.
                $modulPerJenis[$this->filterJenis] ?? ['__tidak_ada__']
            ))
            // Rentang tanggal memakai whereDate supaya batas atasnya ikut
            // seharian penuh: pembanding datetime biasa akan memotong di
            // 00:00 dan menyembunyikan koreksi yang terjadi pada hari itu.
            ->when($this->dariTanggal !== '', fn ($query) => $query->whereDate('created_at', '>=', $this->dariTanggal))
            ->when($this->sampaiTanggal !== '', fn ($query) => $query->whereDate('created_at', '<=', $this->sampaiTanggal))
            ->when($this->search !== '', function ($query) {
                $cari = '%' . $this->search . '%';

                $query->where(function ($sub) use ($cari) {
                    $sub->where('field', 'like', $cari)
                        ->orWhere('nilai_lama', 'like', $cari)
                        ->orWhere('nilai_baru', 'like', $cari)
                        ->orWhere('alasan', 'like', $cari)
                        ->orWhere('modul_id', 'like', $cari)
                        ->orWhereHas('pengaju', fn ($user) => $user->where('name', 'like', $cari));
                });
            })
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.audit-perubahan-data-table', [
            'auditList' => $audit,
            'daftarJenis' => $this->jenisYangPernahAda($modulPerJenis),
        ]);
    }

    /**
     * Jenis pengajuan yang benar-benar punya baris di tabel ini.
     *
     * Dropdown yang diisi dari config akan menawarkan seluruh 19 jenis padahal
     * sebagian besar belum pernah punya koreksi satu pun. Pilihan yang pasti
     * menghasilkan tabel kosong lebih menyesatkan daripada tidak ditawarkan:
     * pembacanya tidak bisa membedakan "belum pernah ada" dari "filternya
     * rusak". Daftar ini tumbuh sendiri begitu sebuah modul mendapat koreksi
     * pertamanya.
     *
     * Modul yang slug-nya tidak dikenali config — misalnya sisa modul yang
     * sudah dihapus — tidak memunculkan jenis apa pun, jadi barisnya hanya
     * terlihat saat filternya kosong. Itu disengaja: menaruhnya di bawah label
     * karangan lebih buruk daripada membiarkannya di daftar penuh.
     *
     * @param  array<string, array<int, string>>  $modulPerJenis
     * @return array<int, string>
     */
    private function jenisYangPernahAda(array $modulPerJenis): array
    {
        $terpakai = AuditPerubahanData::query()
            ->distinct()
            ->pluck('modul')
            ->all();

        return collect($modulPerJenis)
            ->filter(fn (array $modul) => array_intersect($modul, $terpakai) !== [])
            ->keys()
            ->all();
    }
}
