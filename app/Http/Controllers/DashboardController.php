<?php

namespace App\Http\Controllers;

use App\Services\CsvService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly CsvService $csv)
    {
    }

    public function index(Request $request): View
    {
        $products = $this->csv->read('produk');
        $transactions = $this->csv->read('transaksi');
        $incomingGoods = $this->csv->read('barang_masuk');

        $today = now()->toDateString();
        $todaySalesRows = array_values(array_filter($transactions, fn (array $row): bool => str_starts_with($row['tanggal'] ?? '', $today)));
        $todayIncomingRows = array_values(array_filter($incomingGoods, fn (array $row): bool => str_starts_with($row['tanggal'] ?? '', $today)));

        $stokTotal = array_sum(array_map(fn (array $row): int => (int) ($row['stok'] ?? 0), $products));
        $todaySales = array_sum(array_map(fn (array $row): int => (int) ($row['total'] ?? 0), $todaySalesRows));
        $todayIncomingValue = array_sum(array_map(function (array $row): int {
            return ((int) ($row['qty'] ?? 0)) * ((int) ($row['harga'] ?? 0));
        }, $todayIncomingRows));

        return view('dashboard', [
            'user' => $request->session()->get('auth_user'),
            'productCount' => count($products),
            'stockTotal' => $stokTotal,
            'todayTransactionCount' => count($todaySalesRows),
            'todaySales' => $todaySales,
            'todayIncomingValue' => $todayIncomingValue,
        ]);
    }
}
