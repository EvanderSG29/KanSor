<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $isNativeDesktop = (bool) config('nativephp-internal.running');
        $scriptSrc = $isNativeDesktop
            ? "script-src 'self' 'unsafe-inline';"
            : "script-src 'self' 'unsafe-inline' http://localhost:5173 http://127.0.0.1:5173 http://[::1]:5173;";
        $styleSrc = $isNativeDesktop
            ? "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;"
            : "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com http://localhost:5173 http://127.0.0.1:5173 http://[::1]:5173;";
        $connectSrc = $isNativeDesktop
            ? "connect-src 'self' http://127.0.0.1:8100 http://localhost:8000 http://127.0.0.1:8000;"
            : "connect-src 'self' http://127.0.0.1:8100 http://localhost:8000 http://127.0.0.1:8000 http://localhost:5173 http://127.0.0.1:5173 http://[::1]:5173 ws://localhost:5173 ws://127.0.0.1:5173 ws://[::1]:5173;";
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta
        http-equiv="Content-Security-Policy"
        content="
            default-src 'self';
            base-uri 'self';
            object-src 'none';
            form-action 'self';
            {{ $scriptSrc }}
            {{ $styleSrc }}
            font-src 'self' https://fonts.gstatic.com data:;
            img-src 'self' data: https:;
            {{ $connectSrc }}
            frame-src 'self' https://github.com https://*.github.com;
        "
    >

    @php
        $debugUiEnabled = app()->environment('local')
            && auth()->check()
            && auth()->user()?->isAdmin()
            && auth()->user()?->isActiveUser();
        $kanSorAppShell = [
            'setupRunUrl' => app()->environment('local') ? route('setup.run-migrations') : null,
            'setupStatusUrl' => app()->environment('local') ? route('setup.status') : null,
            'hasPendingMigrations' => app(\App\Services\Setup\SchemaReadinessService::class)->hasPendingMigrations(),
            'isNativeDesktop' => $isNativeDesktop,
            'nativeWindowControlUrl' => $isNativeDesktop
                ? route('native.desktop.window-control', ['action' => '__ACTION__'])
                : null,
        ];
        $viteEntryPoints = [
            'resources/sass/app.scss',
            'resources/js/app.js',
        ];
        $nativeDesktopVite = (new \Illuminate\Foundation\Vite)
            ->useHotFile(storage_path('framework/nativephp-desktop-build-only.hot'))
            ->withEntryPoints($viteEntryPoints);
    @endphp

    <title>{{ trim($__env->yieldContent('title')) ? trim($__env->yieldContent('title')).' | ' : '' }}{{ config('app.name', 'KanSor') }}</title>

    @if ($isNativeDesktop)
        {{ $nativeDesktopVite }}
    @else
        @vite($viteEntryPoints)
    @endif
    <script>
        window.KanSorAppShell = {{ \Illuminate\Support\Js::from($kanSorAppShell) }};
    </script>
    <style>
        .kansor-shell-nav .btn[disabled] {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .kansor-loading-overlay {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: rgba(15, 23, 42, 0.72);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.18s ease;
        }

        .kansor-loading-overlay.is-visible {
            opacity: 1;
            pointer-events: auto;
        }

        .kansor-loading-overlay__card {
            width: min(100%, 26rem);
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px solid rgba(148, 163, 184, 0.35);
            background: linear-gradient(160deg, #0f172a 0%, #182230 100%);
            box-shadow: 0 28px 70px rgba(15, 23, 42, 0.4);
            color: #e2e8f0;
            text-align: center;
        }

        .kansor-loading-overlay__logo {
            width: 4rem;
            height: 4rem;
            margin: 0 auto 1rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            color: #f8fafc;
            font-size: 1.6rem;
            animation: kansor-shell-spin 1s linear infinite;
        }

        .kansor-loading-overlay__title {
            margin-bottom: 0.35rem;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .kansor-loading-overlay__message {
            margin: 0;
            color: #cbd5e1;
        }

        body.kansor-shell-busy {
            cursor: progress;
        }

        @keyframes kansor-shell-spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>
    @auth
        @if ($isNativeDesktop && $debugUiEnabled)
            @php
                $kanSorNativeDesktop = [
                    'debugbarDrawerEvent' => \App\Events\ToggleDebugbarDrawer::class,
                    'debugbarRepoUrl' => 'https://github.com/fruitcake/laravel-debugbar',
                    'telescopeActionUrl' => route('native.desktop.telescope-window'),
                    'enabled' => true,
                ];
            @endphp
            <script>
                window.KanSorNativeDesktop = {{ \Illuminate\Support\Js::from($kanSorNativeDesktop) }};
            </script>
            <style>
                .kansor-debug-drawer {
                    position: fixed;
                    inset: auto 1.5rem 1.5rem 1.5rem;
                    z-index: 1080;
                    pointer-events: none;
                }

                .kansor-debug-drawer__backdrop {
                    position: fixed;
                    inset: 0;
                    background: rgba(15, 23, 42, 0.45);
                    opacity: 0;
                    pointer-events: none;
                    transition: opacity 0.18s ease;
                }

                .kansor-debug-drawer__panel {
                    height: min(32rem, 58vh);
                    background: #101828;
                    border: 1px solid rgba(148, 163, 184, 0.35);
                    border-radius: 1rem;
                    overflow: hidden;
                    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.35);
                    transform: translateY(calc(100% + 2rem));
                    transition: transform 0.22s ease;
                    pointer-events: auto;
                }

                .kansor-debug-drawer--open {
                    pointer-events: auto;
                }

                .kansor-debug-drawer--open .kansor-debug-drawer__backdrop {
                    opacity: 1;
                    pointer-events: auto;
                }

                .kansor-debug-drawer--open .kansor-debug-drawer__panel {
                    transform: translateY(0);
                }

                .kansor-debug-drawer__header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 1rem;
                    padding: 0.9rem 1rem;
                    background: linear-gradient(135deg, #182230 0%, #0f172a 100%);
                    color: #e2e8f0;
                    border-bottom: 1px solid rgba(148, 163, 184, 0.24);
                }

                .kansor-debug-drawer__title {
                    display: flex;
                    flex-direction: column;
                    gap: 0.15rem;
                    min-width: 0;
                }

                .kansor-debug-drawer__title strong,
                .kansor-debug-drawer__title span {
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .kansor-debug-drawer__title span {
                    color: #94a3b8;
                    font-size: 0.85rem;
                }

                .kansor-debug-drawer__actions {
                    display: flex;
                    gap: 0.5rem;
                    flex-wrap: wrap;
                    justify-content: flex-end;
                }

                .kansor-debug-drawer__body {
                    height: calc(100% - 73px);
                    background: #0b1120;
                }

                .kansor-debug-drawer__webview {
                    width: 100%;
                    height: 100%;
                    border: 0;
                    display: block;
                }

                @media (max-width: 767.98px) {
                    .kansor-debug-drawer {
                        inset: auto 0.75rem 0.75rem 0.75rem;
                    }

                    .kansor-debug-drawer__panel {
                        height: min(28rem, 62vh);
                    }
                }
            </style>
        @endif
        @php
            $kanSorSync = [
                'statusUrl' => route('pos-kantin.sync.status'),
                'autoUrl' => route('pos-kantin.sync.auto'),
                'intervalSeconds' => (int) ($kanSorSyncNavigationStatus['syncIntervalSeconds'] ?? config('services.pos_kantin.sync_interval_seconds', 60)),
            ];
        @endphp
        <script>
            window.KanSorSync = {{ \Illuminate\Support\Js::from($kanSorSync) }};
        </script>
    @endauth
    @stack('styles')
</head>
<body class="@yield('body_class', 'hold-transition sidebar-mini layout-fixed') {{ $isNativeDesktop ? 'kansor-native-desktop' : '' }}">
    @php
        $kanSorSyncNavigationStatus = $kanSorSyncNavigationStatus ?? [];
        $currentUser = Auth::user();
        $syncQueuedCount = (int) ($kanSorSyncNavigationStatus['queuedCount'] ?? $kanSorSyncNavigationStatus['pendingCount'] ?? 0);
        $syncFailedCount = (int) ($kanSorSyncNavigationStatus['failedCount'] ?? 0);
        $syncConflictCount = (int) ($kanSorSyncNavigationStatus['conflictCount'] ?? 0);
        $syncAttentionCount = $syncFailedCount + $syncConflictCount;
    @endphp

    @if ($isNativeDesktop)
        @include('layouts.partials.native-titlebar')
    @endif

    @hasSection('auth_page')
        @yield('content')
    @else
        <div class="wrapper">
            <nav class="main-header navbar navbar-expand navbar-white navbar-light">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                            <i class="fas fa-bars"></i>
                        </a>
                    </li>
                    @auth
                        <li class="nav-item d-none d-sm-inline-block">
                            <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">Dashboard</a>
                        </li>
                        @if ($currentUser?->isAdmin())
                            <li class="nav-item d-none d-lg-inline-block">
                                <a href="{{ route('pos-kantin.reports.index') }}" class="nav-link {{ request()->routeIs('pos-kantin.reports.*') ? 'active' : '' }}">Laporan Operasional</a>
                            </li>
                        @endif
                        @if (! $isNativeDesktop)
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
                    @endauth
                </ul>

                <ul class="navbar-nav ml-auto">
                    @guest
                        @if (Route::has('login'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                            </li>
                        @endif
                    @else
                        <li class="nav-item d-flex align-items-center mr-2">
                            <span class="badge badge-secondary" data-sync-indicator>Sync</span>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-expanded="false">
                                <i class="far fa-user mr-1"></i>
                                {{ Auth::user()->name }}
                            </a>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fas fa-sign-out-alt mr-2"></i>
                                    {{ __('Logout') }}
                                </a>
                            </div>
                        </li>
                    @endguest
                </ul>
            </nav>

            <aside class="main-sidebar sidebar-dark-primary elevation-4">
                <a href="{{ url('/') }}" class="brand-link text-center">
                    <i class="fas fa-store brand-image mt-1 ml-3"></i>
                    <span class="brand-text font-weight-light">{{ config('app.name', 'KanSor') }}</span>
                </a>

                <div class="sidebar">
                    @auth
                        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                            <div class="image">
                                <i class="fas fa-user-circle fa-2x text-light"></i>
                            </div>
                            <div class="info">
                                <a href="{{ route('home') }}" class="d-block">{{ Auth::user()->name }}</a>
                            </div>
                        </div>
                    @endauth

                    <nav class="mt-2">
                        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                            @auth
                                @if ($currentUser?->isAdmin())
                                    @include('layouts.partials.sidebar-admin')
                                @elseif ($currentUser?->isPetugas())
                                    @include('layouts.partials.sidebar-petugas')
                                @endif
                            @else
                                <li class="nav-item">
                                    <a href="{{ route('login') }}" class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}">
                                        <i class="nav-icon fas fa-sign-in-alt"></i>
                                        <p>{{ __('Login') }}</p>
                                    </a>
                                </li>
                            @endauth
                        </ul>
                    </nav>
                </div>
            </aside>

            <div class="content-wrapper">
                <section class="content-header">
                    <div class="container-fluid">
                        @hasSection('page_header')
                            @yield('page_header')
                        @else
                            <div class="row mb-2">
                                <div class="col-sm-8">
                                    <h1>@yield('title', 'Dashboard')</h1>
                                    @hasSection('page_subtitle')
                                        <p class="text-muted mb-0">@yield('page_subtitle')</p>
                                    @endif
                                </div>
                                <div class="col-sm-4">
                                    @hasSection('page_actions')
                                        <div class="float-sm-right mb-2">
                                            @yield('page_actions')
                                        </div>
                                    @endif
                                    <ol class="breadcrumb float-sm-right">
                                        <li class="breadcrumb-item"><a href="{{ auth()->check() ? route('home') : url('/') }}">Dashboard</a></li>
                                        @hasSection('breadcrumbs')
                                            @yield('breadcrumbs')
                                        @else
                                            <li class="breadcrumb-item active">@yield('title', 'Dashboard')</li>
                                        @endif
                                    </ol>
                                </div>
                            </div>
                        @endif
                    </div>
                </section>

                <section class="content">
                    <div class="container-fluid">
                        @yield('content')
                    </div>
                </section>
            </div>

            <footer class="main-footer">
                <strong>&copy; {{ date('Y') }} {{ config('app.name', 'KanSor') }}.</strong>
                <span>Template berbasis AdminLTE 3.2.</span>
            </footer>

            @auth
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            @endauth

            @auth
                @if ($isNativeDesktop && $debugUiEnabled)
                    <div id="kansor-debugbar-drawer" class="kansor-debug-drawer" aria-hidden="true">
                        <div class="kansor-debug-drawer__backdrop" data-debugbar-dismiss></div>
                        <section class="kansor-debug-drawer__panel" aria-label="Fruitcake Debugbar Drawer">
                            <header class="kansor-debug-drawer__header">
                                <div class="kansor-debug-drawer__title">
                                    <strong>Fruitcake Laravel Debugbar</strong>
                                    <span>Toggle dengan Ctrl+Shift+D lalu F</span>
                                </div>
                                <div class="kansor-debug-drawer__actions">
                                    <a href="https://github.com/fruitcake/laravel-debugbar" target="_blank" rel="noreferrer" class="btn btn-outline-light btn-sm">
                                        <i class="fab fa-github mr-1"></i>
                                        Buka repo
                                    </a>
                                    <button type="button" class="btn btn-light btn-sm" data-debugbar-dismiss>
                                        <i class="fas fa-times mr-1"></i>
                                        Tutup
                                    </button>
                                </div>
                            </header>
                            <div class="kansor-debug-drawer__body">
                                <webview
                                    id="kansor-debugbar-webview"
                                    class="kansor-debug-drawer__webview"
                                    partition="persist:kansor-debugbar"
                                    allowpopups
                                ></webview>
                            </div>
                        </section>
                    </div>
                @endif
            @endauth
        </div>
    @endif

    <div class="kansor-loading-overlay" data-app-shell-overlay aria-hidden="true">
        <div class="kansor-loading-overlay__card" role="status" aria-live="polite">
            <div class="kansor-loading-overlay__logo">
                <i class="fas fa-store"></i>
            </div>
            <div class="kansor-loading-overlay__title">{{ config('app.name', 'KanSor') }}</div>
            <p class="kansor-loading-overlay__message" data-app-shell-message>Sistem sedang memproses...</p>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
