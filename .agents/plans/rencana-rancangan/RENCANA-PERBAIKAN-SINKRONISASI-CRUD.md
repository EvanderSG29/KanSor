# Rencana Perbaikan Sinkronisasi CRUD Laravel ke Apps Script

## Summary
- Masalah saat ini: UI menampilkan operasi CRUD lokal sebagai berhasil, tetapi data belum benar-benar masuk ke spreadsheet Apps Script.
- Akar masalah utama ada pada mismatch `action`, mismatch payload, dan gap model data antara Laravel lokal dengan backend spreadsheet.
- Guard sementara sudah dipasang di codebase: UI sekarang memberi warning bahwa perubahan lokal belum tentu sudah tersinkron ke spreadsheet.
- Rencana ini memecah perbaikan menjadi patch kecil yang bisa diverifikasi satu per satu.

## Temuan Saat Ini
- Laravel masih memakai action seperti `createSupplier`, `updateUser`, `createTransaction`, dan sejenisnya.
- Apps Script aktif hanya mengenal action seperti `saveSupplier`, `saveUser`, `saveTransaction`, dan `deleteTransaction`.
- Payload supplier, user, dan transaction dari Laravel tidak cocok dengan field yang diminta Apps Script.
- Entitas `food` belum punya kontrak backend yang jelas di Apps Script aktif.
- Model transaksi lokal bersifat parent-child (`sale` + banyak `sale_items`), sedangkan sheet `transactions` di Apps Script masih berbentuk baris flat.

## Target Akhir
- Operasi CRUD lokal tetap berjalan normal.
- Untuk entity yang sudah kompatibel, data benar-benar tersimpan ke spreadsheet.
- Untuk entity yang belum kompatibel, aplikasi menampilkan status yang jujur dan tidak menyesatkan.
- Alur sinkronisasi punya kontrak tetap yang terdokumentasi dan bisa dites.

## Prinsip Eksekusi
1. Jangan aktifkan sinkronisasi penuh sebelum kontrak request/response stabil.
2. Perbaiki entity yang sederhana lebih dulu: `supplier` dan `user`.
3. `transaction` dikerjakan setelah mapping data final disepakati.
4. `food` tidak boleh dipaksa sync sebelum backend Apps Script punya desain field yang pasti.
5. Setiap patch wajib selesai dengan test lokal dan verifikasi spreadsheet manual.

## Patch Breakdown
| Patch | Fokus | File Utama | Acceptance |
|---|---|---|---|
| Patch 0 | Guard UI agar tidak mengklaim sync remote berhasil | `app/Services/PosKantin/PosKantinMutationDispatcher.php`, controller CRUD lokal, `resources/views/kansor/partials/alerts.blade.php` | Sudah selesai. User melihat warning bila perubahan hanya tersimpan lokal. |
| Patch 1 | Bekukan kontrak sinkronisasi entity per entity | Dokumen ini + update test kontrak | Ada daftar action final, payload final, dan entity yang memang belum didukung. |
| Patch 2 | Sinkronisasi `supplier` | `PosKantinClient`, `SupplierController`, Apps Script `Suppliers.gs`, test HTTP fake | Create/update/delete supplier benar-benar menulis ke sheet `suppliers`. |
| Patch 3 | Sinkronisasi `user` | `PosKantinClient`, `UserController`, Apps Script `Users.gs`, test HTTP fake | Create/update/nonaktif user benar-benar menulis ke sheet `users`. |
| Patch 4 | Keputusan final untuk `food` | Dokumen kontrak + Apps Script baru bila disetujui | Ada keputusan tegas: `food` disinkronkan atau tetap lokal-only. |
| Patch 5 | Redesign sinkronisasi `transaction` | `SaleController`, `Admin\\SaleController`, Apps Script `Transactions.gs`, test integrasi | Save/update/delete transaksi punya mapping yang valid ke spreadsheet. |
| Patch 6 | Perapihan status sinkronisasi | halaman sync, log, outbox, failed state | User bisa membedakan `queued`, `unsupported`, `failed`, dan `applied`. |

## Kontrak yang Harus Dibekukan

### Supplier
- Action Laravel final: `saveSupplier`.
- Operasi create dan update memakai action yang sama, dibedakan oleh ada/tidaknya `id`.
- Nonaktifkan supplier juga memakai `saveSupplier` dengan `isActive=false`.
- Mapping minimum:
  - `name` -> `supplierName`
  - `contact_info` -> dipecah atau dipetakan jelas ke `contactName` / `contactPhone`
  - `percentage_cut` -> `commissionRate`
  - `active` -> `isActive`

### User
- Action Laravel final: `saveUser`.
- Nonaktifkan user tidak perlu `deleteUser`; cukup update `status=nonaktif`.
- Mapping minimum:
  - `name` -> `fullName`
  - perlu aturan tetap untuk `nickname`
  - `email` -> `email`
  - `role` -> `role`
  - `status` -> `status`
  - password hanya dikirim bila diubah

