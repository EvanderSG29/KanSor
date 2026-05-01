# Skill Suppliers

## Deskripsi
Skill ini menangani manajemen pemasok lokal POS Kantin, termasuk data kontak, potongan kantin, status aktif, dan sinkronisasi pemasok.

## Trigger Conditions
- Ketika membuat CRUD pemasok.
- Ketika menulis validasi `percentage_cut`.
- Ketika memfilter pemasok aktif untuk transaksi baru.
- Ketika memperbarui payload sinkronisasi supplier.

## Use Cases
- Menambah pemasok baru.
- Mengubah kontak pemasok.
- Menonaktifkan pemasok dari dropdown transaksi.
- Menampilkan daftar pemasok dengan status aktif.

## Input Parameters
- `name`: `string`, wajib, maks `255`.
- `contact_info`: `string|null`, maks `255`.
- `percentage_cut`: `numeric`, wajib, rentang `0-100`.
- `active`: `boolean`.

## Output / Response
- Redirect ke daftar pemasok.
- Blade view daftar, create, edit.
- JSON payload sinkronisasi supplier.

## Implementation Notes
- Model terkait: `Supplier`, `Food`, `Sale`.
- Controller terkait: `App\Http\Controllers\PosKantin\Admin\SupplierController`.
- Route terkait: `pos-kantin.admin.suppliers.*`.
- Relasi: `Supplier hasMany Food`, `Supplier hasMany Sale`.
- Jangan hard delete pemasok aktif historis.
- Pemasok nonaktif tidak boleh muncul pada transaksi baru.
