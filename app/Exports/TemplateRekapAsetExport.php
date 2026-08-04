<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Template import rekap aset.
 *
 * Sheet 1 "Data Aset"        -> tempat mengisi, header sudah sesuai kolom import
 * Sheet 2 "Petunjuk Pengisian" -> seluruh penjelasan aturan pengisian
 *
 * Urutannya penting: sheet data harus paling depan, karena import hanya
 * membaca sheet pertama.
 */
class TemplateRekapAsetExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new TemplateAsetDataSheet,
            new TemplateAsetPetunjukSheet,
        ];
    }
}
