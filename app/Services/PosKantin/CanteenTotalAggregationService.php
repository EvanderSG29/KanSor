<?php

namespace App\Services\PosKantin;

use App\Models\CanteenTotal;
use App\Models\Sale;
use Carbon\CarbonImmutable;

class CanteenTotalAggregationService
{
    public function recalculateForDate(string $date): CanteenTotal
    {
        $normalizedDate = CarbonImmutable::parse($date, 'Asia/Jakarta')->toDateString();
        $totalAmount = Sale::query()
            ->whereDate('date', $normalizedDate)
            ->sum('total_canteen');

        $canteenTotal = CanteenTotal::query()->firstOrNew([
            'date' => $normalizedDate,
        ]);

        $canteenTotal->total_amount = $totalAmount;
        $canteenTotal->status_iii ??= 'belum';
        $canteenTotal->save();

        return $canteenTotal;
    }

    public function recalculateRange(string $from, string $to): int
    {
        $startDate = CarbonImmutable::parse($from, 'Asia/Jakarta');
        $endDate = CarbonImmutable::parse($to, 'Asia/Jakarta');
        $processedDays = 0;

        for ($date = $startDate; $date->lte($endDate); $date = $date->addDay()) {
            $this->recalculateForDate($date->toDateString());
            $processedDays++;
        }

        return $processedDays;
    }
}
