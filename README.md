# KanSorv1 - Aplikasi Keuangan Kantin (CSV)

Aplikasi pencatatan keuangan kantin berbasis Laravel dengan 3 role:

- `admin`
- `petugas`
- `pemasok`

Penyimpanan data menggunakan file CSV di `storage/app/data`, tanpa MySQL.

## Fitur

- Login berbasis CSV (`users.csv`)
- Role middleware (`admin`, `petugas`, `pemasok`)
- Kelola user (admin)
- Kelola produk dan stok
- Input transaksi penjualan + detail transaksi
- Input barang masuk dari pemasok
- Dashboard ringkasan
- Laporan keuangan + export CSV

## Struktur CSV

File yang otomatis dibuat:

- `users.csv`
- `produk.csv`
- `transaksi.csv`
- `detail_transaksi.csv`
- `barang_masuk.csv`

## Akun Default

Saat pertama dijalankan, sistem otomatis membuat akun:

- Username: `admin`
- Password: `admin123`

Segera ubah password setelah login pertama.

## Menjalankan Aplikasi

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

Akses: `http://127.0.0.1:8000`

## Catatan NativePHP

Project ini sudah siap sebagai web app Laravel. Untuk desktop via NativePHP, instal paket NativePHP di tahap berikutnya (butuh akses internet dan dependency tambahan).

## Testing

```bash
php artisan test
```

## Override Lokasi Data CSV (Opsional)

Secara default data berada di `storage/app/data`.

Kalau ingin ganti lokasi:

```env
CSV_DATA_DIR=C:\path\ke\folder\data
```
