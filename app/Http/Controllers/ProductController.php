<?php

namespace App\Http\Controllers;

use App\Services\CsvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly CsvService $csv)
    {
    }

    public function index(): View
    {
        $products = $this->csv->read('produk');
        usort($products, fn (array $a, array $b): int => strcmp($a['nama_produk'] ?? '', $b['nama_produk'] ?? ''));

        return view('produk.index', ['products' => $products]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_produk' => ['required', 'string', 'max:100'],
            'harga_jual' => ['required', 'integer', 'min:0'],
            'stok' => ['required', 'integer', 'min:0'],
            'harga_beli' => ['required', 'integer', 'min:0'],
        ]);

        $this->csv->insert('produk', $data);

        return back()->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'nama_produk' => ['required', 'string', 'max:100'],
            'harga_jual' => ['required', 'integer', 'min:0'],
            'stok' => ['required', 'integer', 'min:0'],
            'harga_beli' => ['required', 'integer', 'min:0'],
        ]);

        $updated = $this->csv->updateById('produk', $id, $data);
        if ($updated === null) {
            return back()->with('error', 'Produk tidak ditemukan.');
        }

        return back()->with('success', 'Produk berhasil diubah.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $deleted = $this->csv->deleteById('produk', $id);
        if (! $deleted) {
            return back()->with('error', 'Produk tidak ditemukan.');
        }

        return back()->with('success', 'Produk berhasil dihapus.');
    }
}
