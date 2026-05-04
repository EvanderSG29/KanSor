<li class="nav-item">
    <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
        <i class="nav-icon fas fa-tachometer-alt"></i>
        <p>Dashboard</p>
    </a>
</li>

<li class="nav-header">OPERASIONAL</li>
<li class="nav-item">
    <a href="{{ route('kansor.sales.create') }}" class="nav-link {{ request()->routeIs('kansor.sales.create') ? 'active' : '' }}">
        <i class="nav-icon fas fa-cash-register"></i>
        <p>Input Transaksi</p>
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('kansor.sales.index') }}" class="nav-link {{ request()->routeIs('kansor.sales.index') || request()->routeIs('kansor.sales.show') || request()->routeIs('kansor.sales.edit') ? 'active' : '' }}">
        <i class="nav-icon fas fa-history"></i>
        <p>Riwayat Transaksi</p>
    </a>
</li>

@include('layouts.partials.sidebar-sync', ['showServerData' => false])

<li class="nav-header">PENGATURAN</li>
<li class="nav-item">
    <a href="{{ route('kansor.preferences.index') }}" class="nav-link {{ request()->routeIs('kansor.preferences.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-sliders-h"></i>
        <p>Preferensi</p>
    </a>
</li>

