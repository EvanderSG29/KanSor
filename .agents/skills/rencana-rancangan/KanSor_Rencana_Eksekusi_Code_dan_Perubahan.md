# Rencana Eksekusi Code & Perubahan UI/UX + Keamanan KanSor

**Project:** KanSor  
**Repo:** `EvanderSG29/KanSor`  
**Tanggal dokumen:** 30 April 2026  
**Fokus:** Perapihan UI/UX, struktur alur pengguna, hardening keamanan, dan penyesuaian baseline kontrol keamanan nasional/SMKI.

---

## 1. Tujuan Dokumen

Dokumen ini disiapkan sebagai panduan eksekusi perubahan code berdasarkan hasil review UI/UX dan keamanan sebelumnya. Dokumen ini dapat dipakai sebagai:

1. acuan implementasi developer;
2. checklist perubahan per branch/PR;
3. dasar pembagian sprint;
4. bahan review dengan stakeholder;
5. baseline validasi keamanan sebelum aplikasi dipakai operasional.

---

## 2. Ringkasan Temuan Utama

### 2.1 UI/UX

Beberapa bagian UI masih terlalu mengikuti struktur backend/developer, bukan alur kerja pengguna kantin.

Temuan utama:

- istilah menu seperti `Snapshot`, `CRUD`, `Status I`, `Status II`, `Status III`, dan `Status IV` kurang ramah pengguna;
- menu `Transaksi Lokal`, `Snapshot Transaksi`, `Pemasok Lokal`, dan `Snapshot Pemasok` terasa seperti representasi storage/sync internal;
- dashboard belum memprioritaskan aksi utama petugas, yaitu input transaksi harian;
- halaman konfirmasi admin mencampur proses pembayaran pemasok dan setoran kantin;
- form transaksi masih berat untuk input cepat dan belum optimal untuk mobile/tablet;
- conflict sync belum menampilkan perbandingan data lokal vs server secara jelas.

### 2.2 Keamanan

Temuan utama:

- route POS belum seluruhnya dikunci dengan role middleware;
- self-registration bawaan Laravel masih berpotensi aktif;
- password policy masih minimum;
- debug tool/debug drawer tidak boleh muncul di environment produksi;
- payload sinkronisasi dan konflik belum dienkripsi;
- action sync perlu throttle dan lock server-side;
- audit trail untuk aksi finansial dan konflik data perlu diperkuat.

---

## 3. Prinsip Desain Perubahan

Perubahan harus mengikuti prinsip berikut:

1. **Task-first navigation**  
   Struktur menu harus mengikuti pekerjaan pengguna, bukan struktur database/backend.

2. **Role-based experience**  
   Petugas hanya melihat fitur operasional. Admin melihat fitur monitoring, konfirmasi, master data, laporan, dan keamanan.

3. **No technical wording in production UI**  
   Istilah `CRUD`, `snapshot`, dan `status I/II/III/IV` tidak ditampilkan sebagai label utama pengguna.

4. **Secure by default**  
   Semua route POS harus melewati autentikasi, active-user check, dan role authorization.

5. **Financial action must be auditable**  
   Pembayaran pemasok, setoran kantin, koreksi transaksi, dan resolusi konflik harus punya jejak audit.

6. **Sync conflict must be explainable**  
   User/admin harus dapat membandingkan data lokal dan server sebelum memilih aksi resolusi.

7. **Small safe PRs**  
   Perubahan dilakukan bertahap agar mudah dites dan rollback.

---

## 4. Strategi Branch dan Pull Request

Gunakan beberapa PR kecil, bukan satu PR besar.

```bash
git checkout main
git pull origin main
git checkout -b chore/kansor-uiux-security-plan
```

Rekomendasi branch lanjutan:

```text
fix/pos-route-authorization
fix/disable-public-registration
refactor/sidebar-navigation
refactor/payment-confirmation-fields
refactor/sync-conflict-ui
refactor/transaction-form-ux
chore/security-env-baseline
test/pos-security-regression
```

---

## 5. Prioritas Eksekusi

