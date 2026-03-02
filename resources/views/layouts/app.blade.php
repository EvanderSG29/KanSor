<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Kantin Sore' }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f5f7fb; color: #1f2937; }
        .container { width: min(1100px, 92vw); margin: 0 auto; }
        .topbar { background: #1d4ed8; color: #fff; padding: 12px 0; }
        .topbar .inner { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
        .brand { font-weight: 700; }
        .nav { display: flex; gap: 14px; flex-wrap: wrap; }
        .nav a { color: #dbeafe; text-decoration: none; font-size: 14px; }
        .nav a.active { color: #fff; font-weight: 700; }
        .section { margin: 20px 0; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; box-shadow: 0 2px 6px rgba(0, 0, 0, .03); }
        .grid { display: grid; gap: 14px; }
        .grid.cols-4 { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
        .grid.cols-2 { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
        h1 { margin: 0 0 12px; font-size: 24px; }
        h2 { margin: 0 0 12px; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 8px; text-align: left; font-size: 14px; vertical-align: top; }
        form.inline { display: inline; }
        input, select, button { padding: 8px; border-radius: 6px; border: 1px solid #d1d5db; font-size: 14px; }
        button { cursor: pointer; background: #1d4ed8; color: #fff; border: none; }
        button.secondary { background: #4b5563; }
        button.danger { background: #dc2626; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 8px; margin-bottom: 8px; }
        .flash { margin: 10px 0; padding: 10px; border-radius: 6px; font-size: 14px; }
        .flash.success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .flash.error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .muted { color: #6b7280; font-size: 13px; }
        .mt-12 { margin-top: 12px; }
        .mb-12 { margin-bottom: 12px; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
@php($authUser = session('auth_user'))
@if($authUser)
    <header class="topbar">
        <div class="container inner">
            <div>
                <div class="brand">Kantin Sore</div>
                <div class="muted" style="color:#bfdbfe;">{{ $authUser['nama'] ?? '' }} ({{ $authUser['role'] ?? '' }})</div>
            </div>
            <nav class="nav">
                <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                <a class="{{ request()->routeIs('produk.*') ? 'active' : '' }}" href="{{ route('produk.index') }}">Produk</a>
                @if(in_array($authUser['role'] ?? '', ['admin', 'petugas'], true))
                    <a class="{{ request()->routeIs('transaksi.*') ? 'active' : '' }}" href="{{ route('transaksi.index') }}">Transaksi</a>
                @endif
                @if(in_array($authUser['role'] ?? '', ['admin', 'pemasok'], true))
                    <a class="{{ request()->routeIs('barang-masuk.*') ? 'active' : '' }}" href="{{ route('barang-masuk.index') }}">Barang Masuk</a>
                @endif
                @if(($authUser['role'] ?? null) === 'admin')
                    <a class="{{ request()->routeIs('laporan.*') ? 'active' : '' }}" href="{{ route('laporan.index') }}">Laporan</a>
                    <a class="{{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">User</a>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button class="secondary" type="submit">Logout</button>
                </form>
            </nav>
        </div>
    </header>
@endif

<main class="container section">
    @if(session('success'))
        <div class="flash success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="flash error">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="flash error">
            {{ $errors->first() }}
        </div>
    @endif

    @yield('content')
</main>
</body>
</html>
