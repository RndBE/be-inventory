<?php

namespace App\Livewire;

use App\Models\Unit;
use Livewire\Component;
use App\Models\BarangAset;
use App\Models\JenisBahan;
use App\Models\JobPosition;
use App\Models\RekapAset;
use App\Models\User;
use Livewire\WithPagination;

class RekapAsetTable extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 25;

    /**
     * Penyaring penempatan aset. Kosong berarti semua.
     *
     * Nilai khusus 'kosong' menyaring aset yang belum punya ruangan / PIC —
     * justru kelompok yang paling perlu ditengok, karena itulah aset yang ada
     * di tangan manajemen dan belum ditugaskan ke siapa pun.
     */
    public $filterRuangan = '';
    public $filterPic = '';
    public $id_barang, $nama_barang, $jenis_bahan_id, $unit_id, $kode_barang, $link_gambar, $id_rekap, $nomor_aset;
    public $selectedIds = [];
    public $isDeleteModalOpen = false;
    public $isEditModalOpen = false;
    public $isShowGambarModalOpen = false;
    public $isRiwayatModalOpen = false;
    public $riwayatAset;
    public $tabRiwayat = 'peminjaman';

    /**
     * Modal pencatatan serah terima ke manajemen. Dibuka per PIC, bukan per aset:
     * karyawan biasanya menyerahkan beberapa barang sekaligus, dan satu tanggal
     * serta satu set bukti foto berlaku untuk semuanya.
     */
    public $isPengembalianModalOpen = false;
    public $picPengembalian;
    public $asetPengembalian;
    public $asetTerpilihAwal = [];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterRuangan()
    {
        $this->resetPage();
    }

    public function updatedFilterPic()
    {
        $this->resetPage();
    }

    /**
     * Mengosongkan seluruh penyaring, termasuk kata pencarian.
     *
     * search sengaja ikut: kalau tidak, tombol "Reset filter" menyisakan
     * pencarian yang masih aktif sehingga tabelnya tetap tersaring padahal
     * pengguna merasa sudah membersihkannya. Perilakunya sekarang sama dengan
     * PergerakanAsetTable::resetFilter().
     */
    public function resetFilter()
    {
        $this->reset(['search', 'filterRuangan', 'filterPic']);
        $this->resetPage();
    }

    public function showRiwayat(int $id)
    {
        $this->riwayatAset = RekapAset::with([
            'barangAset',
            'riwayatMutasi.pencatat',
            'riwayatMutasi.pengembalianManajemen.buktiFoto',
            'riwayatPeminjaman.peminjamanAset.dataUser',
            'riwayatPeminjaman.buktiFoto',
        ])->findOrFail($id);

        $this->tabRiwayat = 'peminjaman';
        $this->isRiwayatModalOpen = true;
    }

    public function setTabRiwayat(string $tab)
    {
        $this->tabRiwayat = $tab;
    }

    public function showGambar(int $id)
    {
        $Data = RekapAset::findOrFail($id);
        $this->id_rekap= $id;
        $this->link_gambar = $Data->link_gambar;
        $this->isShowGambarModalOpen = true;
    }

    public function editBarang(int $id)
    {
        $barang = BarangAset::findOrFail($id);
        $this->id_barang = $id;
        $this->nama_barang = $barang->nama_barang;
        $this->kode_barang = $barang->kode_barang;
        $this->jenis_bahan_id = $barang->jenisBahan->id ?? 'N/A';
        $this->unit_id = $barang->dataUnit->id ?? 'N/A';
        $this->isEditModalOpen = true;
    }

    public function deleteBarang(int $id)
    {
        $barang = RekapAset::findOrFail($id);
        $this->id_barang = $id;
        $this->nama_barang = $barang->barangAset->nama_barang;
        $this->nomor_aset = $barang->nomor_aset;
        $this->isDeleteModalOpen = true;
    }

    /**
     * Buka pencatatan serah terima untuk PIC pemegang aset ini.
     *
     * Seluruh aset yang dipegang PIC tersebut ikut ditampilkan supaya bisa
     * dicentang sekaligus, dengan aset yang tombolnya diklik tercentang di awal.
     *
     * Aset yang sedang dipinjam lewat pengajuan dikecualikan: pengembaliannya
     * punya alurnya sendiri di modul peminjaman, lengkap dengan penutupan status
     * pinjamnya. Melepasnya dari sini akan meninggalkan peminjaman yang
     * menggantung tanpa tanggal kembali.
     */
    public function openPengembalian(int $id)
    {
        $aset = RekapAset::with('dataPic')->findOrFail($id);

        if (!$aset->pic_id) {
            return;
        }

        $this->picPengembalian = $aset->dataPic;
        $this->asetPengembalian = RekapAset::with('barangAset', 'dataRuangan')
            ->where('pic_id', $aset->pic_id)
            ->whereDoesntHave('peminjamanAktif')
            ->orderBy('nomor_aset')
            ->get();

        $this->asetTerpilihAwal = $this->asetPengembalian->contains('id', $aset->id) ? [$aset->id] : [];
        $this->isPengembalianModalOpen = true;
    }

    public function closeModal()
    {
        $this->isDeleteModalOpen = false;
        $this->isEditModalOpen = false;
        $this->isShowGambarModalOpen = false;
        $this->isRiwayatModalOpen = false;
        $this->riwayatAset = null;
        $this->isPengembalianModalOpen = false;
        $this->picPengembalian = null;
        $this->asetPengembalian = null;
        $this->asetTerpilihAwal = [];
    }

    public function render()
    {
        $units = Unit::all();
        $jenisBahan = JenisBahan::all();
        $dataUser = User::all();
        $dataDivisi = JobPosition::all();
        $barangAset = BarangAset::all();

        // Pilihan filter dihitung dari rekap_aset, bukan dari master ruangan/user:
        // yang ditawarkan hanya yang benar-benar punya aset, supaya tidak ada
        // pilihan yang begitu dipilih malah menghasilkan tabel kosong. Jumlahnya
        // sekalian ditampilkan sebagai gambaran sebaran aset.
        $opsiRuangan = $this->opsiPenempatan('ruangan_id', 'ruangan', 'nama_ruangan');
        $opsiPic = $this->opsiPenempatan('pic_id', 'users', 'name');

        $rekap_asets = RekapAset::with('jenisBahan', 'dataUnit','dataUser.dataJobPosition', 'barangAset','dataDivisi', 'dataPic', 'dataRuangan', 'peminjamanAktif.peminjamanAset.dataUser' )
            ->where(function ($query) {
                $query->where('nomor_aset', 'like', '%' . $this->search . '%')
                ->orWhere('serial_number', 'like', '%' . $this->search . '%')
                ->orWhere('merek', 'like', '%' . $this->search . '%')
                ->orWhere('tgl_perolehan', 'like', '%' . $this->search . '%')
                ->orWhere('kondisi', 'like', '%' . $this->search . '%')
                    ->orWhereHas('dataUser', function ($query) {
                        $query->where('name', 'like', '%' . $this->search . '%');
                    })->orWhereHas('dataUser.dataJobPosition', function ($query) {
                        $query->where('nama', 'like', '%' . $this->search . '%');
                    })->orWhereHas('barangAset', function ($query) {
                        $query->where('nama_barang', 'like', '%' . $this->search . '%');
                    })->orWhereHas('dataPic', function ($query) {
                        $query->where('name', 'like', '%' . $this->search . '%');
                    })->orWhereHas('dataRuangan', function ($query) {
                        $query->where('nama_ruangan', 'like', '%' . $this->search . '%')
                            ->orWhere('kode_ruangan', 'like', '%' . $this->search . '%');
                    });
            })
            // Ketiga filter menumpuk di atas pencarian, bukan menggantikannya:
            // pilih ruangan lalu ketik kata kunci akan menyaring di dalam ruangan itu.
            ->when($this->filterRuangan !== '', fn ($query) => $this->saringPenempatan($query, 'ruangan_id', $this->filterRuangan))
            ->when($this->filterPic !== '', fn ($query) => $this->saringPenempatan($query, 'pic_id', $this->filterPic))
            ->paginate($this->perPage);


        return view('livewire.rekap-aset-table', [
            'rekap_asets' => $rekap_asets,
            'jenisBahan' => $jenisBahan,
            'units' => $units,
            'dataUser' => $dataUser,
            'dataDivisi' => $dataDivisi,
            'barangAset' => $barangAset,
            'opsiRuangan' => $opsiRuangan,
            'opsiPic' => $opsiPic,
        ]);
    }

    /**
     * Daftar pilihan untuk satu kolom penempatan, beserta jumlah asetnya.
     *
     * Baris "belum ada" ikut dihitung dari aset yang kolomnya NULL dan diberi
     * kunci 'kosong' — bukan dibuang. Aset tanpa ruangan/PIC adalah keadaan yang
     * sah (ada di manajemen, belum ditugaskan) dan perlu bisa disaring.
     */
    private function opsiPenempatan(string $kolom, string $tabelRelasi, string $kolomNama): array
    {
        $terisi = RekapAset::query()
            ->join($tabelRelasi, $tabelRelasi . '.id', '=', 'rekap_aset.' . $kolom)
            ->selectRaw("rekap_aset.{$kolom} as id, {$tabelRelasi}.{$kolomNama} as nama, COUNT(*) as jumlah")
            ->groupBy('rekap_aset.' . $kolom, $tabelRelasi . '.' . $kolomNama)
            ->orderBy($tabelRelasi . '.' . $kolomNama)
            ->get()
            ->map(fn ($baris) => [
                'nilai' => (string) $baris->id,
                'label' => $baris->nama,
                'jumlah' => $baris->jumlah,
            ])
            ->all();

        $jumlahKosong = RekapAset::whereNull($kolom)->count();

        if ($jumlahKosong > 0) {
            $terisi[] = [
                'nilai' => 'kosong',
                'label' => $kolom === 'pic_id' ? 'Belum ada PIC' : 'Belum ada ruangan',
                'jumlah' => $jumlahKosong,
            ];
        }

        return $terisi;
    }

    private function saringPenempatan($query, string $kolom, string $nilai): void
    {
        if ($nilai === 'kosong') {
            $query->whereNull($kolom);
            return;
        }

        $query->where($kolom, $nilai);
    }
}