| Prioritas | Area | Tujuan | Risiko Jika Ditunda |
|---|---|---|---|
| P0 | Route authorization | Semua halaman POS wajib terkunci role | User aktif tanpa role valid bisa mengakses halaman POS tertentu |
| P0 | Disable register | Cegah pendaftaran publik | Akun tidak sah bisa dibuat dari route register |
| P0 | Pisahkan pembayaran pemasok & setoran kantin | Hindari overwrite data finansial | Nominal/tanggal/catatan bisa tertimpa |
| P1 | Navigasi UI | Menu sesuai alur kerja user | UI terasa membingungkan dan teknis |
| P1 | Rename istilah UI | Hilangkan label teknis | User tidak memahami konteks fitur |
| P1 | Debug production gate | Debug tool tidak tampil di production | Risiko kebocoran informasi internal |
| P1 | Sync conflict UI | User bisa memilih resolusi berbasis data | Salah pilih resolusi konflik |
| P2 | Form transaksi UX | Input harian lebih cepat dan minim error | Entry data lambat dan rawan salah |
| P2 | Enkripsi payload sync | Lindungi data operasional/PII | Data sensitif tersimpan plaintext |
| P2 | Test suite | Cegah regresi | Perubahan keamanan tidak terjaga |

---

## 6. Target Struktur Navigasi Baru

### 6.1 Sidebar untuk Petugas

```text
Dashboard
Operasional
- Input Transaksi
- Riwayat Transaksi

Sinkronisasi
- Status Sinkronisasi

Pengaturan
- Preferensi
```

### 6.2 Sidebar untuk Admin

```text
Dashboard

Operasional
- Semua Transaksi
- Koreksi Transaksi

Konfirmasi Admin
- Pembayaran Pemasok
- Setoran Kantin

Master Data
- Pemasok
- Menu / Makanan
- Pengguna

Keuangan & Rekap
- Rekap Kantin
- Payout Pemasok
- Laporan Operasional

Sinkronisasi
- Status Sinkronisasi
- Konflik Data
- Data Server / Snapshot

Pengaturan
- Preferensi
- Keamanan Perangkat
```

### 6.3 Mapping Rename Label

| Label Lama | Label Baru | Catatan |
|---|---|---|
| Beranda | Dashboard | Gunakan satu istilah saja |
| CRUD Pengguna POS | Kelola Pengguna | Hilangkan istilah developer |
| CRUD Makanan POS | Kelola Menu / Makanan | Lebih sesuai domain kantin |
| CRUD Pemasok POS | Kelola Pemasok | Lebih natural |
| Transaksi Lokal | Riwayat Transaksi | Petugas tidak perlu tahu storage lokal |
| Snapshot Transaksi | Data Transaksi Server | Tempatkan di menu Sinkronisasi |
| Pemasok Lokal | Pemasok | Default pengguna cukup melihat data aktif |
| Snapshot Pemasok | Data Pemasok Server | Tempatkan di menu Sinkronisasi |
| Status I | Status Pembayaran Pemasok | Label bisnis |
| Status II | Status Setoran Kantin | Label bisnis |
| Status III | Status Rekap Harian | Sesuaikan rule bisnis |
| Status IV | Status Tutup Buku / Validasi Akhir | Sesuaikan rule bisnis |

---

## 7. Rencana Eksekusi Code

## Phase 0 — Persiapan Baseline

### Tujuan

Memastikan kondisi repo saat ini bersih, dependency bisa di-install, dan test/build baseline diketahui.

### Langkah

```bash
composer install
npm install
php artisan key:generate
php artisan migrate
php artisan test
npm run build
```

Jika memakai SQLite lokal:

```bash
touch database/database.sqlite
php artisan migrate:fresh --seed
```

### Output yang diharapkan

- dependency PHP dan Node berhasil;
- migrasi database berhasil;
- test baseline diketahui;
- build frontend berhasil;
- daftar failure dicatat sebelum perubahan.

---

## Phase 1 — Hardening Route Authorization

### File Target

```text
routes/web.php
app/Http/Middleware/RoleMiddleware.php
bootstrap/app.php
tests/Feature/PosKantinAccessTest.php
```

