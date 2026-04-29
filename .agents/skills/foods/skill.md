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
- Route terkait: `pos-kantin.admin.foods.*`.
- Relasi: `Food belongsTo Supplier`, `Food hasMany SaleItem`.
- Makanan dari pemasok nonaktif tidak boleh dipilih pada transaksi baru.
- Nilai uang disimpan integer, format rupiah hanya di view.
