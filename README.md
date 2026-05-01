# KanSor

KanSor adalah aplikasi **desktop-native offline-first** untuk operasional POS kantin sekolah. Proyek ini dibangun dengan Laravel 12 dan NativePHP/Electron, dengan backend sinkronisasi berbasis Google Apps Script.

## Tujuan Produk

- Login online cukup sekali, kemudian login offline dapat digunakan berulang sesuai masa sesi.
- Operasional utama tetap berjalan saat offline (supplier, makanan, transaksi lokal).
- Sinkronisasi data ke server dilakukan manual (all atau selected outbox).
- Konflik sinkronisasi dapat dibandingkan lokal vs server sebelum resolusi.
- Target distribusi utama adalah aplikasi Windows `.exe` beserta installer.

## Arsitektur Ringkas

### 1) Aplikasi Desktop (Laravel + NativePHP)
- Menyimpan data operasional lokal (mirror + outbox).
- Menjalankan CRUD lokal untuk modul POS.
- Mengelola antrean sinkronisasi dan konflik.

### 2) Backend Sinkronisasi (Google Apps Script)
- Nama script: **KanSor API**.
- Nama spreadsheet default: **KanSor Database**.
- Menyediakan action `health`, `login`, `syncPull`, `syncPush`, dan action domain lainnya.

### 3) Data Layer Offline-First
- **Mirror tables**: snapshot data server untuk akses offline cepat.
- **Outbox**: perubahan lokal yang menunggu push ke server.
- **Cursors**: penanda delta pull per resource.

## Fitur Utama

- Autentikasi online + trusted-device untuk mode offline.
- Preferensi durasi login offline per user (dengan batas maksimum sistem).
- Sinkronisasi manual penuh atau sinkronisasi terpilih per outbox.
- Resolusi konflik sinkronisasi (pilih versi lokal atau server).
- Mapping transaksi ter-normalisasi: satu `sale_item` menjadi satu row remote transaction.

## Teknologi

- PHP 8.5
- Laravel 12
- NativePHP Desktop
- Pest (testing)
- Laravel Pint (formatting)
- Google Apps Script (backend sync)

## Persiapan Lokal

### Prasyarat
- PHP 8.5+
- Composer
- Node.js + npm
- SQLite/MySQL (sesuai konfigurasi `.env`)

### Instalasi

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

## Menjalankan Aplikasi

### Mode Laravel Dev

```bash
php artisan serve
npm run dev
```

### Build Frontend

```bash
npm run build
```

### Build Native Windows

```bash
php artisan native:build win
```

> Catatan: fokus deployment proyek ini adalah desktop-native, bukan web hosting sebagai target utama.

## Sinkronisasi POS

- Status sinkronisasi tersedia di halaman `pos-kantin/sinkronisasi`.
- Mendukung:
  - Sync semua antrean.
  - Sync selected outbox.
  - Retry failed/conflict.
- Konflik menampilkan konteks per field untuk membantu keputusan resolusi.

## Seeder Dummy Data

Tersedia command:

```bash
php artisan pos-kantin:seed-dummy --all
```

Opsi tambahan:

```bash
php artisan pos-kantin:seed-dummy --only=users,suppliers,foods,transactions,finance
php artisan pos-kantin:seed-dummy --fresh --all
```

## Integrasi Apps Script

Dokumentasi detail CLASP, setup spreadsheet, dan verifikasi endpoint ada di:

- `apps-script/README.md`

Dokumen tersebut mencakup:
- login `clasp` dengan project scopes,
- `setupApplicationSpreadsheet`,
- `setUserPasswordByEmail`,
- contoh `curl` untuk `health`, `login`, `syncPull`, `syncPush`.

## Quality Checks yang Direkomendasikan

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

Untuk validasi penuh desktop-native, lanjutkan dengan:

```bash
php artisan native:build win
```

## Struktur Direktori Penting

- `app/Services/PosKantin/` — logic sinkronisasi, mirror, outbox, payload.
- `app/Services/Auth/` — autentikasi online/offline.
- `apps-script/` — source Google Apps Script backend.
- `database/migrations/` — skema database lokal.
- `database/seeders/` — data dummy/seeding modular.
- `resources/views/pos-kantin/` — UI POS dan sinkronisasi.

## Kontribusi

1. Buat branch fitur dari branch aktif.
2. Implementasi perubahan dengan test yang relevan.
3. Jalankan formatting dan test sebelum commit.
4. Buat commit message yang jelas dan spesifik.

## Keamanan

Jangan commit file rahasia berikut:
- `.clasp.json`
- `.clasprc.json`
- `client_secret.json`
- kredensial production lain di luar `.env` lokal

## Lisensi

Internal project KanSor. Gunakan sesuai kebijakan tim/pemilik repository.