### Masalah

Sebagian route POS berada di dalam `auth`, tetapi belum semuanya melewati middleware role. Karena `RoleMiddleware` juga mengecek user aktif, semua route POS perlu melewati middleware ini.

### Perubahan

Refactor route group menjadi pola berikut:

```php
Route::middleware(['auth', 'role:admin,petugas'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::prefix('pos-kantin')->name('pos-kantin.')->group(function () {
        Route::resource('sales', LocalSaleController::class);
        Route::resource('preferences', PreferenceController::class)
            ->only(['index', 'store', 'update']);

        Route::get('/transaksi', [TransactionController::class, 'index'])
            ->name('transactions.index');

        Route::get('/simpanan', [SavingController::class, 'index'])
            ->name('savings.index');

        Route::get('/pemasok', [SupplierController::class, 'index'])
            ->name('suppliers.index');

        Route::get('/pembayaran', [SupplierPayoutController::class, 'index'])
            ->name('supplier-payouts.index');

        Route::get('/laporan', [ReportController::class, 'index'])
            ->name('reports.index');

        Route::prefix('admin')
            ->name('admin.')
            ->middleware('role:admin')
            ->group(function () {
                // admin-only routes
            });

        Route::prefix('sinkronisasi')
            ->name('sync.')
            ->group(function () {
                // sync routes
            });
    });
});
```

### Acceptance Criteria

- user guest diarahkan ke login;
- user nonaktif mendapat 403;
- user dengan role tidak valid mendapat 403;
- petugas tidak bisa akses route admin;
- admin bisa akses semua route admin;
- semua route POS berada di bawah `auth + role`.

### Test yang Wajib Ada

```text
tests/Feature/PosKantinAccessTest.php
```

Minimal scenario:

```text
- guest cannot access POS routes
- inactive user cannot access POS routes
- petugas cannot access admin routes
- admin can access admin routes
- petugas can access sales routes
```

---

## Phase 2 — Disable Public Registration

### File Target

```text
routes/web.php
resources/views/auth/login.blade.php
tests/Feature/Auth/RegisterDisabledTest.php
```

### Masalah

Aplikasi POS/internal sebaiknya tidak membuka pendaftaran publik. User harus dibuat oleh admin.

### Perubahan

Ubah:

```php
Auth::routes();
```

Menjadi:

```php
Auth::routes([
    'register' => false,
]);
```

Pastikan link register di login view tidak tampil.

### Acceptance Criteria

- `GET /register` tidak bisa diakses;
- `POST /register` tidak bisa dipakai membuat user;
- login page tidak menampilkan link register;
- user tetap bisa dibuat dari halaman admin.

---

## Phase 3 — Refactor Navigasi Sidebar dan Istilah UI

### File Target

```text
resources/views/layouts/app.blade.php
resources/views/home.blade.php
resources/views/pos-kantin/**/*.blade.php
```

### Masalah

Sidebar dan judul halaman masih memakai istilah backend/developer.

### Perubahan

1. Hapus menu `Beranda` jika fungsinya sama dengan Dashboard.
2. Kelompokkan menu berdasarkan role.
3. Ganti label teknis:
   - `CRUD` menjadi `Kelola`;
   - `Snapshot` menjadi `Data Server`;
   - `Status I/II/III/IV` menjadi status bisnis.
4. Gunakan `page_actions`, `page_subtitle`, dan `breadcrumbs` secara konsisten.

### Contoh Struktur Blade

```blade
@auth
    <li class="nav-item">
        <a href="{{ route('home') }}" class="nav-link @if(request()->routeIs('home')) active @endif">
            <i class="nav-icon fas fa-chart-line"></i>
            <p>Dashboard</p>
        </a>
    </li>

    @if(Auth::user()->isAdmin())
        @include('layouts.partials.sidebar-admin')
    @endif

    @if(Auth::user()->isPetugas())
        @include('layouts.partials.sidebar-petugas')
    @endif
@endauth
```

### Struktur Partial Baru

