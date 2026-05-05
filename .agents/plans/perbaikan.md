Project: EvanderSG29/KanSor
Fokus: Desktop-native offline-first POS Kantin, bukan web-first.

Tujuan utama:
- Login online cukup sekali.
- Offline login bisa dipakai berkali-kali selama default 30 hari.
- Durasi offline login bisa diatur per user/per perangkat dengan batas maksimum.
- CRUD pemasok dan makanan bisa berjalan lokal/offline.
- Data pemasok dan makanan dari Spreadsheet harus ikut tersinkron ke local mirror.
- Input UI boleh memilih satu pemasok dan banyak makanan, tetapi database tetap normalized.
- Sync default manual.
- User bisa sync semua aksi atau memilih beberapa aksi tertentu dari outbox.
- Conflict harus bisa dibandingkan lokal vs server.
- Apps Script bernama KanSor API.
- Spreadsheet bernama KanSor Database.
- Target akhir aplikasi adalah desktop Windows `.exe` plus setup installer.

Patch 1 — Rename Apps Script dan env
- Ubah CONFIG.APP_NAME menjadi "KanSor API".
- Ubah DEFAULT_SPREADSHEET_TITLE menjadi "KanSor Database".
- Update README apps-script dan env example.
- Jangan commit secret, .clasp.json, .clasprc.json, client_secret.json.

Patch 2 — Local mirror foods
- Tambah migration pos_foods.
- Tambah foods ke PosKantinLocalStore::tableMap().
- Tambah tests syncPull foods.
- Pastikan cursor foods dipakai.
- Pastikan data foods bisa dibaca saat offline.

Patch 3 — Supplier-food UX
- Di form transaksi, pilih supplier dulu.
- Setelah supplier dipilih, tampilkan makanan aktif milik supplier.
- Tambahkan dynamic repeater/chips untuk banyak makanan.
- Validasi food_id wajib milik supplier yang dipilih.
- Simpan ke sale_items terpisah.

Patch 4 — Mapping transaksi ke spreadsheet
- Satu sale_item menjadi satu row transactions remote.
- Tambah client_sale_id dan client_sale_item_id di payload.
- Jangan simpan multi makanan sebagai comma-separated string di spreadsheet.
- Tambah test create/update/delete transaction mapping.

Patch 5 — Selective manual sync
- Ubah sync service agar menerima selectedOutboxIds.
- Tambah endpoint Sync Selected.
- UI queue memakai checkbox untuk multi pilih.
- Conflict resolution memakai radio per konflik.
- Tambah status skipped/unsupported bila action tidak dipilih atau belum didukung.

Patch 6 — Offline login user preference
- Tambah setting offline_session_days.
- Default 30 dari env.
- Clamp minimum 1, maksimum dari KANSOR_OFFLINE_LOGIN_DAYS_MAX.
- Saat password/status remote berubah, invalidate offline login.
- Tambah test online login, offline valid, offline expired, remote auth changed.

Patch 7 — Dummy data dari XLSX
- Buat KanSorDemoSeeder.
- Buat seeder terpisah users, suppliers, foods, transactions, finance.
- Ambil mapping pemasok/makanan dari XLSX upload.
- Normalisasi nama makanan.
- Tambah command php artisan kansor:seed-dummy dengan opsi --all, --only, --supplier, --date, --fresh.

Patch 8 — CLASP verification docs
- Dokumentasikan login clasp with project scopes.
- Dokumentasikan setupApplicationSpreadsheet.
- Dokumentasikan setUserPasswordByEmail.
- Dokumentasikan health/login/syncPull/syncPush curl.
- Jelaskan fallback jika CLI dibatasi OAuth/API Executable.

Patch 9 — Native desktop build
- Dokumentasikan php artisan native:build win.
- Pastikan first run membuat DB lokal.
- Pastikan migration lokal berjalan aman.
- Tambah dokumentasi setup installer `.exe`.
- Jangan fokus membuat web deployment sebagai target utama.

Patch 10 — Skill folder cleanup
- Standarkan semua skill menjadi SKILL.md.
- Pindahkan rencana sekali pakai ke .agents/plans atau docs/planning.
- Tambah skill offline-auth, sync, desktop-native.
- Pastikan setiap skill punya trigger, data contract, files, tests, acceptance criteria.

Validasi akhir:
- composer install
- npm install
- php artisan migrate:fresh --seed
- php artisan test --compact
- vendor/bin/pint --dirty --format agent
- npm run build
- php artisan native:build win
- curl "$KANSOR_API_URL?action=health"
- Manual test login online/offline
- Manual test supplier-food offline
- Manual test sync selected
- Manual test conflict resolution
