<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Satu baris jejak perubahan data: satu kolom, satu record, satu kejadian.
 *
 * Hanya boleh ditambah. Update dan delete ditolak di sini supaya tidak ada jalan
 * tulis kedua walau nanti ada kode lain yang memuat model ini; satu-satunya
 * penulis yang dimaksud adalah AuditPerubahanDataService.
 *
 * Tanpa `updated_at` — kolomnya memang tidak ada di tabel, dan barisnya tidak
 * pernah berubah setelah ditulis.
 */
class AuditPerubahanData extends Model
{
    use HasFactory;

    protected $table = 'audit_perubahan_data';

    protected $guarded = [];

    public const UPDATED_AT = null;

    protected $casts = [
        'created_at' => 'datetime',
        'disetujui_sendiri' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException(
                'Baris audit perubahan data tidak boleh diubah. Catat koreksi baru, jangan menimpa yang lama.'
            );
        });

        static::deleting(function () {
            throw new RuntimeException(
                'Baris audit perubahan data tidak boleh dihapus.'
            );
        });
    }

    public function perbaikanData()
    {
        return $this->belongsTo(PerbaikanData::class, 'perbaikan_data_id');
    }

    public function pengaju()
    {
        return $this->belongsTo(User::class, 'pengaju_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Nama modul yang enak dibaca, mis. "Bahan Masuk".
     *
     * Diambil dari config/perbaikan_data.php supaya halaman audit dan form
     * pengajuan menyebut modul yang sama dengan istilah yang sama. Modul yang
     * sudah dikeluarkan dari daftar putih tetap tampil apa adanya — barisnya
     * sudah ada dan tidak boleh jadi kosong hanya karena config-nya berubah.
     */
    public function labelModul(): string
    {
        return config("perbaikan_data.modul.{$this->modul}.label", $this->modul);
    }

    /**
     * Label kolom yang dikoreksi, mis. "Harga per Unit".
     */
    public function labelField(): string
    {
        return config("perbaikan_data.modul.{$this->modul}.field.{$this->field}.label", $this->field);
    }
}