```text
resources/views/layouts/partials/sidebar-admin.blade.php
resources/views/layouts/partials/sidebar-petugas.blade.php
resources/views/layouts/partials/sidebar-sync.blade.php
```

### Acceptance Criteria

- petugas hanya melihat menu yang relevan;
- admin melihat menu admin;
- tidak ada label `CRUD` di UI;
- `Snapshot` tidak menjadi menu utama operasional;
- semua halaman punya judul dan action yang konsisten.

---

## Phase 4 — Pisahkan Pembayaran Pemasok dan Setoran Kantin

### File Target

```text
database/migrations/xxxx_xx_xx_xxxxxx_split_sale_confirmation_fields.php
app/Models/Sale.php
app/Http/Controllers/PosKantin/Admin/SaleController.php
app/Http/Requests/PosKantin/Admin/ConfirmSupplierPaidRequest.php
app/Http/Requests/PosKantin/Admin/ConfirmCanteenDepositedRequest.php
resources/views/pos-kantin/admin/sales/show.blade.php
tests/Feature/PosKantinSaleConfirmationTest.php
```

### Masalah

Saat ini pembayaran pemasok dan setoran kantin sama-sama memakai field:

```text
paid_at
paid_amount
taken_note
```

Ini berisiko menyebabkan data salah satu proses menimpa proses lainnya.

### Opsi A — Perubahan Minimal

Tambahkan field ke `sales`:

```text
supplier_paid_at
supplier_paid_amount
supplier_payment_note
supplier_payment_confirmed_by

canteen_deposited_at
canteen_deposited_amount
canteen_deposit_note
canteen_deposit_confirmed_by
```

### Contoh Migration

```php
Schema::table('sales', function (Blueprint $table) {
    $table->date('supplier_paid_at')->nullable()->after('status_i');
    $table->unsignedBigInteger('supplier_paid_amount')->nullable()->after('supplier_paid_at');
    $table->string('supplier_payment_note')->nullable()->after('supplier_paid_amount');
    $table->foreignId('supplier_payment_confirmed_by')->nullable()->constrained('users')->nullOnDelete();

    $table->date('canteen_deposited_at')->nullable()->after('status_ii');
    $table->unsignedBigInteger('canteen_deposited_amount')->nullable()->after('canteen_deposited_at');
    $table->string('canteen_deposit_note')->nullable()->after('canteen_deposited_amount');
    $table->foreignId('canteen_deposit_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
});
```

### Opsi B — Lebih Rapi

Buat tabel baru:

```text
sale_confirmations
- id
- sale_id
- type: supplier_payment | canteen_deposit
- amount
- confirmed_at
- note
- confirmed_by
- created_at
- updated_at
```

Rekomendasi jangka panjang: **Opsi B**, karena lebih mudah diaudit dan bisa menyimpan histori.  
Untuk perubahan cepat dan aman: **Opsi A**.

### Perubahan Controller

```php
public function confirmSupplierPaid(ConfirmSupplierPaidRequest $request, Sale $sale): RedirectResponse
{
    $sale->update([
        'status_i' => Sale::STATUS_SUPPLIER_PAID,
        'supplier_paid_at' => $request->date('paid_at'),
        'supplier_paid_amount' => $request->integer('paid_amount'),
        'supplier_payment_note' => $request->input('taken_note'),
        'supplier_payment_confirmed_by' => $request->user()->id,
    ]);

    return back()->with('status', 'Pembayaran pemasok berhasil dikonfirmasi.');
}
```

```php
public function confirmCanteenDeposited(ConfirmCanteenDepositedRequest $request, Sale $sale): RedirectResponse
{
    $sale->update([
        'status_ii' => Sale::STATUS_CANTEEN_DEPOSITED,
        'canteen_deposited_at' => $request->date('paid_at'),
        'canteen_deposited_amount' => $request->integer('paid_amount'),
        'canteen_deposit_note' => $request->input('taken_note'),
        'canteen_deposit_confirmed_by' => $request->user()->id,
    ]);

    return back()->with('status', 'Setoran kantin berhasil dikonfirmasi.');
}
```

### Acceptance Criteria

