<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris perubahan yang diminta: satu kolom pada satu record.
 */
class PerbaikanDataTarget extends Model
{
    use HasFactory;

    protected $table = 'perbaikan_data_target';

    protected $guarded = [];

    public function perbaikanData()
    {
        return $this->belongsTo(PerbaikanData::class, 'perbaikan_data_id');
    }

    public function labelModul(): string
    {
        return config("perbaikan_data.modul.{$this->modul}.label", $this->modul);
    }

    public function labelField(): string
    {
        return config("perbaikan_data.modul.{$this->modul}.field.{$this->field}.label", $this->field);
    }

    /**
     * Sudah dicatat ke jejak audit, dan tiketnya tertutup untuk baris ini.
     *
     * Namanya `dicatat`, bukan `dieksekusi` seperti dulu. Aplikasi tidak
     * mengubah data yang dikoreksi — itu dikerjakan tim software langsung
     * di database. Status yang mengaku "dieksekusi" akan membuat pembacanya
     * mengira datanya sudah berubah, padahal yang berubah baru catatannya.
     */
    public function sudahDicatat(): bool
    {
        return $this->status === 'dicatat';
    }
}
