<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Aksi isi/ubah tautan foto unit, dipakai bersama oleh tabel Produk Jadi dan
 * Produk Setengah Jadi.
 *
 * Dijadikan trait supaya aturannya — terutama pemeriksaan hak akses dan validasi
 * tautannya — hanya ada satu salinan. Dua salinan yang lama-lama berbeda adalah
 * cara paling mudah membuat salah satu tabel jadi lebih longgar tanpa disadari.
 */
trait MengelolaLinkGambarProduk
{
    public ?int $idLinkGambar = null;
    public string $linkGambar = '';
    public bool $modalLinkGambarTerbuka = false;

    public string $linkPreviewGambar = '';
    public string $judulPreviewGambar = '';
    public bool $modalPreviewGambarTerbuka = false;

    /**
     * Baris yang tautannya diubah. Diisi masing-masing komponen dengan modelnya.
     */
    abstract protected function cariUntukLinkGambar(int $id): ?Model;

    public function editLinkGambar(int $id): void
    {
        $this->pastikanBolehUbahLinkGambar();

        $baris = $this->cariUntukLinkGambar($id);

        if (!$baris) {
            return;
        }

        $this->idLinkGambar = $id;
        $this->linkGambar = (string) ($baris->link_gambar ?? '');
        $this->modalLinkGambarTerbuka = true;
        $this->resetErrorBag();
    }

    public function simpanLinkGambar(): void
    {
        // Diperiksa lagi di sini, bukan hanya saat modal dibuka. Aksi Livewire
        // bisa dipanggil langsung dari sisi klien tanpa melewati tombolnya.
        $this->pastikanBolehUbahLinkGambar();

        $this->validate([
            // http/https, atau id berkas Drive telanjang seperti yang sudah
            // ditoleransi GoogleDriveHelper. Pola ini sekaligus menutup
            // 'javascript:...' yang akan ikut jalan kalau tautannya diklik.
            'linkGambar' => ['nullable', 'string', 'max:2048', 'regex:/^(https?:\/\/\S+|[A-Za-z0-9_-]{10,})$/'],
        ], [
            'linkGambar.regex' => 'Isi tautan yang diawali http:// atau https://, atau id berkas Google Drive.',
        ]);

        $baris = $this->cariUntukLinkGambar((int) $this->idLinkGambar);

        if (!$baris) {
            return;
        }

        $baris->link_gambar = trim($this->linkGambar) ?: null;
        $baris->save();

        $this->tutupModalLinkGambar();
        session()->flash('success', 'Tautan gambar tersimpan.');
    }

    public function tutupModalLinkGambar(): void
    {
        $this->reset(['idLinkGambar', 'linkGambar', 'modalLinkGambarTerbuka']);
        $this->resetErrorBag();
    }

    /**
     * Pratinjau gambar unit di modal.
     *
     * Sengaja tidak memeriksa permission apa pun: yang boleh membuka halaman ini
     * sudah lolos `lihat-produk-jadi` / `lihat-bahan-setengahjadi` di level route,
     * dan fotonya tidak lebih rahasia daripada baris yang menampilkannya.
     */
    public function lihatGambar(int $id): void
    {
        $baris = $this->cariUntukLinkGambar($id);

        if (!$baris) {
            return;
        }

        $this->linkPreviewGambar = (string) ($baris->link_gambar ?? '');
        $this->judulPreviewGambar = (string) ($baris->kode_transaksi ?? '');
        $this->modalPreviewGambarTerbuka = true;
    }

    public function tutupPreviewGambar(): void
    {
        $this->reset(['linkPreviewGambar', 'judulPreviewGambar', 'modalPreviewGambarTerbuka']);
    }

    private function pastikanBolehUbahLinkGambar(): void
    {
        abort_unless(Auth::user()?->can('edit-link-gambar-produk'), 403);
    }
}
