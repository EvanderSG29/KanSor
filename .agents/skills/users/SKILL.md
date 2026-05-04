# Skill Users

## Deskripsi
Skill ini menangani CRUD pengguna lokal POS Kantin untuk peran admin dan petugas, termasuk aktivasi, nonaktifkan akun, validasi email unik, dan reset password.

## Trigger Conditions
- Ketika membuat controller CRUD User.
- Ketika menulis Form Request user admin/petugas.
- Ketika menambahkan guard admin aktif terakhir.
- Ketika menyinkronkan data user ke PosKantinClient.

## Use Cases
- Menambah admin baru.
- Menambah petugas kantin baru.
- Mengubah role atau status pengguna.
- Menonaktifkan pengguna tanpa hard delete.

## Input Parameters
- `name`: `string`, wajib, maks `255`.
- `email`: `string`, format email, wajib, unik.
- `password`: `string`, minimal `8`, confirmed, opsional saat update.
- `role`: `string`, wajib, `admin|petugas`.
- `active`: `boolean`, status aktif lokal.

## Output / Response
- Redirect ke daftar pengguna dengan flash success/error.
- JSON payload sinkronisasi user bila dipakai oleh job async.
- Blade view daftar, form create, dan form edit.

## Implementation Notes
- Model terkait: `User`.
- Controller terkait: `App\Http\Controllers\PosKantin\Admin\UserController`.
- Route terkait: `kansor.admin.users.*`.
- Validasi wajib menggunakan `StoreUserRequest` dan `UpdateUserRequest`.
- `active=false` harus diselaraskan menjadi `status=nonaktif`.
- Jangan izinkan admin aktif terakhir dinonaktifkan.
- Password selalu di-hash oleh cast model.

## Files Touched
- app/Models/User.php
- app/Http/Controllers/PosKantin/Admin/UserController.php
- app/Http/Requests/StoreUserRequest.php
- app/Http/Requests/UpdateUserRequest.php
- resources/views/kansor/users/*.blade.php
- tests/Feature/User*Test.php

## Data Contract
- `name`: string, required, max 255
- `email`: string, required, valid email, unique
- `password`: string|required on create, min 8, confirmed
- `role`: string, required, one of `admin|petugas`
- `active`: boolean

## Testing Wajib
- Test validasi email unik dan password confirmed.
- Test peran dan status active/nonactive.
- Test admin terakhir tidak bisa dinonaktifkan.
- Test sinkronisasi user local ke PosKantinClient bila diperlukan.

## Acceptance Criteria
- User admin dan petugas dapat dibuat dan diperbarui.
- Status nonaktif tidak menghapus histori user.
- Guard admin dan validasi role berjalan sesuai aturan.
- Data user dapat diolah untuk sinkronisasi dengan layanan POS.

