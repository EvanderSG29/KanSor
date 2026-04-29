<?php

namespace App\Console\Commands;

use App\Services\PosKantin\CanteenTotalAggregationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class PosKantinRecalculateCanteenTotalsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'pos-kantin:recalculate-canteen-totals
                            {--date= : Recalculate a single operational date (Y-m-d)}
                            {--from= : Start date for recalculation range (Y-m-d)}
                            {--to= : End date for recalculation range (Y-m-d)}';

    /**
     * @var string
     */
    protected $description = 'Recalculate local canteen totals from POS sales records.';

    public function handle(CanteenTotalAggregationService $aggregationService): int
    {
        $date = $this->option('date');
        $from = $this->option('from');
        $to = $this->option('to');

        if (is_string($date) && $date !== '') {
            $result = $aggregationService->recalculateForDate($date);

            $this->info(sprintf(
                'Total kantin tanggal %s diperbarui menjadi Rp %s.',
                $result->date->format('Y-m-d'),
                number_format($result->total_amount, 0, ',', '.'),
            ));

            return self::SUCCESS;
        }

        if (is_string($from) && $from !== '' && is_string($to) && $to !== '') {
            $processedDays = $aggregationService->recalculateRange($from, $to);

            $this->info(sprintf('Rekap total kantin diperbarui untuk %d hari.', $processedDays));

            return self::SUCCESS;
        }

        $today = CarbonImmutable::now('Asia/Jakarta')->toDateString();
        $result = $aggregationService->recalculateForDate($today);

        $this->info(sprintf(
            'Total kantin tanggal %s diperbarui menjadi Rp %s.',
            $result->date->format('Y-m-d'),
            number_format($result->total_amount, 0, ',', '.'),
        ));

        return self::SUCCESS;
    }
}
