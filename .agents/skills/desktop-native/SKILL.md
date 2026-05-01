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
