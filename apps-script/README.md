# Apps Script POS Kantin

Folder ini berisi source backend Google Apps Script untuk integrasi POS Kantin di KanSor.

## File utama

- `Code.gs` routing `doGet` dan `doPost`
- `Config.gs` konstanta app, schema sheet, dan property key
- `Setup.gs` helper setup spreadsheet baru dan set password awal
- `Migration.gs` preview dan eksekusi migrasi spreadsheet lama ke spreadsheet aktif
- `DesktopSync.gs` delta pull untuk desktop Electron
- `Auth.gs` login, logout, validasi session
- `Users.gs`, `Buyers.gs`, `Transactions.gs`, `Savings.gs`, `Finance.gs`, `Suppliers.gs`, `Dashboard.gs`

## Setup lokal

1. Masuk ke folder ini.
2. Buat file `.clasp.json` lokal dari `.clasp.example.json`.
3. Login CLASP memakai akun Google yang memang punya akses edit ke Apps Script project.
   - Di mesin ini, `clasp` versi `3.2.0` tidak mendukung flag profil seperti `-u evander`.
   - Gunakan browser login dan pilih akun yang benar saat flow OAuth berjalan.
   - `clasp login`
4. Buat standalone Apps Script baru, lalu isi `scriptId` di `.clasp.json` lokal.
   - Alternatif yang lebih praktis dari folder ini:
   - `clasp create --title "KanSor POS Kantin API" --type standalone`
5. Push source:
   - `clasp push`
6. Di editor Apps Script, jalankan:
   - `setupApplicationSpreadsheet()`
7. Setelah spreadsheet baru selesai dibuat, set password admin secara manual untuk:
   - `evandersmidgidiin@gmail.com`
   - Cara yang direkomendasikan:
   - siapkan OAuth Desktop App untuk `clasp run`
   - login ulang dengan project scopes
   - jalankan `clasp run setUserPasswordByEmail -p "[\"evandersmidgidiin@gmail.com\",\"PASSWORD_ANDA\"]"`
8. Deploy sebagai Web App dan simpan URL deploy hanya di environment Laravel lokal/server.

## Setup untuk `clasp run`

`clasp run` tidak cukup dengan login biasa. Ikuti alur resmi `clasp`:

1. Pastikan project Apps Script sudah punya Google Cloud Project.
2. Buat OAuth Client ID tipe `Desktop App`.
3. Download file secret OAuth dan simpan lokal, misalnya `client_secret.json`.
4. Login ulang:
   - `clasp login --use-project-scopes --creds client_secret.json`
5. Jika `clasp run` mengeluh `Script API executable not published/deployed`, buka editor Apps Script lalu:
   - `Deploy > New deployment > API Executable`
6. Setelah itu, fungsi berparameter bisa dipanggil dari CLI tanpa membuat wrapper sementara.

## Membuat OAuth Desktop App

Istilah terbaru di Google Cloud sekarang ada di `Google Auth Platform`, bukan lagi layar lama `APIs & Services > Credentials` saja.

1. Buka project Google Cloud yang dipakai bersama oleh Apps Script dan `clasp`.
2. Masuk ke halaman `Google Auth Platform > Clients`.
3. Jika diminta, selesaikan registrasi app dulu:
   - isi nama app
   - isi support email
   - pilih audience yang sesuai
4. Klik `Create client`.
5. Pilih tipe `Desktop app`.
6. Isi nama yang jelas, misalnya `KanSor POS Kantin CLASP Local`.
7. Klik `Create`.
8. Download file OAuth client secret saat itu juga.
   - Untuk client baru, Google hanya menampilkan full secret saat pembuatan.
9. Simpan file itu lokal saja, misalnya di:
   - `C:\Projects\KanSor\apps-script\client_secret.json`
10. Login ulang `clasp` memakai file itu:
   - `clasp login --use-project-scopes --creds client_secret.json`
11. Verifikasi hasil login:
   - `clasp show-authorized-user --json`

Checklist teknis supaya `clasp run` benar-benar bekerja:

1. Apps Script project dan OAuth client harus memakai standard Google Cloud project yang sama.
2. `Google Apps Script API` harus enabled di project itu.
3. Script harus dideploy sebagai `API Executable`.
4. Login `clasp` perlu memakai `--use-project-scopes` karena script ini butuh scope runtime seperti Sheets, Drive, dan Mail.
5. Karena `clasp` lokal Anda tidak punya fitur profil akun, pastikan akun browser yang aktif saat `clasp login` memang akun yang benar.

Jika login masih memakai akun yang salah, logout dulu lalu login ulang:

- `clasp logout`
- `clasp login --use-project-scopes --creds client_secret.json`

## Migrasi data lama

1. Pastikan spreadsheet backend baru sudah aktif dan akun Apps Script punya akses ke spreadsheet lama.
2. Preview dulu agar jumlah baris dan header terverifikasi:
   - `clasp run previewLegacySpreadsheetMigration -p "[\"SPREADSHEET_ID_LAMA\", false]"`
3. Jika preview aman, jalankan migrasi:
   - `clasp run runLegacySpreadsheetMigration -p "[\"SPREADSHEET_ID_LAMA\", false, false]"`
4. Untuk workflow server-side dari Laravel, gunakan Artisan:
   - `php artisan pos-kantin:migrate-legacy-spreadsheet --source=SPREADSHEET_ID_LAMA`
   - tambah `--commit` setelah hasil preview valid.

Catatan:
- Default migrasi hanya memindahkan sheet operasional. Session, trusted device, dan OTP tidak ikut dipindahkan.
- Jika `users` tidak ikut dimigrasikan, referensi `*_user_id` histori akan dikosongkan tetapi nama snapshot tetap dipertahankan.
- Backup source dan target dicoba otomatis sebelum overwrite. Jika izin Drive belum cukup, Anda bisa override dari Laravel/CLI dengan opsi yang sesuai.

## Catatan penting

- Jangan commit `.clasp.json`.
- Jangan commit `.clasprc.json`.
- Jangan commit `client_secret.json`.
- Spreadsheet ID live tidak disimpan di source. Gunakan `setSpreadsheetId()` atau `setupApplicationSpreadsheet()`.
- `doGet` hanya untuk `health`.
- Semua action aplikasi masuk lewat `doPost`.
- Import CSV pembeli dijalankan dari frontend admin dan diteruskan ke action `importBuyers`.
- Apps Script dan spreadsheet baru dikelola oleh akun Evander.
- Seed awal hanya membuat satu admin backend. Password tidak disimpan mentah di `password_hash`.
- PIN lama masih didukung sementara lewat `pin_hash` untuk migrasi.
- OTP reset password dikirim lewat `MailApp.sendEmail` dari akun deployer Apps Script.
- Jangan simpan script ID live, spreadsheet ID live, atau password admin ke git.
