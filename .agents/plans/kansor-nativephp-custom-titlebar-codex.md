# Codex Task: NativePHP Custom Titlebar untuk KanSor

## Tujuan

Implementasikan titlebar/native action navbar custom untuk aplikasi KanSor saat berjalan lewat NativePHP Desktop. Tampilan yang dituju mirip bar atas Codex: dark, tipis, ada brand KanSor di kiri, tombol back/forward, menu aksi `File`, `Edit`, `View`, `Window`, `Help`, user di kanan, serta tombol minimize, maximize, dan close di ujung kanan.

Perubahan hanya aktif ketika KanSor berjalan sebagai NativePHP Desktop. Saat dibuka lewat browser biasa, layout web tetap memakai navbar AdminLTE seperti sekarang.

## Konteks project

Project root diasumsikan:

```txt
KanSor/
```

File terkait yang sudah ada:

```txt
KanSor/
├─ app/
│  ├─ Providers/
│  │  └─ NativeAppServiceProvider.php
│  └─ Http/
│     └─ Controllers/
│        └─ NativeDesktopController.php
├─ routes/
│  └─ web.php
├─ resources/
│  ├─ views/
│  │  └─ layouts/
│  │     ├─ app.blade.php
│  │     └─ partials/
│  ├─ sass/
│  │  └─ app.scss
│  └─ js/
│     └─ app.js
└─ config/
   └─ nativephp.php
```

NativePHP sudah terpasang. Jangan edit folder `nativephp/electron` untuk task ini kecuali benar-benar diperlukan. Gunakan API Laravel/NativePHP yang sudah tersedia.

## Acceptance criteria

1. Saat menjalankan `composer native:dev`, window KanSor tampil tanpa titlebar bawaan OS/Electron.
2. Muncul titlebar custom di paling atas aplikasi.
3. Area kosong titlebar bisa digunakan untuk drag/memindahkan window.
4. Tombol `minimize`, `maximize`, `close`, dan `reload` bekerja melalui route Laravel yang memanggil facade `Native\Desktop\Facades\Window`.
5. Tombol back/forward masih memakai logic existing `data-app-shell-back` dan `data-app-shell-forward` di `resources/js/app.js`.
6. Saat KanSor dibuka lewat browser biasa, titlebar custom tidak muncul.
7. Build asset berhasil dengan `npm run build`.

## 1. Update NativePHP main window

Edit file:

```txt
app/Providers/NativeAppServiceProvider.php
```

Cari method `openMainWindow()` dan ubah chain `Window::open(self::MAIN_WINDOW_ID)` menjadi seperti ini:

```php
private function openMainWindow(): void
{
    Window::open(self::MAIN_WINDOW_ID)
        ->title(config('app.name', 'KanSor'))
        ->frameless()
        ->hideMenu()
        ->backgroundColor('#20272b')
        ->width((int) config('nativephp.window.width', 1440))
        ->height((int) config('nativephp.window.height', 960))
        ->minWidth((int) config('nativephp.window.min_width', 1024))
        ->minHeight((int) config('nativephp.window.min_height', 720))
        ->webPreferences([
            'webviewTag' => true,
        ])
        ->when(
            (bool) config('nativephp.window.remember_state', true),
            fn ($window) => $window->rememberState()
        );
}
```

Catatan:

- `frameless()` menghilangkan titlebar bawaan.
- `hideMenu()` menyembunyikan menu OS/Electron bawaan.
- `backgroundColor('#20272b')` mengurangi flash putih saat window pertama kali dibuka.

## 2. Tambahkan route window control

Edit file:

```txt
routes/web.php
```

Tambahkan route ini setelah `Auth::routes([...]);` dan sebelum group route `auth` existing:

```php
Route::post('/native/desktop/window/{action}', [NativeDesktopController::class, 'controlWindow'])
    ->where('action', 'minimize|maximize|reload|close')
    ->name('native.desktop.window-control');
```

Route ini sengaja tidak dibungkus middleware `auth`, supaya tombol close/minimize tetap tersedia di halaman login. CSRF tetap aktif karena route ini berada di `web.php`.

## 3. Tambahkan controller action untuk NativePHP window

Edit file:

```txt
app/Http/Controllers/NativeDesktopController.php
```