- konfirmasi pemasok tidak mengubah data setoran kantin;
- konfirmasi setoran kantin tidak mengubah data pembayaran pemasok;
- detail transaksi menampilkan dua blok status yang jelas;
- admin dapat melihat siapa yang mengonfirmasi;
- action tercatat di audit log.

---

## Phase 5 — Tambah Audit Log Finansial dan Konflik Sync

### File Target

```text
database/migrations/xxxx_xx_xx_xxxxxx_create_audit_logs_table.php
app/Models/AuditLog.php
app/Services/Audit/AuditLogger.php
app/Http/Controllers/PosKantin/Admin/SaleController.php
app/Http/Controllers/PosKantin/SyncController.php
```

### Struktur Tabel

```text
audit_logs
- id
- actor_user_id
- action
- subject_type
- subject_id
- ip_address
- user_agent
- metadata JSON encrypted
- created_at
```

### Event yang Harus Dicatat

```text
sale.supplier_payment_confirmed
sale.canteen_deposit_confirmed
sale.updated
sale.deleted
sync.conflict.resolved_with_server
sync.conflict.retry_local
user.created
user.updated
user.deactivated
```

### Acceptance Criteria

- setiap aksi finansial masuk audit log;
- setiap resolusi konflik masuk audit log;
- metadata sensitif dienkripsi atau diminimalkan;
- audit log hanya bisa dilihat admin.

---

## Phase 6 — Perbaikan Conflict Sync UI

### File Target

```text
resources/views/pos-kantin/sync/index.blade.php
app/Http/Controllers/PosKantin/SyncController.php
app/Services/PosKantin/PosKantinSyncService.php
```

### Masalah

UI konflik sinkronisasi belum menampilkan perbandingan data lokal vs server secara jelas.

### Perubahan UI

Buat tabel konflik seperti:

```text
Entitas
ID Server
Field Berbeda
Nilai Lokal
Nilai Server
Waktu Perubahan Lokal
Waktu Perubahan Server
Aksi
```

### Modal Konfirmasi

Untuk `Pakai Server`:

```text
Anda akan mengganti data lokal dengan versi server. Perubahan lokal yang belum tersinkron dapat hilang. Aksi ini akan dicatat di audit log. Lanjutkan?
```

Untuk `Kirim Ulang Lokal`:

```text
Anda akan mengirim ulang data lokal ke server. Pastikan data lokal adalah versi yang benar. Aksi ini akan dicatat di audit log. Lanjutkan?
```

### Acceptance Criteria

- user/admin dapat melihat perbedaan lokal vs server;
- action destruktif memakai modal konfirmasi;
- resolusi konflik dicatat audit log;
- tidak ada tombol resolusi tanpa konteks data.

---

## Phase 7 — Perbaikan Form Transaksi Harian

### File Target

```text
resources/views/pos-kantin/sales/_form.blade.php
resources/views/pos-kantin/sales/create.blade.php
resources/views/pos-kantin/sales/edit.blade.php
resources/js/app.js
app/Http/Requests/PosKantin/StoreSaleRequest.php
app/Http/Requests/PosKantin/UpdateSaleRequest.php
```

### Masalah

Form transaksi masih berbasis table input dan belum optimal untuk input cepat.

### Perubahan UX

1. Tambahkan field-level error.
2. Tambahkan `id` dan `for` pada label/input.
3. Gunakan `inputmode="numeric"` untuk nominal.
4. Tambahkan subtotal otomatis per item.
5. Tambahkan ringkasan sebelum submit:
   - total terjual;
   - total pemasok;
   - total kantin;
   - jumlah item.
6. Pada mobile, tampilkan item sebagai card, bukan table lebar.
7. Tambahkan tombol `Tambah Item` yang jelas.
8. Tambahkan validasi client-side ringan tanpa mengganti validasi server.

### Acceptance Criteria

- form bisa digunakan nyaman di desktop dan tablet;
- error muncul dekat field yang bermasalah;
- user melihat total sebelum submit;
- validasi server tetap menjadi sumber kebenaran;
- tidak ada input harga/qty kosong yang lolos.

---

## Phase 8 — Password Policy dan User Management Hardening

