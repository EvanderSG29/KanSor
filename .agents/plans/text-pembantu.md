1. Audit login online 1 kali, offline berkali-kali sampai 30 hari

Secara konsep, backend Laravel sudah mendekati requirement Anda. PosKantinUserAuthenticator mencoba login remote lebih dulu. Jika gagal karena koneksi/configuration, sistem fallback ke offline login lokal. Saat online login berhasil, user lokal disimpan dan offline_login_expires_at diisi dengan services.kansor.offline_login_days, default 30 hari.

Offline login juga tidak asal menerima user lokal. OfflineLoginService mengecek user aktif, password lokal valid, credential perangkat ada, dan trusted device belum expired. Model User juga membatasi offline login hanya untuk user aktif dengan offline_login_expires_at masih di masa depan.

Yang perlu ditambah agar sesuai persis dengan requirement Anda:

Setting durasi offline per user/per perangkat, bukan hanya env global.
Saat ini default 30 hari ada di env/config.
Tambahkan kolom misalnya offline_session_days pada tabel user preference atau device credential.
Batas aman: minimal 1 hari, default 30 hari, maksimal 30 atau 90 hari sesuai kebijakan admin.
UI pengaturan sesi tersimpan:
Petugas bisa memilih: 1 hari, 7 hari, 14 hari, 30 hari.
Admin bisa mengunci batas maksimum.
Invalidate offline session saat password/status berubah.
Ini sudah sebagian ada karena saat remote_auth_updated_at berubah, sistem mengatur offline_login_expires_at = now().
Tetap perlu test khusus: password berubah remote → offline login lama langsung gagal.
Bedakan session aplikasi dan trusted offline login.
SESSION_LIFETIME=60 di env production berarti sesi web/app Laravel 60 menit, bukan masa berlaku offline login 30 hari. Ini tidak salah, tetapi dokumentasinya harus jelas agar tidak membingungkan.
2. Nama Apps Script dan Spreadsheet wajib disesuaikan

Saat ini Config.gs masih memakai:

APP_NAME: "KanSor POS Kantin API"
DEFAULT_SPREADSHEET_TITLE: "KanSor - POS Kantin Database"

Ini perlu diubah menjadi:

APP_NAME: "KanSor API"
DEFAULT_SPREADSHEET_TITLE: "KanSor Database"

Konfigurasi saat ini juga menetapkan TRUSTED_DEVICE_TTL_DAYS: 30, timezone Asia/Jakarta, dan seed admin awal. Jadi patch pertama harus mengganti nama tanpa mengubah kontrak session/trusted-device.

Acceptance criteria:

[ ] action=health menampilkan appName = KanSor API
[ ] setupApplicationSpreadsheet() membuat spreadsheet bernama KanSor Database
[ ] tidak ada nama lama KanSor POS Kantin API di Config.gs, README, env, atau docs aktif
[ ] tidak ada spreadsheet ID live/script ID live masuk git
3. Masalah paling penting: supplier-food sudah ada di Apps Script, tapi belum lengkap di local Laravel mirror

Requirement Anda: data dari spreadsheet seperti pemasok dan makanan per pemasok harus ikut kebawa ke lokal agar petugas offline cukup pilih pemasok lalu pilih makanan yang biasa dijual pemasok tersebut.

Di sisi Apps Script, schema foods sudah ada: supplier_id, supplier_name_snapshot, food_name, unit_name, default_price, is_active, dan timestamps. Code.gs juga sudah punya action listFoods dan saveFood. DesktopSync.gs bahkan sudah memasukkan entity food, action saveFood, dan syncPullAction_ mengembalikan foods beserta cursor foods.

Namun di Laravel, PosKantinLocalStore::tableMap() belum memasukkan foods; yang ada hanya users, suppliers, buyers, transactions, savings, dailyFinance, changeEntries, dan supplierPayouts. Karena pullChanges() mengiterasi resource dari local store, data foods dari Apps Script tidak akan lengkap masuk ke mirror lokal sampai foods ditambahkan ke table map.

