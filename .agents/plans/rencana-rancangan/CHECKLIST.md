# Checklist Integrasi POS Kantin

## Selesai di codebase
- [x] Patch 0: dokumen root `RENCANA-INTEGRASI-BACKEND-POS-KANTIN.md` sudah ada.
- [x] Patch 1: source `apps-script/` sudah masuk repo dan `.gitignore` sudah mengabaikan `apps-script/.clasp.json`.
- [x] Patch 2: setup CLASP, Apps Script live, spreadsheet baru, password admin manual, dan deploy Web App sudah selesai.
- [x] Patch 3: konfigurasi Laravel server-side sudah ada untuk `services.pos_kantin`, `.env.example`, `PosKantinClient`, `PosKantinException`, cache token service-account, dan test HTTP fake.
  Verifikasi lokal 2026-04-27: `health`, `loginAsServiceAccount()`, dan `dashboardSummary()` berhasil dipanggil dari Laravel server-side setelah CA bundle PHP dikonfigurasi.
- [x] Patch 4: dashboard AdminLTE menampilkan status backend dan ringkasan `dashboardSummary`, sidebar dirapikan untuk Transaksi, Simpanan, Pemasok, Pembayaran, Laporan, dan Pengguna.
  Verifikasi lokal 2026-04-27: route `pos-kantin/laporan` aktif, test halaman POS lolos, dan `npm run build` sukses.
- [x] Patch 5: modul transaksi, simpanan, pemasok, payout, dan pengguna sudah memakai controller tipis, request tervalidasi, dan test HTTP fake.
  Verifikasi lokal 2026-04-27: `PosKantinPagesTest` dan `PosKantinModuleValidationTest` lolos, termasuk skenario filter invalid dan backend error per modul.

## Selesai di codebase, menunggu eksekusi live
- [x] Patch 6A: utilitas migrasi data lama sudah ditambahkan di `apps-script/Migration.gs`, action backend `migrateLegacySpreadsheet`, wrapper `PosKantinClient`, env `POS_KANTIN_LEGACY_SPREADSHEET_ID`, dan command `php artisan pos-kantin:migrate-legacy-spreadsheet`.
  Verifikasi lokal 2026-04-27: `PosKantinClientTest` dan `PosKantinLegacySpreadsheetCommandTest` lolos, termasuk preview default dan commit dengan opsi `include-users` serta `allow-without-backups`.

## Belum selesai / perlu verifikasi manual
- [x] Patch 6B: eksekusi live migrasi data lama dari spreadsheet Ivan ke spreadsheet baru.
  Blokir saat ini 2026-04-27: source spreadsheet ID lama tidak ada di repo/local env, sehingga command live belum bisa dijalankan sampai ID sumber dan izin Apps Script tersedia.

## Catatan Batas Aman
- Patch 3 dijaga tetap server-side. Token backend tidak boleh dipindahkan ke browser.
- Status Patch 2 mengikuti verifikasi manual environment live dari Anda.
- Patch 4 ke atas sebaiknya lanjut terpisah agar tidak menimpa pekerjaan yang sudah lebih dulu ada di controller, route, dan Blade.
- Patch 6 live wajib diawali preview (`php artisan pos-kantin:migrate-legacy-spreadsheet`) sebelum `--commit`, dan backup source/target perlu dipastikan berhasil atau di-override secara sadar.