### File Target

```text
app/Http/Requests/PosKantin/Admin/StoreUserRequest.php
app/Http/Requests/PosKantin/Admin/UpdateUserRequest.php
app/Http/Controllers/PosKantin/Admin/UserController.php
tests/Feature/AdminUserSecurityTest.php
```

### Perubahan Password Policy

Gunakan:

```php
use Illuminate\Validation\Rules\Password;

'password' => [
    'required',
    'confirmed',
    Password::min(12)
        ->letters()
        ->mixedCase()
        ->numbers()
        ->symbols()
        ->uncompromised(),
],
```

Untuk update:

```php
'password' => [
    'nullable',
    'confirmed',
    Password::min(12)
        ->letters()
        ->mixedCase()
        ->numbers()
        ->symbols()
        ->uncompromised(),
],
```

### Tambahan Safety Rule

- admin tidak boleh menonaktifkan akun admin terakhir;
- admin tidak boleh menghapus dirinya sendiri tanpa admin lain aktif;
- perubahan role admin harus dicatat audit log;
- email user harus unik dan dinormalisasi lowercase.

### Acceptance Criteria

- password lemah ditolak;
- admin terakhir tidak bisa dinonaktifkan;
- perubahan role tercatat;
- user nonaktif tidak bisa login atau akses POS.

---

## Phase 9 — Environment dan Debug Hardening

### File Target

```text
.env.example
.env.production.example
resources/views/layouts/app.blade.php
resources/js/app.js
config/services.php
```

### Perubahan

Buat file baru:

```text
.env.production.example
```

Isi baseline:

```env
APP_NAME=KanSor
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

LOG_LEVEL=warning

SESSION_DRIVER=database
SESSION_LIFETIME=60
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict

BCRYPT_ROUNDS=12

POS_KANTIN_TIMEOUT=20
POS_KANTIN_CONNECT_TIMEOUT=10
POS_KANTIN_SYNC_INTERVAL_SECONDS=60
```

### Gate Debug UI

Semua debug drawer, Telescope shortcut, dan internal debug panel harus diproteksi:

```blade
@if (app()->environment('local') && Auth::check() && Auth::user()->isAdmin())
    {{-- debug tooling --}}
@endif
```

### Acceptance Criteria

- tidak ada debug UI di production;
- `.env.production.example` tersedia;
- default nama aplikasi bukan `Laravel`;
- production baseline memakai secure session;
- debug shortcut JS tidak aktif di production.

---

## Phase 10 — Enkripsi Payload Sync dan Conflict

### File Target

```text
app/Models/PosKantinSyncOutbox.php
app/Models/PosKantinSyncConflict.php
database/migrations/xxxx_xx_xx_xxxxxx_encrypt_sync_payloads.php
```

### Masalah

Payload sync dan konflik berpotensi berisi data transaksi, pengguna, supplier, dan informasi operasional.

### Perubahan

Untuk data baru:

```php
protected function casts(): array
{
    return [
        'payload' => 'encrypted:array',
        'server_snapshot' => 'encrypted:array',
    ];
}
```

Pada conflict:

```php
protected function casts(): array
{
    return [
        'local_payload' => 'encrypted:array',
        'server_payload' => 'encrypted:array',
    ];
}
```

### Catatan Migrasi

Jika data lama sudah tersimpan plaintext, perlu migration/script transisi:

1. baca payload lama;
2. simpan ulang dengan cast encrypted;
3. verifikasi bisa dibaca ulang;
4. backup database sebelum migration.

### Acceptance Criteria

- data sync baru tersimpan encrypted;
- data lama bisa dimigrasikan;
- index/search tetap memakai field non-sensitif;
- tidak ada token atau payload sensitif tampil di log.

---

## Phase 11 — Throttle dan Server-side Lock untuk Sync

### File Target

```text
routes/web.php
app/Http/Controllers/PosKantin/SyncController.php
app/Services/PosKantin/PosKantinSyncService.php
app/Providers/RouteServiceProvider.php atau bootstrap/app.php sesuai versi Laravel
```

### Perubahan Route

