<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\RekapAset;
use App\Models\PeminjamanAset;

class AsetPeminjamanCart extends Component
{
    /**
     * Aset terpilih, dikunci per rekap_aset_id supaya tidak bisa dobel.
     */
    public $items = [];

    /**
     * Diisi saat dipakai di layar edit: keranjang dimuati aset pengajuan itu.
     * Null berarti pengajuan baru, keranjang mulai kosong.
     */
    public $peminjamanId = null;

    protected $listeners = ['asetSelected' => 'tambahAset'];

    public function mount($peminjamanId = null)
    {
        $this->peminjamanId = $peminjamanId;

        if (!$peminjamanId) {
            return;
        }

        $peminjaman = PeminjamanAset::with('peminjamanAsetDetails.dataAset.barangAset')
            ->find($peminjamanId);

        foreach ($peminjaman?->peminjamanAsetDetails ?? [] as $detail) {
            if (!$detail->dataAset) {
                continue;
            }

            $this->tambahAset($detail->rekap_aset_id);
            $this->items[$detail->rekap_aset_id]['jumlah'] = $detail->jumlah;
            $this->items[$detail->rekap_aset_id]['keterangan'] = $detail->keterangan;
        }
    }

    public function tambahAset(int $asetId)
    {
        if (isset($this->items[$asetId])) {
            return;
        }

        $aset = RekapAset::with('barangAset', 'dataRuangan', 'peminjamanAktif.peminjamanAset.dataUser')
            ->findOrFail($asetId);

        $peminjamAktif = $aset->peminjamanAktif;

        $this->items[$asetId] = [
            'rekap_aset_id' => $aset->id,
            'nomor_aset' => $aset->nomor_aset,
            'nama_barang' => $aset->barangAset->nama_barang ?? '-',
            'ruangan' => $aset->dataRuangan->nama_ruangan ?? '-',
            'kondisi' => $aset->kondisi,
            'jumlah' => 1,
            'keterangan' => null,
            'dipinjam_oleh' => $peminjamAktif
                ? ($peminjamAktif->peminjamanAset->dataUser->name ?? 'orang lain')
                : null,
            'dipinjam_sejak' => $peminjamAktif
                ? $peminjamAktif->peminjamanAset->tgl_pinjam
                : null,
        ];

        $this->kabariPerubahan();
    }

    public function hapusAset(int $asetId)
    {
        unset($this->items[$asetId]);
        $this->kabariPerubahan();
    }

    /**
     * Beri tahu daftar kartu aset mana saja yang sudah masuk keranjang.
     */
    private function kabariPerubahan(): void
    {
        $this->dispatch('asetTerpilihBerubah', ids: array_keys($this->items));
    }

    public function getItemsJsonProperty(): string
    {
        return json_encode(array_values($this->items));
    }

    public function render()
    {
        return view('livewire.aset-peminjaman-cart');
    }
}