Tambahkan import:

```php
use Native\Desktop\Facades\Window;
```

Tambahkan method ini di dalam class `NativeDesktopController`:

```php
public function controlWindow(Request $request, string $action): JsonResponse
{
    abort_unless((bool) config('nativephp-internal.running'), 404);

    match ($action) {
        'minimize' => Window::minimize('main'),
        'maximize' => Window::maximize('main'),
        'reload' => Window::reload('main'),
        'close' => Window::close('main'),
        default => abort(404),
    };

    return response()->json([
        'success' => true,
        'action' => $action,
    ]);
}
```

Expected final shape:

```php
<?php

namespace App\Http\Controllers;

use App\Events\OpenTelescopeWindow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Native\Desktop\Facades\Window;

class NativeDesktopController extends Controller
{
    public function openTelescopeWindow(Request $request): JsonResponse
    {
        abort_unless(
            app()->environment('local') && $request->user()?->isAdmin() && $request->user()?->isActiveUser(),
            403,
        );

        event(new OpenTelescopeWindow);

        return response()->json([
            'success' => true,
        ]);
    }

    public function controlWindow(Request $request, string $action): JsonResponse
    {
        abort_unless((bool) config('nativephp-internal.running'), 404);

        match ($action) {
            'minimize' => Window::minimize('main'),
            'maximize' => Window::maximize('main'),
            'reload' => Window::reload('main'),
            'close' => Window::close('main'),
            default => abort(404),
        };

        return response()->json([
            'success' => true,
            'action' => $action,
        ]);
    }
}
```

## 4. Update app shell config di Blade layout

Edit file:

```txt
resources/views/layouts/app.blade.php
```

Cari array `$kanSorAppShell`. Ubah menjadi:

```php
$kanSorAppShell = [
    'setupRunUrl' => app()->environment('local') ? route('setup.run-migrations') : null,
    'setupStatusUrl' => app()->environment('local') ? route('setup.status') : null,
    'hasPendingMigrations' => app(\App\Services\Setup\SchemaReadinessService::class)->hasPendingMigrations(),
    'isNativeDesktop' => (bool) config('nativephp-internal.running'),
    'nativeWindowControlUrl' => (bool) config('nativephp-internal.running')
        ? route('native.desktop.window-control', ['action' => '__ACTION__'])
        : null,
];
```

Kemudian ubah tag `<body>` dari:

```blade
<body class="@yield('body_class', 'hold-transition sidebar-mini layout-fixed')">
```

Menjadi:

```blade
<body class="@yield('body_class', 'hold-transition sidebar-mini layout-fixed') {{ config('nativephp-internal.running') ? 'kansor-native-desktop' : '' }}">
```

Setelah blok `@php` awal di body, sebelum `@hasSection('auth_page')`, tambahkan:

```blade
@if (config('nativephp-internal.running'))
    @include('layouts.partials.native-titlebar')
@endif
```

Contoh posisi:

```blade
<body class="@yield('body_class', 'hold-transition sidebar-mini layout-fixed') {{ config('nativephp-internal.running') ? 'kansor-native-desktop' : '' }}">
    @php
        $kanSorSyncNavigationStatus = $kanSorSyncNavigationStatus ?? [];
        $currentUser = Auth::user();
        $syncQueuedCount = (int) ($kanSorSyncNavigationStatus['queuedCount'] ?? $kanSorSyncNavigationStatus['pendingCount'] ?? 0);
        $syncFailedCount = (int) ($kanSorSyncNavigationStatus['failedCount'] ?? 0);
        $syncConflictCount = (int) ($kanSorSyncNavigationStatus['conflictCount'] ?? 0);
        $syncAttentionCount = $syncFailedCount + $syncConflictCount;
    @endphp

    @if (config('nativephp-internal.running'))
        @include('layouts.partials.native-titlebar')
    @endif

    @hasSection('auth_page')
```

## 5. Sembunyikan tombol back/forward lama ketika NativePHP

Masih di:

```txt
resources/views/layouts/app.blade.php
```

Cari blok navbar existing yang berisi:

```blade
<div class="btn-group btn-group-sm kansor-shell-nav" role="group" aria-label="Navigasi pengembangan">
```

