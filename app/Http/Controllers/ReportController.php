<?php

namespace App\Http\Controllers;

use App\Services\CsvService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly CsvService $csv)
    {
    }

    public function index(Request $request): View
    {
        $start = $request->query('start', now()->startOfMonth()->toDateString());
        $end = $request->query('end', now()->toDateString());

        $transactions = $this->filterByDate($this->csv->read('transaksi'), $start, $end);
        $incomingGoods = $this->filterByDate($this->csv->read('barang_masuk'), $start, $end);

        $salesTotal = array_sum(array_map(fn (array $row): int => (int) ($row['total'] ?? 0), $transactions));
        $purchaseTotal = array_sum(array_map(function (array $row): int {
            return ((int) ($row['qty'] ?? 0)) * ((int) ($row['harga'] ?? 0));
        }, $incomingGoods));

        return view('laporan.index', [
            'start' => $start,
            'end' => $end,
            'salesTotal' => $salesTotal,
            'purchaseTotal' => $purchaseTotal,
            'grossMargin' => $salesTotal - $purchaseTotal,
            'transactionCount' => count($transactions),
            'incomingCount' => count($incomingGoods),
            'transactions' => array_slice($transactions, 0, 50),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $start = $request->query('start', now()->startOfMonth()->toDateString());
        $end = $request->query('end', now()->toDateString());

        $transactions = $this->filterByDate($this->csv->read('transaksi'), $start, $end);

        return response()->streamDownload(function () use ($transactions): void {
            $out = fopen('php://output', 'wb');
            if ($out === false) {
                return;
            }

            fputcsv($out, ['id', 'tanggal', 'petugas_id', 'total', 'status']);
            foreach ($transactions as $row) {
                fputcsv($out, [
                    $row['id'] ?? '',
                    $row['tanggal'] ?? '',
                    $row['petugas_id'] ?? '',
                    $row['total'] ?? '',
                    $row['status'] ?? '',
                ]);
            }

            fclose($out);
        }, 'laporan-transaksi.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<int, array<string, string>>
     */
    private function filterByDate(array $rows, string $start, string $end): array
    {
        $startTs = strtotime($start.' 00:00:00');
        $endTs = strtotime($end.' 23:59:59');

        return array_values(array_filter($rows, function (array $row) use ($startTs, $endTs): bool {
            $ts = strtotime((string) ($row['tanggal'] ?? ''));
            if ($ts === false) {
                return false;
            }

            return $ts >= $startTs && $ts <= $endTs;
        }));
    }
}
