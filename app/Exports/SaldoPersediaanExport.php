<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SaldoPersediaanExport implements WithMultipleSheets
{
    public function __construct(
        protected string $startDate,
        protected string $endDate
    ) {
    }

    public function sheets(): array
    {
        $builder = new SaldoPersediaanReportBuilder($this->startDate, $this->endDate);
        $periodLabel = sprintf(
            'Rentang Waktu: %s s.d. %s',
            Carbon::parse($this->startDate)->translatedFormat('d F Y'),
            Carbon::parse($this->endDate)->translatedFormat('d F Y')
        );

        return [
            new SaldoPersediaanSheetExport($builder->monthlyRows(), 'Bulanan', $periodLabel),
            new SaldoPersediaanSheetExport($builder->yearlyRows(), 'Tahunan', $periodLabel),
        ];
    }
}
