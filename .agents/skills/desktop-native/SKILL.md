# desktop-native

## Trigger
Use when task mentions NativePHP/Electron build, Windows `.exe`, installer setup, first-run local DB, or migration safety.

## Data Contract
- Inputs: build target (`win`), app metadata, first-run flags.
- Outputs: build artifact path, installer notes, migration readiness status.

## Files
- `nativephp/electron/electron-builder.mjs`
- `app/Providers/NativeAppServiceProvider.php`
- `resources/views/setup/schema-readiness.blade.php`
- `tests/Feature/NativeDesktopInteractionTest.php`

## Tests
- `php artisan native:build win`
- `php artisan test --compact --filter=NativeDesktopInteractionTest`

## Acceptance Criteria
- Build command for windows documented and reproducible.
- First-run creates local DB if missing.
- Local migrations run safely and are recoverable.
- Installer setup for `.exe` documented.
