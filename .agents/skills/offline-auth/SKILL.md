# offline-auth

## Trigger
Use when task mentions offline login, trusted device, expiry window, auth invalidation, or remote auth change handling.

## Data Contract
- Inputs: `email`, `password`, `offline_session_days`, `offline_login_days_max`, `remote_auth_updated_at`.
- Outputs: login mode (`online|offline`), persisted `offline_login_expires_at`, invalidation reason.

## Files
- `app/Services/Auth/PosKantinUserAuthenticator.php`
- `app/Services/Auth/OfflineLoginService.php`
- `config/services.php`
- `tests/Feature/PosKantinAdminLoginTest.php`

## Tests
- `php artisan test --compact --filter=PosKantinAdminLoginTest`

## Acceptance Criteria
- Online login seeds offline trust.
- Offline login works before expiry and fails after expiry.
- Offline session days clamped `min=1` and `max=POS_KANTIN_OFFLINE_LOGIN_DAYS_MAX`.
- Offline login invalidated when remote auth snapshot changes.