### Food
- Status saat ini: belum ada kontrak backend aktif yang pasti.
- Keputusan wajib:
  - Opsi A: buat sheet + endpoint Apps Script baru untuk `food`
  - Opsi B: tetapkan `food` sebagai data lokal-only dan jangan tampilkan seolah tersinkron

### Transaction
- Status saat ini: paling kompleks dan tidak boleh dikerjakan setengah jadi.
- Keputusan wajib:
  - Satu `sale` lokal menjadi satu baris spreadsheet
  - atau satu `sale_item` menjadi satu baris spreadsheet
- Setelah keputusan diambil, semua field turunan seperti total supplier, total kantin, status bayar, dan item harus mengikuti satu kontrak tetap.

## Langkah Eksekusi Pasti

### Langkah 1
- Selesaikan Patch 1 dengan membekukan action final di Laravel.
- Hapus pemakaian action `create*`, `update*`, `delete*` untuk entity yang sebenarnya memakai action `save*`.
- Perbarui test `PosKantinMutationClientTest` agar mengikuti kontrak final, bukan kontrak lama yang salah.

### Langkah 2
- Implementasikan adapter payload supplier di satu tempat, bukan tersebar di controller.
- Ubah alur create/update/nonaktif supplier agar semuanya mengarah ke action `saveSupplier`.
- Tambahkan test sukses, test `success=false`, dan test payload mismatch.
- Verifikasi manual: tambah supplier dari UI lalu cek sheet `suppliers`.

### Langkah 3
- Implementasikan adapter payload user.
- Tetapkan aturan `nickname` default yang stabil.
- Ubah alur create/update/nonaktif user agar semuanya mengarah ke action `saveUser`.
- Pastikan password tidak selalu ditimpa bila update tanpa password baru.
- Verifikasi manual: tambah user dari UI lalu cek sheet `users`.

### Langkah 4
- Putuskan nasib `food` lebih dulu sebelum ada coding lanjutan.
- Bila `food` ikut remote:
  - tambah sheet schema
  - tambah endpoint Apps Script
  - tambah adapter payload Laravel
- Bila `food` tetap lokal-only:
  - pertahankan warning
  - jangan masukkan `food` ke klaim sinkronisasi remote

### Langkah 5
- Finalkan desain transaksi.
- Buat mapping tunggal dari model lokal ke bentuk payload Apps Script.
- Ubah create/update/delete transaksi hanya setelah kontrak transaksi selesai dan dites.
- Tambahkan test skenario: create, update, delete, supplier sudah nonaktif, transaksi terkunci, dan backend reject.
- Verifikasi manual: buat transaksi dari UI lalu cocokkan hasil row di spreadsheet.

### Langkah 6
- Rapikan status sinkronisasi agar entity unsupported tidak sekadar dianggap warning generik.
- Tambahkan kategori yang jelas:
  - `applied`
  - `queued`
  - `unsupported`
  - `failed`
- Sinkronkan tampilan halaman sync, log, dan indikator UI dengan kategori ini.

## Urutan Implementasi yang Disarankan
1. Patch 1
2. Patch 2
3. Patch 3
4. Keputusan Patch 4
5. Patch 5
6. Patch 6

## Verifikasi per Patch
- Jalankan `vendor/bin/pint --dirty --format agent` setelah edit PHP.
- Jalankan `php artisan test --compact` dengan filter test yang relevan pada patch aktif.
- Gunakan `Http::fake()` dan `Http::preventStrayRequests()` untuk test kontrak HTTP.
- Setelah patch entity selesai, lakukan 1 verifikasi manual dari UI ke spreadsheet target.
- Jangan lanjut ke patch berikutnya sebelum spreadsheet manual check lulus.

## Risiko
- Mengganti action tanpa menyesuaikan payload akan tetap gagal walau nama action sudah benar.
- Memaksakan sinkronisasi `food` atau `transaction` terlalu cepat akan menambah data mismatch di spreadsheet.
- Mengubah Apps Script tanpa redeploy Web App akan membuat code Laravel baru tetap gagal di environment live.
- Menyamakan status lokal dengan status remote tanpa kategori khusus akan mengulang masalah yang sama.

## Rollback
1. Pertahankan guard warning yang sudah ada.
2. Bila patch entity baru gagal, kembalikan entity itu ke mode lokal-only sementara.
3. Jangan rollback dengan menghapus warning UI sebelum kontrak remote benar-benar stabil.
4. Redeploy Apps Script hanya setelah versi lama dan versi baru bisa dibedakan dengan jelas.

## Definition of Done
- Supplier sync lulus end-to-end.
- User sync lulus end-to-end.
- Keputusan `food` terdokumentasi tegas.
- Transaksi punya kontrak final dan lulus verifikasi spreadsheet.
- UI tidak lagi memberi kesan palsu bahwa semua CRUD sudah pasti masuk spreadsheet.

