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
- Route terkait: `pos-kantin.admin.users.*`.
- Validasi wajib menggunakan `StoreUserRequest` dan `UpdateUserRequest`.
- `active=false` harus diselaraskan menjadi `status=nonaktif`.
- Jangan izinkan admin aktif terakhir dinonaktifkan.
- Password selalu di-hash oleh cast model.
