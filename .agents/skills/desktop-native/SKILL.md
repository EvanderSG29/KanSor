# desktop-native

## Trigger
Use when task mentions NativePHP/Electron build, Windows `.exe`, installer setup, first-run local DB, or migration safety.

## Data Contract
- Inputs: build target (`win`), app metadata, first-run flags.
- Outputs: build artifact path, installer notes, migration readiness status.

## Files
- `nativephp/electron/electron-builder.mjs`
- `app/Providers/NativeAppServiceProvider.php`
- `resources/views/setup/schema-readiness.blade.php`
- `tests/Feature/NativeDesktopInteractionTest.php`

## Tests
- `php artisan native:build win`
- `php artisan test --compact --filter=NativeDesktopInteractionTest`

## Acceptance Criteria
- Build command for windows documented and reproducible.
- First-run creates local DB if missing.
- Local migrations run safely and are recoverable.
- Installer setup for `.exe` documented.
# Desktop Native

## Deskripsi
Skill ini menangani fitur khusus desktop NativePHP untuk KanSor, seperti titlebar custom, window controls, dan behavior berbeda saat dijalankan sebagai aplikasi desktop.

## Tujuan
Automasi pengenalan lingkungan NativePHP dan penerapan UI/UX desktop khusus tanpa mengubah experience browser normal.

## Trigger
- Ketika menambahkan window frame custom untuk NativePHP.
- Ketika menambahkan route atau controller yang hanya beroperasi di desktop.
- Ketika memisahkan behavior desktop dari browser web.
- Ketika menambahkan native action seperti minimize, maximize, dan close.

## Files Touched
- app/Providers/NativeAppServiceProvider.php
- app/Http/Controllers/NativeDesktopController.php
- config/nativephp.php
- resources/views/layouts/app.blade.php
- resources/js/app.js
- package.json
- vite.config.js

## Data Contract
- `nativephp.window.*` config values
- `action`: `minimize|maximize|reload|close`
- `frameless`: boolean

## Aturan Implementasi
- Titlebar custom hanya muncul saat aplikasi dijalankan sebagai NativePHP desktop.
- Behavior browser biasa tidak berubah.
- Route native desktop tidak perlu dibungkus auth agar kontrol window tetap tersedia di login.
- Button action desktop harus memanggil facade Native\Desktop\Facades\Window.

## Testing Wajib
- Test route desktop window control mengembalikan `success`.
- Test titlebar custom tidak muncul di mode browser biasa.
- Test fungsi minimize/maximize/reload/close dari controller.

## Acceptance Criteria
- NativePHP desktop membuka jendela tanpa titlebar bawaan OS.
- Tombol minimize/maximize/close/reload bekerja.
- Mode browser tetap menggunakan layout web normal.
- Build asset berhasil tanpa error.
