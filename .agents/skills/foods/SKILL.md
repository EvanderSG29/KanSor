# Skill Foods

## Deskripsi
Skill ini menangani data makanan lokal yang terhubung ke pemasok, satuan, harga default, dan status aktif.

## Trigger Conditions
- Ketika membuat CRUD makanan.
- Ketika menambahkan validasi `supplier_id`.
- Ketika menyiapkan dropdown makanan pada form transaksi.
- Ketika mengatur harga default dan satuan item.

## Use Cases
- Menambah makanan baru milik pemasok tertentu.
- Mengubah satuan atau harga default makanan.
- Menonaktifkan makanan lama tanpa menghapus histori.
- Menyaring makanan berdasarkan pemasok.

## Input Parameters
- `supplier_id`: `integer`, wajib, harus ada pada tabel `suppliers`.
- `name`: `string`, wajib, maks `255`.
- `unit`: `string`, wajib, maks `50`.
- `default_price`: `integer|null`, minimal `0`.
- `active`: `boolean`.

## Output / Response
- Redirect ke daftar makanan.
- Blade view daftar, create, edit.
- JSON payload sinkronisasi food.

## Implementation Notes
- Model terkait: `Food`, `Supplier`, `SaleItem`.
- Controller terkait: `App\Http\Controllers\PosKantin\Admin\FoodController`.
- Route terkait: `kansor.admin.foods.*`.
- Relasi: `Food belongsTo Supplier`, `Food hasMany SaleItem`.
- Makanan dari pemasok nonaktif tidak boleh dipilih pada transaksi baru.
- Nilai uang disimpan integer, format rupiah hanya di view.

## Files Touched
- app/Models/Food.php
- app/Http/Controllers/PosKantin/Admin/FoodController.php
- app/Http/Controllers/PosKantin/FoodController.php
- app/Http/Requests/StoreFoodRequest.php
- app/Http/Requests/UpdateFoodRequest.php
- resources/views/kansor/foods/*.blade.php
- routes/web.php
- tests/Feature/Food*Test.php

## Data Contract
- `supplier_id`: integer, required, must exist in suppliers
- `name`: string, required, max 255
- `unit`: string, required, max 50
- `default_price`: integer|null, min 0
- `active`: boolean

## Testing Wajib
- Validasi supplier dan field food harus lulus.
- CRUD makanan harus bekerja dengan redirect atau JSON response sukses.
- Makanan nonaktif tidak tampil pada dropdown transaksi.
- Relasi `Food belongsTo Supplier` harus teruji.

## Acceptance Criteria
- Admin dapat membuat, mengedit, dan menonaktifkan makanan.
- Dropdown makanan pada transaksi hanya menunjukkan makanan aktif dari supplier terpilih.
- Harga disimpan sebagai integer, bukan string terformat.
- Makanan nonaktif tidak dapat dipilih untuk transaksi baru.

