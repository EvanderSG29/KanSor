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
- Command terkait: `kansor:recalculate-canteen-totals`.
- Service terkait: `CanteenTotalAggregationService`.
- Hindari kalkulasi total harian tersebar di banyak tempat.
- Gunakan total `sales.total_canteen` sebagai sumber agregasi.

## Files Touched
- app/Models/CanteenTotal.php
- app/Http/Controllers/PosKantin/Admin/CanteenTotalController.php
- app/Services/CanteenTotalAggregationService.php
- app/Console/Commands/RecalculateCanteenTotals.php
- resources/views/kansor/reports/*.blade.php
- tests/Feature/Reports*Test.php

## Data Contract
- `date`: date|null
- `from`: date|null
- `to`: date|null
- `month`: string|null (`YYYY-MM`)

## Testing Wajib
- Test agregasi ulang per hari dan per bulan.
- Test output ringkasan per pemasok.
- Test perhitungan `canteen_totals` ketika transaksi berubah.

## Acceptance Criteria
- Admin dapat melihat ringkasan total harian dan bulanan.
- Command recalculation memperbarui `canteen_totals` dengan benar.
- Laporan menggunakan nilai `sales.total_canteen` sebagai sumber tunggal.