```php
Route::post('/sinkronisasi/auto', [SyncController::class, 'auto'])
    ->middleware('throttle:sync-auto')
    ->name('sync.auto');
```

### Perubahan Service

```php
return Cache::lock("pos-sync:user:{$user->id}", 120)->block(3, function () use ($user) {
    return $this->sync($user, 'auto');
});
```

### Acceptance Criteria

- request sync paralel untuk user sama tidak berjalan bersamaan;
- request sync berlebihan terkena throttle;
- error lock ditampilkan user-friendly;
- tidak ada duplicate mutation karena double submit.

---

## Phase 12 — Komponen Blade Reusable

### Struktur Baru

```text
resources/views/components/pos/page-header.blade.php
resources/views/components/pos/filter-card.blade.php
resources/views/components/pos/data-table.blade.php
resources/views/components/pos/status-badge.blade.php
resources/views/components/pos/empty-state.blade.php
resources/views/components/form/input.blade.php
resources/views/components/form/select.blade.php
resources/views/components/form/money.blade.php
```

### Tujuan

Mengurangi duplikasi dan membuat semua halaman terasa konsisten.

### Acceptance Criteria

- alert global konsisten;
- form field konsisten;
- badge status memakai mapping warna dan label yang sama;
- empty state tidak lagi berupa tabel kosong tanpa konteks;
- action primer selalu berada di page header.

---

## 8. Testing Plan

### 8.1 Command Umum

```bash
php artisan test
npm run build
php artisan route:list
```

### 8.2 Test Keamanan

```text
tests/Feature/PosKantinAccessTest.php
tests/Feature/Auth/RegisterDisabledTest.php
tests/Feature/AdminUserSecurityTest.php
tests/Feature/SyncSecurityTest.php
```

Scenario wajib:

```text
- guest tidak bisa akses POS;
- user nonaktif mendapat 403;
- petugas tidak bisa akses admin;
- admin bisa akses admin;
- register route disabled;
- password lemah ditolak;
- admin terakhir tidak bisa dinonaktifkan;
- auto sync terkena throttle;
- sync conflict resolution tercatat audit log.
```

### 8.3 Test UI/UX Smoke

Checklist manual:

```text
[ ] Login sebagai petugas
[ ] Petugas hanya melihat menu petugas
[ ] Petugas bisa input transaksi
[ ] Petugas melihat total transaksi sebelum submit
[ ] Petugas tidak melihat menu admin
[ ] Login sebagai admin
[ ] Admin melihat menu admin
[ ] Admin bisa konfirmasi pembayaran pemasok
[ ] Admin bisa konfirmasi setoran kantin
[ ] Konfirmasi pemasok tidak menimpa data setoran
[ ] Konfirmasi setoran tidak menimpa data pemasok
[ ] Sync conflict menampilkan perbandingan lokal vs server
[ ] Debug drawer tidak muncul di production
```

---

## 9. Definition of Done

Perubahan dianggap selesai jika:

1. semua route POS berada di bawah `auth + role`;
2. public registration dimatikan;
3. istilah `CRUD` tidak tampil di UI produksi;
4. `Snapshot` tidak menjadi menu operasional utama;
5. label status diganti menjadi istilah bisnis;
6. pembayaran pemasok dan setoran kantin tidak lagi memakai field yang sama;
7. action finansial dan konflik sync masuk audit log;
8. dashboard petugas dan admin punya prioritas aksi berbeda;
9. debug tooling hanya aktif di local/development;
10. payload sync/conflict dienkripsi atau diminimalkan;
11. `php artisan test` lulus;
12. `npm run build` lulus;
13. hasil smoke test role petugas/admin lulus.

---

## 10. Rencana PR Bertahap

### PR 1 — Security Access Baseline

Isi:

- refactor route group;
- disable register;
- tambah access tests.

Target selesai:

```text
P0 security baseline aman.
```

### PR 2 — Navigation & Label Cleanup

Isi:

- sidebar role-based;
- rename label teknis;
- standardisasi `page_actions`.

Target selesai:

```text
UI tidak lagi terasa developer-facing.
```