Bungkus seluruh `<li>` tombol back/forward/refresh lama dengan:

```blade
@if (! config('nativephp-internal.running'))
    <li class="nav-item d-none d-md-flex align-items-center ml-2">
        <div class="btn-group btn-group-sm kansor-shell-nav" role="group" aria-label="Navigasi pengembangan">
            <button type="button" class="btn btn-outline-secondary" data-app-shell-back disabled title="Kembali">
                <i class="fas fa-arrow-left"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary" data-app-shell-forward disabled title="Maju">
                <i class="fas fa-arrow-right"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary" data-app-shell-refresh title="Refresh halaman">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </li>
@endif
```

Tujuannya agar selector JS `data-app-shell-back` dan `data-app-shell-forward` tidak menemukan dua set tombol sekaligus.

## 6. Buat partial custom titlebar

Buat file baru:

```txt
resources/views/layouts/partials/native-titlebar.blade.php
```

Isi file:

```blade
<header class="kansor-native-titlebar" aria-label="KanSor native titlebar">
    <div class="kansor-native-titlebar__left">
        <a
            href="{{ auth()->check() ? route('home') : url('/') }}"
            class="kansor-native-titlebar__brand"
            data-skip-loading
            title="{{ config('app.name', 'KanSor') }}"
        >
            <i class="fas fa-store"></i>
            <span>{{ config('app.name', 'KanSor') }}</span>
        </a>

        @auth
            <div class="kansor-native-titlebar__history" role="group" aria-label="Navigasi halaman">
                <button type="button" data-app-shell-back disabled title="Kembali">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button type="button" data-app-shell-forward disabled title="Maju">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        @endauth

        <nav class="kansor-native-titlebar__menu" aria-label="Menu aplikasi">
            <div class="dropdown">
                <button
                    type="button"
                    class="kansor-native-titlebar__menu-button"
                    data-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    File
                </button>

                <div class="dropdown-menu">
                    @auth
                        <a class="dropdown-item" href="{{ route('home') }}">
                            Dashboard
                        </a>
                    @else
                        <a class="dropdown-item" href="{{ route('login') }}">
                            Login
                        </a>
                    @endauth

                    <button type="button" class="dropdown-item" data-native-window-control="reload">
                        Reload
                    </button>

                    <div class="dropdown-divider"></div>

                    <button type="button" class="dropdown-item text-danger" data-native-window-control="close">
                        Keluar
                    </button>
                </div>
            </div>

            <button type="button" class="kansor-native-titlebar__menu-button" disabled>
                Edit
            </button>

            <div class="dropdown">
                <button
                    type="button"
                    class="kansor-native-titlebar__menu-button"
                    data-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    View
                </button>

                <div class="dropdown-menu">
                    <button type="button" class="dropdown-item" data-native-window-control="reload">
                        Refresh halaman
                    </button>

                    @auth
                        <button type="button" class="dropdown-item" data-widget="pushmenu">
                            Toggle sidebar
                        </button>
                    @endauth
                </div>
            </div>

            <div class="dropdown">
                <button
                    type="button"
                    class="kansor-native-titlebar__menu-button"
                    data-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    Window
                </button>

                <div class="dropdown-menu">
                    <button type="button" class="dropdown-item" data-native-window-control="minimize">
                        Minimize
                    </button>
                    <button type="button" class="dropdown-item" data-native-window-control="maximize">
                        Maximize
                    </button>
                    <button type="button" class="dropdown-item" data-native-window-control="reload">
                        Reload
                    </button>
                    <div class="dropdown-divider"></div>
                    <button type="button" class="dropdown-item text-danger" data-native-window-control="close">
                        Close
                    </button>
                </div>
            </div>

            <div class="dropdown">
                <button
                    type="button"
                    class="kansor-native-titlebar__menu-button"
                    data-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    Help
                </button>

                <div class="dropdown-menu">
                    <span class="dropdown-item-text">
                        {{ config('app.name', 'KanSor') }}
                    </span>
                </div>
            </div>
        </nav>
    </div>

    <div class="kansor-native-titlebar__right">
        @auth
            <span class="kansor-native-titlebar__user" title="{{ Auth::user()->name }}">
                <i class="far fa-user"></i>
                <span>{{ Auth::user()->name }}</span>
            </span>
        @endauth

        <div class="kansor-native-titlebar__window-controls" role="group" aria-label="Window controls">
            <button type="button" data-native-window-control="minimize" title="Minimize">
                <i class="fas fa-minus"></i>
            </button>

            <button type="button" data-native-window-control="maximize" title="Maximize">
                <i class="far fa-square"></i>
            </button>

            <button type="button" data-native-window-control="close" title="Close" class="is-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</header>
```

