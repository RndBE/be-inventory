<?php

namespace App\Imports;

use App\Models\Bahan;
use App\Models\JenisBahan;
use App\Models\PurchaseDetail;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

/**
 * Membaca dan melakukan upsert data bahan berdasarkan Kode Bahan.
 *
 * Lima kolom utama sama dengan hasil export bahan, sehingga file hasil export
 * dapat langsung diedit lalu di-import kembali. Kolom Status dan Supplier
 * bersifat opsional agar file export lama tetap dapat digunakan.
 */
class BahanImport
{
    private const KOLOM_WAJIB = [
        'kode_bahan',
        'nama_bahan',
        'jenis_bahan',
        'satuan_unit',
        'penempatan',
    ];

    public int $jumlahBaru = 0;

    public int $jumlahDiperbarui = 0;

    public int $jumlahTidakBerubah = 0;

    /**
     * Baca worksheet pertama tanpa mengubah database.
     */
    public function bacaFile($file): array
    {
        $sheets = Excel::toArray([], $file);

        return $this->bacaSheet($sheets[0] ?? []);
    }

    /**
     * Ubah worksheet mentah menjadi baris dengan nama kolom internal.
     */
    public function bacaSheet(array $rows): array
    {
        if ($rows === []) {
            throw new RuntimeException('File import kosong.');
        }

        $header = array_shift($rows);
        $header = is_array($header) ? $header : (array) $header;
        $petaHeader = [];

        foreach ($header as $index => $nama) {
            $kolom = self::namaKolom($nama);

            if ($kolom !== null && ! in_array($kolom, $petaHeader, true)) {
                $petaHeader[$index] = $kolom;
            }
        }

        $kolomTersedia = array_values($petaHeader);
        $kolomKurang = array_diff(self::KOLOM_WAJIB, $kolomTersedia);

        if ($kolomKurang !== []) {
            $label = array_map(fn (string $kolom) => self::labelKolom($kolom), $kolomKurang);

            throw new RuntimeException('Kolom wajib tidak ditemukan: '.implode(', ', $label).'.');
        }

        $hasil = [];

        foreach ($rows as $index => $row) {
            $row = is_array($row) ? $row : (array) $row;
            $data = [];

            foreach ($petaHeader as $kolom => $nama) {
                $data[$nama] = $row[$kolom] ?? null;
            }

            if (empty(array_filter($data, fn ($nilai) => $nilai !== null && trim((string) $nilai) !== ''))) {
                continue;
            }

            $data['_baris'] = $index + 2;
            $hasil[] = $data;
        }

        return $hasil;
    }

