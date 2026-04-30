<li class="nav-header">SINKRONISASI</li>
<li class="nav-item">
    <a href="{{ route('pos-kantin.sync.index') }}" class="nav-link {{ request()->routeIs('pos-kantin.sync.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-sync-alt"></i>
        <p>
            Status Sinkronisasi
            @if ($syncAttentionCount > 0)
                <span class="right badge badge-danger">{{ $syncAttentionCount }}</span>
            @elseif ($syncQueuedCount > 0)
                <span class="right badge badge-warning">{{ $syncQueuedCount }}</span>
            @endif
        </p>
    </a>
</li>

@if (($showServerData ?? false) === true)
    @php
        $isServerDataMenuActive = request()->routeIs('pos-kantin.transactions.*')
            || request()->routeIs('pos-kantin.suppliers.*')
            || request()->routeIs('pos-kantin.users.*')
            || request()->routeIs('pos-kantin.savings.*');
    @endphp
    <li class="nav-item has-treeview {{ $isServerDataMenuActive ? 'menu-open' : '' }}">
        <a href="#" class="nav-link {{ $isServerDataMenuActive ? 'active' : '' }}">
            <i class="nav-icon fas fa-database"></i>
            <p>
                Data Server
                <i class="right fas fa-angle-left"></i>
            </p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('pos-kantin.transactions.index') }}" class="nav-link {{ request()->routeIs('pos-kantin.transactions.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Data Transaksi Server</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('pos-kantin.suppliers.index') }}" class="nav-link {{ request()->routeIs('pos-kantin.suppliers.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Data Pemasok Server</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('pos-kantin.users.index') }}" class="nav-link {{ request()->routeIs('pos-kantin.users.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Data Pengguna Server</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('pos-kantin.savings.index') }}" class="nav-link {{ request()->routeIs('pos-kantin.savings.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Data Simpanan Server</p>
                </a>
            </li>
        </ul>
    </li>
@endif
