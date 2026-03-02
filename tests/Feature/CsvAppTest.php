<?php

use App\Services\CsvService;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $dir = storage_path('framework/testing/csv');
    putenv("CSV_DATA_DIR={$dir}");
    $_ENV['CSV_DATA_DIR'] = $dir;
    $_SERVER['CSV_DATA_DIR'] = $dir;

    if (File::isDirectory($dir)) {
        File::deleteDirectory($dir);
    }
});

afterEach(function (): void {
    $dir = env('CSV_DATA_DIR');
    if (is_string($dir) && File::isDirectory($dir)) {
        File::deleteDirectory($dir);
    }
});

test('login page can be opened', function (): void {
    $response = $this->get('/login');

    $response->assertOk();
    $response->assertSee('Login Kantin Sore');
});

test('default admin can login', function (): void {
    // Resolve service once to trigger CSV bootstrap + default admin creation.
    app(CsvService::class);

    $response = $this->post('/login', [
        'username' => 'admin',
        'password' => 'admin123',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertEquals('admin', session('auth_user.role'));
});

test('admin can create product', function (): void {
    app(CsvService::class);

    $this->post('/login', [
        'username' => 'admin',
        'password' => 'admin123',
    ])->assertRedirect('/dashboard');

    $this->post('/produk', [
        'nama_produk' => 'Nasi Goreng',
        'harga_jual' => 12000,
        'stok' => 20,
        'harga_beli' => 8000,
    ])->assertRedirect();

    $products = app(CsvService::class)->read('produk');
    expect($products)->toHaveCount(1);
    expect($products[0]['nama_produk'])->toBe('Nasi Goreng');
});