Patch wajib:

// app/Services/PosKantin/PosKantinLocalStore.php
protected function tableMap(): array
{
    return [
        'users' => 'pos_users',
        'suppliers' => 'pos_suppliers',
        'foods' => 'pos_foods',
        'buyers' => 'pos_buyers',
        'transactions' => 'pos_transactions',
        'savings' => 'pos_savings',
        'dailyFinance' => 'pos_daily_finance',
        'changeEntries' => 'pos_change_entries',
        'supplierPayouts' => 'pos_supplier_payouts',
    ];
}

Tambahkan migration:

pos_foods
- id
- scope_owner_user_id
- remote_id
- supplier_remote_id
- supplier_name_snapshot
- food_name
- unit_name
- default_price
- is_active
- payload encrypted/json
- remote_created_at
- remote_updated_at
- remote_deleted_at
- timestamps

Index wajib:

(scope_owner_user_id, remote_id) unique
(scope_owner_user_id, supplier_remote_id)
(scope_owner_user_id, is_active)

Acceptance criteria:

[ ] syncPull membawa suppliers dan foods
[ ] ketika offline, dropdown pemasok tetap muncul
[ ] setelah memilih pemasok, dropdown makanan hanya menampilkan makanan pemasok itu
[ ] makanan dari pemasok nonaktif tidak bisa dipilih untuk transaksi baru
[ ] test sync pull memasukkan foods ke pos_foods
4. Desain input UI/UX supplier → banyak makanan, database tetap terpisah

Requirement Anda benar: UI boleh menampilkan satu input dengan banyak makanan, tetapi database harus tetap normalized.

Contoh UI:

Pemasok	Makanan
Uni	Otak-otak, Cireng

Database harus menjadi:

pemasok	makanan
Uni	Otak-otak
Uni	Cireng

Standar implementasinya:

sales
- id
- sale_date
- supplier_id
- input_by_user_id
- status
- total_gross
- total_supplier
- total_canteen
- sync_status
- timestamps

sale_items
- id
- sale_id
- supplier_id
- food_id
- food_name_snapshot
- unit_name
- quantity
- leftover_quantity
- sold_quantity
- unit_price
- gross_sales
- supplier_net_amount
- canteen_amount
- timestamps

Untuk spreadsheet Apps Script yang berbentuk flat transactions, mapping paling aman adalah 1 sale item = 1 row spreadsheet. Dengan begitu input UI bisa multi item, tetapi sync ke spreadsheet tetap mudah dihitung dan diaudit.

Tambahkan field pengikat agar beberapa row spreadsheet tetap dikenali sebagai satu transaksi lokal:

client_sale_id
client_sale_item_id
transaction_group_id

Jangan gunakan satu row spreadsheet berisi makanan dipisah koma, karena akan menyulitkan audit, edit, conflict resolution, dan payout pemasok.

5. Sync manual semua aksi + sync manual pilihan tertentu

Saat ini PosKantinSyncService::sync() sudah memakai Cache::lock per user agar sync paralel tidak berjalan bersamaan. Ini bagus untuk mencegah double submit. Status sync juga sudah mengekspos jumlah pending, applied, failed, conflict, last run, masa offline login, trusted device expiry, dan interval sync.

Masalahnya: pushOutbox() sekarang mengambil semua item pending dan failed. Belum ada filter pilihan beberapa action tertentu.

Desain yang disarankan:

public function sync(
    User $user,
    string $trigger = 'manual',
    ?array $selectedOutboxIds = null,
): array

Lalu di pushOutbox():

$query = PosKantinSyncOutbox::query()
    ->whereBelongsTo($user, 'user')
    ->whereIn('status', ['pending', 'failed'])
    ->orderBy('created_at');

if ($selectedOutboxIds !== null) {
    $query->whereIn('id', $selectedOutboxIds);
}

$pendingItems = $query->get();

