<?php

namespace App\Livewire;

use App\Models\AuditPerubahanData;
use App\Services\PerbaikanDataService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tabel jejak perubahan data.
 *
 * Satu baris tabel = satu pencatatan koreksi: waktu, tiket, pengaju, dan
 * approvernya. Perubahan per kolomnya — nilai lama, nilai baru, alasan — ada
 * di popup detail, karena satu tiket bisa melahirkan banyak baris audit dan
 * menjejerkan semuanya di tabel utama membuat pembacanya harus memilah sendiri
 * mana yang satu keputusan.
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

    /** Kunci kelompok yang detailnya sedang dibuka di popup. */
    public ?string $kunciDetail = null;

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

    public function bukaDetail(string $kunci): void
    {
        $this->kunciDetail = $kunci;
    }

    public function tutupDetail(): void
    {
        $this->kunciDetail = null;
    }

    public function render(PerbaikanDataService $perbaikan)
    {
        $modulPerJenis = $perbaikan->modulPerJenis();

        // Dipaginasi per kelompok, bukan per baris audit. Paginasi per baris
        // bisa memotong satu tiket di batas halaman, dan di tabel yang satu
        // barisnya satu tiket, potongan itu tampil sebagai dua pencatatan
        // terpisah yang masing-masing detailnya tidak lengkap.
        //
        // Kolom pelakunya diambil lewat max(): dalam satu kelompok isinya
        // sama, karena kelompoknya memang satu tiket.
        $kelompok = AuditPerubahanData::query()
            ->fromSub($this->barisBerkunci(), 'audit_perubahan_data')
            ->selectRaw(
                'kunci, max(id) as id, min(created_at) as created_at,'
                . ' max(perbaikan_data_id) as perbaikan_data_id,'
                . ' max(pengaju_id) as pengaju_id, max(approver_id) as approver_id,'
                . ' max(disetujui_sendiri) as disetujui_sendiri, count(*) as jumlah'
            )
            ->when($this->adaFilter(), fn ($query) => $query->whereIn('kunci', $this->kunciYangCocok($modulPerJenis)))
            ->groupBy('kunci')
            ->orderByRaw('max(id) desc')
            ->with(['pengaju', 'approver', 'perbaikanData'])
            ->paginate($this->perPage);

        $detail = $this->barisDetail();

        return view('livewire.audit-perubahan-data-table', [
            'kelompokList' => $kelompok,
            'daftarJenis' => $this->jenisYangPernahAda($modulPerJenis),
            'detail' => $detail,
            'runAlasan' => $this->runAlasan($detail),
        ]);
    }

    private function adaFilter(): bool
    {
        return $this->search !== ''
            || $this->filterJenis !== ''
            || $this->dariTanggal !== ''
            || $this->sampaiTanggal !== '';
    }

    /**
     * Kunci kelompok yang punya setidaknya satu baris cocok dengan filter.
     *
     * Filternya memilih kelompok, bukan memangkas isinya. Kalau pencarian
     * "harga" memangkas baris, tiket berisi tiga perubahan akan tampil sebagai
     * "1 perubahan" di tabel lalu memperlihatkan tiga di popup — dua angka
     * yang saling membantah di layar yang sama.
     *
     * @param  array<string, array<int, string>>  $modulPerJenis
     */
    private function kunciYangCocok(array $modulPerJenis): Builder
    {
        return AuditPerubahanData::query()
            ->selectRaw($this->ekspresiKunci())
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
                        ->orWhereHas('pengaju', fn ($user) => $user->where('name', 'like', $cari))
                        ->orWhereHas('approver', fn ($user) => $user->where('name', 'like', $cari))
                        ->orWhereHas('perbaikanData', fn ($tiket) => $tiket->where('kode_pengajuan', 'like', $cari));
                });
            });
    }

    /**
     * Seluruh baris audit, masing-masing dengan kolom `kunci` kelompoknya.
     *
     * Dibungkus sebagai subquery supaya query luarnya bisa GROUP BY kolom
     * biasa. Mengelompokkan langsung lewat ekspresinya ditolak ONLY_FULL_GROUP_BY
     * di MySQL lama, yang menganggap `id` dan `created_at` di dalam ekspresi
     * itu kolom tak teragregasi.
     */
    private function barisBerkunci(): Builder
    {
        return AuditPerubahanData::query()
            ->select('audit_perubahan_data.*')
            ->selectRaw($this->ekspresiKunci() . ' as kunci');
    }

    /**
     * Ekspresi SQL kunci pengelompokan satu baris audit.
     *
     * Satu tiket biasanya melahirkan beberapa baris audit sekaligus — harga
     * satuan dua bahan dan shipping cost transaksinya, misalnya, tercatat pada
     * detik yang sama oleh approver yang sama. Semuanya satu keputusan, jadi
     * semuanya satu baris di tabel.
     *
     * Waktunya ikut jadi kunci, bukan nomor tiketnya saja. Baris yang gagal
     * dicatat bisa dicoba lagi berhari-hari kemudian di bawah tiket yang sama;
     * menggabungkannya dengan pencatatan pertama akan menempelkan satu waktu ke
     * kejadian yang berbeda hari.
     *
     * Baris tanpa tiket selalu berdiri sendiri. Yang menyatukannya cuma
     * kebetulan waktu, dan itu bukan alasan untuk menyatukannya di layar.
     *
     * Dua dialek karena test berjalan di SQLite, sedangkan server memakai MySQL.
     */
    private function ekspresiKunci(): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "case when perbaikan_data_id is null then 'baris-' || id"
                . " else 'tiket-' || perbaikan_data_id || '-' || coalesce(strftime('%Y%m%d%H%M', created_at), '') end";
        }

        return "case when perbaikan_data_id is null then concat('baris-', id)"
            . " else concat('tiket-', perbaikan_data_id, '-', coalesce(date_format(created_at, '%Y%m%d%H%i'), '')) end";
    }

    /**
     * Isi popup detail: semua baris audit di kelompok yang dibuka.
     *
     * Filter tabel tidak ikut diterapkan. Popupnya menjawab "apa saja yang
     * diubah pada pencatatan ini", dan jawaban yang dipangkas pencarian
     * adalah jawaban yang tidak lengkap.
     *
     * @return Collection<int, AuditPerubahanData>
     */
    private function barisDetail(): Collection
    {
        if ($this->kunciDetail === null) {
            return collect();
        }

        return AuditPerubahanData::query()
            ->fromSub($this->barisBerkunci(), 'audit_perubahan_data')
            ->where('kunci', $this->kunciDetail)
            ->with(['pengaju', 'approver', 'perbaikanData'])
            ->orderBy('id')
            ->get();
    }

    /**
     * Deret baris beralasan sama yang berurutan: id => [awal, tinggi].
     *
     * Satu tiket sering memuat beberapa baris beralasan sama persis — kalimat
     * yang ditulis sekali di form lalu menempel ke tiap baris. Diulang ke
     * bawah, kalimat panjang itu jadi bagian paling ramai di popup padahal
     * isinya satu.
     *
     * Yang digabung deret berurutan, bukan seluruh kelompok. Pengecekan
     * "semua baris beralasan sama" gagal begitu satu baris saja berbeda — dan
     * justru itu bentuk yang paling lazim: dua baris harga satuan beralasan
     * sama, disusul baris shipping cost yang alasannya lain.
     *
     * @param  Collection<int, AuditPerubahanData>  $baris
     * @return array<int, array{awal: bool, tinggi: int}>
     */
    private function runAlasan(Collection $baris): array
    {
        $hasil = [];
        $idAwal = null;
        $sebelumnya = null;

        foreach ($baris as $satu) {
            $alasan = (string) $satu->alasan;

            if ($idAwal === null || $alasan !== $sebelumnya) {
                $idAwal = $satu->id;
                $sebelumnya = $alasan;
                $hasil[$idAwal] = ['awal' => true, 'tinggi' => 0];
            } else {
                $hasil[$satu->id] = ['awal' => false, 'tinggi' => 0];
            }

            $hasil[$idAwal]['tinggi']++;
        }

        return $hasil;
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
