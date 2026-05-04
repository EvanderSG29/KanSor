# Rencana Integrasi Backend POS Kantin ke KanSor

## Arsitektur

- Aplikasi utama tetap Laravel 12 dengan autentikasi lokal KanSor sebagai gerbang login utama.
- Backend POS Kantin berjalan di Google Apps Script dan diakses hanya dari sisi server Laravel.
- Kontrak API backend:
  - `GET ?action=health`
  - `POST { action, token?, payload? }`
  - respons standar `{ success, message, data }`
- Token Apps Script disimpan di cache Laravel dan tidak pernah dikirim ke browser.
- Antarmuka admin tetap memakai layout AdminLTE yang sudah ada di KanSor.

## Checklist Deploy

1. Salin source `apps-script/` ke repo KanSor tanpa `.clasp.json` live.
2. Login CLASP memakai profil Google yang benar untuk akun Evander.
3. Buat standalone Apps Script baru.
4. Push source dengan `clasp`.
5. Jalankan `setupApplicationSpreadsheet()` dari editor Apps Script.
6. Set password admin seed secara manual untuk `evandersmidgidiin@gmail.com`.
7. Deploy Web App dan salin URL hasil deploy ke `.env`.
8. Isi env Laravel untuk URL API, email admin service-account, timeout, dan password admin.
9. Jalankan test, Pint, lalu build frontend.

## Roadmap Patch

- Patch 0: dokumentasi integrasi, risiko, rollback, dan checklist deploy.
- Patch 1: salin source Apps Script ke repo KanSor dan netralkan konfigurasi lokal/live.
- Patch 2: provisioning Google Apps Script, spreadsheet baru, dan deploy Web App.
- Patch 3: integrasi Laravel server-side untuk health check, login service-account, dan wrapper API.
- Patch 4: dashboard AdminLTE dengan status backend dan ringkasan POS Kantin.
- Patch 5: halaman awal untuk transaksi, simpanan, pemasok, payout pemasok, dan pengguna.
- Patch 6: migrasi data lama setelah backend baru tervalidasi.

## Risiko

- Salah akun Google saat deploy Apps Script.
- URL Web App, script ID, spreadsheet ID, atau password admin bocor ke git.
- Token backend terekspos ke browser bila integrasi tidak dibatasi di sisi server.
- Session Apps Script kedaluwarsa dan membuat request dashboard gagal.
- Layout AdminLTE rusak bila frontend lama POS Kantin ikut tercampur ke KanSor.
- Migrasi data lama dilakukan terlalu cepat sebelum backend baru stabil.

## Mitigasi

- Gunakan profil CLASP khusus akun Evander.
- Jangan commit `.clasp.json`, script ID live, spreadsheet ID, atau password admin.
- Simpan seluruh kredensial backend hanya di `.env` atau konfigurasi lokal.
- Gunakan cache token server-side dan refresh token otomatis saat sesi backend kedaluwarsa.
- Batasi integrasi UI ke Blade yang extend layout KanSor, bukan copy frontend lama.
- Tunda migrasi data lama sampai health check, dashboard, dan modul dasar lulus validasi.

## Rollback

1. Kosongkan konfigurasi `KANSOR_*` di environment Laravel.
2. Kembalikan route atau tampilan dashboard ke versi lokal-only jika diperlukan.
3. Hapus atau nonaktifkan deployment Apps Script baru dari akun Google terkait.
4. Arsipkan spreadsheet baru tanpa menyentuh spreadsheet lama.
5. Rollback commit integrasi bila perilaku aplikasi utama terganggu.