### PR 3 — Financial Confirmation Refactor

Isi:

- migration field pembayaran/setoran;
- update model/controller/request;
- update detail transaksi;
- tambah test konfirmasi.

Target selesai:

```text
Data finansial tidak saling overwrite.
```

### PR 4 — Audit Log

Isi:

- tabel audit log;
- audit logger service;
- event finansial dan sync conflict.

Target selesai:

```text
Aksi sensitif tercatat.
```

### PR 5 — Sync Conflict UI

Isi:

- tabel diff lokal vs server;
- modal konfirmasi;
- audit log resolusi.

Target selesai:

```text
Resolusi konflik transparan dan aman.
```

### PR 6 — Transaction Form UX

Isi:

- field-level errors;
- input numeric;
- subtotal;
- mobile-friendly item card.

Target selesai:

```text
Input transaksi lebih cepat dan minim error.
```

### PR 7 — Production Hardening

Isi:

- `.env.production.example`;
- gate debug tooling;
- encrypt sync payload;
- throttle/lock sync.

Target selesai:

```text
Baseline produksi lebih aman.
```

---

## 11. Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Migration pembayaran salah | Data finansial tidak konsisten | Backup DB, migration bertahap, test transaksi existing |
| Rename route/menu membingungkan user lama | Training ulang singkat | Buat mapping label lama-baru |
| Enkripsi payload membuat query lama gagal | Feature sync terganggu | Simpan index non-sensitif terpisah |
| Gate debug terlalu ketat saat development | Developer kehilangan tool debug | Pastikan `APP_ENV=local` tetap membuka debug |
| Route refactor menyebabkan 404 | Fitur tidak bisa dibuka | Jalankan `php artisan route:list` dan smoke test |
| Conflict UI salah mapping payload | Resolusi data salah | Buat test dengan sample konflik lokal/server |

---

## 12. Catatan Standar Keamanan Nasional

Baseline perubahan diarahkan agar selaras dengan prinsip SMKI/SNI ISO/IEC 27001 dan praktik perlindungan data pribadi:

- kontrol akses berbasis peran;
- prinsip least privilege;
- audit trail untuk aktivitas sensitif;
- keamanan sesi;
- enkripsi data sensitif;
- pengendalian perubahan;
- minimisasi data;
- pengamanan environment produksi;
- pemisahan tugas admin dan petugas.

Catatan: validasi kepatuhan formal tetap perlu dilakukan oleh auditor/penanggung jawab keamanan organisasi, terutama jika aplikasi digunakan untuk operasional resmi dan menyimpan data pribadi/keuangan.

---

## 13. Checklist Eksekusi Singkat

```text
[ ] Buat branch baru
[ ] Jalankan baseline test/build
[ ] Refactor route authorization
[ ] Disable register
[ ] Tambah test access control
[ ] Refactor sidebar role-based
[ ] Rename istilah UI teknis
[ ] Pisahkan field pembayaran pemasok dan setoran kantin
[ ] Tambah audit log
[ ] Perbaiki UI konflik sync
[ ] Perbaiki form transaksi
[ ] Tambah password policy kuat
[ ] Tambah env production example
[ ] Gate debug tooling
[ ] Enkripsi payload sync/conflict
[ ] Tambah throttle dan lock sync
[ ] Jalankan test
[ ] Jalankan build
[ ] Smoke test admin dan petugas
[ ] Review final dengan stakeholder
```

---

## 14. Perintah Validasi Final

```bash
php artisan optimize:clear
php artisan route:list
php artisan test
npm run build
```

Jika production deployment:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```

---

## 15. Prioritas Implementasi Paling Disarankan

Urutan paling aman:

```text
1. Route authorization + disable register
2. Pisahkan pembayaran pemasok dan setoran kantin
3. Audit log
4. Sidebar dan label UI
5. Sync conflict UI
6. Form transaksi
7. Environment hardening
8. Enkripsi payload dan throttle sync
9. Test regression lengkap
```

Dengan urutan ini, risiko keamanan dan risiko salah data finansial ditutup lebih dulu sebelum polishing UI/UX.
