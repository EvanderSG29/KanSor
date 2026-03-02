<?php

namespace App\Http\Controllers;

use App\Services\CsvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class IncomingGoodsController extends Controller
{
    public function __construct(private readonly CsvService $csv)
    {
    }

    public function index(): View
    {
        $products = $this->csv->read('produk');
        $incomingGoods = $this->csv->read('barang_masuk');

        usort($incomingGoods, fn (array $a, array $b): int => ((int) ($b['id'] ?? 0)) <=> ((int) ($a['id'] ?? 0)));

        $productById = [];
        foreach ($products as $product) {
            $productById[$product['id']] = $product;
        }

        return view('barang-masuk.index', [
            'products' => $products,
            'incomingGoods' => array_slice($incomingGoods, 0, 30),
            'productById' => $productById,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'produk_id' => ['required', 'integer', 'min:1'],
            'qty' => ['required', 'integer', 'min:1'],
            'harga' => ['required', 'integer', 'min:0'],
        ]);

        $user = $request->session()->get('auth_user');
        $pemasokId = (string) ($user['id'] ?? '');
        if ($pemasokId === '') {
            return back()->with('error', 'Sesi user tidak valid, silakan login ulang.');
        }

        $products = $this->csv->read('produk');
        $incomingGoods = $this->csv->read('barang_masuk');

        $productIndex = null;
        foreach ($products as $idx => $product) {
            if ((int) ($product['id'] ?? 0) === (int) $data['produk_id']) {
                $productIndex = $idx;
                break;
            }
        }

        if ($productIndex === null) {
            return back()->with('error', 'Produk tidak ditemukan.');
        }

        $products[$productIndex]['stok'] = (string) (((int) ($products[$productIndex]['stok'] ?? 0)) + $data['qty']);
        $products[$productIndex]['harga_beli'] = (string) $data['harga'];

        $incomingGoods[] = [
            'id' => (string) $this->csv->nextId('barang_masuk'),
            'tanggal' => now()->format('Y-m-d H:i:s'),
            'pemasok_id' => $pemasokId,
            'produk_id' => (string) $data['produk_id'],
            'qty' => (string) $data['qty'],
            'harga' => (string) $data['harga'],
        ];

        $originalProducts = $this->csv->read('produk');
        $originalIncomingGoods = $this->csv->read('barang_masuk');

        try {
            $this->csv->write('produk', $products);
            $this->csv->write('barang_masuk', $incomingGoods);
        } catch (Throwable $exception) {
            $this->csv->write('produk', $originalProducts);
            $this->csv->write('barang_masuk', $originalIncomingGoods);

            report($exception);

            return back()->with('error', 'Gagal menyimpan data barang masuk.');
        }

        return back()->with('success', 'Barang masuk berhasil disimpan.');
    }
}
