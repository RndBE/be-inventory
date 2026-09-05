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
            'kunciKelompok' => $this->kunciKelompok($audit->items()),
            'tinggiKelompok' => $this->tinggiKelompok($audit->items()),
            'runAlasan' => $this->runAlasan($audit->items()),
        ]);
    }

    /**
     * Deret baris beralasan sama yang berurutan: id => [awal, tinggi].
     *
     * Satu tiket sering memuat beberapa baris beralasan sama persis — kalimat
     * yang ditulis sekali di form lalu menempel ke tiap baris. Diulang ke
     * bawah, kalimat panjang itu jadi bagian paling ramai di tabel padahal
     * isinya satu.
     *
     * Yang digabung deret berurutan, bukan seluruh kelompok. Pengecekan
     * "semua baris di kelompok ini beralasan sama" gagal begitu satu baris
     * saja berbeda — dan justru itu bentuk yang paling lazim: dua baris harga
     * satuan beralasan sama, disusul baris shipping cost yang alasannya lain.
     * Menyerah pada kasus itu berarti mengulang kalimat yang sama persis tepat
     * di tempat pengulangannya paling terlihat.
     *
     * @param  array<int, AuditPerubahanData>  $baris
     * @return array<int, array{awal: bool, tinggi: int}>
     */
    private function runAlasan(array $baris): array
    {
        $kunciPer = $this->kunciKelompok($baris);
        $hasil = [];
        $idAwal = null;
        $sebelumnya = null;

        foreach ($baris as $satu) {
            $tanda = $kunciPer[$satu->id] . '|' . (string) $satu->alasan;

            if ($tanda !== $sebelumnya) {
                $idAwal = $satu->id;
                $hasil[$satu->id] = ['awal' => true, 'tinggi' => 0];
                $sebelumnya = $tanda;
            } else {
                $hasil[$satu->id] = ['awal' => false, 'tinggi' => 0];
            }

            $hasil[$idAwal]['tinggi']++;
        }

        // Tinggi deretnya baru diketahui setelah deret itu habis, jadi baris
        // penerusnya menyalin dari barisan awalnya di akhir, bukan saat lewat.
        $tinggiPer = [];

        foreach ($baris as $satu) {
            if ($hasil[$satu->id]['awal']) {
                $idAwal = $satu->id;
            }

            $tinggiPer[$satu->id] = [
                'awal' => $hasil[$satu->id]['awal'],
                'tinggi' => $hasil[$idAwal]['tinggi'],
            ];
        }

        return $tinggiPer;
    }

    /**
     * Kunci pengelompokan tiap baris: id audit => kunci kelompoknya.
     *
     * Satu tiket biasanya melahirkan beberapa baris audit sekaligus — harga
     * satuan dua bahan dan shipping cost transaksinya, misalnya, tercatat pada
     * detik yang sama oleh approver yang sama. Ditampilkan sebagai baris-baris
     * berdiri sendiri, waktu, tiket, pengaju, dan approvernya terulang identik
     * dan pembacanya harus membandingkan sendiri untuk tahu ketiganya berasal
     * dari satu keputusan.
     *
     * Waktunya ikut jadi kunci, bukan nomor tiketnya saja. Baris yang gagal
     * dicatat bisa dicoba lagi berhari-hari kemudian di bawah tiket yang sama;
     * menggabungkannya dengan pencatatan pertama akan menempelkan satu waktu ke
     * kejadian yang berbeda hari.
     *
     * Baris tanpa tiket selalu berdiri sendiri. Yang menyatukannya cuma
     * kebetulan waktu, dan itu bukan alasan untuk menyatukannya di layar.
     *
     * @param  array<int, AuditPerubahanData>  $baris
     * @return array<int, string>
     */
    private function kunciKelompok(array $baris): array
    {
        $kunci = [];

        foreach ($baris as $satu) {
            $kunci[$satu->id] = $satu->perbaikan_data_id
                ? 'tiket-' . $satu->perbaikan_data_id . '-' . optional($satu->created_at)->format('YmdHi')
                : 'baris-' . $satu->id;
        }

        return $kunci;
    }

    /**
     * Banyaknya baris tiap kelompok, untuk rowspan sel yang digabung.
     *
     * Dihitung dari isi halaman ini saja, bukan dari seluruh hasil query. Satu
     * kelompok yang kebetulan terpotong batas halaman akan tampil sebagai dua
     * kelompok kecil — tidak ideal, tapi benar: rowspan yang menghitung baris
     * di halaman berikutnya akan menembus tabelnya.
     *
     * @param  array<int, AuditPerubahanData>  $baris
     * @return array<string, int>
     */
    private function tinggiKelompok(array $baris): array
    {
        $tinggi = [];

        foreach ($this->kunciKelompok($baris) as $kunci) {
            $tinggi[$kunci] = ($tinggi[$kunci] ?? 0) + 1;
        }

        return $tinggi;
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
