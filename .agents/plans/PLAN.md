# Rencana Integrasi Backend POS Kantin ke KanSor

## Summary
- `KanSor` tetap memakai Laravel 12, Laravel UI auth lokal, NativePHP, dan template AdminLTE 3.2 yang sudah ada.
- Backend dari `EvanderSG29/kansor` diambil hanya bagian `apps-script/`, lalu ditempatkan di `KanSor/apps-script/` sebagai source Google Apps Script.
- Google Apps Script dan Spreadsheet baru dibuat/dimiliki akun `evandersmidgidiin@gmail.com`.
- Seed admin backend baru hanya `evandersmidgidiin@gmail.com`.
- Data spreadsheet lama milik Ivan tidak dimigrasikan di tahap awal; dibuat patch terpisah setelah backend baru stabil.

## Patch Breakdown
| Patch | Isi | Acceptance |
|---|---|---|
| Patch 0 | Buat file root `RENCANA-INTEGRASI-BACKEND-kansor.md` berisi arsitektur, checklist deploy, patch roadmap, risiko, dan rollback. | Dokumentasi ada, tidak ada secret/URL live/spreadsheet ID. |
| Patch 1 | Copy `kansor/apps-script` ke `KanSor/apps-script`; update `Config.gs` untuk nama database KanSor dan seed admin Evander baru; tambahkan ignore untuk `apps-script/.clasp.json`. | Source backend siap dipush CLASP, tidak membawa frontend Electron/SB Admin dari `kansor/app`. |
| Patch 2 | Setup Google baru: login CLASP profile `evander`, buat standalone Apps Script, push source, jalankan `setupApplicationSpreadsheet()`, set password admin manual, deploy Web App. | `action=health` aktif, spreadsheet baru dibuat oleh akun Evander, password tidak hardcoded/tercommit. |
| Patch 3 | Tambah integrasi Laravel server-side: config `services.kansor`, `.env.example`, `PosKantinClient`, exception, token cache service-account. | Laravel bisa memanggil `health`, `login`, dan action bertoken tanpa mengekspos token ke browser. |
| Patch 4 | Integrasi AdminLTE: dashboard menampilkan status backend dan ringkasan dari `dashboardSummary`; sidebar dibuat rapi untuk Transaksi, Simpanan, Pemasok, Pembayaran, Laporan, Pengguna. | Layout tetap extend `resources/views/layouts/app.blade.php`, AdminLTE tidak rusak. |
| Patch 5 | Implementasi modul per halaman secara bertahap: transaksi, simpanan, pemasok, payout, users. | Tiap modul punya controller tipis, request tervalidasi, dan test HTTP fake. |
| Patch 6 | Migrasi data lama opsional: backup/export spreadsheet Ivan, import terkontrol ke spreadsheet baru, verifikasi jumlah dan format baris. Status 2026-04-27: tooling preview/commit sudah ada di Apps Script + Laravel command, eksekusi live menunggu source spreadsheet ID lama. | Data lama hanya masuk setelah backend baru lulus validasi. |

## Interfaces
- Apps Script tetap memakai kontrak `GET ?action=health` dan `POST { action, token?, payload? }` dengan response `{ success, message, data }`.
- Env Laravel yang ditambahkan: `KANSOR_API_URL`, `KANSOR_ADMIN_EMAIL`, `KANSOR_ADMIN_PASSWORD`, `KANSOR_LEGACY_SPREADSHEET_ID`, `KANSOR_TIMEOUT`, `KANSOR_CONNECT_TIMEOUT`.
- `PosKantinClient` menyediakan `health()`, `request($action, $payload = [], $token = null)`, `loginAsServiceAccount()`, wrapper awal untuk `dashboardSummary`, `listTransactions`, `listSuppliers`, dan `migrateLegacySpreadsheet`.
- Semua route proxy data POS wajib di dalam middleware `auth`; Laravel auth lokal tetap sumber login KanSor untuk tahap awal.

## Test Plan
- Jalankan `php artisan test --compact` dan test service memakai `Http::fake()` serta `Http::preventStrayRequests()`.
- Uji skenario sukses, response `success=false`, timeout, connection error, session expired, dan malformed response.
- Jalankan `vendor/bin/pint --dirty --format agent` setelah perubahan PHP.
- Jalankan `npm run build` setelah perubahan Blade/Vite/AdminLTE.
- Manual check: `php artisan route:list --except-vendor`, Apps Script `action=health`, setup spreadsheet, dan dashboard AdminLTE setelah login lokal.
- Manual check Patch 6: jalankan `php artisan kansor:migrate-legacy-spreadsheet` untuk preview, pastikan jumlah baris per sheet sesuai, lalu lanjut `--commit` setelah source spreadsheet ID lama tersedia.

## Risks & Mitigations
- Salah akun Google: pakai CLASP profile `evander`, jangan pakai script ID Ivan, dan jangan commit `.clasp.json`.
- Secret bocor: Web App URL, spreadsheet ID, admin password, dan script ID live hanya di `.env` atau konfigurasi lokal.
- Auth membingungkan: tahap awal Laravel login tetap lokal; token Apps Script hanya service-account server-side.
- Quota Apps Script: mulai dengan request server-side, timeout/retry eksplisit, cache token, dan modul data dipatch bertahap.
- AdminLTE rusak karena copy UI lama: jangan copy frontend Electron/SB Admin dari `kansor`; semua UI dibuat sebagai Blade yang extend layout KanSor.
- Migrasi data korup: data lama tidak dipindah di patch awal; lakukan backup dan import terverifikasi di patch khusus.
- Patch 6 live tetap berisiko salah sumber spreadsheet; mitigasinya adalah preview default, backup source/target otomatis, dan opsi `--commit` yang eksplisit.