    /**
     * Simpan semua baris. Method ini harus dipanggil di dalam transaction.
     */
    public function prosesBaris(array $rows): void
    {
        if ($rows === []) {
            throw new RuntimeException('File tidak mempunyai baris data bahan yang dapat di-import.');
        }

        $jenisBahan = $this->katalogBerdasarkanNama(JenisBahan::all(), 'Jenis Bahan');
        $units = $this->katalogBerdasarkanNama(Unit::all(), 'Satuan Unit');
        $suppliers = $this->katalogBerdasarkanNama(Supplier::all(), 'Supplier');
        $bahanTersimpan = $this->bahanBerdasarkanKode();
        $kodeDalamFile = [];

        foreach ($rows as $data) {
            $baris = (int) ($data['_baris'] ?? 0);
            $kode = $this->nilaiWajib($data, 'kode_bahan', $baris);
            $nama = $this->nilaiWajib($data, 'nama_bahan', $baris);
            $namaJenis = $this->nilaiWajib($data, 'jenis_bahan', $baris);
            $namaUnit = $this->nilaiWajib($data, 'satuan_unit', $baris);
            $penempatan = $this->nilaiWajib($data, 'penempatan', $baris);
            $kodeNormal = self::normalisasi($kode);

            if (isset($kodeDalamFile[$kodeNormal])) {
                throw new RuntimeException(
                    "Kode Bahan '{$kode}' duplikat pada baris {$kodeDalamFile[$kodeNormal]} dan {$baris}."
                );
            }
            $kodeDalamFile[$kodeNormal] = $baris;

            $jenis = $jenisBahan[self::normalisasi($namaJenis)] ?? null;
            if ($jenis === null) {
                throw new RuntimeException("Jenis Bahan '{$namaJenis}' pada baris {$baris} belum terdaftar.");
            }

            $unit = $units[self::normalisasi($namaUnit)] ?? null;
            if ($unit === null) {
                throw new RuntimeException("Satuan Unit '{$namaUnit}' pada baris {$baris} belum terdaftar.");
            }

            $attributes = [
                'kode_bahan' => $kode,
                'nama_bahan' => $nama,
                'jenis_bahan_id' => $jenis->id,
                'unit_id' => $unit->id,
                'penempatan' => $penempatan,
            ];

            if (array_key_exists('status', $data) && trim((string) $data['status']) !== '') {
                $attributes['status'] = $this->status($data['status'], $baris);
            }

            // Kolom opsional untuk bahan batangan. Hanya diikutkan kalau
            // kolomnya ada di file — kalau tidak, panjang standar bahan yang
            // sudah ada tidak boleh ikut terhapus oleh import.
            if (array_key_exists('panjang_standar', $data)) {
                $attributes['panjang_standar'] = $this->panjangStandar($data['panjang_standar'], $baris);
            }

            $supplierIds = null;
            if (array_key_exists('supplier', $data)) {
                $supplierIds = $this->supplierIds($data['supplier'], $suppliers, $baris);
            }

            /** @var Bahan|null $bahan */
            $bahan = $bahanTersimpan->get($kodeNormal);

            if ($bahan === null) {
                $bahan = Bahan::create($attributes + [
                    'stok_awal' => 0,
                    'kondisi' => 'Baik',
                    'status' => 'Digunakan',
                ]);

                if ($supplierIds !== null) {
                    $bahan->suppliers()->sync($supplierIds);
                }

                $bahanTersimpan->put($kodeNormal, $bahan);
                $this->jumlahBaru++;

                continue;
            }

            // Diperiksa sebelum fill(), selagi nilai lama masih utuh di model.
            $konversiPanjang = array_key_exists('panjang_standar', $attributes)
                ? $this->jagaPanjangStandar($bahan, $attributes['panjang_standar'], $baris)
                : null;

            $bahan->fill($attributes);
            $berubah = $bahan->isDirty();

            if ($berubah) {
                // Konversi dijalankan lebih dulu karena yang dicari adalah lot
                // yang `panjang_standar`-nya masih null, dan urutannya tidak
                // berpengaruh pada hasil — keduanya berada di transaction yang
                // sama, jadi kegagalan salah satu membatalkan dua-duanya.
                if ($konversiPanjang !== null) {
                    PurchaseDetail::konversiLotLama($bahan, $konversiPanjang);
                }

                $bahan->save();
            }

            if ($supplierIds !== null) {
                $supplierSekarang = $bahan->suppliers()->pluck('supplier.id')->map(fn ($id) => (int) $id)->sort()->values()->all();
                $supplierBaru = collect($supplierIds)->map(fn ($id) => (int) $id)->sort()->values()->all();

                if ($supplierSekarang !== $supplierBaru) {
                    $bahan->suppliers()->sync($supplierBaru);
                    $berubah = true;
                }
            }

            $berubah ? $this->jumlahDiperbarui++ : $this->jumlahTidakBerubah++;
        }
    }

    /**
     * Panjang standar yang berubah lewat import ditolak atau dikonversi.
     *
     * Mengubah panjang standar mengubah arti setiap angka stok bahan itu:
     * kolom `sisa` yang tadinya dibaca sebagai jumlah barang mendadak dibaca
     * sebagai cm. Lewat form, BahanController sudah menahannya; lewat import
     * jalurnya berbeda dan tanpa penjagaan yang sama satu sel Excel bisa
     * memangkas stok ratusan kali tanpa satu pun error.
     *
     * Aturannya tidak ditulis ulang di sini, tapi diambil dari
     * Bahan::alasanTolakPanjangStandar() supaya form dan import tidak bisa
     * berbeda. Yang ditambahkan hanya nomor baris dan kode bahannya, karena
     * satu import menyentuh banyak baris dan pesan tanpa penunjuk baris tidak
     * bisa ditindaklanjuti.
     *
     * Mengembalikan panjang yang perlu dipakai untuk konversi lot lama, atau
     * null kalau tidak ada yang berubah.
     */
    private function jagaPanjangStandar(Bahan $bahan, ?int $panjangBaru, int $baris): ?int
    {
        if ((int) $bahan->panjang_standar === (int) $panjangBaru) {
            return null;
        }

        // setelUlangLot false: import tidak menyetel ulang stok. Satu sel
        // Excel bisa menyentuh puluhan bahan tanpa dilihat satu per satu, jadi
        // mengubah angka yang sudah dipakai tetap harus lewat form edit bahan.
        $alasanTolak = $bahan->alasanTolakPanjangStandar($panjangBaru, false);

        if ($alasanTolak !== null) {
            throw new RuntimeException(
                "Panjang per Batang (cm) pada baris {$baris} (Kode Bahan '{$bahan->kode_bahan}') ditolak. {$alasanTolak}"
            );
        }

        return $panjangBaru;
    }

