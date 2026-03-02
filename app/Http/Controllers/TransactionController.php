<?php

namespace App\Http\Controllers;

use App\Services\CsvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class TransactionController extends Controller
{
    public function __construct(private readonly CsvService $csv)
    {
    }

    public function index(): View
    {
        $products = $this->csv->read('produk');
        $transactions = $this->csv->read('transaksi');
        $details = $this->csv->read('detail_transaksi');

        usort($transactions, fn (array $a, array $b): int => ((int) ($b['id'] ?? 0)) <=> ((int) ($a['id'] ?? 0)));

        $productById = [];
        foreach ($products as $product) {
            $productById[$product['id']] = $product;
        }

        $detailsByTransaction = [];
        foreach ($details as $detail) {
            $detailsByTransaction[$detail['transaksi_id']][] = $detail;
        }

        return view('transaksi.index', [
            'products' => $products,
            'transactions' => array_slice($transactions, 0, 30),
            'detailsByTransaction' => $detailsByTransaction,
            'productById' => $productById,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'produk_id' => ['required', 'array', 'min:1'],
            'produk_id.*' => ['required', 'integer', 'min:1'],
            'qty' => ['required', 'array', 'min:1'],
            'qty.*' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:lunas,belum_lunas'],
        ]);

        $user = $request->session()->get('auth_user');
        $petugasId = (string) ($user['id'] ?? '');
        if ($petugasId === '') {
            return back()->with('error', 'Sesi user tidak valid, silakan login ulang.');
        }

        $products = $this->csv->read('produk');
        $transactions = $this->csv->read('transaksi');
        $details = $this->csv->read('detail_transaksi');

        $productById = [];
        foreach ($products as $idx => $product) {
            $productById[(int) $product['id']] = ['data' => $product, 'index' => $idx];
        }

        $lineItems = [];
        $total = 0;

        foreach ($validated['produk_id'] as $idx => $productId) {
            $qty = (int) ($validated['qty'][$idx] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $current = $productById[$productId]['data'] ?? null;
            if ($current === null) {
                return back()->with('error', "Produk ID {$productId} tidak ditemukan.");
            }

            $stok = (int) ($current['stok'] ?? 0);
            if ($stok < $qty) {
                return back()->with('error', "Stok {$current['nama_produk']} tidak cukup.");
            }

            $hargaJual = (int) ($current['harga_jual'] ?? 0);
            $subtotal = $hargaJual * $qty;

            $lineItems[] = [
                'produk_id' => (string) $productId,
                'qty' => (string) $qty,
                'subtotal' => (string) $subtotal,
            ];

            $total += $subtotal;

            $productIndex = $productById[$productId]['index'];
            $products[$productIndex]['stok'] = (string) ($stok - $qty);
        }

        if ($lineItems === []) {
            return back()->with('error', 'Minimal 1 item transaksi harus diisi.');
        }

        $transactionId = (string) $this->csv->nextId('transaksi');
        $detailNextId = $this->csv->nextId('detail_transaksi');

        $transactions[] = [
            'id' => $transactionId,
            'tanggal' => now()->format('Y-m-d H:i:s'),
            'petugas_id' => $petugasId,
            'total' => (string) $total,
            'status' => $validated['status'],
        ];

        foreach ($lineItems as $item) {
            $details[] = [
                'id' => (string) $detailNextId++,
                'transaksi_id' => $transactionId,
                'produk_id' => $item['produk_id'],
                'qty' => $item['qty'],
                'subtotal' => $item['subtotal'],
            ];
        }

        $originalProducts = $this->csv->read('produk');
        $originalTransactions = $this->csv->read('transaksi');
        $originalDetails = $this->csv->read('detail_transaksi');

        try {
            $this->csv->write('produk', $products);
            $this->csv->write('transaksi', $transactions);
            $this->csv->write('detail_transaksi', $details);
        } catch (Throwable $exception) {
            $this->csv->write('produk', $originalProducts);
            $this->csv->write('transaksi', $originalTransactions);
            $this->csv->write('detail_transaksi', $originalDetails);

            report($exception);

            return back()->with('error', 'Gagal menyimpan transaksi.');
        }

        return back()->with('success', 'Transaksi berhasil disimpan.');
    }
}
