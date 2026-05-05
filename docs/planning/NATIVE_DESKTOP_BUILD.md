# Native Desktop Build (Windows)

Target utama KanSor adalah desktop-native offline-first, bukan web-first.

## Build Windows `.exe`

```bash
php artisan native:build win
```

## First Run Local DB

- Saat aplikasi pertama kali dibuka, proses setup harus memastikan DB lokal tersedia.
- View readiness: `resources/views/setup/schema-readiness.blade.php`.
- Jika schema belum siap, jalankan migrasi lokal secara aman sebelum user masuk modul POS.

## Safe Local Migration

- Gunakan endpoint/setup internal yang sudah ada untuk memeriksa readiness schema.
- Pastikan migrasi tidak menghapus data user tanpa persetujuan (`migrate:fresh` hanya untuk environment dev/test).

## Installer `.exe`

- Konfigurasi builder ada di `nativephp/electron/electron-builder.mjs`.
- Setelah build selesai, paket installer Windows dihasilkan dari pipeline NativePHP/Electron.
- Distribusi ke user dilakukan melalui installer `.exe`, bukan mengandalkan deployment web.

## Recommended Validation

1. `php artisan native:build win`
2. Jalankan aplikasi hasil build di mesin Windows bersih.
3. Verifikasi first run membentuk DB lokal.
4. Verifikasi migrasi lokal berjalan tanpa error.
5. Verifikasi login online sekali lalu offline tetap berjalan sesuai kebijakan sesi.
