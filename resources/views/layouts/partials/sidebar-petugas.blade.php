<li class="nav-item">
    <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
        <i class="nav-icon fas fa-tachometer-alt"></i>
        <p>Dashboard</p>
    </a>
</li>

<li class="nav-header">OPERASIONAL</li>
<li class="nav-item">
    <a href="{{ route('pos-kantin.sales.create') }}" class="nav-link {{ request()->routeIs('pos-kantin.sales.create') ? 'active' : '' }}">
        <i class="nav-icon fas fa-cash-register"></i>
        <p>Input Transaksi</p>
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('pos-kantin.sales.index') }}" class="nav-link {{ request()->routeIs('pos-kantin.sales.index') || request()->routeIs('pos-kantin.sales.show') || request()->routeIs('pos-kantin.sales.edit') ? 'active' : '' }}">
        <i class="nav-icon fas fa-history"></i>
        <p>Riwayat Transaksi</p>
    </a>
</li>

@include('layouts.partials.sidebar-sync', ['showServerData' => false])

<li class="nav-header">PENGATURAN</li>
<li class="nav-item">
    <a href="{{ route('pos-kantin.preferences.index') }}" class="nav-link {{ request()->routeIs('pos-kantin.preferences.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-sliders-h"></i>
        <p>Preferensi</p>
    </a>
</li>