## 7. Tambahkan styling titlebar

Edit file:

```txt
resources/sass/app.scss
```

Tambahkan CSS/SCSS ini di bawah import existing:

```scss
body.kansor-native-desktop {
    padding-top: 38px;
}

body.kansor-native-desktop .main-sidebar {
    top: 38px;
    height: calc(100vh - 38px);
}

.kansor-native-titlebar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 3000;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #20272b;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    color: #cbd5e1;
    font-size: 13px;
    line-height: 1;
    user-select: none;
    -webkit-app-region: drag;
}

.kansor-native-titlebar button,
.kansor-native-titlebar a,
.kansor-native-titlebar .dropdown-menu {
    -webkit-app-region: no-drag;
}

.kansor-native-titlebar__left,
.kansor-native-titlebar__right,
.kansor-native-titlebar__menu,
.kansor-native-titlebar__history,
.kansor-native-titlebar__window-controls {
    display: flex;
    align-items: center;
    height: 100%;
}

.kansor-native-titlebar__brand {
    height: 100%;
    min-width: 160px;
    padding: 0 12px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #e5e7eb;
    text-decoration: none;
}

.kansor-native-titlebar__brand:hover {
    color: #ffffff;
    text-decoration: none;
    background: rgba(255, 255, 255, 0.07);
}

.kansor-native-titlebar__history {
    gap: 2px;
    padding: 0 6px;
}

.kansor-native-titlebar__history button,
.kansor-native-titlebar__window-controls button,
.kansor-native-titlebar__menu-button {
    height: 100%;
    border: 0;
    outline: 0;
    background: transparent;
    color: #cbd5e1;
}

.kansor-native-titlebar__history button {
    width: 30px;
    border-radius: 6px;
}

.kansor-native-titlebar__history button:not(:disabled):hover,
.kansor-native-titlebar__menu-button:hover,
.kansor-native-titlebar__window-controls button:hover {
    background: rgba(255, 255, 255, 0.08);
    color: #ffffff;
}

.kansor-native-titlebar__history button:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}

.kansor-native-titlebar__menu-button {
    padding: 0 11px;
}

.kansor-native-titlebar__user {
    max-width: 220px;
    padding: 0 12px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #94a3b8;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.kansor-native-titlebar__window-controls button {
    width: 46px;
}

.kansor-native-titlebar__window-controls button.is-close:hover {
    background: #e81123;
    color: #ffffff;
}

.kansor-native-titlebar .dropdown-menu {
    margin-top: 0;
    padding: 6px;
    min-width: 180px;
    background: #111827;
    border: 1px solid rgba(148, 163, 184, 0.22);
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.35);
}

.kansor-native-titlebar .dropdown-item,
.kansor-native-titlebar .dropdown-item-text {
    color: #d1d5db;
    border-radius: 6px;
    font-size: 13px;
}

.kansor-native-titlebar .dropdown-item:hover,
.kansor-native-titlebar .dropdown-item:focus {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.08);
}

.kansor-native-titlebar .dropdown-divider {
    border-top-color: rgba(148, 163, 184, 0.18);
}
```

Important:

- `-webkit-app-region: drag` wajib ada pada root titlebar supaya window bisa di-drag.
- Semua button/link/dropdown wajib `-webkit-app-region: no-drag` supaya bisa diklik.

## 8. Tambahkan JS untuk window control

Edit file:

```txt
resources/js/app.js
```

Di dalam blok:

```js
if (appShellConfig) {
```

Tambahkan deklarasi ini setelah deklarasi button existing:

```js
const nativeWindowControlUrl = appShellConfig.nativeWindowControlUrl ?? null;
const appShellCsrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute('content') ?? '';
```

Lalu tambahkan function dan event listener ini masih di dalam blok `if (appShellConfig)`, sebelum logic history/navigation existing juga boleh:

