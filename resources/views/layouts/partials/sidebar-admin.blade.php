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
        <i class="nav-icon fas fa-receipt"></i>
        <p>Semua Transaksi</p>
    </a>
</li>

<li class="nav-header">KONFIRMASI ADMIN</li>
<li class="nav-item">
    <a href="{{ route('kansor.admin.sales.index') }}" class="nav-link {{ request()->routeIs('kansor.admin.sales.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-check-double"></i>
        <p>Konfirmasi Pembayaran &amp; Setoran</p>
    </a>
</li>

<li class="nav-header">MASTER DATA</li>
<li class="nav-item">
    <a href="{{ route('kansor.admin.suppliers.index') }}" class="nav-link {{ request()->routeIs('kansor.admin.suppliers.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-user"></i>
        <p>Kelola Pemasok</p>
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('kansor.admin.foods.index') }}" class="nav-link {{ request()->routeIs('kansor.admin.foods.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-utensils"></i>
        <p>Kelola Menu / Makanan</p>
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('kansor.admin.users.index') }}" class="nav-link {{ request()->routeIs('kansor.admin.users.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-users-cog"></i>
        <p>Kelola Pengguna</p>
    </a>
</li>

<li class="nav-header">KEUANGAN &amp; REKAP</li>
<li class="nav-item">
    <a href="{{ route('kansor.admin.canteen-totals.index') }}" class="nav-link {{ request()->routeIs('kansor.admin.canteen-totals.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-chart-line"></i>
        <p>Rekap Kantin</p>
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('kansor.supplier-payouts.index') }}" class="nav-link {{ request()->routeIs('kansor.supplier-payouts.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-hand-holding-usd"></i>
        <p>Payout Pemasok</p>
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('kansor.reports.index') }}" class="nav-link {{ request()->routeIs('kansor.reports.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-chart-pie"></i>
        <p>Laporan Operasional</p>
    </a>
</li>

@include('layouts.partials.sidebar-sync', ['showServerData' => true])

<li class="nav-header">PENGATURAN</li>
<li class="nav-item">
    <a href="{{ route('kansor.preferences.index') }}" class="nav-link {{ request()->routeIs('kansor.preferences.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-sliders-h"></i>
        <p>Preferensi</p>
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('kansor.admin.audit-logs.index') }}" class="nav-link {{ request()->routeIs('kansor.admin.audit-logs.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-shield-alt"></i>
        <p>Audit Aktivitas</p>
    </a>
</li>