    /**
     * Panjang standar dari sel import, atau null kalau selnya kosong.
     *
     * Kosong berarti bahan biasa, bukan bahan batangan. Nilai nol atau negatif
     * ditolak daripada disimpan: itu akan mengaktifkan mode batangan dengan
     * pembagi yang tidak masuk akal.
     */
    private function panjangStandar($nilai, int $baris): ?int
    {
        $teks = trim((string) $nilai);

        if ($teks === '') {
            return null;
        }

        if (! is_numeric($teks) || (int) $teks < 1) {
            throw new RuntimeException(
                "Panjang per Batang (cm) pada baris {$baris} harus berupa angka minimal 1, atau dikosongkan untuk bahan non-batangan."
            );
        }

        return (int) $teks;
    }

    public function ringkasan(): string
    {
        return "{$this->jumlahBaru} ditambahkan, {$this->jumlahDiperbarui} diperbarui, {$this->jumlahTidakBerubah} tidak berubah.";
    }

    public static function namaKolom($nama): ?string
    {
        $nama = self::normalisasi($nama);

        return match ($nama) {
            'kode bahan', 'kode' => 'kode_bahan',
            'nama bahan', 'nama' => 'nama_bahan',
            'jenis bahan', 'jenis' => 'jenis_bahan',
            'satuan unit', 'satuan', 'unit' => 'satuan_unit',
            'penempatan', 'lokasi' => 'penempatan',
            'status' => 'status',
            'supplier', 'suppliers', 'pemasok' => 'supplier',
            'panjang per batang (cm)', 'panjang per batang', 'panjang standar', 'panjang' => 'panjang_standar',
            default => null,
        };
    }

    private static function labelKolom(string $kolom): string
    {
        return match ($kolom) {
            'kode_bahan' => 'Kode Bahan',
            'nama_bahan' => 'Nama Bahan',
            'jenis_bahan' => 'Jenis Bahan',
            'satuan_unit' => 'Satuan Unit',
            'penempatan' => 'Penempatan',
            'panjang_standar' => 'Panjang per Batang (cm)',
            default => Str::headline($kolom),
        };
    }

    private static function normalisasi($nilai): string
    {
        $nilai = Str::ascii(trim((string) $nilai));
        $nilai = preg_replace('/[\s_\-]+/u', ' ', $nilai) ?? $nilai;

        return mb_strtolower(trim($nilai));
    }

    private function nilaiWajib(array $data, string $kolom, int $baris): string
    {
        $nilai = trim((string) ($data[$kolom] ?? ''));

        if ($nilai === '') {
            throw new RuntimeException(self::labelKolom($kolom)." wajib diisi pada baris {$baris}.");
        }

        if (mb_strlen($nilai) > 255) {
            throw new RuntimeException(self::labelKolom($kolom)." pada baris {$baris} maksimal 255 karakter.");
        }

        return $nilai;
    }

    private function katalogBerdasarkanNama(Collection $items, string $label): Collection
    {
        $hasil = collect();

        foreach ($items as $item) {
            $kunci = self::normalisasi($item->nama);

            if ($hasil->has($kunci)) {
                throw new RuntimeException("Terdapat lebih dari satu {$label} dengan nama '{$item->nama}'.");
            }

            $hasil->put($kunci, $item);
        }

        return $hasil;
    }

    private function bahanBerdasarkanKode(): Collection
    {
        $hasil = collect();

        foreach (Bahan::all() as $bahan) {
            $kunci = self::normalisasi($bahan->kode_bahan);

            if ($hasil->has($kunci)) {
                throw new RuntimeException("Terdapat lebih dari satu bahan dengan Kode Bahan '{$bahan->kode_bahan}'.");
            }

            $hasil->put($kunci, $bahan);
        }

        return $hasil;
    }

    private function status($nilai, int $baris): string
    {
        return match (self::normalisasi($nilai)) {
            'digunakan' => 'Digunakan',
            'tidak digunakan' => 'Tidak digunakan',
            default => throw new RuntimeException(
                "Status pada baris {$baris} harus 'Digunakan' atau 'Tidak digunakan'."
            ),
        };
    }

    private function supplierIds($nilai, Collection $suppliers, int $baris): array
    {
        $namaSuppliers = preg_split('/[,;\r\n]+/', trim((string) $nilai), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $ids = [];

        foreach ($namaSuppliers as $namaSupplier) {
            $namaSupplier = trim($namaSupplier);
            $supplier = $suppliers[self::normalisasi($namaSupplier)] ?? null;

            if ($supplier === null) {
                throw new RuntimeException("Supplier '{$namaSupplier}' pada baris {$baris} belum terdaftar.");
            }

            $ids[] = $supplier->id;
        }

        return array_values(array_unique($ids));
    }
}
