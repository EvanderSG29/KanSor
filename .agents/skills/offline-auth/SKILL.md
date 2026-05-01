# Offline Auth

## Deskripsi
Skill ini menangani login online/offline untuk POS Kantin, termasuk fallback offline login, trusted device, dan validasi sesi.

## Tujuan
Automasi pengambilan keputusan antara autentikasi remote dan offline, serta aturan masa berlaku sesi offline.

## Trigger
- Ketika mengubah flow login atau otentikasi user.
- Ketika menambahkan fallback offline saat koneksi gagal.
- Ketika menambah pengaturan durasi sesi offline.
- Ketika memperbarui validasi trusted device dan status user.

## Files Touched
- app/Services/PosKantin/PosKantinUserAuthenticator.php
- app/Models/User.php
- app/Models/PosKantinDeviceCredential.php
- app/Http/Requests/LoginRequest.php
- config/pos_kantin.php
- resources/views/auth/login.blade.php
- database/migrations/*_add_offline_login_columns_to_users.php

## Data Contract
- `email`: string, required
- `password`: string, required
- `device_id`: string|null
- `offline_session_days`: integer, default 30
- `offline_login_expires_at`: datetime|null
- `trusted_device_expires_at`: datetime|null

## Aturan Implementasi
- Login remote harus dicoba terlebih dahulu.
- Jika remote gagal karena koneksi, fallback ke offline login lokal.
- Offline login hanya valid ketika user aktif dan `offline_login_expires_at` masih di masa depan.
- Ubah `offline_login_expires_at` saat password atau status user berubah.
- Jangan gunakan offline login untuk user nonaktif atau expired.

## Testing Wajib
- Test online login berhasil.
- Test offline login gagal jika user nonaktif.
- Test offline login gagal kalau `offline_login_expires_at` lewat.
- Test perubahan password menginvalidasi sesi offline lama.

## Acceptance Criteria
- User dapat login online jika server bisa dihubungi.
- Jika remote tidak tersedia, sistem mencoba login offline.
- User offline bisa login selama durasi offline aktif.
- Offline login diblokir ketika user dinonaktifkan atau sesi offline expired.