```js
const runNativeWindowControl = async (action) => {
    if (! nativeWindowControlUrl || ! action) {
        return;
    }

    const url = nativeWindowControlUrl.replace('__ACTION__', encodeURIComponent(action));

    await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': appShellCsrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });
};

const bindNativeWindowControls = () => {
    document.querySelectorAll('[data-native-window-control]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();

            const action = button.getAttribute('data-native-window-control');

            if (! action) {
                return;
            }

            void runNativeWindowControl(action);
        });
    });
};

bindNativeWindowControls();
```

Contoh awal blok `if (appShellConfig)` setelah perubahan:

```js
if (appShellConfig) {
    const overlay = document.querySelector('[data-app-shell-overlay]');
    const overlayMessage = document.querySelector('[data-app-shell-message]');
    const backButton = document.querySelector('[data-app-shell-back]');
    const forwardButton = document.querySelector('[data-app-shell-forward]');
    const refreshButton = document.querySelector('[data-app-shell-refresh]');
    const nativeWindowControlUrl = appShellConfig.nativeWindowControlUrl ?? null;
    const appShellCsrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? '';
    const historyKey = 'kansor-shell-history';
    const historyIndexKey = 'kansor-shell-history-index';
    const historyTargetIndexKey = 'kansor-shell-history-target-index';
    const defaultMessage = overlayMessage?.textContent?.trim() || 'Sistem sedang memproses...';

    const runNativeWindowControl = async (action) => {
        if (! nativeWindowControlUrl || ! action) {
            return;
        }

        const url = nativeWindowControlUrl.replace('__ACTION__', encodeURIComponent(action));

        await fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': appShellCsrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
    };

    const bindNativeWindowControls = () => {
        document.querySelectorAll('[data-native-window-control]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();

                const action = button.getAttribute('data-native-window-control');

                if (! action) {
                    return;
                }

                void runNativeWindowControl(action);
            });
        });
    };

    bindNativeWindowControls();

    // Keep existing app shell code below this point.
}
```

## 9. Optional polish

Jika titlebar terlalu tinggi atau terlalu pendek, hanya ubah angka ini secara konsisten:

```scss
38px
```

Lokasi yang harus ikut berubah:

```scss
body.kansor-native-desktop {
    padding-top: 38px;
}

body.kansor-native-desktop .main-sidebar {
    top: 38px;
    height: calc(100vh - 38px);
}

.kansor-native-titlebar {
    height: 38px;
}
```

Jika ingin titlebar lebih mirip screenshot Codex, gunakan tinggi 34px atau 36px. Default task ini memakai 38px agar klik tombol lebih nyaman di Windows.

## 10. Validasi

Jalankan dari root project:

```bash
php artisan optimize:clear
npm run build
php artisan test
composer native:dev
```

Jika `php artisan test` gagal karena test lama belum disesuaikan dengan window chain baru, update ekspektasi test di:

```txt
tests/Unit/NativeAppServiceProviderTest.php
```

Minimal pastikan test masih mengizinkan `webPreferences(['webviewTag' => true])` dan window `main` tetap terbuka.

## 11. Catatan teknis

- NativePHP saat ini menyediakan `Window::minimize`, `Window::maximize`, `Window::reload`, dan `Window::close` dari facade Window.
- Tombol maximize pada implementasi ini melakukan maximize, bukan toggle restore/unmaximize. Jangan menambah patch ke `nativephp/electron` hanya untuk toggle restore kecuali task baru memang meminta behavior tersebut.
- Jangan menghapus `registerNativeMenu()` di `NativeAppServiceProvider.php`. Menu NativePHP existing tetap boleh ada untuk shortcut/debug; custom titlebar ini hanya UI di dalam window.
- Jangan pindahkan logic sync, debugbar drawer, atau app shell history existing. Cukup tambahkan binding window control baru.

## 12. Ringkasan file yang harus berubah

```txt
app/Providers/NativeAppServiceProvider.php
app/Http/Controllers/NativeDesktopController.php
routes/web.php
resources/views/layouts/app.blade.php
resources/views/layouts/partials/native-titlebar.blade.php
resources/sass/app.scss
resources/js/app.js
```

