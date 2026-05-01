# Skill Reports

## Deskripsi
Skill ini menangani rekap total kantin, agregasi harian dan bulanan, ringkasan per pemasok, serta perintah recalculation total lokal.

## Trigger Conditions
- Ketika membuat laporan total kantin per hari.
- Ketika menulis command agregasi ulang.
- Ketika menghitung ringkasan pendapatan kantin per rentang tanggal.
- Ketika menampilkan rekap per pemasok.

## Use Cases
- Admin melihat total kantin harian.
- Admin memfilter rekap bulanan.
- Sistem mengagregasi ulang `canteen_totals` setelah transaksi berubah.
- Menampilkan kontribusi setiap pemasok ke pendapatan kantin.

## Input Parameters
- `date`: `date|null`, untuk recalculation satu hari.
- `from`: `date|null`, awal rentang.
- `to`: `date|null`, akhir rentang.
- `month`: `YYYY-MM|null`, filter bulanan.

## Output / Response
- Blade view ringkasan `canteen_totals`.
- Output command Artisan untuk hasil recalculation.
- Summary array per pemasok dan total pendapatan kantin.

## Implementation Notes
- Model terkait: `CanteenTotal`, `Sale`, `Supplier`.
- Controller terkait: `App\Http\Controllers\PosKantin\Admin\CanteenTotalController`.
- Command terkait: `pos-kantin:recalculate-canteen-totals`.
- Service terkait: `CanteenTotalAggregationService`.
- Hindari kalkulasi total harian tersebar di banyak tempat.
- Gunakan total `sales.total_canteen` sebagai sumber agregasi.