Untuk UI, saya sarankan checkbox, bukan radio, karena Anda ingin user bisa memilih beberapa aksi yang mau dikirim. Radio hanya cocok untuk memilih satu opsi resolusi per konflik, misalnya:

Konflik data:
( ) Pakai data server
( ) Kirim ulang data lokal
( ) Abaikan dulu

Halaman sync sebaiknya punya:

[ ] Pilih semua
[ ] Supplier: Uni diperbarui
[ ] Food: Otak-otak ditambahkan
[ ] Transaction: Uni / Otak-otak / 2026-04-30
[ ] User: Petugas diperbarui

Button:
- Sync Terpilih
- Sync Semua
- Retry Gagal

Status minimal:

pending
syncing
applied
failed
conflict
unsupported
skipped
discarded

Conflict UI sudah punya fondasi karena service menyiapkan fieldDiffs, timestamp lokal/server, dan konteks perbedaan. Tinggal dibuat UI yang jelas dan audit log resolusi.

6. CLASP CLI untuk validasi Apps Script API

Saya tidak bisa menjalankan CLASP live dari sesi ini karena butuh OAuth browser/account Google lokal dan credential project Anda. Tetapi jalur validasinya bisa dibuat sangat jelas.

clasp run memang menjalankan fungsi Apps Script secara remote, tetapi perlu setup khusus: project ID, OAuth Desktop client, login dengan creds, deployment API Executable, Apps Script API enabled, dan project scopes. Dokumentasi clasp juga menekankan bahwa Apps Script API perlu diaktifkan dan OAuth client desktop direkomendasikan untuk workflow lokal. Web App Apps Script sendiri memakai doGet(e) untuk GET dan doPost(e) untuk POST.

Urutan validasi:

cd apps-script

npm install -g @google/clasp
clasp --version

cp .clasp.example.json .clasp.json

Login dengan project scopes:

clasp login --use-project-scopes --creds client_secret.json
clasp show-authorized-user --json

Buat/push project:

clasp create --title "KanSor API" --type standalone
clasp push

Jalankan setup spreadsheet:

clasp run setupApplicationSpreadsheet

Set password admin:

clasp run setUserPasswordByEmail -p '["evandersmidgidiin@gmail.com","PASSWORD_KUAT_ANDA"]'

Deploy:

Apps Script Editor
Deploy > New deployment > Web app
Execute as: Me
Who has access: sesuai kebutuhan deployment

Jika clasp run gagal dengan pesan API executable belum dipublish:

Apps Script Editor
Deploy > New deployment > API Executable

Tes health:

curl -s "$KANSOR_API_URL?action=health"

Tes login:

curl -s -X POST "$KANSOR_API_URL" \
  -H "Content-Type: application/json" \
  -d '{"action":"login","payload":{"email":"evandersmidgidiin@gmail.com","password":"PASSWORD_KUAT_ANDA"}}'

Catatan wajib dari README repo: jangan commit .clasp.json, .clasprc.json, client_secret.json, script ID live, spreadsheet ID live, atau password admin.

7. Audit env

.env.production.example sudah cukup rapi untuk baseline production: APP_NAME=KanSor, APP_DEBUG=false, secure session, POS API URL/email/password kosong, timeout, token cache key, device label, dan offline login 30 hari. config/services.php juga sudah membaca env POS Kantin dengan default device_label=KanSor Desktop, offline_login_days=30, dan sync_interval_seconds=60.

Yang saya sarankan ditambah:

KANSOR_API_URL=
KANSOR_ADMIN_EMAIL=
KANSOR_ADMIN_PASSWORD=
KANSOR_TIMEOUT=20
KANSOR_CONNECT_TIMEOUT=10

KANSOR_DEVICE_LABEL="KanSor Desktop"
KANSOR_OFFLINE_LOGIN_DAYS=30
KANSOR_OFFLINE_LOGIN_DAYS_MAX=30
KANSOR_SYNC_INTERVAL_SECONDS=60
KANSOR_SYNC_BATCH_SIZE=50
KANSOR_SYNC_MODE=manual

