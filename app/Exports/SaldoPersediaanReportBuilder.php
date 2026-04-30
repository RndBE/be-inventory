<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Bahan;
use App\Models\PurchaseDetail;
use Illuminate\Support\Collection;
use App\Models\BahanKeluarDetails;

class SaldoPersediaanReportBuilder
{
    protected Carbon $startDate;
    protected Carbon $endDate;
    protected Collection $bahans;
    protected Collection $incomingByBahan;
    protected Collection $outgoingByBahan;

    public function __construct(string $startDate, string $endDate)
    {
        $this->startDate = Carbon::parse($startDate)->startOfDay();
        $this->endDate = Carbon::parse($endDate)->endOfDay();

        $this->loadData();
    }

    public function monthlyRows(): array
    {
        return $this->buildRows($this->monthlyPeriods());
    }

    public function yearlyRows(): array
    {
        return $this->buildRows($this->yearlyPeriods());
    }

    protected function loadData(): void
    {
        $this->bahans = Bahan::with('dataUnit')
            ->orderBy('nama_bahan')
            ->get()
            ->keyBy('id');

        $incoming = PurchaseDetail::query()
            ->select(
                'purchase_details.bahan_id',
                'purchase_details.qty',
                'purchase_details.sub_total',
                'purchases.tgl_masuk as transaction_date'
            )
            ->join('purchases', 'purchases.id', '=', 'purchase_details.purchase_id')
            ->whereNotNull('purchase_details.bahan_id')
            ->whereNotNull('purchases.tgl_masuk')
            ->whereDate('purchases.tgl_masuk', '<=', $this->endDate->toDateString())
            ->get()
            ->map(function ($transaction) {
                return [
                    'bahan_id' => (int) $transaction->bahan_id,
                    'date' => Carbon::parse($transaction->transaction_date)->startOfDay(),
                    'qty' => (float) $transaction->qty,
                    'value' => (float) $transaction->sub_total,
                ];
            });

        $outgoing = BahanKeluarDetails::query()
            ->select(
                'bahan_keluar_details.bahan_id',
                'bahan_keluar_details.qty',
                'bahan_keluar_details.sub_total',
                'bahan_keluars.tgl_keluar as transaction_date'
            )
            ->join('bahan_keluars', 'bahan_keluars.id', '=', 'bahan_keluar_details.bahan_keluar_id')
            ->whereNotNull('bahan_keluar_details.bahan_id')
            ->where('bahan_keluars.status', 'Disetujui')
            ->whereNotNull('bahan_keluars.tgl_keluar')
            ->whereDate('bahan_keluars.tgl_keluar', '<=', $this->endDate->toDateString())
            ->get()
            ->map(function ($transaction) {
                return [
                    'bahan_id' => (int) $transaction->bahan_id,
                    'date' => Carbon::parse($transaction->transaction_date)->startOfDay(),
                    'qty' => (float) $transaction->qty,
                    'value' => (float) $transaction->sub_total,
                ];
            });

        $this->incomingByBahan = $incoming->groupBy('bahan_id');
        $this->outgoingByBahan = $outgoing->groupBy('bahan_id');
    }

    protected function monthlyPeriods(): array
    {
        $periods = [];
        $cursor = $this->startDate->copy()->startOfMonth();
        $lastMonth = $this->endDate->copy()->startOfMonth();

        while ($cursor->lte($lastMonth)) {
            $periodStart = $cursor->copy()->startOfMonth();
            $periodEnd = $cursor->copy()->endOfMonth();
            if ($periodEnd->gt($this->endDate)) {
                $periodEnd = $this->endDate->copy();
            }

            if ($periodEnd->gte($this->startDate)) {
                $effectiveStart = $periodStart->copy();
                if ($effectiveStart->lt($this->startDate)) {
                    $effectiveStart = $this->startDate->copy();
                }

                $periods[] = [
                    'label' => $periodStart->format('m/Y'),
                    'start' => $effectiveStart,
                    'balance_start' => $effectiveStart,
                    'end' => $periodEnd,
                ];
            }

            $cursor->addMonth();
        }

        return $periods;
    }

