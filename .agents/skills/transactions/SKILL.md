# Skill Transactions

## Deskripsi
Skill ini menangani transaksi harian petugas, item penjualan multi-baris, konfirmasi admin, koreksi transaksi, dan sinkronisasi eksternal best-effort.

## Trigger Conditions
- Ketika membuat form input transaksi harian.
- Ketika menulis validasi nested array `items`.
- Ketika menghitung `total_item`, `cut_amount`, `total_supplier`, dan `total_canteen`.
- Ketika mengunci transaksi setelah status final.

## Use Cases
- Petugas membuat transaksi untuk satu pemasok.
- Petugas menambah beberapa makanan dalam satu transaksi.
- Admin mengoreksi transaksi yang salah.
- Admin mengubah status dibayar atau disetor.

## Input Parameters
- `date`: `date`, wajib, tidak boleh melebihi hari ini `Asia/Jakarta`.
- `supplier_id`: `integer`, wajib, pemasok aktif.
- `additional_users`: `array<int>|null`, petugas tambahan aktif.
- `items`: `array`, minimal satu item.
- `items.*.food_id`: `integer`, wajib, makanan aktif milik pemasok.
- `items.*.unit`: `string`, wajib.
- `items.*.quantity`: `integer`, minimal `1`.
- `items.*.leftover`: `integer|null`, minimal `0`.
- `items.*.price_per_unit`: `integer`, minimal `1`.

## Output / Response
- Redirect ke detail transaksi atau daftar transaksi.
- Blade view create, edit, show, index.
- JSON payload sinkronisasi transaksi untuk job async.

## Implementation Notes
- Model terkait: `Sale`, `SaleItem`, `Supplier`, `Food`, `User`.
- Controller terkait: `App\Http\Controllers\PosKantin\SaleController`, `App\Http\Controllers\PosKantin\Admin\SaleController`.
- Route terkait: `pos-kantin.sales.*`, `pos-kantin.admin.sales.*`.
- Service terkait: `SaleCalculationService`, `CanteenTotalAggregationService`.
- Policy terkait: `SalePolicy`.
- Soft delete dipakai untuk pembatalan transaksi.
- Transaksi petugas hanya boleh diubah pada hari yang sama dan sebelum status final.