KANSOR_API_DISPLAY_NAME="KanSor API"
KANSOR_SPREADSHEET_TITLE="KanSor Database"

NATIVEPHP_APP_ID=id.kansor.desktop
NATIVEPHP_APP_NAME=KanSor
NATIVEPHP_APP_VERSION=0.1.0

Untuk desktop single-user/offline, SQLite masuk akal karena Laravel 12 mendukung SQLite sebagai database runtime. Tetapi kalau nanti ada server admin multi-user, gunakan MySQL/PostgreSQL di server, bukan SQLite.

8. Fokus aplikasi desktop .exe, bukan web

NativePHP desktop memang membungkus Laravel ke aplikasi desktop berbasis Electron runtime dan bisa dibuild menjadi executable per platform. Command build resminya memakai php artisan native:build, dan build dilakukan per platform.

Planning desktop:

Phase Desktop 1 — Local Runtime
[ ] Pastikan SQLite lokal dibuat otomatis saat first run
[ ] Jalankan migration lokal saat versi app berubah
[ ] Simpan credential trusted device secara terenkripsi
[ ] Jangan tampilkan halaman web/debug di production desktop

Phase Desktop 2 — Offline UX
[ ] Login screen menampilkan akun trusted yang bisa offline
[ ] Semua CRUD utama tetap bisa dilakukan tanpa internet
[ ] Semua perubahan masuk outbox lokal
[ ] Banner status: Online / Offline / Belum Sync / Ada Konflik

Phase Desktop 3 — Build Windows
[ ] php artisan native:build win
[ ] Buat dokumentasi build `.exe`
[ ] Siapkan code signing Windows
[ ] Siapkan installer setup `.exe`
[ ] Validasi first install, update, uninstall, dan data lokal tidak hilang

Jangan jadikan dashboard web sebagai fokus utama. Web route tetap ada karena Laravel butuh rendering UI, tetapi experience final harus seperti aplikasi kasir/operasional desktop.

9. Data dummy dari XLSX upload

Dari workbook yang Anda kirim, saya menemukan struktur utama:

10%
20%
Total Simpanan
Total kantin Per Hari
Jualan per Hari
Nama User
Sheet12

Data transaksi yang bisa dijadikan dummy awal berisi sekitar 84 baris item transaksi. Pemasok utama yang muncul:

Uni
Kang Latif
Pak Arie

Mapping makanan yang perlu dibersihkan:

Uni:
- Otak-otak
- Cireng
- Hotdog
- Ayam
- Rolade
- Tahu Krispi
- Burger
- Otak-otak & Bakso

Kang Latif:
- Cimol
- Cilok
- Cirawang
- Makaroni
- Cigo
- Cilung Keju
- Cakue

Pak Arie:
- Cilok
- Siomay
- Baso Tahu

Ada variasi penulisan seperti Otak2, Otak3, otak otak, Makroni, dan Siomai; seeder harus melakukan normalisasi agar dummy tidak membuat data master berantakan.

Struktur seeder:

database/seeders/
- KanSorDemoSeeder.php
- KanSorUserDummySeeder.php
- KanSorSupplierFoodDummySeeder.php
- KanSorTransactionDummySeeder.php
- KanSorFinanceDummySeeder.php

database/seeders/data/
- kansor_suppliers.php
- kansor_foods.php
- kansor_users.php
- kansor_transactions.php

Command yang diinginkan:

php artisan db:seed --class=KanSorDemoSeeder
php artisan db:seed --class=KanSorSupplierFoodDummySeeder
php artisan db:seed --class=KanSorTransactionDummySeeder

Command custom yang lebih enak untuk development:

php artisan kansor:seed-dummy --all
php artisan kansor:seed-dummy --only=suppliers
php artisan kansor:seed-dummy --only=foods
php artisan kansor:seed-dummy --only=transactions
php artisan kansor:seed-dummy --supplier=Uni
php artisan kansor:seed-dummy --date=2026-04-30
php artisan kansor:seed-dummy --fresh

