# sync

## Trigger
Use when task mentions outbox, syncPush/syncPull, conflict resolution, selective sync, cursor, or offline mirror.

## Data Contract
- Inputs: `selectedOutboxIds`, outbox status, mutation payloads, cursors.
- Outputs: push summary (`queued/applied/failed/conflicts/skipped/unsupported`), persisted cursor map, conflict records.

## Files
- `app/Services/PosKantin/PosKantinSyncService.php`
- `app/Http/Controllers/PosKantin/SyncController.php`
- `resources/views/pos-kantin/sync/index.blade.php`
- `tests/Feature/Feature/PosKantinSyncServiceTest.php`

## Tests
- `php artisan test --compact --filter=PosKantinSyncServiceTest`

## Acceptance Criteria
- Manual sync all works.
- Selective sync only processes chosen outbox IDs.
- Conflict UI can compare local vs server and resolve action per conflict.
- Unsupported actions are surfaced as `unsupported`.
# Sync

## Deskripsi
Skill ini menangani alur sinkronisasi manual dan selektif antara local outbox dan server, termasuk handling retry, failed items, dan conflict resolution.

## Tujuan
Automasi logika selective/manual sync, filtering outbox item, dan sinkronisasi ulang yang dapat dipilih oleh user.

## Trigger
- Ketika menulis layanan sinkronisasi `PosKantinSyncService`.
- Ketika menambahkan endpoint atau UI untuk sync terpilih.
- Ketika memperbarui query outbox untuk `pending` dan `failed`.
- Ketika menulis conflict resolution atau retry logic.

## Files Touched
- app/Services/PosKantin/PosKantinSyncService.php
- app/Models/PosKantinSyncOutbox.php
- app/Http/Controllers/PosKantin/SyncController.php
- resources/views/pos-kantin/sync/*.blade.php
- routes/web.php
- tests/Feature/Sync*Test.php

## Data Contract
- `user_id`: integer
- `selected_outbox_ids`: array<int>|null
- `status`: string (`pending`, `failed`, `applied`, `conflict`)
- `action`: string
- `payload`: array
- `last_run_at`: datetime|null

## Aturan Implementasi
- Sync harus menerima `selectedOutboxIds` dan memfilter query outbox.
- `pushOutbox()` hanya memproses `pending` dan `failed` item.
- `selectedOutboxIds` null berarti sync semua item.
- Lock per user harus dipertahankan untuk mencegah sync paralel.
- Konflik harus didokumentasikan dan bisa dipilih dalam UI.

## Testing Wajib
- Test sync terpilih hanya memproses item yang dipilih.
- Test sync semua memproses semua item pending/failed.
- Test lock per user mencegah sync paralel.
- Test conflict resolution flow bila item berstatus `conflict`.

## Acceptance Criteria
- User dapat memilih item outbox lalu menekan `Sync Selected`.
- Sync all tetap tersedia untuk semua pending/failed item.
- Outbox `selectedOutboxIds` tidak memproses item di luar daftar.
- Konflik dapat dibedakan dan dijadikan aksi retry/skip.
