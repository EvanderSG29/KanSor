<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ trim($__env->yieldContent('title')) ? trim($__env->yieldContent('title')).' | ' : '' }}{{ config('app.name', 'KanSor') }}</title>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @auth
        @if (config('nativephp-internal.running'))
            @php
                $kanSorNativeDesktop = [
                    'debugbarDrawerEvent' => \App\Events\ToggleDebugbarDrawer::class,
                    'debugbarRepoUrl' => 'https://github.com/fruitcake/laravel-debugbar',
                    'telescopeActionUrl' => route('native.desktop.telescope-window'),
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
    @endauth
    @stack('styles')
</head>
<body class="@yield('body_class', 'hold-transition sidebar-mini layout-fixed')">
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
                    <li class="nav-item d-none d-sm-inline-block">
                        <a href="{{ url('/') }}" class="nav-link">Beranda</a>
                    </li>
                    @auth
                        <li class="nav-item d-none d-sm-inline-block">
                            <a href="{{ route('home') }}" class="nav-link">Dashboard</a>
                        </li>
                        <li class="nav-item d-none d-lg-inline-block">
                            <a href="{{ route('pos-kantin.reports.index') }}" class="nav-link">Laporan</a>
                        </li>
                    @endauth
                </ul>

                <ul class="navbar-nav ml-auto">
                    @guest
                        @if (Route::has('login'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                            </li>
                        @endif
                        @if (Route::has('register'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                            </li>
                        @endif
                    @else
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
                            <li class="nav-item">
                                <a href="{{ url('/') }}" class="nav-link {{ request()->is('/') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-home"></i>
                                    <p>Beranda</p>
                                </a>
                            </li>
                            @auth
                                <li class="nav-header">RINGKASAN</li>
                                <li class="nav-item">
                                    <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
                                        <i class="nav-icon fas fa-tachometer-alt"></i>
                                        <p>Dashboard POS</p>
                                    </a>
                                </li>

                                <li class="nav-header">OPERASIONAL</li>
                                <li class="nav-item">
                                    <a href="{{ route('pos-kantin.transactions.index') }}" class="nav-link {{ request()->routeIs('pos-kantin.transactions.*') ? 'active' : '' }}">
                                        <i class="nav-icon fas fa-receipt"></i>
                                        <p>Transaksi</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('pos-kantin.savings.index') }}" class="nav-link {{ request()->routeIs('pos-kantin.savings.*') ? 'active' : '' }}">
                                        <i class="nav-icon fas fa-wallet"></i>
                                        <p>Simpanan</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('pos-kantin.suppliers.index') }}" class="nav-link {{ request()->routeIs('pos-kantin.suppliers.*') ? 'active' : '' }}">
                                        <i class="nav-icon fas fa-truck"></i>
                                        <p>Pemasok</p>
                                    </a>
                                </li>

                                <li class="nav-header">PELAPORAN</li>
                                <li class="nav-item">
                                    <a href="{{ route('pos-kantin.supplier-payouts.index') }}" class="nav-link {{ request()->routeIs('pos-kantin.supplier-payouts.*') ? 'active' : '' }}">
                                        <i class="nav-icon fas fa-hand-holding-usd"></i>
                                        <p>Pembayaran</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('pos-kantin.reports.index') }}" class="nav-link {{ request()->routeIs('pos-kantin.reports.*') ? 'active' : '' }}">
                                        <i class="nav-icon fas fa-chart-pie"></i>
                                        <p>Laporan</p>
                                    </a>
                                </li>

                                <li class="nav-header">ADMINISTRASI</li>
                                <li class="nav-item">
                                    <a href="{{ route('pos-kantin.users.index') }}" class="nav-link {{ request()->routeIs('pos-kantin.users.*') ? 'active' : '' }}">
                                        <i class="nav-icon fas fa-users-cog"></i>
                                        <p>Pengguna</p>
                                    </a>
                                </li>
                            @else
                                <li class="nav-item">
                                    <a href="{{ route('login') }}" class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}">
                                        <i class="nav-icon fas fa-sign-in-alt"></i>
                                        <p>{{ __('Login') }}</p>
                                    </a>
                                </li>
                                @if (Route::has('register'))
                                    <li class="nav-item">
                                        <a href="{{ route('register') }}" class="nav-link {{ request()->routeIs('register') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-user-plus"></i>
                                            <p>{{ __('Register') }}</p>
                                        </a>
                                    </li>
                                @endif
                            @endauth
                        </ul>
                    </nav>
                </div>
            </aside>

            <div class="content-wrapper">
                <section class="content-header">
                    <div class="container-fluid">
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
                                    <li class="breadcrumb-item"><a href="{{ url('/') }}">Beranda</a></li>
                                    @hasSection('breadcrumbs')
                                        @yield('breadcrumbs')
                                    @else
                                        <li class="breadcrumb-item active">@yield('title', 'Dashboard')</li>
                                    @endif
                                </ol>
                            </div>
                        </div>
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
                @if (config('nativephp-internal.running'))
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

    @stack('scripts')
</body>
</html>