Laravel mendukung menjalankan seeder spesifik, dan factory/seeder memang pola standar untuk data development/testing.

Saran penting: jangan seed email asli dari XLSX ke mode demo publik. Gunakan email dummy seperti:

asdar@example.test
evander@example.test
salman@example.test

Bila perlu data asli untuk dev internal, buat flag eksplisit:

php artisan kansor:seed-dummy --all --use-uploaded-real-identities
10. Perapihan .agents/skills

Repo sekarang punya daftar skill POS: users, suppliers, foods, transactions, reports, dan rencana-rancangan. Isi domain skill sudah cukup berguna, misalnya foods menjelaskan relasi makanan ke pemasok, validasi supplier, dropdown transaksi, dan status aktif. transactions juga sudah memuat input multi item, validasi nested items, perhitungan total, dan status final.

Masalahnya: struktur masih terasa bercampur antara skill reusable, planning sekali pakai, dan guideline framework. Saya sarankan struktur final:

.agents/
  README.md
  skills/
    laravel-best-practices/
      SKILL.md
    pest-testing/
      SKILL.md
    pos/
      offline-auth/
        SKILL.md
      sync/
        SKILL.md
      suppliers/
        SKILL.md
      foods/
        SKILL.md
      transactions/
        SKILL.md
      reports/
        SKILL.md
      desktop-native/
        SKILL.md
  plans/
    2026-04-30-kansor-desktop-offline-sync-plan.md
    2026-04-30-kansor-crud-sync-contract.md

Standar isi setiap SKILL.md:

---
name:
description:
---

# Tujuan
# Trigger
# File yang biasanya disentuh
# Data contract
# Aturan implementasi
# Testing wajib
# Acceptance criteria
# Anti-pattern

Pindahkan dokumen rencana besar seperti RENCANA-* dan PLAN.md dari folder skills ke .agents/plans atau docs/planning, karena itu bukan skill reusable. Skill harus berisi aturan kerja yang bisa dipakai berulang.

11. Planning implementasi untuk Codex

Berikut instruksi yang bisa langsung Anda berikan ke Codex.

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
12. Definition of Done final

KanSor dianggap selesai untuk requirement ini jika:

[ ] Login online sekali berhasil membuat trusted device.
[ ] Offline login berhasil tanpa internet selama masa aktif.
[ ] Default masa offline login 30 hari.
[ ] User/admin bisa mengatur durasi sesi tersimpan sesuai batas.
[ ] Nama Apps Script menjadi KanSor API.
[ ] Nama Spreadsheet menjadi KanSor Database.
[ ] Pemasok dari spreadsheet tersimpan di lokal.
[ ] Makanan per pemasok dari spreadsheet tersimpan di lokal.
[ ] Form transaksi memilih pemasok lalu makanan milik pemasok tersebut.
[ ] Banyak makanan di UI tersimpan sebagai banyak row item di database.
[ ] Sync manual semua aksi tersedia.
[ ] Sync manual pilihan beberapa aksi tersedia.
[ ] Queue sync memakai checkbox.
[ ] Conflict resolution memakai pilihan per konflik dan audit log.
[ ] Payload sync tetap encrypted.
[ ] Seeder dummy bisa semua, per CRUD, dan spesifik supplier/tanggal.
[ ] Build desktop `.exe` terdokumentasi dan bisa dijalankan.
[ ] Skill folder rapi dan tidak bercampur dengan dokumen planning sekali pakai.
[ ] Test, Pint, build frontend, dan smoke test desktop lulus.

Prioritas paling aman: foods local mirror → supplier-food UI → selective sync → dummy seeder → rename Apps Script/Spreadsheet → Native build docs → skill cleanup. Ini menutup celah paling berbahaya lebih dulu: data makanan belum benar-benar ikut tersedia lokal/offline.
