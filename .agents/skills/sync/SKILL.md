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