    protected function yearlyPeriods(): array
    {
        $periods = [];
        $cursor = $this->startDate->copy()->startOfYear();
        $lastYear = $this->endDate->copy()->startOfYear();

        while ($cursor->lte($lastYear)) {
            $periodStart = $cursor->copy()->startOfYear();
            $periodEnd = $cursor->copy()->endOfYear();
            if ($periodEnd->gt($this->endDate)) {
                $periodEnd = $this->endDate->copy();
            }

            if ($periodEnd->gte($this->startDate)) {
                $effectiveStart = $periodStart->copy();
                if ($effectiveStart->lt($this->startDate)) {
                    $effectiveStart = $this->startDate->copy();
                }

                $periods[] = [
                    'label' => $periodStart->format('Y'),
                    'start' => $effectiveStart,
                    'balance_start' => $effectiveStart,
                    'end' => $periodEnd,
                ];
            }

            $cursor->addYear();
        }

        return $periods;
    }

    protected function buildRows(array $periods): array
    {
        $rows = [];

        foreach ($periods as $period) {
            foreach ($this->bahans as $bahan) {
                $incoming = collect($this->incomingByBahan->get($bahan->id, []));
                $outgoing = collect($this->outgoingByBahan->get($bahan->id, []));

                $openingQty = $this->sumQtyBefore($incoming, $outgoing, $period['balance_start']);
                $openingValue = $this->sumValueBefore($incoming, $outgoing, $period['balance_start']);
                $incomingQty = $this->sumQtyBetween($incoming, $period['start'], $period['end']);
                $incomingValue = $this->sumValueBetween($incoming, $period['start'], $period['end']);
                $outgoingQty = $this->sumQtyBetween($outgoing, $period['start'], $period['end']);
                $outgoingValue = $this->sumValueBetween($outgoing, $period['start'], $period['end']);
                $closingQty = $openingQty + $incomingQty - $outgoingQty;
                $closingValue = $openingValue + $incomingValue - $outgoingValue;

                if ($this->isEmptyRow($openingQty, $incomingQty, $outgoingQty, $closingQty, $openingValue, $incomingValue, $outgoingValue, $closingValue)) {
                    continue;
                }

                $rows[] = [
                    $period['label'],
                    $bahan->kode_bahan,
                    $bahan->nama_bahan,
                    $bahan->dataUnit->nama ?? '-',
                    round($openingQty, 2),
                    round($incomingQty, 2),
                    round($outgoingQty, 2),
                    round($closingQty, 2),
                    round($openingValue, 2),
                    round($incomingValue, 2),
                    round($outgoingValue, 2),
                    round($closingValue, 2),
                ];
            }
        }

        return $rows;
    }

    protected function sumQtyBefore(Collection $incoming, Collection $outgoing, Carbon $date): float
    {
        return $incoming
            ->filter(fn(array $transaction) => $transaction['date']->lt($date))
            ->sum('qty')
            - $outgoing
                ->filter(fn(array $transaction) => $transaction['date']->lt($date))
                ->sum('qty');
    }

    protected function sumValueBefore(Collection $incoming, Collection $outgoing, Carbon $date): float
    {
        return $incoming
            ->filter(fn(array $transaction) => $transaction['date']->lt($date))
            ->sum('value')
            - $outgoing
                ->filter(fn(array $transaction) => $transaction['date']->lt($date))
                ->sum('value');
    }

    protected function sumQtyBetween(Collection $transactions, Carbon $start, Carbon $end): float
    {
        return $transactions
            ->filter(fn(array $transaction) => $transaction['date']->gte($start) && $transaction['date']->lte($end))
            ->sum('qty');
    }

    protected function sumValueBetween(Collection $transactions, Carbon $start, Carbon $end): float
    {
        return $transactions
            ->filter(fn(array $transaction) => $transaction['date']->gte($start) && $transaction['date']->lte($end))
            ->sum('value');
    }

    protected function isEmptyRow(
        float $openingQty,
        float $incomingQty,
        float $outgoingQty,
        float $closingQty,
        float $openingValue,
        float $incomingValue,
        float $outgoingValue,
        float $closingValue
    ): bool {
        return round($openingQty, 2) === 0.0
            && round($incomingQty, 2) === 0.0
            && round($outgoingQty, 2) === 0.0
            && round($closingQty, 2) === 0.0
            && round($openingValue, 2) === 0.0
            && round($incomingValue, 2) === 0.0
            && round($outgoingValue, 2) === 0.0
            && round($closingValue, 2) === 0.0;
    }
}
