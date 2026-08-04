<?php

namespace App\Livewire;

use App\Models\RiwayatMutasiAset;
use App\Models\Ruangan;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Pemantauan pergerakan aset lintas seluruh aset.
 *
 * Riwayat per aset sudah ada di modal Rekap Aset, tapi untuk memantau tidak
 * praktis: harus dibuka satu aset per satu aset. Halaman ini membaca tabel
 * riwayat_mutasi_aset yang sama, hanya tanpa disaring per aset — supaya
 * pertanyaan seperti "apa saja yang kembali ke manajemen minggu ini" bisa
 * dijawab dalam satu tampilan.
 *
 * Murni baca. Tidak ada satu pun aksi di sini yang mengubah data.
 */
class PergerakanAsetTable extends Component
{
    use WithPagination;

    public $search = '';
    public $jenis = '';

    /**
     * 'manajemen' menyaring perpindahan yang tujuannya kosong — yaitu aset yang
     * dilepas dari PIC atau dikeluarkan dari ruangan. Inilah penanda aset kembali
     * ke tangan manajemen, baik lewat pengembalian peminjaman maupun offboarding.
     */
    public $tujuan = '';

    /**
     * Menyaring ruangan/orang yang terlibat, sebagai asal MAUPUN tujuan. Sengaja
     * dua arah: kalau hanya tujuan, aset yang keluar dari sebuah ruangan tidak
     * akan terlihat saat ruangan itu disaring — padahal itu justru kejadian yang
     * paling perlu dipantau.
     */
    public $ruanganId = '';
    public $orangId = '';

    public $dariTanggal = '';
    public $sampaiTanggal = '';
    public $perPage = 25;

    /**
     * Penggantian filter apa pun mengembalikan ke halaman 1. Tanpa ini, filter
     * yang hasilnya sedikit bisa mendarat di halaman 3 yang isinya kosong.
     */
    public function updated()
    {
        $this->resetPage();
    }

    public function resetFilter()
    {
        $this->reset(['search', 'jenis', 'tujuan', 'ruanganId', 'orangId', 'dariTanggal', 'sampaiTanggal']);
        $this->resetPage();
    }

    public function render()
    {
        $pergerakan = RiwayatMutasiAset::with([
                'dataAset.barangAset',
                'pencatat',
                'pengembalianManajemen.buktiFoto',
            ])
            ->when($this->search !== '', function ($query) {
                $query->whereHas('dataAset', function ($aset) {
                    $aset->where('nomor_aset', 'like', '%' . $this->search . '%')
                        ->orWhere('serial_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('barangAset', function ($barang) {
                            $barang->where('nama_barang', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->jenis !== '', fn ($query) => $query->where('jenis', $this->jenis))
            ->when($this->tujuan === 'manajemen', fn ($query) => $query->whereNull('ke_id'))
            ->when($this->ruanganId !== '', function ($query) {
                $query->where('jenis', 'Ruangan')->where(function ($sub) {
                    $sub->where('dari_id', $this->ruanganId)->orWhere('ke_id', $this->ruanganId);
                });
            })
            ->when($this->orangId !== '', function ($query) {
                $query->where('jenis', 'PIC')->where(function ($sub) {
                    $sub->where('dari_id', $this->orangId)->orWhere('ke_id', $this->orangId);
                });
            })
            // Disaring memakai tanggal kejadian, bukan waktu pencatatan. Serah
            // terima yang terjadi Senin tapi dicatat Kamis harus muncul di rentang
            // yang memuat Senin — kalau tidak, laporan per periode jadi salah.
            // COALESCE menjaga baris lama yang tgl_kejadian-nya kosong tetap ikut.
            ->when($this->dariTanggal !== '', fn ($query) => $query
                ->whereRaw('COALESCE(tgl_kejadian, DATE(created_at)) >= ?', [$this->dariTanggal]))
            ->when($this->sampaiTanggal !== '', fn ($query) => $query
                ->whereRaw('COALESCE(tgl_kejadian, DATE(created_at)) <= ?', [$this->sampaiTanggal]))
            ->latest('id')
            ->paginate($this->perPage);

        return view('livewire.pergerakan-aset-table', [
            'pergerakan' => $pergerakan,
            'opsiRuangan' => $this->opsiPelaku('Ruangan', Ruangan::class, 'nama_ruangan'),
            'opsiOrang' => $this->opsiPelaku('PIC', User::class, 'name'),
        ]);
    }

    /**
     * Ruangan / orang yang pernah muncul di riwayat, sebagai asal atau tujuan.
     *
     * Diambil dari isi riwayatnya, bukan dari seluruh master data: daftar semua
     * user akan panjang sekali padahal cuma sebagian yang pernah jadi PIC, dan
     * memilih yang tidak pernah muncul hanya menghasilkan tabel kosong.
     *
     * Namanya diambil dari master data agar mengikuti nama terbaru, dengan
     * cadangan ke nama snapshot kalau datanya sudah dihapus.
     */
    private function opsiPelaku(string $jenis, string $model, string $kolomNama): array
    {
        $baris = RiwayatMutasiAset::where('jenis', $jenis)
            ->get(['dari_id', 'dari_nama', 'ke_id', 'ke_nama']);

        $pasangan = $baris
            ->flatMap(fn ($b) => [
                ['id' => $b->dari_id, 'nama' => $b->dari_nama],
                ['id' => $b->ke_id, 'nama' => $b->ke_nama],
            ])
            ->filter(fn ($p) => !empty($p['id']))
            ->unique('id');

        if ($pasangan->isEmpty()) {
            return [];
        }

        $namaTerbaru = $model::whereIn('id', $pasangan->pluck('id'))->pluck($kolomNama, 'id');

        return $pasangan
            ->map(fn ($p) => [
                'nilai' => (string) $p['id'],
                'label' => $namaTerbaru[$p['id']] ?? ($p['nama'] ?: 'ID ' . $p['id']),
            ])
            ->sortBy('label')
            ->values()
            ->all();
    }
}
